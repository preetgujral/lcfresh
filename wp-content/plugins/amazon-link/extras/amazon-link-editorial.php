<?php

/*
Plugin Name: Amazon Link Extra - Editorial Content
Plugin URI: http://www.houseindorset.co.uk/
Description: Update the Amazon Link plugin to return 'Editorial Content' (this can be a lot of data and may have a performance impact on your site)
Version: 1.3.2
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
 * Utility function to create some html based on an associative array.
 */
function alx_merge_items($items, $elements, $preserve_duplicates = False) {
   if (!isset($items[0])) {
      $items = array('0' => $items);
   }
   $unique_items = array();
   $primary = key($elements);
   $result = '';
   foreach ($items as $item) {
      if (isset($item[$primary]) && ($preserve_duplicates || !in_array($item[$primary], $unique_items))) {
         $unique_items[] = $item[$primary];
         foreach($elements as $element => $info) {
            $result .= $info['Pre'] . $item[$element] . $info['Post'];
         }
      }
   }
   return $result;
}

/*
 * Filter to process the raw editorial content AWS data
 */
function alx_process_editorial ($editorial) {

   /* Only process if it is an array, if it isn't then it probably has already been filtered. */
   if (is_array($editorial)) {
      return alx_merge_items($editorial, array('Source' => array('Pre' => '<div class="al_editorial_source">', 
                                                                 'Post' => '</div>'), 
                                               'Content' => array('Pre' => '<div class="al_editorial_content">', 
                                                                  'Post' =>'</div>')));
   } else {
      return $editorial;
   }
}


/*
 * Filter to add the 'Editorial' keyword and attach a filter to it to grab the content
 */
function alx_add_editorial ($keywords) {

   $keywords['editorial'] = array( 'Description' => __('Editorial Reviews (non-copyrighted only)', 'amazon-link'),
                                   'Group' => 'EditorialReview', 
                                   'Callback' => 'alx_process_editorial',
                                   'Default' => '-',
                                   'Live' => '1',
                                   'Position' => array(array('EditorialReviews','EditorialReview')) );

   return $keywords;
}

/*
 * Filter to add extra default templates
 */
function alx_add_editorial_templates ($templates) {

   $templates['editorial'] = array( 'Name' => __('Editorial','amazon-link'),
                                    'Description' => __('Editorial Reviews (non-copyrighted only)', 'amazon-link'),
                                    'Content' => htmlspecialchars('<div class="al_found%FOUND%">%EDITORIAL%US#</div>'),
                                    'Version' => '1',
                                    'Notice' => 'New Template',
                                    'Type' => 'Product',
                                    'Preview_Off' => 0 );

   return $templates;
}


/*
 * Install the editorial content keyword, data filters and template
 */
   add_action( 'amazon_link_pre_init', 'alx_editorial_install' );
   function alx_editorial_install() {
      add_filter('amazon_link_keywords', 'alx_add_editorial');
      add_filter('amazon_link_default_templates', 'alx_add_editorial_templates');
   }
?>