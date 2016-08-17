<?php

/*
Plugin Name: Amazon Link Extra - References
Plugin URI: http://www.houseindorset.co.uk/plugins/amazon-link/
Description: !!!BETA!!! This plugin adds the ability to pre-define shortcodes and save them in the database with a unique reference that can be re-used many times across your site from within multiple shortcodes, updating the single item in the database will change all the links that use that reference. Create the named reference on the 'Reference' settings page and then in the shortcode simply add the argument 'ref=XXX'
Version: 1.3.8
Author: Paul Stuttard
Author URI: http://www.houseindorset.co.uk
*/

/*
Copyright 2012-2013 Paul Stuttard

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define("refs_table", "amazon_link_refs");

function alx_reference_show_panel ($post, $args) {
   global $wpdb;

   $ths = $args['args'];

   $options    = alx_reference_get_options ($ths);
   $refs_table = $wpdb->prefix . refs_table;

   $results_template = htmlspecialchars ('
<div class="al_found%FOUND%">
 <div class="amazon_prod">
  <div class="amazon_img_container">%LINK_OPEN%<img src="%THUMB%" class="%IMAGE_CLASS%">%LINK_CLOSE%</div>

  <div class="amazon_text_container">
   <p>%LINK_OPEN%%TITLE%%LINK_CLOSE%</p>

   <div class="amazon_details">
      <div style="float:right">
       <div style="width:100%" id="al_buttons">
        <input style="float:left" type="button" title="'. __('Add ASIN to country specific list of ASINs below.','amazon-link'). '"onClick="return wpAmazonLinkAd.addASIN(this.form, {asin: \'%ASIN%\', cc: \'%CC%\'} );" value="'.__('+', 'amazon-link').'" class="button-secondary">
        <input style="float:right" id="upload-button-%ASIN%" type="button" title="'. __('Upload cover image into media library','amazon-link'). '"onClick="return wpAmazonLinkSearch.grabMedia(this.form, {asin: \'%ASIN%\'} );" value="'.__('Upload', 'amazon-link').'" class="al_hide-%DOWNLOADED% button-secondary">
        <input style="float:right" id="uploaded-button-%ASIN%" type="button" title="'. __('Remove image from media library','amazon-link'). '"onClick="return wpAmazonLinkSearch.removeMedia(this.form, {asin: \'%ASIN%\'} );" value="'.__('Delete', 'amazon-link').'" class="al_show-%DOWNLOADED% button-secondary">
       </div>
      </div>
   
     <p>'. __('by %ARTIST% [%MANUFACTURER%]', 'amazon-link') .'<br />
     '. __('Type: %PRODUCT%', 'amazon-link') .'<br />
     '. __('Binding: %BINDING%', 'amazon-link') .'<br />
     '. __('Rank/Rating: %RANK%/%RATING%', 'amazon-link').'<br />
     <b>' .__('Price', 'amazon-link').': <span style="color:red;">%PRICE%</span></b>
    </p>
   </div>

  </div>
 </div>
</div>');

   $Action = (isset($_POST[ 'AmazonReferenceAction' ]) && check_admin_referer( 'reference-AmazonLink-filter' )) ?
                      $_POST[ 'AmazonReferenceAction' ] : 'No Action';

   /************************************************************************************/
   /*
    * Process the options selected by the User
    */
   foreach ($options as $id => $details) {
      if (isset($details['Name'])) {
         // Read their posted value or if no action set to defaults
         if (isset($_POST[$id])) {
            $opts[$id] = stripslashes($_POST[$id]);
         } else if (($Action == 'No Action') && isset($details['Default'])) {
            $opts[$id] = $details['Default'];
         }
      }
   }

   /************************************************************************************/
   /*
    * Now process the actions
    */

   if (($Action == __('Update Shortcode','amazon-link')) ) {
   
      /* Populate the Shortcode settings based on the other options */

      $shortcode = '';
      $sep = '';
      $country_data = $ths->get_country_data();
      foreach($country_data as $cc => $data) {
         if (!empty($opts['asin'.$cc])) {
            $shortcode .= $sep.'asin['.$cc.']='. $opts['asin'.$cc];
            $sep='&';
         }
      }
      foreach ($options as $opt => $data) {
         if (isset($data['Shortcode']) && !empty($opts[$opt]) && (trim($opts[$opt]) != '')) {
            $shortcode .= $sep.$opt.'='.$opts[$opt];
            $sep='&';
         }
      }
      $opts['shortcode'] = $shortcode . (!empty($opts['args']) ? $sep . $opts['args'] : '');
      $Action = __('Update','amazon-link');
   }

   if (($Action == __('New','amazon-link')) ) {
      $opts = array('name' => 'New Reference');
      $Action == __('Create','amazon-link');
   }
   if (($Action == __('Create','amazon-link')) ) {

      $found = True;

      while ($found) {
         $opts['ref'] = strtolower(preg_replace('![^a-zA-Z0-9]+!', '-',$opts['name']));
         $sql = "SELECT * FROM $refs_table WHERE `ref` LIKE '".$opts['ref']."';";
         $data = $wpdb->get_row($sql, ARRAY_A);
         $found = $data;
         if ($found) $opts['name'] .= ' New';
      }

      $sql_data = $opts;
      foreach ($options as $option => $details) if ($details['Type'] == 'hidden') unset($sql_data[$option]);

      $wpdb->insert($refs_table, $sql_data);
      $update = sprintf(__('Reference %s created.', 'amazon-link' ), $sql_data['ref']);
   }

   if (($Action == __('Update','amazon-link')) ) {

      $sql = "DELETE FROM $refs_table WHERE ref LIKE '".$opts['ref']."';";
      $wpdb->query($sql);

      $sql_data = $opts;
      $sql_data['ref'] = strtolower(preg_replace('![^a-zA-Z0-9]+!', '-',$opts['name']));
      foreach ($options as $option => $details) if ($details['Type'] == 'hidden') unset($sql_data[$option]);

      $result = $wpdb->insert($refs_table, $sql_data);
      if ($result !== False) {
         $update = sprintf(__('Reference %s updated.', 'amazon-link' ), $sql_data['ref']);
         $opts['ref'] = $sql_data['ref'];
      } else {
         $update = sprintf(__('Update for %s failed [%s].', 'amazon-link' ), $sql_data['ref'], $wpdb->last_error);
      }
   }

   if (($Action == __('Delete','amazon-link')) ) {
      $sql = "DELETE FROM $refs_table WHERE ref LIKE '".$opts['ref']."';";
      $wpdb->query($sql);
      if ($result !== False) {
         $update = sprintf(__('Reference %s deleted.', 'amazon-link' ), $opts['ref']);
         $sql = "SELECT * FROM $refs_table LIMIT 1;";
         $data = $wpdb->get_row($sql, ARRAY_A);
         $opts = array_merge($opts, $data);
       } else {
         $update = sprintf(__('Deletion of %s failed [%s].', 'amazon-link' ), $opts['ref'], $wpdb->last_error);
      }
   }

   if (($Action == __('Select','amazon-link')) ) {
      $sql = "SELECT * FROM $refs_table WHERE `ref` LIKE '".$opts['ref']."';";
      $data = $wpdb->get_row($sql, ARRAY_A);
      $opts = array_merge($opts, $data);
   }

   // Default ASIN for the reference form is the one from the default cc
   $opts['asin'] = $opts['asin' . $opts['home_cc']];

   if (isset($update)) {
      // **********************************************************
      // Put an options updated message on the screen
      echo '<div class="updated"><p><strong>'. $update . '</strong></p></div>';
   }

   /************************************************************************************/
   /*
    * Display the modified search form
    */
   $opts['form_template'] = isset($opts['template']) ? $opts['template'] : '';
   remove_filter('amazon_link_search_form', 'alx_reference_search_form',12,2);
   add_filter('amazon_link_search_form', 'alx_reference_form',12,2);
   $ths->insertForm('admin', array('results_template' => $results_template, 'Options' => $opts));

   /************************************************************************************/
   /*
    * Display the preview
    */
   if (!empty($opts['shortcode'])) {
      $opts['shortcode'] .= '&live=1';
      //$settings = $ths->parseArgs($opts['shortcode']);
      $ths->in_post = 0;
      $ths->post_ID = '';
      echo $ths->shortcode_expand( array( 'args'=>$opts['shortcode'] ) );
      $ths->footer_scripts();
   }
}

/*
 * Return the base form options
 */
function alx_reference_get_options ($al) {
   global $wpdb;   
   $settings = $al->getSettings();
   $refs_table = $wpdb->prefix . refs_table;

   $options = array( 
         'nonce'       => array ( 'Type' => 'nonce', 'Value' => 'reference-AmazonLink-filter' ),
         'page'        => array ( 'Type' => 'hidden'),
         'home_cc'     => array ( 'Type' => 'hidden', 'Name' => 'Home cc', 'Id' => 'AmazonLinkOpt', 'Default' => $settings['default_cc']),
         'ref'         => array ( 'Type' => 'selection', 'Name' => __('Reference', 'amazon-link'), 'Description' => __('Reference used to access this shortcode, derived from the Name', 'amazon-link'),
                                  'Buttons' => array( __('Select', 'amazon-link') => array( 'Action' => 'AmazonReferenceAction', 'Hint' => __('Show the details of this Reference','amazon-link'), 'Class' => 'button-secondary'),
                                                      __('Delete', 'amazon-link') => array( 'Action' => 'AmazonReferenceAction', 'Hint' => __('Delete this Reference','amazon-link'), 'Class' => 'button-secondary') )
                                ),

         'name'        => array ( 'Type' => 'text', 'Name' => __('Item Name', 'amazon-link'), 'Description' => __('The item\'s name that is used to derive the reference that can be used in shortcodes', 'amazon-link'),
                                  'Buttons' => array( __('Create', 'amazon-link') => array( 'Action' => 'AmazonReferenceAction', 'Hint' => __('Create a new Reference using the details displayed','amazon-link'), 'Class' => 'button-secondary'),
                                                      __('New', 'amazon-link') => array( 'Action' => 'AmazonReferenceAction', 'Hint' => __('Create a new blank Reference','amazon-link'), 'Class' => 'button-secondary') )
                                ),

         'description' => array ( 'Type' => 'text', 'Name' => __('Description', 'amazon-link'), 'Description' => __('Short description to indicate what the reference represents', 'amazon-link')),

         'shortcode'   => array ( 'Type' => 'textbox', 'Input_Class' => 'al_fixed_width', 'Disabled' => 1, 'Read_Only' => 1, 'Name' => __('Shortcode', 'amazon-link'), 'Description' => __('Shortcode parameters saved with this item, this is what the \'reference\' will be replaced with.', 'amazon-link'), 'Rows'=> 5, 'Default' => '',
                                  'Buttons' => array ( __('Update Shortcode', 'amazon-link') => array( 'Action' => 'AmazonReferenceAction', 'Hint' => __( 'Update the content of the Shortcode based on all other fields', 'amazon-link'), 'Class' => 'button-secondary'))
                                ),
         'args'        => array ( 'Type' => 'textbox', 'Input_Class' => 'al_fixed_width', 'Name' => __('Extra Arguments', 'amazon-link'), 'Description' => __('Additional Shortcode Arguments.', 'amazon-link'), 'Rows'=> 5, 'Default' => ''),
         'template'    => array ( 'Type' => 'selection', 'Shortcode' => 1, 'Name' => __('Template', 'amazon-link')),
         'chan'        => array ( 'Type' => 'selection', 'Shortcode' => 1, 'Name' => __('Channel', 'amazon-link')),
         'title1'      => array ( 'Type' => 'title', 'Value' => __('Country Specific ASINs:', 'amazon-link'), 'Title_Class' => 'al_sub_head'));


   $sql = "SELECT ref,name,description FROM $refs_table;";
   $references = $wpdb->get_results($sql);
   if ($references) {
      foreach ($references as $reference) {
         $options['ref']['Options'][$reference->ref] = array('Name' => $reference->ref, 'Hint' => $reference->description);
      }
   }
   $country_data = $al->get_country_data();
   foreach($country_data as $cc => $data) {
      $options['asin' . $cc] = array('Type' => 'text', 'Default' => '',
                                     'Name' => '<img style="height:14px;" src="'. $data['flag'] . '"> ' . $data['country_name'],
                                     'Hint' => sprintf(__('ASIN(s) specific to %1$s.', 'amazon-link'), $data['country_name'] ));
   }
   return $options;
}

/*
 * filter to change the search form to options to display the 'Reference' data and Actions
 */
function alx_reference_form($options, $al) {
   $keys = array_keys($options);

   // Create new options page using elements from the original
   $new_options = alx_reference_get_options ($al);
   $new_options['template'] = $options['template'];
   unset($new_options['template']['Default']);
   $new_options['chan'] = $options['chan'];


   $new_options['Buttons'] = array ( 'Type' => 'buttons', 'Buttons' => 
                                           array ( __('Update', 'amazon-link') => array( 'Action' => 'AmazonReferenceAction', 'Hint' => __( 'Save this item to the database.', 'amazon-link'), 'Class' => 'button-secondary')
                                   ));

   $new_options += array_slice( $options, array_search('subhd2', $keys), 3, true);
   $new_options['asin'] = array ( 'Type' => 'hidden', 'Id' => 'AmazonLinkOpt');

   $new_options['default_cc'] = array( 'Id' => 'amazon-link-search', 'Name' => __('Country', 'amazon-link'), 'Hint' => __('The Amazon site to search for products.', 'amazon-link'), 'Default' => 'uk', 'Type' => 'selection' );

   $new_options['s_index'] = $options['s_index'];
   $new_options['s_author'] = $options['s_author'];
   $new_options['s_title'] = array('Id' => 'amazon-link-search', 'Name' => __('Title', 'amazon-link'), 'Hint' => __('Items Title to search for', 'amazon-link'), 'Type' => 'text', 'Default' => '', 
                             'Buttons' => array ( __('Translate', 'amazon-link') => array( 'Type' => 'button',
                                                                                           'Hint' => __('Translate the Title into the local language', 'amazon-link'),
                                                                                           'Id' => 'amazon-link-search',
                                                                                           'Class' => 'button-secondary',
                                                                                           'Script' => 'return wpAmazonLinkAd.translate(this.form);')));
   $new_options['s_title_trans'] = array('Id' => 'amazon-link-search', 'Name' => __('Translated Title', 'amazon-link'), 'Hint' => __('Item Title in local Language', 'amazon-link'), 'Type' => 'text', 'Default' => '');
   $new_options['translate']     = array('Id' => 'amazon-link-search', 'Name' => __('Use Translation', 'amazon-link'), 'Hint' => __('Translate the title into locale language', 'amazon-link'), 'Type' => 'checkbox', 'Default' => '0');

   $country_data = $al->get_country_data();
   foreach($country_data as $cc => $data) {
      $new_options['default_cc']['Options'][$cc]['Name'] = $data['country_name'];
   }
   $new_options += array_slice( $options, array_search('s_page', $keys), 3, true);
   return $new_options;
}

/*
 * filter to add reference to standard search form
 */
function alx_reference_search_form($options, $al) {
   $keys = array_keys($options);
   $insert_at = array_search('text', $keys);
   $new_options = array_slice( $options, 1, $insert_at, true);
   $ref_options = alx_reference_get_options ($al);
   $refs = array('' => array('Name' => '')) + (isset($ref_options['ref']['Options']) ? $ref_options['ref']['Options'] : array());

   $new_options['ref'] = array( 'Name' => 'Reference', 'Hint' => __('Use this reference as the basis for the shortcode','amazon-link'), 
                                'Options' => $refs, 'Type' => 'selection', 'Id' => 'AmazonLinkOpt');

   $new_options += array_slice( $options, $insert_at+1, NULL, true);
   return $new_options;
}

/*
 * Add the Product Reference Menu
 */
function alx_reference_menus ($menu, $al) {

   $menu['amazon-link-reference'] = array( 'Slug' => 'amazon-link-reference',
//                                        'Help' => 'help/reference.php',
                                           'Icon' => 'tools',
                                           'Description' => __('On this page you can search for Amazon products across all locales. It also provides the ability to add \'named items\' to the database that can be referenced from within shortcodes.', 'amazon-link'),
                                           'Title' => __('Create Shortcode References to Amazon Products', 'amazon-link'), 
                                           'Label' => __('References', 'amazon-link'), 
                                           'Capability' => 'manage_options',
                                           'Scripts' => array( array($al,'edit_scripts')),
                                           'Metaboxes' => array( 'al-reference' => array( 'Title' => __( 'References', 'amazon-link' ),
                                                                                          'Callback' => 'alx_reference_show_panel', 
                                                                                          'Context' => 'normal',
                                                                                          'Priority' => 'core'))
                                        );
   return $menu;
}

function alx_reference_install_db() {
   global $wpdb;

   $awlfw = new AmazonWishlist_For_WordPress();
   $country_data = $awlfw->get_country_data();

   $refs_table = $wpdb->prefix . refs_table;
   $sql = "CREATE TABLE $refs_table (
           ref varchar(30) NOT NULL,
           name varchar(30) NOT NULL,
           description varchar(50) NOT NULL,
           shortcode blob NOT NULL,
           args blob NOT NULL,
           template varchar(30) NOT NULL,
           chan varchar(30) NOT NULL,
";
   foreach($country_data as $cc => $data) {
      $sql .= "asin$cc varchar(10) NOT NULL,
";
   }
   $sql .= "PRIMARY KEY  (ref)
           );";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);

   return True;
}

/*
 * Needs to be in keywords for search / replace to work & for it to be part of search javascript???
 */
function alx_reference_keywords ($options) {
   $options['ref'] = array ( 'User' => 1, 'Description' => __('Unique Shortcode reference','amazon-link'));
   return $options;
}

function alx_reference_lookup ($args, $al) {
   global $wpdb;
   $refs_table = $wpdb->prefix . refs_table;
   if ( ! empty ($args['global']['ref'])) {
      $sql = "SELECT * FROM $refs_table WHERE `ref` LIKE '".$args['global']['ref']."';";
      $data = $wpdb->get_row($sql, ARRAY_A);
      if ($data) {
         $al->parse_str($data['shortcode'], $args);
      }
   }
   return $args;
}


/*
 * Install the Product Reference Settings Page
 *
 * Modifies the following Functions:
 *  - Add a new Admin Menu page that provides the Product Reference facility (alx_reference_menus)
 *  - Add a filter on the arguments to extract the 'reference' arguments (alx_reference_lookup)
 *  - Add a activation hook to install the database (alx_reference_install_db)
 */
   add_action( 'amazon_link_pre_init', 'alx_reference_install');
   function alx_reference_install()
   {
      add_filter('amazon_link_admin_menus', 'alx_reference_menus',12,2);
      add_filter('amazon_link_process_args', 'alx_reference_lookup',12,2);
      add_filter('amazon_link_keywords', 'alx_reference_keywords',12,1);
      add_filter('amazon_link_search_form', 'alx_reference_search_form',12,2);
   }
   register_activation_hook( __FILE__, 'alx_reference_install_db');
?>
