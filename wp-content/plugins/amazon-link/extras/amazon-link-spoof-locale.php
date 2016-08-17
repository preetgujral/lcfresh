<?php

/*
Plugin Name: Amazon Link Extra - Spoof Locale
Plugin URI: http://www.houseindorset.co.uk/plugins/amazon-link/
Description: Used for testing the appearance of your site when viewed from another country (needs ip2nation installed & localise Amazon Link option enabled to have any affect).  To change the locale, update the 'Spoof Locale' option in the Amazon Link settings page, or append `?spoof_locale=<country code>` to the page URL. You must Deactivate/Un-install this plugin to disable the spoofing.
Version: 1.2
Author: Paul Stuttard
Author URI: http://www.houseindorset.co.uk
*/

/*
Copyright 2011-2012 Paul Stuttard

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
 * Add the Spoof Locale option to the Amazon Link Settings Page
 */
function alx_spoof_locale_options ($options_list) {
   
   if (is_admin()) {
      $options_list['spoof_locale'] = array ( 'Name' => __('Spoof Locale', 'amazon-link'),
                                              'Description' => __('Force the localisation to this country code, for testing how your site will appear from another country.', 'amazon-link'),
                                              'Type' => 'selection', 
                                              'Default' => '', 
                                              'Class' => 'al_border');


      $options_list['spoof_locale']['Options'] = array_merge(array('' => array('Name' => 'Disabled')),$options_list['default_cc']['Options']);
   } else {
      $option_list['spoof_locale'] = array('Default' => '');
   }
  
   return $options_list;
}

/*
 * The Spoof Locale action function that is called when the Amazon Link plugin is Initialised
 */
function alx_spoof_locale ($s, $al) {

   global $wpdb, $_SERVER, $_REQUEST;
   $db = 'ip2nation';

   $settings = $al->get_default_settings();

   // Check Database is installed
   $sql = "SHOW TABLE STATUS WHERE Name LIKE '". $db ."'";
   $db_info = $wpdb->get_row($sql);

   // Grab an IP address for the country the user wants to spoof and set the global REMOTE_ADDR
   if ($db_info != NULL) {
      $locale = (isset($_REQUEST['spoof_locale']) ? $_REQUEST['spoof_locale'] : $settings['spoof_locale']);
      if ($locale != '') {
         $sql = 'SELECT ip FROM ' . $db .' WHERE country LIKE "'. $locale .'" LIMIT 0,1';
         $_SERVER['REMOTE_ADDR'] = long2ip($wpdb->get_var($sql)+1);
      }
   }
}

/*
 * Install the Spoof Locale option and action
 */
add_filter('amazon_link_option_list', 'alx_spoof_locale_options', 10,1);
add_action('amazon_link_init', 'alx_spoof_locale',10,2);
?>