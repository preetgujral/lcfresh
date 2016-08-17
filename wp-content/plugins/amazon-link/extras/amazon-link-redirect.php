<?php

/*
Plugin Name: Amazon Link Extra - Redirect
Plugin URI: http://www.houseindorset.co.uk/plugins/amazon-link/
Description: Adds the ability to redirect to any Amazon Link product using a URL of the format www.mydomain.com/go/<ASIN>/<LINK TYPE S,R or A>/<Domain ca,cn,de, etc.>/?args. Note if using these type of links it is recommended that you clearly indicate on your site that the link is to Amazon otherwise you might be in breach of the terms and conditions of your associates account.
Version: 1.2.5
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
 * The Redirect action function that is called when the Amazon Link plugin is Initialised
 */
function alx_redirect($settings, $al) {

   $url = NULL;
   $uri = $_SERVER['REQUEST_URI'];
   parse_url(home_url(),$url);
   $match = preg_match( '!'.$url['path'].'/'.$settings['redirect_word'].'(?:/(?P<asin>[A-Z0-9]{10})|/(?P<ref>[^/]{2,}))?(?:/(?P<type>A|S|R|B))?(?:/(?P<default_cc>mx|br|ca|cn|de|fr|in|it|es|jp|uk|us))?!', $uri, $args);
   if ( $match ) {
      $arg_position = strpos($uri,'?');
      $opts = array();
      if ($arg_position > 0) $opts['args'] = substr($uri,$arg_position+1);

      // Get all named args
      foreach ($args as $arg => $data) {
         if (!is_int($arg) && !empty($data)) $opts[$arg] = $data;
      }

      // Extract the Hard coded Type if set
      $type = !empty($opts['type']) ? $opts['type'] : '';

      unset($opts['type']);

      // If hard coded to a specific locale then disable localisation
      if (isset($opts['default_cc'])) {
         $asin = isset($opts['asin'])?$opts['asin']:'';
         unset($opts['asin']);
         $opts['asin'] = $asin;
         $opts['localise']=0;
      }

      $opts['template_content'] = '%'.$type.'URL%';

      $al->in_post=false;
      $al->post_ID=NULL;
      $url = $al->shortcode_expand($opts);
      $url_bits = explode('?', $url, 2);
      $url_bits[1] = str_replace(array('&amp;', ' ','|','\''), array('&', '+','%7c','%27'), $url_bits[1]);
      $url = $url_bits[0].'?'.$url_bits[1];
      if (!empty($url)) {
         //echo "<PRE>$url</PRE>";
         wp_redirect($url, '302');
         die();
      }
   }

   if ($settings['redirect_url']) {
      add_filter('amazon_link_multi_link_templates', 'alx_redirect_multi_link_templates',10,2);
   }
   
   if ($settings['redirect_shortcode']) {
      add_filter('amazon_link_regex', 'alx_redirect_regex',10,2);
      add_filter('amazon_link_shortcode_template', 'alx_redirect_shortcode_template',10,2);
   }

}

function alx_redirect_multi_link_templates($templates, $al) {
   
   // TODO: Handle 'ref' & chan (does not expand in multi-popup)
   $settings = $al->getSettings();
   $templates['A'] = get_option('home'). '/'. $settings['redirect_word']. '/%ARG%/%MANUAL_CC%';
   $templates['S'] = get_option('home'). '/'. $settings['redirect_word']. '/search/S/%MANUAL_CC%?search_text_s=%ARG%';
   $templates['R'] = get_option('home'). '/'. $settings['redirect_word']. '/%ARG%/R/%MANUAL_CC%';
   $templates['B'] = get_option('home'). '/'. $settings['redirect_word']. '/%ARG%/B/%MANUAL_CC%';
   return $templates;
}
   
/*
 * Create a redirection style URL - OPTIONAL!
 */
function alx_redirect_url($url, $type, $asin, $search, $local_info, $settings, $al) {

   $options = $al->getOptions();

   /* Work out which ASIN to use */
   if (!empty($settings['ref'])) {
      $asin = $settings['ref'].'/';
   } else if (!empty($asin[$local_info['cc']])) {
      // User Specified ASIN always use
      $asin = $asin[$local_info['cc']].'/';
   } else if ($settings['search_link'] && ($type == 'A') && !empty($asin[$settings['home_cc']])) {

      // User wants search links for non-local domains
      $type = 'S';
      $asin = $asin[$settings['home_cc']].'/';
   } else if (empty($asin[$settings['home_cc']]) && !empty($settings['url'])){
      return $settings['url'][$local_info['cc']];
   } else {
     
      // Try using the default cc ASIN
      $asin = $asin[$settings['home_cc']].'/';
   }

   // If search links are enabled then pass the search text as an argument
   if (($type == 'S') || ($settings['search_link'] && ($options['search_text'] != $search))) {
      $search = '?search_text='. $search;
   } else {
      $search = '';
   }

   // If not localised then force redirect function to send to specific locale.
   if (!$settings['localise']) {
      $cc = $local_info['cc'];
      // For country specific links that aren't search links don't need this
      if ($type != 'S') $search = '';
   } else {
      $cc = '';
   }

   $text= get_option('home'). '/'. $settings['redirect_word']. '/'. $asin . $type. '/' . $cc . $search;
   return $text;
}

/*
 * Change the Amazon Link Shortcode Template to create links of the form:
 * <a class="amazon-link" title="Title" href="/<redirect_word>/<asin>?shortcode">Text</a>
 */
function alx_redirect_shortcode_template ($template, $al) {
   $settings = $al->getSettings();
   return '<a class="amazon-link" title="%TITLE%" href="/'. 
          $settings['redirect_word'] .
          '/%ASIN%%REF%?%UNUSED_ARGS%">%TEXT%</a>';
}

/*
 * Change the Amazon Link Regex to find links of the form <a class="amazon-link" href="/<redirect_word>/<asin>|<ref>?args">Text</a>
 * 
 */
function alx_redirect_regex ($regex, $al) {
   $settings = $al->getSettings();
   return '!<a\sclass="(?U:.*)amazon-link(?U:.*)"'. // Must start with class element ignoring any other classes
          ' (?:(?U:.*)title="(?P<title>[^"]*)")?'.    // Optional Title
          ' (?:(?U:.*)href=".*/'. $settings['redirect_word'].
          '  (?:/(?P<asin>[A-Z0-9]{10})|/(?P<ref>[^/?]{2,}))'.
          '  (?:\?(?P<args>[^"]*) )"'.
          ' )?'. // optional href
          ' (?:[>]*)' .                             // ignore any further data inside the element
          ' > '.                                    // End of link tag
          ' (?P<text>.*)'.                           // optional text
          ' </a>!x';                                // close link tag
}

/*
 * Add Filter and Template to Import/Export Table
 * 
 */
function alx_redirect_impexp_expression ($expressions, $al) {

   $expressions['redirect'] = array ( 'Regex'       => alx_redirect_regex('',$al),
                                      'Name'        => __('Redirect Link', 'amazon-link'),
                                      'Description' => 'A Link Element using the redirection plugin of the form <a class="amazon-link" title="%TITLE%" href="/al/%ASIN%?%ARGS%>%TEXT%</a>.',
                                      'Template'    => alx_redirect_shortcode_template('', $al));
   return $expressions;
}

/*
 * Add the Redirect option to the Amazon Link Settings Page
 */
function alx_redirect_options ($options_list) {
   $options_list['redirect_word'] = array ( 'Name' => __('Redirect Word', 'amazon-link'),
                                            'Description' => __('The word that the redirect plugin looks for to indicate that it should try and link to Amazon (www.yourdomain.com/<REDIRECT WORD>/ASIN/?options)', 'amazon-link'),
                                            'Type' => 'text',
                                            'Default' => 'al',
                                            'Class' => 'al_border');
   $options_list['redirect_url'] = array ( 'Name' => __('Redirection Links', 'amazon-link'),
                                            'Description' => __('The links to Amazon displayed on your site are of the form &lta href="/&ltREDIRECT WORD>/ASIN/...".', 'amazon-link'),
                                            'Type' => 'checkbox',
                                            'Default' => '1',
                                            'Class' => 'al_border');
   $options_list['redirect_shortcode'] = array ( 'Name' => __('Link Style Shortcode', 'amazon-link'),
                                            'Description' => __('Amazon Links in Posts are of the form &lta href="/&ltREDIRECT WORD>/ASIN/...".', 'amazon-link'),
                                            'Type' => 'checkbox',
                                            'Default' => '0',
                                            'Class' => 'al_border');
   return $options_list;
}

/*
 * Install the Redirect option, filter and action
 *
 * Modifies the following Functions:
 *  - On Init checks to see if the URI is a redirect link - if it is then redirect to Amazon (redirect)
 *    - [Optionally: When dynamically creating links on the Page generate them in the form <a href="/al/ASIN/..."> (redirect_url) ]
 *    - [Optionally: When processing the Content search for <a class="amazon-link" href="/al/ASIN/..."> to replace with amazon-link templates (redirect_regex)]
 *    - [Optionally: On creating links using the Post/Page Edit helper, create links of the from <a class="amazon-link" href="/al/ASIN/..."> (shortcode_template)]
 *  - Add three extra options to control the redirect links (redirect_options)
 */
add_action('amazon_link_init', 'alx_redirect',12,2);
add_filter('amazon_link_option_list', 'alx_redirect_options');
add_filter('amazon_link_impexp_expressions', 'alx_redirect_impexp_expression',10,2);

?>
