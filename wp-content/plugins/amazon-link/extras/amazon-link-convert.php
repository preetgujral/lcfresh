<?php

/*
Plugin Name: Amazon Link Extra - Convert
Plugin URI: http://www.houseindorset.co.uk/plugins/amazon-link/
Description: <strong>!!!BETA!!!</strong> This plugin adds the ability to search for Amazon Link shortcodes and convert them into static content or links of a different format and vice versa.</br>
Version: 1.8
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

function alx_convert_show_panel () {
   global $awlfw, $alx_convert;


   $settings = $awlfw->getSettings();

   /************************************************************************************/
   /*
    * Possible Search and Replace Patterns
    */
   $expressions = array( 'standard'    => array ( 'Regex'       => '~\[amazon\s+(?P<args>(?:(?>[^\[\]]*)(?:\[(?>[a-z]*)\])?)*)\]~sx',
                                                  'Name'        => __('Standard Shortcode', 'amazon-link'),
                                                  'Description' => 'The original Amazon Link shortcode of the form [amazon arg=xxx].',
                                                  'Template'    => '[amazon %ARGS%]'),
                         'static'      => array ( 'Name'        => __('Static Content', 'amazon-link'),
                                                  'Description' => 'Expand all shortcodes into static content based on the default locale. Note this option cannot be reversed!',
                                                  'Template'    => '%STATIC%'),
                         'remove'      => array ( 'Name'        => __('Remove Shortcodes', 'amazon-link'),
                                                  'Description' => 'Remove all shortcodes',
                                                  'Template'    => ''),
                         'hide'        => array ( 'Regex'       => '/<!--amazon-link-open (?P<args>(?:(?>[^\[\]]*)(?:\[(?>[a-z]*)\])?)*)-->(?U:.*)<!--amazon-link-close-->/',
                                                  'Name'        => __('Hidden Shortcodes', 'amazon-link'),
                                                  'Description' => 'Links are represented by hidden html elements of the form <!--amazon-link-open: %ARGS%-->...<!--amazon-link-close-->',
                                                  'Template'    => '<!--amazon-link-open: %ARGS%--><!--amazon-link-close-->'),
                         'static-hide' => array ( 'Name'        => __('Static + Hidden Shortcodes', 'amazon-link'),
                                                  'Description' => 'Links are expanded into static content and enclosed with hidden html elements of the form <!--amazon-link-open: %ARGS%-->Expanded Static Link<!--amazon-link-close-->',
                                                  'Template'    => '<!--amazon-link-open: %ARGS%-->%STATIC%<!--amazon-link-close-->'),
                         'amazon'      => array ( 'Regex'       => '!<a(?U:.*)href="(?U:[^"]*)www.amazon.(?:com|co.uk|ca|fr|jp|de|es)/(?U:[^/?]*[/?])*?(?P<asin>[0-9A-Z]{10})(?U:[/?][^"/?]*)*?"(?U:.*)>(?P<text>.*?)</a>!',
                                                  'Name'        => __('Amazon Link', 'amazon-link'),
                                                  'Description' => 'Standard Amazon Product Links of the form <a ... href="www.amazon.%TLD%/.../%ASIN%/" ... >%TEXT%</a>'
                                                 )
                         );
   $expressions = apply_filters ('amazon_link_convert_expressions',$expressions, $awlfw);

   /************************************************************************************/
   /*
    * Options for the Search And Replace Tasks
    */
   $options = array( 
         'nonce'       => array ( 'Type' => 'nonce', 'Value' => 'convert-AmazonLink-filter' ),
         'page'        => array ( 'Type' => 'hidden'),

         'Filter'      => array ( 'Type' => 'selection', 'Name' => __('Search Filter', 'amazon-link'), 'Description' => __('The items to search for in the Post / Page content', 'amazon-link')),
         'Replace'     => array ( 'Type' => 'selection', 'Name' => __('Replacement Template', 'amazon-link'), 'Description' => __('What to replace the items with in the Post / Page content', 'amazon-link')),
         'Raw'         => array ( 'Type' => 'checkbox', 'Name' => __('Show raw HTML', 'amazon-link'), 'Description' => __('When testing the replacement, output the raw HTML code.', 'amazon-link'), 'Default' => '1'),
         'Query'       => array ( 'Type' => 'text', 'Name' => __('Post Query', 'amazon-link'), 'Description' => __('What arguments to use when searching for posts/pages to apply the filter to, as per <a href="http://codex.wordpress.org/Class_Reference/WP_Query#Parameters">WP_Query</a>', 'amazon-link'), 'Default' => 'post_type=any&posts_per_page=-1'),
         'Buttons'     => array ( 'Type' => 'buttons', 'Buttons' => 
                                           array ( __('Find', 'amazon-link') => array( 'Action' => 'AmazonLinkAction', 'Hint' => __( 'Search for the Input Filter items in all posts and pages.', 'amazon-link'), 'Class' => 'button-secondary'),
                                                   __('Test', 'amazon-link') => array( 'Action' => 'AmazonLinkAction', 'Hint' => __( 'Perform a dry run Search and Replace and Preview the results.', 'amazon-link'), 'Class' => 'button-secondary'),
                                                   __('Replace', 'amazon-link') => array( 'Action' => 'AmazonLinkAction', 'Hint' => __( 'Perform the search and replace writing the content back to the posts.', 'amazon-link'), 'Class' => 'button-secondary')
                                )),

         );

   /*
    * Populate the selection drop downs with the expressions available
    */
   foreach ($expressions as $id => $data) {
      if (isset($data['Regex'])) {
         $options['Filter']['Options'][$id] = array( 'Name' => $data['Name'], 'Hint' => htmlspecialchars($data['Description']));
      }
      if (isset($data['Template'])) {
         $options['Replace']['Options'][$id] = array( 'Name' => $data['Name'], 'Hint' => htmlspecialchars($data['Description']));
      }
   }

   /************************************************************************************/
   /*
    * Process the options selected by the User
    */
   $Action = (isset($_POST[ 'AmazonLinkAction' ]) && check_admin_referer( 'convert-AmazonLink-filter' )) ?
                      $_POST[ 'AmazonLinkAction' ] : 'No Action';

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
    * Display the options form first
    */
   $awlfw->form->displayForm($options , $opts);


   /************************************************************************************/
   /*
    * Now process the actions
    */
   if (($Action == __('Find','amazon-link')) && (isset($opts['Filter']))) {

      /*
       * FIND
       */

      $Filter = isset($expressions[$opts['Filter']]) ? $expressions[$opts['Filter']] : $expressions['standard'];

      $lastposts = get_posts($opts['Query']);
      echo '<TABLE class="widefat">';
      echo '<THEAD><TR><TH>Post</TH><TH>Count</TH><TH>Matching Text</TH><TH>Shortcode Arguments</TH><TH>Text</TH><TH>ASIN</TH></TR></THEAD><TBODY>';
      foreach ($lastposts as $id => $post) {
         $regex = $Filter['Regex'];
         $count = preg_match_all( $regex, $post->post_content, $matches, PREG_SET_ORDER);
         foreach ($matches as $index => $match) {
            echo "<TR><TD><a href='". get_edit_post_link( $post->ID)."'>".$post->ID."</a></TD><TD>$index</TD><TD>".htmlspecialchars($match[0])."</TD><TD>".htmlspecialchars(isset($match['args'])?$match['args']:'')."</TD><TD>".htmlspecialchars(isset($match['text'])?$match['text']:'')."</TD><TD>".htmlspecialchars(isset($match['asin'])?$match['asin']:'')."</TD></TR>";
         }
      }
      echo "</TBODY></TABLE>";      

   } else if (($Action == __('Test','amazon-link')) && (isset($opts['Filter']))) {

      /*
       * TEST
       */

      $Filter  = isset($expressions[$opts['Filter']]) ? $expressions[$opts['Filter']] : $expressions['standard'];
      $Replace = isset($expressions[$opts['Replace']]) ? $expressions[$opts['Replace']] : $expressions['standard'];
      $awlfw->get_keywords();
      $awlfw->keywords['unused_args'] = array( 'Calculated' => 1 );
      $awlfw->keywords['args'] = array( 'Calculated' => 1 );
      $awlfw->keywords['static'] = array( 'Calculated' => 1 );
      $alx_convert['Template'] = $Replace['Template'];

      $lastposts = get_posts($opts['Query']);
      echo '<TABLE class="widefat">';
      echo '<THEAD><TR><TH>Post</TH><TH>Count</TH><TH>Matching Text</TH><TH>Replacement</TH></TR></THEAD><TBODY>';
      foreach ($lastposts as $id => $post) {
         $regex = $Filter['Regex'];
         $count = preg_match_all( $regex, $post->post_content, $matches, PREG_SET_ORDER);
         foreach ($matches as $index => $match) {
            $alx_convert['Count'] = 0;
            $output = alx_convert_do_shortcode($match);
            if ($opts['Raw']) $output = htmlspecialchars($output);
            echo "<TR><TD><a href='". get_edit_post_link( $post->ID)."'>".$post->ID."</a></TD><TD>$index</TD><TD>".htmlspecialchars($match[0])."</TD><TD>".($output)."</TD></TR>";
         }
      }
      echo "</TBODY></TABLE>";      
   } else if (($Action == __('Replace','amazon-link')) && (isset($opts['Filter']))) {

      /*
       * REPLACE
       */

      $Filter  = isset($expressions[$opts['Filter']]) ? $expressions[$opts['Filter']] : $expressions['standard'];
      $Replace = isset($expressions[$opts['Replace']]) ? $expressions[$opts['Replace']] : $expressions['standard'];
      $awlfw->get_keywords();
      $awlfw->keywords['unused_args'] = array( 'Calculated' => 1 );
      $awlfw->keywords['args'] = array( 'Calculated' => 1 );
      $awlfw->keywords['static'] = array( 'Calculated' => 1 );
      $alx_convert['Template'] = $Replace['Template'];

      $lastposts = get_posts($opts['Query']);
      echo '<TABLE class="widefat">';
      echo '<THEAD><TR><TH>Post</TH><TH>Count</TH></TR></THEAD><TBODY>';
      foreach ($lastposts as $id => $post) {
         $regex = $Filter['Regex'];
         $alx_convert['Count'] = 0;
         $content = preg_replace_callback( $regex, 'alx_convert_do_shortcode', $post->post_content);
         if ($alx_convert['Count']) {
            $my_post = array();
            $my_post['ID'] = $post->ID;
            $my_post['post_content'] = $content;
            wp_update_post( $my_post );
         }
         echo "<TR><TD><a href='". get_edit_post_link( $post->ID)."'>".$post->ID."</a></TD><TD>".$alx_convert['Count']."</TD></TR>";
      }
      echo "</TBODY></TABLE>";      
   }
}

/*
 * Replace the shortcode with the user selected Template
 */
function alx_convert_do_shortcode($match) {
   global $awlfw, $alx_convert;

   $extra_args  = !empty($match['args']) ? $match['args'] : '';
   unset ($match['args']);
   $args = $sep ='';
   foreach ($match as $arg => $data) {
      if (!is_int($arg) && !empty($data)) {
         $args .= $sep. $arg .'='. str_replace('&','%26',$data);
         $sep = '&';
      }
   }
   $args .= $extra_args;

   $sc = array( 'args' => $args );
   $awlfw->in_post = False;
   $awlfw->post_ID = '0';
   $static = $awlfw->shortcode_expand( $sc );

   $settings = $awlfw->parse_shortcode ( $sc );

   $settings[$settings['local_cc']]['static'] = $static;
   $settings[$settings['local_cc']]['args'] = $args;
   $settings[$settings['local_cc']]['unused_args'] = $args;

   $settings[$settings['local_cc']]['template_content'] = $alx_convert['Template'];
   $alx_convert['Count']++;

   return preg_replace( '![\s]+!', ' ',$awlfw->make_links($settings));
}

/*
 * Add the Link Convert Menu
 */
function alx_convert_menus ($menu, $al) {

   $menu['amazon-link-convert'] = array( 'Slug' => 'amazon-link-convert',
//                                        'Help' => 'help/convert.php',
                                         'Icon' => 'tools',
                                         'Description' => __('On this page you can search and replace the existing shortcodes with shortcodes of a different format or replace with static content. <br>
                                                             <div class="updated">WARNING: I have not fully tested this plugin, it may destroy <em>ALL</em> your posts, please backup your database before using!!!</div>', 'amazon-link'),
                                         'Title' => __('Manage Amazon Link Shortcodes', 'amazon-link'), 
                                         'Label' => __('Convert Links', 'amazon-link'), 
                                         'Capability' => 'manage_options',
                                         'Metaboxes' => array( 'al-convert' => array( 'Title' => __( 'Convert Links', 'amazon-link' ),
                                                                                    'Callback' => 'alx_convert_show_panel', 
                                                                                    'Context' => 'normal',
                                                                                    'Priority' => 'core'))
                                        );
   return $menu;
}

/*
 * Install the Amazon Link Convert Settings Page
 *
 * Modifies the following Functions:
 *  - Add a new Admin Menu page that provides the convert links facility (alx_convert_menus)
 */
   add_action('amazon_link_pre_init', 'alx_convert_install');
   function alx_convert_install () {
      add_filter('amazon_link_admin_menus', 'alx_convert_menus',12,2);
   }
?>