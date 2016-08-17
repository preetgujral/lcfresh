<?php

   if ( ! function_exists( 'remove_duplicates' ) ) {
      function remove_duplicates( $asins, $local_cc, $default_cc ) {
         
         $unique_asins = array();
         $count = count( $asins );
         for ( $index = 0; $index < $count; $index++ ) {
            $asin = isset( $asins[$index][$local_cc] ) ? $asins[$index][$local_cc] : 
               ( isset($asins[$index][$default_cc] ) ? $asins[$index][$default_cc] : '' );
            if ( (strlen( $asin ) < 8) || in_array( $asin, $unique_asins ) ) {
               unset( $asins[$index] );
            } else {
               $unique_asins[] = $asin;
            }
         }
         return $asins;
      }
   }

   $cc = $settings['local_cc'];

   /*
    * Search Based Wishlist
    */
   if ( ! empty( $settings[$cc]['s_index'] ) ) {
      
      // Create instance of the search facility if it is not available.
      if ( empty( $this->search ) ) {
         include( 'amazonSearch.php' );
         $this->search = new AmazonLinkSearch;
      }
      
      // Create and run the uncached Query to just return the ASIN's
      $request = $this->search->create_search_query( $settings[$cc] );
      $request['ResponseGroup'] = 'ItemIds';
      $response = $this->doQuery( $request, $settings[$cc] );
      
      // Check we have some ASINs to process
      if ( empty( $response['Items']['Item'] ) )
      {
         // No ASINS so drop out with hidden error.
         $output  = '<!--' . __('Amazon query failed to return any results - Have you configured the AWS settings?', 'amazon-link').'-->';
         $output .= '<!-- '. print_r( $request, true ) . '-->';
         return $output;
         
      } else {

         $asins_list = array();

         // Extract ASINs from the response
         if ( ! array_key_exists( '0', $response['Items']['Item'] ) ) {
            $asins_list[] = array ( $cc => $response['Items']['Item']['ASIN'] );
         } else {
            foreach ($response['Items']['Item'] as $details) {
               $asins_list[] = array ( $cc => $details['ASIN'] );
            }
         }
      }

      // Now use these ASINs to generate a wishlist. Force to non-localised 
      // as the search results are from this locale not the default one
      $settings[$cc]['wishlist_type'] = 'multi';
      $settings[$cc]['default_cc'] = $cc;
      $settings[$cc]['home_cc'] = $cc;
      $settings['default_cc'] = $cc;
      $settings['home_cc'] = $cc;
   } else if ( ! empty( $settings[$cc]['alt'] )) {
       
      /*
       * Alternate Version based List
       */
      $request = array( 'Operation' => 'ItemLookup',
                           'ItemId' => $settings[$cc]['alt'],
                           'ResponseGroup' => 'AlternateVersions',
                           'IdType' => 'ASIN' );
      $response = $this->doQuery( $request, $settings[$cc] );
      

      if ( empty($response['Items']['Item']['AlternateVersions']['AlternateVersion'] ) ) {
         
         $output = '<!--' . __('Amazon query failed to return any results - Have you configured the AWS settings?', 'amazon-link').'-->';
         $output .= '<!-- '. print_r($response, true) . '-->';
         return $output;
         
      } else {
         
         $asins_list = array();
         // Extract ASINs from the response
         if ( ! array_key_exists( '0', $response['Items']['Item']['AlternateVersions']['AlternateVersion'] ) ) {
            $asins_list[] = array ( $cc => $response['Items']['Item']['AlternateVersions']['AlternateVersion'] );
         } else {
            foreach ( $response['Items']['Item']['AlternateVersions']['AlternateVersion'] as $details ) {
               $asins_list[] = array ( $cc => $details['ASIN'] );
            }
         }
      }
      $settings[$cc]['wishlist_type'] = 'multi';
      $settings[$cc]['default_cc'] = $cc;
      $settings[$cc]['home_cc'] = $cc;
      $settings['default_cc'] = $cc;
      $settings['home_cc'] = $cc;

   } else if ( strcasecmp( $settings[$cc]['cat'], 'local' ) != 0 ) {

      // If using local tags then just process the ones on this page otherwise search categories.
      
      // First process all post content for the selected categories
      $content = '';
      $get_posts = new WP_Query;
      
      // Get posts using either numeric 'cat' or alpha 'category_name'
      if ( preg_match('!^[0-9,]*$!', $settings[$cc]['cat'] ) ) {
         $lastposts = $get_posts->query( array( 'numberposts'=> $settings[$cc]['last'], 
                                                'cat'=> $settings[$cc]['cat'] ) );
      } else {
         $lastposts = $get_posts->query( array( 'numberposts'=> $settings[$cc]['last'], 
                                                'category_name' => $settings[$cc]['cat'] ) );
      }
      
      // Grab all post content
      foreach ( $lastposts as $id => $post) {
         $content .= $post->post_content;
      }
      unset( $lastposts );
      
      // Use content_filter to parse all shortcodes, extracting ASINs
      $saved_tags = $this->tags;
      $this->tags = array();
      $this->content_filter( $content, False, False );
      unset( $content );
      $asins_list = remove_duplicates( $this->tags, $cc, $settings['default_cc'] );
      $this->tags = $saved_tags;
      
      // Need to reset 'Settings' back to our settings. TODO: use local not global?
      $this->Settings = &$settings['global'];

   } else {
      
      // Use the 'local' ASINs already found by the plugin

      $asins_list = remove_duplicates( $this->tags, $cc, $settings['default_cc'] );
   }
   /*
    * If we have some ASINS in the tags array then use them to create a wishlist.
    */
   if ( ( count( $asins_list ) != 0 ) && is_array( $asins_list ) )
   {
      if ( strcasecmp( $settings[$cc]['wishlist_type'], 'similar' ) == 0 ) {
         
         // Special case we want to get 'similar' products to the ones listed...
         // Construct a request to get the Cart Similarities for the items found
         $request = array( 'Operation' => 'CartCreate',
                           'MergeCart' => 'True',
                           'ResponseGroup' => 'CartSimilarities',
                           'IdType' => 'ASIN',
                           'MerchantId' => 'Amazon' );
         $counter = 1;
         foreach ( $asins_list as $asins )
         {
             $asin = isset( $asins[$cc] ) ? $asins[$cc] : 
                                            ( isset( $asins[$settings['default_cc']] ) ? $asins[$settings['default_cc']] : '' );

            $request['Item.' . $counter . '.ASIN'] = $asin;
            $request['Item.' . $counter . '.Quantity'] = 1;
            $counter++;
         }

         $response = $this->doQuery( $request, $settings[$cc] );
         
         if ( empty($response['Cart']['SimilarProducts']['SimilarProduct'] ) ) {
            
            $output = '<!--' . __('Amazon query failed to return any results - Have you configured the AWS settings?', 'amazon-link').'-->';
            $output .= '<!-- '. print_r($request, true) . '-->';
            return $output;
         
         } else {

            $asins_list = array();
            // Extract ASINs from the response
            if ( ! array_key_exists( '0', $response['Cart']['SimilarProducts']['SimilarProduct'] ) ) {
               $asins_list[] = array ( $cc => $response['Cart']['SimilarProducts']['SimilarProduct'] );
            } else {
               foreach ( $response['Cart']['SimilarProducts']['SimilarProduct'] as $details ) {
                  $asins_list[] = array ( $cc => $details['ASIN'] );
               }
            }
         }
         $settings[$cc]['default_cc'] = $cc;
         $settings[$cc]['home_cc'] = $cc;
         $settings['default_cc'] = $cc;
         $settings['home_cc'] = $cc;

      } else if ( strcasecmp( $settings[$cc]['wishlist_type'], 'random' ) == 0 ) {
         
         shuffle( $asins_list );
      }
      
      // We have a processed list of ASINs, get on with displaying the items
      
      $settings[$cc]['live'] = 1;
      $settings['asin'] = array_slice( $asins_list, 0, $settings[$cc]['wishlist_items'] );
      
      if ( ! isset( $settings[$cc]['template'] ) ) {
         $settings[$cc]['template'] = $settings[$cc]['wishlist_template'];
      }
      
      $output = '<div class="amazon_container">';
      $output .= $this->make_links( $settings );
      $output .= "</div>";
   } else {
      $output = "<!--". sprintf(__('No [amazon] tags found in the last %1$s posts in categories %2$s', 'amazon-link'), $settings[$cc]['last'], $settings[$cc]['cat']). "--!>";
   }

   return $output;

?>
