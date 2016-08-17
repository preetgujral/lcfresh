<?php

/*
Plugin Name: Amazon Link Extra - Cache Manager
Plugin URI: http://www.houseindorset.co.uk/plugins/amazon-link/
Description: !!!BETA!!! This plugin adds the ability to manage entries in the Amazon Link Item Cache
Version: 1.0.3
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

/*
 * TODO:
 *  - Add option to enable/disable CRON Task
 *  - Add CRON Task to search and refresh items in cache 1 per second
 *  - Add function to refresh 'last_viewed' of items on cache_fetch
 *  - Update CRON Task to delete items with old 'last_viewed'
 *  - Add upgrade function in main plugin to update the cache_database with 'last_viewed'
 */  
 
function alx_cache_show_settings_panel ($post, $args) {
   global $wpdb;

   $al = $args['args'];
   $cache_table = $wpdb->prefix . AmazonWishlist_For_WordPress::cache_table;
   
   $options = array(
         'nonce'         => array ( 'Type' => 'nonce', 'Value' => 'alx-cache-manage' ),
         'hds1'          => array ( 'Type' => 'section', 'Value' => __('Cache Settings', 'amazon-link'), 'Description' => __('Settings to control the Cache behaviour.','amazon-link'), 'Section_Class' => 'al_subhead1', 'Title_Class' => 'al_section_head'),
         'cache_age'     => array ( 'Name' => __('Cache Data Age', 'amazon-link'), 'Description' => __('Max age in hours of the data held in the Amazon Link Cache', 'amazon-link'), 'Type' => 'text', 'Default' => '48', 'Class' => 'al_border'),
         'cache_enabled' => array ( 'Type' => 'backend', 'Default' => '0'),
     );
   $options = alx_cache_option_list($options);

   $options['end1'] = array ( 'Type' => 'end' );
   $options['update'] = array ( 'Type' => 'buttons', 'Buttons' =>
                                  array ( __('Update Options', 'amazon-link') => array( 'Action' => 'alx-cache-action', 'Hint' => __( 'Update Settings', 'amazon-link'), 'Class' => 'button-primary')
                                         )
                                );
   $options['hds2']= array ( 'Type' => 'section', 'Value' => __('Cache Info', 'amazon-link'), 'Description' => __('Cache Health Check Results.','amazon-link'), 'Section_Class' => 'al_subhead1', 'Title_Class' => 'al_section_head');
   $options['info']= array ( 'Type' => 'title', 'Value' => __('', 'amazon-link'), 'Title_Class' => 'al_para');
   $options['end2'] = array ( 'Type' => 'end' );   

   $options['hds3']= array ( 'Type' => 'section', 'Value' => __('Cache Control', 'amazon-link'), 'Description' => __('Actions to Perform Globally on the Cache.','amazon-link'), 'Section_Class' => 'al_subhead1', 'Title_Class' => 'al_section_head');
   $options['cache_c'] = array ( 'Type' => 'buttons', 'Buttons' => array(
                                                  __('Enable Cache', 'amazon-link' ) => array( 'Hint' => __('Install the sql database table to cache data retrieved from Amazon.', 'amazon-link'), 'Class' => 'button-secondary', 'Action' => 'alx-cache-action'),
                                                  __('Disable Cache', 'amazon-link' ) => array( 'Hint' => __('Remove the Amazon Link cache database table.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'alx-cache-action'),
                                                  __('Enable Refresh', 'amazon-link' ) => array( 'Hint' => __('Enable automatic Amazon Link Product cache refreshing.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'alx-cache-action'),
                                                  __('Disable Refresh', 'amazon-link' ) => array( 'Hint' => __('Disable automatic Amazon Link Product cache refreshing.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'alx-cache-action'),
                                                  __('Test Refresh', 'amazon-link' ) => array( 'Hint' => __('Test Run Cache Refresh Cron Task.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'alx-cache-action'),
                                                  __('Flush Cache', 'amazon-link' ) => array( 'Hint' => __('Delete all data in the Amazon Link cache.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'alx-cache-action')
                                                                        )
                                   );
   $options['end3'] = array ( 'Type' => 'end' );

   
   $opts = $al->get_default_settings();
   $age = $opts['cache_max_age'];
   $cache_table = $wpdb->prefix . AmazonWishlist_For_WordPress::cache_table;

   
/*****************************************************************************************/

   // See if the user has posted us some information
   // If they did, the admin Nonce should be set.
   $Action = (isset($_POST[ 'alx-cache-action' ]) && check_admin_referer( 'alx-cache-manage' )) ?
                    $_POST[ 'alx-cache-action' ] : 'No Action';

   if(  $Action == __('Update Options', 'amazon-link') ) {


   /************************************************************************************/
   /*
    * Process the options selected by the User
    */

      foreach ($options as $optName => $optDetails) {
         if (isset($optDetails['Name'])) {
            if (!isset($_POST[$optName])) $_POST[$optName] = NULL;
            // Read their posted value
            $opts[$optName] = stripslashes($_POST[$optName]);
         }
      }
      $update = __('Options saved.', 'amazon-link' );
     


   /************************************************************************************/
   /*
    * Now process the actions
    */

   // Cache Actions
   } else if ( $Action == __('Enable Cache', 'amazon-link')) {
      if ($al->cache_install()) {
         $update = __('Amazon Data Cache Enabled', 'amazon-link');
         $opts['cache_enabled'] = 1;
      }
   } else if ( $Action == __('Disable Cache', 'amazon-link')) {
      if ($al->cache_remove()) {
         $update = __('Amazon Data Cache Disabled and Removed', 'amazon-link');
         $opts['cache_enabled'] = 0;
      }
   } else if ( $Action == __('Flush Cache', 'amazon-link')) {
      if ($al->cache_empty()) {
         $update = __('Amazon Data Cache Emptied', 'amazon-link');
      }
   } else if ( $Action == __('Enable Refresh', 'amazon-link')) {
      if (alx_cache_enable_cron($al)) {
         $opts['cache_cron'] = 1;
         $update = __('Amazon Background Refresh Task Enabled', 'amazon-link');
      }
   } else if ( $Action == __('Disable Refresh', 'amazon-link')) {
      if (alx_cache_disable_cron($al)) {
         $opts['cache_cron'] = 0;
         $update = __('Amazon Background Refresh Task Disabled', 'amazon-link');
      }
   } else if ( $Action == __('Test Refresh', 'amazon-link')) {
      alx_cache_refresh_entries();
      $update = __('Amazon Data Cache Refresh Run', 'amazon-link');
   }

   
   // If Enabled then take the opportunity to flush old data
   if (!empty($opts['cache_enabled'])) {
      $options ['cache_c']['Buttons'][__('Enable Cache', 'amazon-link' )]['Disabled'] = 1;
   } else {
      $options ['cache_c']['Buttons'][__('Disable Cache', 'amazon-link' )]['Disabled'] = 1;
      $options ['cache_c']['Buttons'][__('Flush Cache', 'amazon-link' )]['Disabled'] = 1;
   }
   
   if (!empty($opts['cache_cron'])) {
      $options ['cache_c']['Buttons'][__('Enable Refresh', 'amazon-link' )]['Disabled'] = 1;
   } else {
      $options ['cache_c']['Buttons'][__('Disable Refresh', 'amazon-link' )]['Disabled'] = 1;
   }
   
   if (isset($update)) {
      // **********************************************************
      // Put an options updated message on the screen
      echo '<div class="updated"><p><strong>'. $update . '</strong></p></div>';
      $al->saveOptions($opts);
   }

   /************************************************************************************/
   /*
    * Display the cache settings
    */
   if ( $opts['cache_enabled'] ) {
      $cache_outofdate = $wpdb->get_var("SELECT count(*) FROM $cache_table WHERE updated < DATE_SUB(NOW(),INTERVAL $age HOUR)");
      $cache_total = $wpdb->get_var("SELECT count(*) FROM $cache_table");
      $timestamp = wp_next_scheduled('amazon_link_refresh_cache');
      $cache_date = date( DATE_RFC822, wp_next_scheduled('amazon_link_refresh_cache') + (get_option('gmt_offset')*60*60));
      $options['info']['Value'] =
                       "<P>
                       Cache Refresh Scheduled for: $cache_date.</br>
                       Cache Items Expired: $cache_outofdate/$cache_total.</br>
                       Raw Times: Now=".microtime(true)." Scheduled=$timestamp
                       </p>";
   }   
  $al->form->displayForm($options,$opts);                                                                                                               

}

function alx_cache_show_entries_panel ($post, $args) {
   global $wpdb;

   $al = $args['args'];
   $cache_table = $wpdb->prefix . AmazonWishlist_For_WordPress::cache_table;
     
   $Action = (isset($_POST[ 'alx-cache-action' ]) && check_admin_referer( 'alx-cache-manage' )) ?
                      $_POST[ 'alx-cache-action' ] : 'No Action';

   /************************************************************************************/
   /*
    * Process the actions selected by the User
    */


  if (($Action == __('Remove Entry','amazon-link')) ||
      ($Action == __('Refresh Entry','amazon-link'))) {
        
    /* Remove Entry from the Cache */
    if (!empty($_POST['cc']) && !empty($_POST['asin'])) {
      $cc = $_POST['cc'];
      $asin = $_POST['asin'];
      $sql = "DELETE FROM $cache_table WHERE `asin` = '$asin' AND `cc` = '$cc';";
      $wpdb->query($sql);
      $update = __('Entry for '. $asin .' in '. $cc . ' locale removed from Cache.','amazon-link');
    }
  }

 if (($Action == __('Refresh Entry','amazon-link')) ) {
   
    /* Lookup the item again */
    if (!empty($_POST['cc']) && !empty($_POST['asin'])) {
      $settings = $al->getOptions();
      $cc = $_POST['cc'];
      $asin = $_POST['asin'];
      $settings['localise'] = 0;
      $settings['default_cc'] = $cc;
      $al->cached_query($asin, $settings);
      $update = __('Cache Data Refreshed for '. $asin. ' in '. $cc .' locale.','amazon-link');
    }
  }

   if (isset($update)) {
      // **********************************************************
      // Put an options updated message on the screen
      echo '<div class="updated"><p><strong>'. $update . '</strong></p></div>';
   }

  
   /************************************************************************************/
   /*
    * Display the cache entries
    */
   $settings = $al->get_default_settings();
   $al->footer_scripts();

   if (!empty($settings['cache_enabled'])) {
     $sql = "SELECT *, (updated < DATE_SUB(NOW(), INTERVAL ". $settings['cache_max_age'] . " HOUR)) AS expired FROM $cache_table order by `asin` ASC";
     $results = $wpdb->get_results($sql, ARRAY_A);
     foreach ($results as $result) {
        $data = unserialize($result['xml']);
        $data['updated'] = $result['updated'];
        $data['expired'] = $result['expired'];
        $data['cc'] = $result['cc'];
        $data['asin'] = $result['asin'];
        alx_cache_display_row($data,$al);
     }
   }
}

function alx_cache_enable_cron($al) {
   $settings = $al->get_default_settings();
   if (!empty($settings['cache_enabled']) && empty($settings['cache_cron'])) {
      wp_schedule_event( time ( ), 'hourly', 'amazon_link_refresh_cache' );
      return true;
   }
   return false;
}
   
function alx_cache_disable_cron($al) {
   $settings = $al->getSettings();
   if (!empty($settings['cache_enabled']) && !empty($settings['cache_cron'])) {
      wp_clear_scheduled_hook('amazon_link_refresh_cache');
      return true;
   }
   return false;
}
   
function alx_cache_refresh_entries() {
   global $awlfw, $wpdb;
   $settings = $awlfw->get_default_settings();
   $items = $settings['cache_refresh_number'];
   $age = $settings['cache_max_age'];
   $cache_table = $wpdb->prefix . AmazonWishlist_For_WordPress::cache_table;
   $sql = "SELECT asin,cc FROM $cache_table WHERE updated < DATE_SUB(NOW(),INTERVAL $age HOUR) ORDER BY RAND() LIMIT $items";
   $items = $wpdb->get_results($sql, ARRAY_A);
   $settings['localise'] = 0;
   foreach ($items as $item) {
      //echo "<PRE>";   print_r($item); echo "</PRE>";
      $settings['default_cc'] = $item['cc'];
      $sql = "DELETE FROM $cache_table WHERE asin LIKE '".$item['asin']."' AND cc LIKE '".$item['cc']."'";
      $wpdb->query($sql);
      $awlfw->cached_query($item['asin'], $settings);
   }
}
   
function alx_cache_display_row($data, $al) {
  
   $country = $al->get_country_data();
   $settings = $al->get_default_settings();
   
   $options = array(
         'nonce'       => array ( 'Type' => 'nonce', 'Value' => 'alx-cache-manage' ),
         'asin'        => array ( 'Type' => 'hidden'),
         'cc'          => array ( 'Type' => 'hidden'),
         'details'     => array ( 'Type' => 'title', 'Name' => 'Item Details',
                                  'Buttons' => array ( __('Remove Entry', 'amazon-link') => array( 'Action' => 'alx-cache-action', 'Hint' => __( 'Remove this entry from the cache', 'amazon-link'), 'Class' => 'button-secondary'),
                                                       __('Refresh Entry', 'amazon-link') => array( 'Action' => 'alx-cache-action', 'Hint' => __( 'Fetch latest data from Amazon for this entry.', 'amazon-link'), 'Class' => 'button-secondary')
                                                      )
                                 )
     );
   
   if (!empty($data['expired'])) {
      $expired = '<img style="height:1em" src="'. $al->URLRoot. '/images/expired.png" title="Expired">';
   } else {
      $expired = '';
   }
   if ( empty($data['found'])) {
      $expired .= '&nbsp;&nbsp;<a href="#" title="Not Found"><b>!</b></A>';
   }
  
   
   $id = rand();
   
   $raw_details="<div style=\'border:1px solid; background:white; width:600px\'>";
   foreach ($data as $key => $item) {
      $item = '<pre style="float:left;position:absolute;left:100px;display:inline;width:500px;">' .str_ireplace(array( "'"), array("\'"), trim(print_r($item,true))) . '</pre>';
      $item =  '<div style="postion:relative"><b><pre style="float:left;position:absolute;left:0px;display:inline;">'. addslashes($key).':</pre></b>' .$item. '</div></br style="clear:both"></br>';
      $item = str_ireplace(array( '"', "'", "\r", "\n"), array('&#34;', '&#39;','&#13;',''), print_r($item, true));
      $raw_details .= $item;
      //addslashes($key)." : " . str_replace(array('\'','"'),array('Q','Q'),(print_r($item,true)) ) . " </br>"; //,ENT_QUOTES
   }
   $raw_details .= "</div>";
      
   $popup = " onmouseout=\"al_link_out();\" onmouseover=\"al_link_in('$id','$raw_details');\"";
   
   $options['details']['Value'] = '<div style="font-size:small"'.$popup.'>'.
                      '<div style="float:left;width:90px">'.$data['asin'] . '</div>' .
                      '<div style="float:left;width:60px"><img style="height:1em" src="' . $country[$data['cc']]['flag'] . '" title="' . $country[$data['cc']]['country_name'] .'">' .$expired.
                      '</div>' .
                      '<div style="float:left;width:150px"> ' . $data['updated'] . ' </div>' .
                      '<div style="float:left;width:250px"> '. implode((array)$data['artist'],', '). ':' . $data['title'] . ' : ' . $data['price'] .'</div>'.
                      '</div>';
  
   $al->form->displayForm($options,$data);                                                                                                               
                                                                                                                        
}
   
/*
 * Add the Cache Admin Menu
 */
function alx_cache_menus ($menu, $al) {

   $menu['amazon-link-cache'] = array( 'Slug' => 'amazon-link-cache',
//                                     'Help' => 'help/cache.php',
                                       'Icon' => 'tools',
                                       'Description' => __('On this page you can manage individual items in the Amazon Link Product Cache.', 'amazon-link'),
                                       'Title' => __('Manage Amazon Link Cache', 'amazon-link'), 
                                       'Label' => __('Cache', 'amazon-link'), 
                                       'Capability' => 'manage_options',
                                       'Scripts' => array( array($al,'edit_scripts')),
                                       'Metaboxes' => array( 'al-cache-entries' => array( 'Title' => __( 'Cache Entries', 'amazon-link' ),
                                                                                  'Callback' => 'alx_cache_show_entries_panel', 
                                                                                  'Context' => 'normal',
                                                                                  'Priority' => 'core'),
                                                            'al-cache-settings' => array( 'Title' => __( 'Cache Settings', 'amazon-link' ),
                                                                                  'Callback' => 'alx_cache_show_settings_panel',
                                                                                  'Context' => 'normal',
                                                                                  'Priority' => 'high'))
                                        );
   return $menu;
}

/*
 * Add the Cache options to the Amazon Link Settings Page
 */
function alx_cache_option_list ($options_list) {
   $options_list['cache_cron'] = array ('Type' => 'backend',
                                       'Default' => '0');
   $options_list['cache_refresh_number'] = array ( 'Name' => __('Cache Refresh Amount', 'amazon-link'),
                                       'Description' => __('Maximum number of items in the cache that should be updated when the cache is refreshed.', 'amazon-link'),
                                       'Type' => 'text',
                                       'Default' => '50',
                                       'Class' => 'al_border');
   $options_list['cache_max_age'] = array ( 'Name' => __('Age of Items to Refresh', 'amazon-link'),
                                       'Description' => __('How old do the items in the cache need to be before being refreshed.', 'amazon-link'),
                                       'Type' => 'text',
                                       'Default' => '48',
                                       'Class' => 'al_border');
   $options_list['stale_tracker'] = array ( 'Name' => __('Stale Item Removal', 'amazon-link'),
                                       'Description' => __('Enabling this option will cause the plugin to remove items that are not viewed.', 'amazon-link'),
                                       'Type' => 'checkbox',
                                       'Default' => '1',
                                       'Class' => 'al_border');
   $options_list['stale_max_age'] = array ( 'Name' => __('Stale Item Age', 'amazon-link'),
                                       'Description' => __('How old do the items in the cache need to be before they are deleted.', 'amazon-link'),
                                       'Type' => 'text',
                                       'Default' => '240',
                                       'Class' => 'al_border');


   return $options_list;
}   
   
/*
 * Install the Cache Management Page
 *
 * Modifies the following Functions:
 *  - Add a new Admin Menu page that provides the Cache Management Options (alx_cache_menus)
 */
   add_action( 'amazon_link_pre_init', 'alx_cache_install' );
   function alx_cache_install() {
      add_filter('amazon_link_admin_menus', 'alx_cache_menus',12,2);
      add_filter('amazon_link_option_list', 'alx_cache_option_list',12,2);
      add_action('amazon_link_refresh_cache', 'alx_cache_refresh_entries');
   }
   
?>
