<?php

/*
Plugin Name: Amazon Link Extra - Translate
Plugin URI: http://www.houseindorset.co.uk/
Description: Provides a WordPress filter 'translate' that uses the Bing Translate facility to translate text from one language to another.
Version: 1.1
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

/*****************************************************************************************/

/*
 * Simple BING Translate Class
 * 
 * To use set the 'id' using the 'set_id' method or by passing in via the 'init' method.
 * To translate text either:
 *   - Call direct via $alx_translate->translate('Text to be Translated', 'From Language', 'To Language')
 *   - Use the 'translate' filter: apply_filters('translate', 'Text to be Translated', 'From Language', 'To Language')
 *   - Use the AJAX 'action': 'amazon-link-translate' passing the arguments in an array ('Text => 'Text to be Translated', 'From' =>'Language', 'To' => 'Language')
 */

if (!class_exists('bing_translate')) {
   class bing_translate {

      function __construct() {
         $this->translate_url = "https://api.datamarket.azure.com/Data.ashx/Bing/MicrosoftTranslator/v1/Translate";
      }

      /*
       * Must be called by the client in its init function.
       */
      function init($settings, $parent) {

         if (is_admin()) {
            add_action('wp_ajax_amazon-link-translate', array($this, 'do_translate'));      // Handle ajax translate requests
         }
         add_filter('translate', array($this, 'translate'), 10, 3);                      // Add a filter to translate text
         $this->set_id($settings['windows_live_id']);
      }
/*****************************************************************************************/
      /// AJAX Call Handlers
/*****************************************************************************************/

      function do_translate() {
         $opts = $_POST;
         $translation = $this->translate($opts['Text'], $opts['From'], $opts['To']);
         print json_encode($translation);
         exit();
      }

/*****************************************************************************************/
      /// API Functions
/*****************************************************************************************/

      function set_id ($id) {
         $this->id = $id;
      }

      function translate ($text, $from, $to) {
         $parameters['From'] = "'".$from."'";
         $parameters['To'] = "'".$to."'";
         $parameters['Text'] = "'".$text."'";
         $parameters['$format']= 'Raw';
         $url = $this->generate_url($this->translate_url, $parameters);
         $headers = array( 'Authorization' => 'Basic ' . base64_encode($this->id. ':' . $this->id));
         $result = wp_remote_get( $url, array( 'headers'=> $headers));

         if ($result instanceof WP_Error )
            return __('Could not access Bing API: '.$url,'amazon-link');

         // extract the translation from the response
         $content = preg_replace('!<(?U:.*)>([^<]*)(<.*>)*!', '\1',$result['body']);

         return $content;
      }

      function generate_url($root, $parameters) {
         $args = array();
         foreach ( $parameters as $arg => $data) {
            $args[] = $arg .'='.urlencode($data);
         }
         $url = $root .'?'. implode('&', $args);
         return $url;
      }

      /*
       * Add the Translate option to the Amazon Link Settings Page
       */
      function options ($options_list) {
         $options_list['windows_live_id'] = array ( 'Name' => __('Translate Key', 'amazon-link'),
                                                    'Description' => __('Windows Live/Azure Secure Account Key that is subscribed to the Microsoft Translator Service.', 'amazon-link'),
                                                    'Type' => 'text',
                                                    'Default' => '',
                                                    'Class' => 'al_border');
         return $options_list;
      }

   }

   /*
    * Instantiate and Install the Translation Functionality into the Main Plugin
    *  - Add Hook to Initialisation to install the translate hooks and grab the Windows Live Key from the main Plugin settings ('init')
    *  - Add the Window Live ID Option to the main plugin options screen ('options').
    */
   $alx_translate = new bing_translate;
   add_action('amazon_link_init', array($alx_translate, 'init'), 10,2);
   add_filter('amazon_link_option_list', array($alx_translate, 'options'), 10,1);

}
?>