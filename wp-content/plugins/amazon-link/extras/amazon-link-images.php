<?php

/*
Plugin Name: Amazon Link Extra - Images
Plugin URI: http://www.houseindorset.co.uk/
Description: Update the Amazon Link plugin to improve the processing of Images, allows setting the Image and Thumbnail size per shortcode as well as grabbing all possible images from the Amazon site.
Version: 1.6
Author: Paul Stuttard
Author URI: http://www.houseindorset.co.uk
*/

/*
Copyright 2014-2015 Paul Stuttard

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
 * Filter to show thumbs/images in an array
 */
function alx_images_display_images( $images, $keyword, $country, $data, $settings, $al ) {

   // If already populated then skip
   if ( ! empty( $images ) ) return $images;

   /* Grab the Image/Thumb array limiting to the configured maximum */
   if ( $keyword == 'thumbs' ) {
      $key = '%THUMB%';
      $images = min( count( (array)$data[$country]['thumb'] ),$settings['max_images'] );
   } else {
      $key = '%IMAGE%';
      $images = min( count( (array)$data[$country]['image'] ),$settings['max_images'] );
   }

   $input  = $settings[$keyword.'_template'];
   $output='';
   for ( $index=0; $index < $images; $index++ ) {
      /*
       * Put each image into our custom HTML, we can use any of the usual template keywords here.
       */
      $output .= str_replace( array( '%IMAGE%', '%THUMB%', '%IMAGE_INDEX%' ), 
                              array( $data[$country]['image'][$index],
                                     $data[$country]['thumb'][$index], 
                                     $index+1 ), 
                              $input );
   }

   return $output;
}

/*
 * Filter to set the image/thumb URL if none available
 */
function alx_images_set_no_image ( $images, $keyword, $country, $data, $settings, $al ) {
   
   if ( empty($images) || ($images == '-') ) {
      return $settings['no_image'];
   } else {
      return $images;
   }
}
   
/*
 * Filter to process the image array
 */
function alx_images_process_images ( $images, $keyword_info, $al ) {

   if ( ! is_array( $images ) ) return $images;

   $keyword = '%'. strtoupper( $keyword_info['Keyword'] ). '_SIZE%';

   $data = array();
   if ( ! empty( $images['ImageSet'] ) ) {
      $images = isset( $images['ImageSet'][0] ) ? $images['ImageSet'] : array( $images['ImageSet'] );
      foreach ( $images as $index => $image ) {

         $url = $image['SmallImage']['URL'];
      
         // URL of the form: 'http://ecx.images-amazon.com/images/I/518FFDVWNQL._SL160_.jpg
         $url = preg_replace( '!(http://(?:[^/]*/)+(?:[^.]*)).*$!', '\1.'.$keyword.'.jpg', $url );

         if (isset($image['@attributes']['Category']) && ($image['@attributes']['Category'] == 'primary')) {
            array_unshift($data, $url);
         } else {
            $data[] = $url;
         }
      }
   } else if ( ! empty( $images['URL'] ) ){
      $url = $images['URL'];
      
      // URL of the form: 'http://ecx.images-amazon.com/images/I/518FFDVWNQL._SL160_.jpg
      $data[] = preg_replace( '!(http://(?:[^/]*/)+(?:[^.]*)).*$!', '\1.'.$keyword.'.jpg', $url );

   }
   return array_values( array_unique( $data ) );
}


/*
 * Filter to change the thumb & image keyword and attach a filter to it to munge the URL
 */
function alx_images_keywords ($keywords) {

   $keywords['thumb']['Callback']          = 'alx_images_process_images';
   $keywords['thumb']['Position']          = array(array('ImageSets'), array('SmallImage'));

   $keywords['image']['Callback']          = 'alx_images_process_images';
   $keywords['image']['Position']          = array(array('ImageSets'), array('SmallImage'));

   $keywords['images'] = array( 'Description' => __('Images Array', 'amazon-link'),
                                'Live' => 1, // Force plugin to retrieve image & thumb(s)
                                'Default' => '' );
   $keywords['thumbs'] = array( 'Description' => __('Thumbs Array', 'amazon-link'),
                                'Live' => 1, // Force plugin to retrieve image(s) & thumb(s)
                                'Default' => '' );

   $keywords['image_size'] = array( 'Description' => __('Size of Images', 'amazon-link') );
   $keywords['thumb_size'] = array( 'Description' => __('Size of Thumbnails', 'amazon-link') );
   
   unset( $keywords['image']['Default'], $keywords['thumb']['Default'] );
   
   add_filter( 'amazon_link_template_process_thumbs', 'alx_images_display_images',11,6 );
   add_filter( 'amazon_link_template_process_images', 'alx_images_display_images',11,6 );

   add_filter( 'amazon_link_template_process_thumb', 'alx_images_set_no_image',11,6 );
   add_filter( 'amazon_link_template_process_image', 'alx_images_set_no_image',11,6 );

   return $keywords;
}

/*
 * Add the Image options to the Amazon Link Settings Page
 */
function alx_images_option_list ($options_list) {
   $options_list['no_image'] = array ( 'Name' => __('Default Image URL', 'amazon-link'),
                                       'Description' => __('The URL to the image to show if none is available.', 'amazon-link'),
                                       'Type' => 'text',
                                       'Default' => 'http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif',
                                       'Class' => 'al_border');
   $options_list['image_size'] = array ( 'Name' => __('Preferred Image Size Modifier', 'amazon-link'),
                                       'Description' => __('Retrieve the URL to an Image modified using this string, e.g. SL800 sets the width or height (the longest) of this size rather than the default \'Large\' Image.', 'amazon-link'),
                                       'Type' => 'text', 
                                       'Default' => 'SL800', 
                                       'Class' => 'al_border');
   $options_list['thumb_size'] = array ( 'Name' => __('Preferred Thumb Size Modifier', 'amazon-link'),
                                       'Description' => __('Retrieve the URL to an Thumbnail modified using this string, e.g. SL100 sets the width or height (the longest) of this size rather than the default \'Small\' Image.', 'amazon-link'),
                                       'Type' => 'text', 
                                       'Default' => 'SL100', 
                                       'Class' => 'al_border');
   $options_list['max_images'] = array ( 'Name' => __('Image Array Limit', 'amazon-link'),
                                       'Description' => __('Limit the number of images/thumbnails returned by the %IMAGES% or %THUMBS% keywords.', 'amazon-link'),
                                       'Type' => 'text', 
                                       'Default' => '5', 
                                       'Class' => 'al_border');
   $options_list['images_template'] = array ( 'Name' => __('Image Array Template', 'amazon-link'),
                                       'Description' => __('Template to use when processing %IMAGES% keyword.', 'amazon-link'),
                                       'Type' => 'textbox', 
                                       'Default' => '<div class="alignleft">%IMAGE_INDEX%: %LINK_OPEN%<img src="%IMAGE%">%LINK_CLOSE%</div>', 
                                       'Class' => 'al_border');
   $options_list['thumbs_template'] = array ( 'Name' => __('Thumb Array Template', 'amazon-link'),
                                       'Description' => __('Template to use when processing %THUMBS% keyword.', 'amazon-link'),
                                       'Type' => 'textbox', 
                                       'Default' => '<div class="alignleft">%IMAGE_INDEX%: %LINK_OPEN%<img src="%THUMB%">%LINK_CLOSE%</div>', 
                                       'Class' => 'al_border');


   return $options_list;
}

   /*
    * Install the image size keyword, data filter and options
    */
   add_action( 'amazon_link_pre_init', 'alx_images_install' );
   function alx_images_install() {
      add_filter( 'amazon_link_keywords', 'alx_images_keywords', 11, 1 );
      add_filter( 'amazon_link_option_list', 'alx_images_option_list', 11, 1 );
   }
?>
