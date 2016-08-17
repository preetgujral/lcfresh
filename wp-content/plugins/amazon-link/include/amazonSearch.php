<?php
/*****************************************************************************************/

/*
 * Amazon Link Search Class
 *
 * Provides a facility to do simple Amazon Searches via the ajax interface and return results in
 * an array.
 *
 * To use the default script and styles you must add the following on init (before the header).
 *    - wp_enqueue_script('amazon-link-search')
 *    - wp_enqueue_styles('amazon-link-styles')
 *
 * The page must consist of a form with input elements all with the id='amazon-link-search', and
 * with the following names:
 *    - s_title
 *    - s_index
 *    - s_author
 *    - s_page
 *    - s_template
 *
 * To initiate a search there must be an element in the form which triggers the javascript:
 * 'return wpAmazonLinkSearch.searchAmazon(this.form);'
 * 
 * The results are inserted into the html element on the page with the id='amazon-link-result-list'.
 * Which should be contained within an element of id='amazon-link-results', there should also be a hidden
 * element with the id='amazon-link-error' to report any errors that occur. As well as an element with the
 * id='amazon-link-status' to indicate a search in progress.
 *
 * The values of the form input items are used to control the search, 'title', 'author' are used as search terms,
 * 'index' should be a valid amazon search index (e.g. Books). 'page' should be used to set which page of the results
 * is to be displayed.
 * 'template' can be used to get the search engine to populate a predefined html template with values - this 
 * should be htmlencoded, and use the same Keywords as used in the normal Templates.
 */

if ( ! class_exists( 'AmazonLinkSearch' ) ) {
   class AmazonLinkSearch {

      var $data = array();

      function __construct() {
      }

      /*
       * Must be called by the client in its init function.
       */
      function init( $parent ) {

         if ( is_admin() ) {

            // Register the Search javascript
            $script = plugins_url( "amazon-link-search.js", __FILE__ );
            wp_register_script( 'amazon-link-search', $script, array( 'jquery' ), $parent->plugin_version );

            // AJAX callbacks need to be registered early during init.
            add_action( 'wp_ajax_amazon-link-search', array( $this, 'perform_search' ) );      // Handle ajax search requests
            add_action( 'wp_ajax_amazon-link-get-image', array( $this, 'get_image' ) );        // Handle ajax image download
            add_action( 'wp_ajax_amazon-link-remove-image', array( $this, 'remove_image' ) );  // Handle ajax image removal
         }
         
         $settings = $parent->get_default_settings();
         if ( ! empty( $settings['media_library'] ) ) {
            // Standard Image Filter
            add_filter( 'amazon_link_template_get_image', array( $this, 'get_images_filter' ), 12, 6 );
            add_filter( 'amazon_link_template_get_thumb', array( $this, 'get_images_filter' ), 12, 6 );
         }

         $this->alink = $parent;
      }

      /*****************************************************************************************/
      /// AJAX Call Handlers

      function perform_search() {
         
         $opts = $_POST;

         $opts['multi_cc'] = 0;
         $opts['localise'] = 0;
         $opts['live'] = 1;
         $opts['skip_slow'] = 1;
         
         $Settings = $this->alink->parse_shortcode($opts);

         $cc = $Settings['local_cc'];
         if ( ! empty( $Settings[$cc]['translate'] ) && ! empty( $Settings[$cc]['s_title_trans'] ) ) {
            $Settings[$cc]['s_title'] = $Settings[$cc]['s_title_trans'];
         }
         
         if ( empty( $Settings[$cc]['s_title'] ) && empty( $Settings[$cc]['s_author'] ) ) {
            $Items = $this->alink->cached_query( $Settings['asin'][0][$cc], $Settings[$cc] );
         } else {
            $Settings[$cc]['found'] = 1;
            $Items = $this->do_search( $Settings[$cc] );
         }

         $results['message'] = 'No Error ';
         $results['success'] = 0;
         if ( isset( $Items['Error'] ) ) {
            
            // Query Failed, report Error Message
            $results['message'] = 'Error: ' . ( isset( $Items['Error']['Message'] ) ? $Items['Error']['Message'] : 'No Error Message' );
            
         } else if ( is_array( $Items ) && ( count( $Items ) > 0 ) ) {
            
            // Query successful output results using template
            $details = $Settings;
            foreach( $Items as $item ) {
               $details[$cc] = array_merge( $item, $Settings[$cc] );
               $details['asin'] = array( $cc => $item['asin'] );
               $results['items'][]['template'] = $this->alink->parse_template( $details );
            }
            $results['success'] = 1;
            $results['message'] = '';
         }

         print json_encode( $results );
         exit();
      }

      function remove_image() {
         
         $opts = $_POST;

         /* Do we have this image? */
         $media_ids = $this->find_attachments( $opts['asin'] );

         if ( is_wp_error( $media_ids ) ) {
            $results = array( 'in_library' => false, 'asin' => $opts['asin'], 'error' => __( 'No matching image found', 'amazon-link' ) );
         } else {

            $results = array( 'in_library' => false, 'asin' => $opts['asin'], 'error' => __( 'Images deleted','amazon-link' ) );

            /* Only remove images attached to this post */
            foreach ( $media_ids as $id => $media_id ) {
               if ( $media_id->post_parent == $opts['post'] ) {
                  /* Remove attachment */
                  wp_delete_attachment( $media_id->ID );
               } else {
                  $results['in_library'] = true;
                  $results['id'] = $media_id->ID;
               }
            }
         }

         print json_encode( $results );
         exit();         
      }

      function get_image() {
         
         $opts = $_POST;

         $this->alink->in_post = False;
         $this->alink->post_ID = 0;
         
         /* Do not upload if we already have this image */
         $media_ids = $this->find_attachments( $opts['asin'] );

         if ( ! is_wp_error( $media_ids ) ) {
            $results = array( 'in_library' => true, 'asin' => $opts['asin'], 'id' => $media_ids[0]->ID );
         } else {

            /* Attempt to download the image */
            $result = $this->grab_image( $opts['asin'], $opts['post'] );
            if ( is_wp_error( $result ) )
            {
               $results = array( 'in_library' => false, 'success' => 0, 'asin' => $opts['asin'], 'error' => $result->get_error_code());
            } else {
               $results = array( 'in_library' => true, 'asin' => $opts['asin'], 'id' => $result);
            }
         }
         
         print json_encode($results);
         exit();         
      }

      /*****************************************************************************************/
      /// Helper Functions

      function get_aws_info() {

         $search_index_by_locale = array( 
            'ca' => array('All', 'Blended', 'Books', 'Classical', 'DVD', 'Electronics', 'ForeignBooks', 'Kitchen', 'Music', 'Software', 'SoftwareVideoGames',
'VHS', 'Video', 'VideoGames'),
            'us' => array('All', 'Apparel', 'Appliances', 'ArtsAndCrafts', 'Automotive', 'Baby', 'Beauty', 'Blended', 'Books', 'Classical', 'Collectibles', 'DigitalMusic',
'Grocery', 'MP3Downloads', 'DVD', 'Electronics', 'HealthPersonalCare', 'HomeGarden', 'Industrial', 'Jewelry', 'KindleStore',
'Kitchen', 'LawnAndGarden', 'Magazines', 'Merchants', 'Miscellaneous', 'MobileApps', 'Music', 'MusicalInstruments', 'MusicTracks',
'OfficeProducts', 'OutdoorLiving', 'PCHardware', 'PetSupplies', 'Photo', 'Shoes', 'Software', 'SportingGoods', 'Tools', 'Toys',
'UnboxVideo', 'VHS', 'Video', 'VideoGames', 'Watches', 'Wireless', 'WirelessAccessories'),
            'cn' => array('All', 'Apparel', 'Appliances', 'Automotive', 'Baby', 'Beauty', 'Books', 'Electronics', 'Grocery', 'HealthPersonalCare', 'Home',
'HomeImprovement', 'Jewelry', 'Misc', 'Music', 'OfficeProducts', 'Photo', 'Shoes', 'Software', 'SportingGoods', 'Toys', 'Video',
'VideoGames', 'Watches'),
            'de' => array('All', 'Apparel', 'Automotive', 'Baby', 'Blended', 'Beauty', 'Books', 'Classical', 'DVD', 'Electronics', 'ForeignBooks', 'Grocery',
'HealthPersonalCare', 'HomeGarden', 'Jewelry', 'KindleStore', 'Kitchen', 'Lighting', 'Magazines', 'MP3Downloads',
'Music', 'MusicalInstruments', 'MusicTracks', 'OfficeProducts', 'OutdoorLiving', 'Outlet', 'PCHardware', 'Photo', 'Software',
'SoftwareVideoGames', 'SportingGoods', 'Tools', 'Toys', 'VHS', 'Video', 'VideoGames', 'Watches'),
            'es' => array('All', 'Books', 'DVD', 'Electronics', 'ForeignBooks', 'Kitchen', 'Music', 'Software', 'Toys', 'VideoGames', 'Watches'),
            'fr' => array('All', 'Apparel', 'Baby', 'Beauty', 'Blended', 'Books', 'Classical', 'DVD', 'Electronics', 'ForeignBooks', 'HealthPersonalCare',
'HomeImprovement', 'Jewelry', 'Kitchen', 'Lighting', 'MP3Downloads', 'Music', 'MusicalInstruments', 'MusicTracks', 'OfficeProducts', 'Outlet',
'Shoes', 'Software', 'SoftwareVideoGames', 'VHS', 'Video', 'VideoGames', 'Watches'),
            'it' => array('All', 'Books', 'DVD', 'Electronics', 'ForeignBooksSearchIndex:Garden', 'KindleStore', 'Kitchen', 'Music', 'Shoes', 'Software', 'Toys',
'VideoGames', 'Watches'),
            'in' => array('All', 'Books', 'DVD', 'Electronics', 'Marketplace'),
            'jp' => array('All', 'Apparel', 'Appliances', 'Automotive', 'Baby', 'Beauty', 'Blended', 'Books', 'Classical', 'DVD', 'Electronics', 'ForeignBooks', 'Grocery',
'HealthPersonalCare', 'Hobbies', 'HomeImprovement', 'Jewelry', 'Kitchen', 'MP3Downloads', 'Music', 'MusicalInstruments',
'MusicTracks', 'OfficeProducts', 'Shoes', 'Software', 'SportingGoods', 'Toys', 'VHS', 'Video', 'VideoGames', 'Watches'),
            'uk' => array('All', 'Apparel', 'Automotive', 'Baby', 'Beauty', 'Blended', 'Books', 'Classical', 'DVD', 'Electronics', 'Grocery', 'HealthPersonalCare',
'HomeGarden', 'Jewelry', 'KindleStore', 'Kitchen', 'Lighting', 'MP3Downloads', 'Music', 'MusicalInstruments', 'MusicTracks',
'OfficeProducts', 'OutdoorLiving', 'Outlet', 'Shoes', 'Software', 'SoftwareVideoGames', 'Toys', 'VHS', 'Video', 'VideoGames', 'Watches'),
            'us' => array('All', 'Apparel', 'Appliances', 'ArtsAndCrafts', 'Automotive', 'Baby', 'Beauty', 'Blended', 'Books', 'Classical', 'DigitalMusic',
'Grocery', 'MP3Downloads', 'DVD', 'Electronics', 'HealthPersonalCare', 'HomeGarden', 'Industrial', 'Jewelry', 'KindleStore',
'Kitchen', 'Magazines', 'Merchants', 'Miscellaneous', 'MobileApps', 'Music', 'MusicalInstruments', 'MusicTracks',
'OfficeProducts', 'OutdoorLiving', 'PCHardware', 'PetSupplies', 'Photo', 'Shoes', 'Software', 'SportingGoods', 'Tools', 'Toys',
'UnboxVideo', 'VHS', 'Video', 'VideoGames', 'Watches', 'Wireless', 'WirelessAccessories'),
            'br' => array('All', 'MobileApps', 'Books', 'KindleStore'),
            'mx' => array('All', 'Baby', 'SportingGoods', 'Electronics', 'HomeImprovement', 'Kitchen', 'Books', 'Music', 'DVD', 'Watches', 'HealthPersonalCare', 'Software', 'VideoGames', 'KindleStore'));

         $search_index_info = array(
            'All' => array ( 'Keywords' => True ),
            'Apparel' => array ( 'Creator' => 'Manufacturer' ),
            'Appliances' => array ( 'Creator' => 'Manufacturer' ),
            'ArtsAndCrafts' => array ( 'Creator' => 'Brand'),
            'Automotive' => array ( 'Creator' => 'Manufacturer' ),
            'Baby' => array ( 'Creator' => 'Brand'),
            'Beauty' => array ( 'Creator' => 'Brand'),
            'Blended' => array ( 'Keywords' => True ),
            'Books' => array ( 'Creator' => 'Author'),
            'Classical' => array ( 'Creator' => 'Composer' ),
            'Collectibles' => array ( ),
            'DigitalMusic' => array ( 'Creator' => 'Actor' ),
            'DVD' => array ( 'Creator' => 'Director'),
            'Electronics' => array ( 'Creator' => 'Manufacturer' ),
            'ForeignBooks' => array ( 'Creator' => 'Author'),
            'Grocery' => array ( 'Creator' => 'Brand'),
            'HealthPersonalCare' => array ( 'Creator' => 'Manufacturer' ),
            'Hobbies' => array ( 'Creator' => 'Manufacturer' ),
            'HomeGarden' => array ( 'Creator' => 'Manufacturer' ),
            'Home' => array ( 'Creator' => 'Manufacturer' ),
            'HomeImprovement' => array ( 'Creator' => 'Manufacturer' ),
            'Industrial' => array ( 'Creator' => 'Manufacturer' ),
            'Jewelry' => array ( ),
            'KindleStore' => array ( 'Creator' => 'Author'),
            'Kitchen' => array ( 'Creator' => 'Manufacturer' ),
            'LawnGarden' => array ( 'Creator' => 'Manufacturer' ),
            'Lighting' => array ( 'Creator' => 'Brand'),
            'Magazines' => array ( 'Creator' => 'Publisher' ),
            'Marketplace' => array ( ),
            'Merchants' => array ( ),
            'Miscellaneous' => array ( 'Creator' => 'Brand'),
            'MobileApps' => array ( 'Creator' => 'Author'),
            'MP3Downloads' => array ( 'Creator' => 'Author'),
            'Music' => array ( 'Creator' => 'Artist' ),
            'MusicalInstruments' => array ( 'Creator' => 'Brand'),
            'MusicTracks' => array ( 'Keywords' => True ),
            'OfficeProducts' => array ( 'Creator' => 'Brand'),
            'OutdoorLiving' => array ( 'Creator' => 'Manufacturer' ),
            'Outlet' => array ( 'Keywords' => True ),
            'PCHardware' => array ( 'Creator' => 'Manufacturer' ),
            'PetSupplies' => array ( 'Creator' => 'Brand'),
            'Photo' => array ( 'Creator' => 'Manufacturer' ),
            'Shoes' => array ( 'Creator' => 'Brand'),
            'Software' => array ( 'Creator' => 'Manufacturer' ),
            'SoftwareVideoGames' => array ( 'Creator' => 'Manufacturer' ),
            'SportingGoods' => array ( 'Creator' => 'Brand' ),
            'Tools' => array ( 'Creator' => 'Manufacturer' ),
            'Toys' => array ( ),
            'UnboxVideo' => array ( 'Creator' => 'Director'),
            'VHS' => array ( 'Creator' => 'Director'),
            'Video' => array ( 'Creator' => 'Director'),
            'VideoGames' => array ( 'Creator' => 'Brand'),
            'Watches' => array ( ),
            'Wireless' => array ( ),
            'WirelessAccessories' => array ( )
            );
         return array('SearchIndexByLocale' => $search_index_by_locale);
      }

      function create_search_query( $Settings ) {
         
         // Not working: Baby, MusicalInstruments
         $Creator = array( 'Author' => array( 'Books', 'ForeignBooks', 'MobileApps', 'MP3Downloads', 'KindleStore'),
                           'Actor' => array( 'DigitalMusic' ),
                           'Artist' => array('Music'),
                           'Director' => array('DVD', 'UnboxVideo', 'VHS', 'Video'),
                           'Publisher' => array('Magazines'),
                           'Brand' => array('Apparel', 'ArtsAndCrafts', 'Baby', 'Beauty', 'Grocery', 'Lighting', 'OfficeProducts', 'Miscellaneous', 'PetSupplies', 'Shoes', 'MusicalInstruments', 'VideoGames'),
                           'Manufacturer' => array('Appliances', 'Automotive', 'Electronics', 'Garden', 'HealthPersonalCare', 'Hobbies', 'Home', 'HomeGarden', 'HomeImprovement', 'Industrial', 'Kitchen',  'OutdoorLiving', 'Photo', 'Software', 'SoftwareVideoGames'),
                           'Composer' => array('Classical'));

         $Keywords = array('Blended', 'All', 'DigitalMusic', 'MusicTracks', 'Outlet');

         $Sort['uk'] = array('salesrank'       => array('Books', 'Classical', 'DVD', 'Electronics', 'HealthPersonalCare', 'HomeGarden', 'HomeImprovement', 'Kitchen', 'MarketPlace', 'Music', 'OutdoorLiving', 'PCHardware', 'Software', 'SoftwareVideoGames', 'Toys', 'VHS', 'Video', 'VideoGames'),
                             'relevancerank'   => array('Apparel', 'Automotive', 'Baby', 'Beauty', 'Grocery', 'Jewelry', 'KindleStore', 'MP3Downloads', 'MusicalInstruments', 'OfficeProducts', 'Shoes', 'Watches'),
                             'xsrelevancerank' => array('Shoes'));
         $Sort['us'] = array('salesrank'       => array('Books', 'Classical', 'DVD', 'Electronics', 'HealthPersonalCare', 'HomeGarden', 'HomeImprovement', 'Kitchen', 'MarketPlace', 'Music', 'OutdoorLiving', 'PCHardware', 'Software', 'SoftwareVideoGames', 'Toys', 'VHS', 'Video', 'VideoGames'),
                             'relevancerank'   => array('Apparel', 'Automotive', 'Baby', 'Beauty', 'Grocery', 'Jewelry', 'KindleStore', 'MP3Downloads', 'MusicalInstruments', 'OfficeProducts', 'Shoes', 'Watches'),
                             'xsrelevancerank' => array('Shoes'));

         // Create query to retrieve the first 10 matching items
         $request = array('Operation' => 'ItemSearch',
                          'ResponseGroup' => 'Offers,ItemAttributes,Small,EditorialReview,Images,SalesRank',
                          'SearchIndex'=>$Settings['s_index'],
                          'ItemPage'=>$Settings['s_page']);

         foreach ($Sort['uk'] as $Term => $Indices) {
            if (in_array($Settings['s_index'], $Indices)) {
               $request['Sort'] = $Term;
               continue;
            }
         }

         if (!empty($Settings['s_author'])) {
             foreach ($Creator as $Term => $Indices) {
                if (in_array($Settings['s_index'], $Indices)) {
                   $request[$Term] = $Settings['s_author'];
                   continue;
                }
             }
         }

         if (in_array($Settings['s_index'], $Keywords)) {
            $request['Keywords']  = $Settings['s_title'];
         } else {
            $request['Title'] = $Settings['s_title'];
         }
         
         return $request;
      }
            
      function do_search( $settings ) {
         
         $request = $this->create_search_query( $settings );
         $items = $this->alink->cached_query( $request, $settings );

         return $items;
      }


/*****************************************************************************************/

      function find_attachments ( $asin ) {

         // Do we already have a local image ? 
         $args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => 'all', 'suppress_filters' => true,
                        'meta_query' => array( array( 'key' => 'amazon-link-ASIN', 'value' => $asin ) ) );
         $query = new WP_Query( $args );
         $media_ids = $query->posts;
         if ( $media_ids ) {
            return $media_ids;
         } else {
            return new WP_Error( __('No images found','amazon-link') );
         }
      }

      function grab_image ( $asin, $post_id = 0) {

         if ( ! ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) )
            return new WP_Error( $uploads['error'] );

         $asin = strtoupper($asin);

         $settings = $this->alink->get_default_settings();
         $data = $this->alink->cached_query( $asin, $settings, True );
         // Strip out arrays
         foreach ($data as $item => $content) {
            if ( is_array($content) ) {
               $data[$item] = $data[$item][0];
            } 
         }
         $data['asin'] = $asin;
         $data['template_content'] = '%IMAGE%';
         $image_url = $this->alink->shortcode_expand( $data );
         if (empty($image_url)) return new WP_Error(__('No Images Found for this ASIN', 'amazon-link'));
                              
         $result = wp_remote_get( $image_url );
         if (is_wp_error($result))
            return $result; //new WP_Error(__('Could not retrieve remote image file','amazon-link'));

         // Save file to media library
         $filename = $asin. '.JPG';
         $filename = '/' . wp_unique_filename( $uploads['path'], basename($filename));
         $filename_full = $uploads['path'] . $filename;
         $content = $result['body'];
         $size = file_put_contents ($filename_full, $content);

         if (is_readable($filename_full)) {
            // Grabbed Image successfully now add it to the media library
            $wp_filetype = wp_check_filetype(basename($filename_full), null );
            $attachment = array(
               'guid' => $filename,
               'post_mime_type' => $wp_filetype['type'],
               'post_title' => $data['artist'] . ' - ' . $data['title'],   // Title
               'post_excerpt' => $data['title'],                     // Caption
               'post_content' => '',                           // Description
               'post_status' => 'inherit');
            $attach_id = wp_insert_attachment( $attachment, $filename_full, $post_id);
            // you must first include the image.php file
            // for the function wp_generate_attachment_metadata() to work
            update_post_meta($attach_id , 'amazon-link-ASIN', $asin);
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename_full );
            wp_update_attachment_metadata( $attach_id,  $attach_data );
         } else {
            return new WP_Error(__('Could not read downloaded image','amazon-link'));
         }
         return $attach_id;
      }

/*****************************************************************************************/

      function get_images_filter ($images, $keyword, $country, $l_data, $settings, $al) {

         $data = &$al->temp_data;

         if (isset($data['get_images_run'][$country][$keyword])) return $images;
         $data['get_images_run'][$country][$keyword] = 1;
         
         /*
          * Check for image in uploads 
          */
         if (empty($data[$country]['media_id'])) {
            $asin = isset($data[$country]['asin']) ? $data[$country]['asin'] : $data[$settings['home_cc']]['asin'];
            $media_ids = $this->find_attachments( $asin );

            if (!is_wp_error($media_ids)) {

               // Only do one country, as other countries may have a different ASIN specified.
               $data[$country]['media_id'] = $media_ids[0]->ID;
               $data[$country]['downloaded'] = '1';
            } else {
               $data[$country]['media_id'] = -1;
               $data[$country]['downloaded'] = '0';
               return $images;
            }
         }

         if ($data[$country]['downloaded']) {
            if ($keyword == 'image') {
               $image = wp_get_attachment_url($data[$country]['media_id']);
            } else if ($keyword == 'thumb') {
               $image = wp_get_attachment_thumb_url($data[$country]['media_id']);
            }
            if (!empty($image)) return (array)$image;
         }
         return $images;
      }

   }
}
?>
