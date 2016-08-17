<?php

/*
Plugin Name: Amazon Link
Plugin URI: http://www.houseindorset.co.uk/plugins/amazon-link
Description: A plugin that provides a facility to insert Amazon product links directly into your site's Pages, Posts, Widgets and Templates.
Version: 3.2.6
Text Domain: amazon-link
Author: Paul Stuttard
Author URI: http://www.houseindorset.co.uk
License: GPL2
*/

/*
Copyright 2013-2014 Paul Stuttard (email : wordpress_amazonlink@ redtom.co.uk)

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
Usage:
  Add one of the following lines into an entry:

  [amazon asin=<ASIN Number>&text=<link text>]           --> inserts link to amazon item
  [amazon cat=<Category List>&last=<Number of Posts>]    --> inserts table of random items

Layout:

   amazon_container      - Encloses whole wishlist.
   amazon_prod           - Encloses each list item.
   amazon_img_container  - Encloses the item thumbnail (link+img)
   amazon_pic            - Class of the item thumbnail IMG element
   amazon_text_container - Encloses the item description (Title paragraphs+link + Details paragraphs)
   amazon_details        - Encloses the item details part of the description
   amazon_price          - Spans the item's formatted price.

*/

/*******************************************************************************************************

To serve a page containing amazon links the plugin performs the following:

* Queue the Amazon javascript and styles

* Search through the content and widget text for Amazon links, for each one:
   * Parse arguments:
      - get_default_settings (>cached),
        - get_options_list (>cached), 
      - get_country_data (>cached), 
      - get country [ip2n lookup (>cached)]
   
   * Make Links:
    * get_templates (>cached)
    * for each ASIN:
      * parse template:
      * (if live) perform [DB cached] itemLookup & process data returned
      * Fill in template
        * Keyword specific actions for:
           - links, 
           - urls, 
           - tags & channels,
           - [optionally] Check for local images

* If 'multinational' link found when doing the above then:
    * Return all channels and user channels(>cached), create the javascript for the multinational popup.

   TODO: Rationalise encoding / decoding
    * Can we prevent WP from encoding?
      - Can't guarantee that (esp visual editor) chars won't be &xxx; encoded
    * Can we pre-encode data?
      - in cached_query => do the " ' \r \n encoding? NO -> as & converted to %26 (do lone & encode as per WP?)
      - in parse arguments also do the "'\r\n encoding?
   
 Item Encoding:
    Content =>
     WordPress content filters:
        & => &amp; 
        '" => smart quotes
     parse_shortcode:
        ALL => html_entity_decode( $args, ENT_QUOTES, 'UTF-8' );
               > this turns &amp; back into & and ensures all other &xx; chars are converted back
                      
        each args !template_content => trim( urldecode( $data ), "\x22\x27 \t\n\r\0\x0B" ); 
                                       > this strips ws & '" and converts any %xx char back.

     cached_query:
        ALL => raw no filters
   
     parse_template_callback:
        escape => ' and & converted to \' and %26
        'Live' =>  '"', "'", "\r", "\n" converted to '&#34;', '&#39;','&#13;','&#10;'


*******************************************************************************************************/

   //include ('include/ip2location.php');
include ('include/ip2nation.php');

if (!class_exists('AmazonWishlist_For_WordPress')) {
   class AmazonWishlist_For_WordPress {

      /*****************************************************************************************/
      // Plugin Constants

      const cache_table      = 'amazon_link_cache';
      const sc_cache_table   = 'amazon_link_sc_cache';
      const refs_table       = 'amazon_link_refs';
      const optionName       = 'AmazonLinkOptions';
      const user_options     = 'amazonlinkoptions';
      const templatesName    = 'AmazonLinkTemplates';
      const channels_name    = 'AmazonLinkChannels';

      var $option_version    = 9;
      var $plugin_version    = '3.2.6';
      var $plugin_home       = 'http://www.houseindorset.co.uk/plugins/amazon-link/';

      var $stats             = array();

      var $scripts_done      = False;
      var $tags              = array();

      /*****************************************************************************************/
      // Constructor for the Plugin
      function __construct() {
         
         $this->filename   = __FILE__;
         $this->URLRoot    = plugins_url('', __FILE__);
         
         // Register Initialisation Hook
         add_action( 'init', array( $this, 'init' ) );
         add_filter( 'the_content', array( $this, 'content_filter' ),15,1 );
         add_filter( 'widget_text', array( $this, 'widget_filter' ), 16,1 );
      }
    
      /*****************************************************************************************/
      // Functions for the above hooks
      
      /*
       * Initialise the Plugin
       *
       * Called on wordpress initialisation. Do all Frontend related aspects:
       * - register styles, scripts & standard filters.
       */
      function init() {

         $settings = get_option( self::optionName, array() );
         $this->plugin_extras = !empty($settings['plugin_extras']);
         if ( ! empty($this->plugin_extras) ) {
            do_action( 'amazon_link_pre_init', $this );
         }
         
         $settings = $this->get_default_settings();
         
         // Create and Initialise Dependent Class Instances:
         
         if ( ! empty( $settings['media_library'] ) ) {

            /*
             * If user is using the media_library to store Amazon images then
             * we need to initialise the Amazon Link Search class.
             */
            include( 'include/amazonSearch.php' );
            $this->search = new AmazonLinkSearch;
            $this->search->init( $this );
         }
         
         if ( ! empty ( $settings['localise'] ) ) {
            // ip2nation needed on Frontend
            $this->ip2n = new AmazonWishlist_ip2nation;
            $this->ip2n->init( $this );
         }

         // Register our frontend styles and scripts:

         // Optional / Override-able stylesheet
         $stylesheet = apply_filters( 'amazon_link_style_sheet', plugins_url( "Amazon.css", __FILE__ ) ); 
         if ( ! empty( $stylesheet ) ) {
            wp_register_style ( 'amazon-link-style', $stylesheet, false, $this->plugin_version );
            add_action( 'wp_enqueue_scripts', array( $this, 'amazon_styles' ) );
         }

         // Multinational popup script - printed in page footer if required.
         $script = plugins_url( "amazon.js", __FILE__ );
         wp_register_script( 'amazon-link-script', $script, false, $this->plugin_version );

         if ( ! empty( $settings['sc_cache_enabled'] ) ) {
            
            // We can't tell if the multinational popup is needed so just load the script
            $this->scripts_done = True;
            add_action( 'wp_print_footer_scripts', array( $this, 'footer_scripts' ) );
         }

         // Set up default plugin filters:
         
         // Add default link generator filters - low priority
         add_filter( 'amazon_link_url',                     array( $this, 'get_url' ), 20, 6 );
         add_filter( 'amazon_link_attributes',              array( $this, 'apply_link_attributes' ), 20, 2 );

         // Default Country Mapping to Store Locale
         add_filter( 'amazon_link_map_country',             array( $this, 'map_country' ), 20, 1);
         
         /* Set up the default channel filters - priority determines order */
         if ( ! empty($settings['do_channels']) ) {
            add_filter( 'amazon_link_get_channel' ,         array( $this, 'get_channel_by_setting' ), 10,4 );
            add_filter( 'amazon_link_get_channel' ,         array( $this, 'get_channel_by_rules' ), 12,4 );
            if ( ! empty($settings['user_ids']) ) {
               add_filter( 'amazon_link_get_channel' ,      array( $this, 'get_channel_by_user' ), 14,4 );
            }
         }
        
         /* Set up the default link and channel filters */
         add_filter( 'amazon_link_template_get_link_open',  array( $this, 'get_links_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_rlink_open', array( $this, 'get_links_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_slink_open', array( $this, 'get_links_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_blink_open', array( $this, 'get_links_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_url',        array( $this, 'get_urls_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_rurl',       array( $this, 'get_urls_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_surl',       array( $this, 'get_urls_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_burl',       array( $this, 'get_urls_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_tag',        array( $this, 'get_tags_filter' ), 12, 5 );
         add_filter( 'amazon_link_template_get_chan',       array( $this, 'get_channel_filter' ), 12, 5 );

         // Call any user hooks - passing the current plugin Settings and the Amazon Link Instance.
         do_action( 'amazon_link_init', $settings, $this );
      }

      /*
       * Enqueue Amazon Link Style Sheet.
       */
      function amazon_styles() {
         wp_enqueue_style( 'amazon-link-style' );
      }

      /*
       * Print Amazon Link Footer Scripts.
       *
       * Only done if multinational popup is used in a link.
       */
      function footer_scripts() {
         
         $settings       = $this->get_default_settings();
         $link_templates = $this->get_link_templates();
         
         // Create Element used to display the popup
         echo '<span id="al_popup" onmouseover="al_div_in()" onmouseout="al_div_out()"></span>';
         
         // Pass required data to the multinational popup script and print it.
         wp_localize_script( 'amazon-link-script', 
                             'AmazonLinkMulti',
                             array('link_templates' => $link_templates, 
                                   'country_data'   => $this->get_country_data(),
                                   'channels'       => $this->get_channels( True ), 
                                   'target'         => ( $settings['new_window'] ? 'target="_blank"' : '' ))
         );
         wp_print_scripts( 'amazon-link-script' );
         
         // If called directly then don't need to print again
         remove_action( 'wp_print_footer_scripts', array( $this, 'footer_scripts' ) );

      }

      /*****************************************************************************************/
      // Various Arrays to Control the Plugin
      /*****************************************************************************************/

      function get_keywords( $keywords = array() ) {

         /*
          * Keyword array arguments:
          *   - Description: For Keyword Help Display
          *   - Live:        [1|0] Indicates if keyword is retrieved via AWS
          *   - Position:    Array of arrays to determine location of data in AWS XML
          *   - Group:       Which ResponseGroup needed for AWS to return item data
          *   - User:        [1|0] Indicates if keyword is supplied by User
          *   - Link:        [1|0] Indicates keyword should not have \r \n replaced before insertion.
          *   - Default:     If not provided/found use this value, if not provided '-' is used
          *   - Calculated:  If keyword should not be substituted during first template run
          */
         
         if ( ! isset( $this->keywords ) ) {
            
            $this->keywords = array(
               'link_open'    => array(  ),
               'rlink_open'   => array(  ),
               'slink_open'   => array(  ),
               'blink_open'   => array(  ),
               'link_close'   => array( 'Default' => '</a>'),
               'asin'         => array( 'Live' => '1', 'Group' => 'ItemAttributes', 'Default' => '0',
                                        'Position' => array(array('ASIN'))),
               'asins'        => array( 'Default' => ''),
               'product'      => array( 'Live' => '1', 'Group' => 'ItemAttributes',
                                        'Position' => array(array('ItemAttributes','ProductGroup'))),
               'binding'      => array( 'Live' => '1', 'Group' => 'ItemAttributes', 'Default' => ' ',
                                        'Position' => array(array('ItemAttributes','Binding'))),
               'features'     => array( 'Live' => '1', 'Group' => 'ItemAttributes', 'Callback' => array($this,'format_list'), 'Default' => ' ',
                                        'Position' => array(array('ItemAttributes','Feature'))),
               'title'        => array( 'Live' => '1', 'Group' => 'ItemAttributes', 'Default' => ' ',
                                        'Position' => array(array('ItemAttributes','Title'))),
               'artist'       => array( 'Live' => '1', 'Group' => 'ItemAttributes', 
                                        'Position' => array(array('ItemAttributes','Artist'),
                                                            array('ItemAttributes','Author'),
                                                            array('ItemAttributes','Director'),
                                                            array('ItemAttributes','Creator'),
                                                            array('ItemAttributes','Brand'))),
               'manufacturer' => array( 'Live' => '1', 'Group' => 'ItemAttributes',
                                        'Position' => array(array('ItemAttributes','Manufacturer'),
                                                            array('ItemAttributes','Brand'))),
               'thumb'        => array( 'Live' => '1', 'Group' => 'Images', 'Default' => 'http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif',
                                        'Position' => array(array('MediumImage','URL'))),
               'image'        => array( 'Live' => '1', 'Group' => 'Images', 'Default' => 'http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif',
                                        'Position' => array(array('LargeImage','URL'),
                                                            array('MediumImage','URL'))),
               'image_class'  => array( ),
               'search_text_s'=> array( ),
               'search_text'  => array( ),
               'url'          => array( ),
               'surl'         => array( ),
               'burl'         => array( ),
               'rurl'         => array( ),
               'rank'         => array( 'Live' => '1', 'Group' => 'SalesRank',
                                        'Position' => array(array('SalesRank'))),
               'rating'       => array( 'Live' => '1',
                                        'Position' => array(array('CustomerReviews','AverageRating'))),
               'offer_price'  => array( 'Live' => '1', 'Group' => 'Offers',
                                        'Position' => array(array('Offers','Offer','OfferListing','Price','FormattedPrice'),
                                                            array('OfferSummary','LowestNewPrice','FormattedPrice'),
                                                            array('OfferSummary','LowestUsedPrice','FormattedPrice'))),
               'list_price'   => array( 'Live' => '1', 'Group' => 'ItemAttributes',
                                        'Position' => array(array('ItemAttributes','ListPrice','FormattedPrice'))),
               'price'        => array( 'Live' => '1', 'Group' => 'Offers', 'National' => '1',
                                        'Position' => array(array('Offers','Offer','OfferListing','Price','FormattedPrice'),
                                                            array('OfferSummary','LowestNewPrice','FormattedPrice'),
                                                            array('OfferSummary','LowestUsedPrice','FormattedPrice'),
                                                            array('ItemAttributes','ListPrice','FormattedPrice'))),
               
               'text'         => array( 'User' => '1'),
               'text1'        => array( 'User' => '1'),
               'text2'        => array( 'User' => '1'),
               'text3'        => array( 'User' => '1'),
               'text4'        => array( 'User' => '1'),
               'pub_key'      => array( ),
               'mplace'       => array( ),
               'mplace_id'    => array( ),
               'rcm'          => array( ),
               'region'       => array( ),
               'imp'          => array( ),
               'buy_button'   => array( ),
               'language'     => array( ),
               
               'tag'          => array( ),
               'chan'         => array( ),
               'cc'           => array( ),
               'flag'         => array( ),
               'tld'          => array( ),
               
               'downloaded'   => array( 'Calculated' => '1'),
               'found'        => array( 'Calculated' => '1', 'Default' => '1', 'National' => 1),
               'count'        => array( 'Calculated' => '1'),
               'timestamp'    => array( 'Calculated' => 1, 'Default' => '0')
            );
            if ( isset ( $keywords ) ) {
               $this->keywords = array_merge_recursive( $keywords, $this->keywords );
            }
            $this->keywords = apply_filters( 'amazon_link_keywords', $this->keywords, $this );
         }
         return $this->keywords;
      }

      function get_country_data( $cc = NULL ) {

         if ( ! isset( $this->country_data ) ) {

            /*
             * Country specific aspects:
             * 
             * Some needed in the plugin code:
             * - cc           -> the country code (also the index).
             * - lang         -> language identifier (see Microsoft Translate)
             * - flag         -> country flag image, used in settings pages (also a keyword)
             * - tld          -> tld of amazon site, used when making AWS request (also a keyword)
             * - site         -> link to affiliate program site, used on settings pages
             * - country_name -> full name of country, used in settings pages (also a keyword)
             *
             * Some only needed for templates:
             * - mplace       -> market place of amazon site, used in Amazon Scripts
             * - mplace_id    -> market place id of amazon locale, used in Amazon Scripts
             * - rcm          -> amazon domain for location of scripts - backward compatible / depreciated
             * - region       -> advert region prefix for iframes & banners (amazon-adsystem.com) & widgets
             * - imp          -> advert prefix for serving impression tracking images
             * - buy_button   -> example buy button stored on Amazon Servers
             * - language     -> Language of each locale.
             */
            $this->country_data = apply_filters( 'amazon_link_get_country_data', array(
               'uk' => array( 'cc' => 'uk', 'mplace' => 'GB', 'mplace_id' => '2',  'lang' => 'en',     'flag' => $this->URLRoot. '/'. 'images/flag_uk.gif', 'tld' => 'co.uk', 'language' => 'English',    'region' => 'eu', 'imp' => 'ir-uk', 'rcm' => 'rcm-eu.amazon-adsystem.com',   'site' => 'https://affiliate-program.amazon.co.uk', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/02/buttons/buy-from-tan.gif', 'country_name' => 'United Kingdom', 'link_close' => '</a>'),
               'us' => array( 'cc' => 'us', 'mplace' => 'US', 'mplace_id' => '1',  'lang' => 'en',     'flag' => $this->URLRoot. '/'. 'images/flag_us.gif', 'tld' => 'com',   'language' => 'English',    'region' => 'na', 'imp' => 'ir-na', 'rcm' => 'rcm.amazon.com',            'site' => 'https://affiliate-program.amazon.com', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/01/buttons/buy-from-tan.gif', 'country_name' => 'United States', 'link_close' => '</a>'),
               'de' => array( 'cc' => 'de', 'mplace' => 'DE', 'mplace_id' => '3',  'lang' => 'de',     'flag' => $this->URLRoot. '/'. 'images/flag_de.gif', 'tld' => 'de',    'language' => 'Deutsch',    'region' => 'eu', 'imp' => 'ir-de', 'rcm' => 'rcm-de.amazon.de',             'site' => 'https://partnernet.amazon.de', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/03/buttons/buy-from-tan.gif', 'country_name' => 'Germany', 'link_close' => '</a>'),
               'es' => array( 'cc' => 'es', 'mplace' => 'ES', 'mplace_id' => '30', 'lang' => 'es',     'flag' => $this->URLRoot. '/'. 'images/flag_es.gif', 'tld' => 'es',    'language' => 'Español',    'region' => 'eu', 'imp' => 'ir-es', 'rcm' => 'rcm-es.amazon.es',             'site' => 'https://afiliados.amazon.es', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/30/buttons/buy-from-tan.gif', 'country_name' => 'Spain', 'link_close' => '</a>'),
               'fr' => array( 'cc' => 'fr', 'mplace' => 'FR', 'mplace_id' => '8',  'lang' => 'fr',     'flag' => $this->URLRoot. '/'. 'images/flag_fr.gif', 'tld' => 'fr',    'language' => 'Français',   'region' => 'eu', 'imp' => 'ir-fr', 'rcm' => 'rcm-fr.amazon.fr',             'site' => 'https://partenaires.amazon.fr', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/08/buttons/buy-from-tan.gif', 'country_name' => 'France', 'link_close' => '</a>'),
               'jp' => array( 'cc' => 'jp', 'mplace' => 'JP', 'mplace_id' => '9',  'lang' => 'ja',     'flag' => $this->URLRoot. '/'. 'images/flag_jp.gif', 'tld' => 'co.jp', 'language' => '日本語',      'region' => 'fe', 'imp' => 'ir-jp', 'rcm' => 'rcm-jp.amazon.co.jp',          'site' => 'https://affiliate.amazon.co.jp', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/09/buttons/buy-from-tan.gif', 'country_name' => 'Japan', 'link_close' => '</a>'),
               'it' => array( 'cc' => 'it', 'mplace' => 'IT', 'mplace_id' => '29', 'lang' => 'it',     'flag' => $this->URLRoot. '/'. 'images/flag_it.gif', 'tld' => 'it',    'language' => 'Italiano',   'region' => 'eu', 'imp' => 'ir-it', 'rcm' => 'rcm-it.amazon.it',             'site' => 'https://programma-affiliazione.amazon.it', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/29/buttons/buy-from-tan.gif', 'country_name' => 'Italy', 'link_close' => '</a>'),
               'cn' => array( 'cc' => 'cn', 'mplace' => 'CN', 'mplace_id' => '28', 'lang' => 'zh-CHS', 'flag' => $this->URLRoot. '/'. 'images/flag_cn.gif', 'tld' => 'cn',    'language' => '简体中文',     'region' => 'cn', 'imp' => 'ir-cn', 'rcm' => 'rcm-cn.amazon.cn',             'site' => 'https://associates.amazon.cn', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/28/buttons/buy-from-tan.gif', 'country_name' => 'China', 'link_close' => '</a>'),
               'in' => array( 'cc' => 'in', 'mplace' => 'IN', 'mplace_id' => '31', 'lang' => 'hi',     'flag' => $this->URLRoot. '/'. 'images/flag_in.gif', 'tld' => 'in',    'language' => 'Hindi',      'region' => 'in', 'imp' => 'ir-in', 'rcm' => 'ws-in.amazon-adsystem.com',    'site' => 'https://associates.amazon.in', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/31/buttons/buy-from-tan.gif', 'country_name' => 'India', 'link_close' => '</a>'),
               'ca' => array( 'cc' => 'ca', 'mplace' => 'CA', 'mplace_id' => '15', 'lang' => 'en',     'flag' => $this->URLRoot. '/'. 'images/flag_ca.gif', 'tld' => 'ca',    'language' => 'English',    'region' => 'na', 'imp' => 'ir-ca', 'rcm' => 'rcm-ca.amazon.ca',             'site' => 'https://associates.amazon.ca', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/15/buttons/buy-from-tan.gif', 'country_name' => 'Canada', 'link_close' => '</a>'),
               'br' => array( 'cc' => 'br', 'mplace' => 'BR', 'mplace_id' => '33', 'lang' => 'pt-br',  'flag' => $this->URLRoot. '/'. 'images/flag_br.gif', 'tld' => 'com.br','language' => 'Portuguese', 'region' => 'na', 'imp' => 'ir-br', 'rcm' => 'rcm-br.amazon-adsystem.br',    'site' => 'https://associados.amazon.com.br/', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/33/buttons/buy-from-tan.gif', 'country_name' => 'Brazil', 'link_close' => '</a>'),
               'mx' => array( 'cc' => 'mx', 'mplace' => 'MX', 'mplace_id' => '34', 'lang' => 'es',     'flag' => $this->URLRoot. '/'. 'images/flag_mx.jpg', 'tld' => 'com.mx','language' => 'Español',    'region' => 'na', 'imp' => 'ir-mx', 'rcm' => 'rcm-mx.amazon-adsystem.mx',    'site' => 'https://afiliados.amazon.com.mx/', 'buy_button' => 'https://images-na.ssl-images-amazon.com/images/G/34/buttons/buy-from-tan.gif', 'country_name' => 'Mexico', 'link_close' => '</a>'),
            ), $this);
         }
         if ( empty( $cc ) ) {
            return $this->country_data;
         } else {
            return $this->country_data[$cc];
         }
         /*
          * To add a new locale:
          * - Add line in the above array
          * - Add default tag in get_channels
          * - Add line in get_country
          * - Add icon to images directory
          * - Update amazon-search.php to support Searches
          */
      }

      function get_link_templates() {

         if ( ! isset( $this->link_templates ) ) {
            
            /*
             * Templates must be correctly encoded
             */
            $this->link_templates = apply_filters( 'amazon_link_multi_link_templates', 
                                                   array( 'A'=>'http://www.amazon.%TLD%/gp/product/%ARG%?ie=UTF8&amp;linkCode=as2&amp;camp=1634&amp;creative=6738&amp;tag=%TAG%%CC%#&amp;creativeASIN=%ARG%',
                                                          'S'=>'http://www.amazon.%TLD%/mn/search/?_encoding=UTF8&amp;linkCode=ur2&amp;camp=1634&amp;creative=19450&amp;tag=%TAG%%CC%#&amp;field-keywords=%ARG%',
                                                          'R'=>'http://www.amazon.%TLD%/review/%ARG%?ie=UTF8&amp;linkCode=ur2&amp;camp=1634&amp;creative=6738&amp;tag=%TAG%%CC%#',
                                                          'B'=>'http://www.amazon.%TLD%/e/e/%ARG%?tag=%TAG%%CC%#',
                                                          'U'=>'%ARG%',
                                                          'X'=>'%ARG%'),
                                                   $this);
         }
         return $this->link_templates;
      }

      /*
       * Get all possible plugin options, these are also the arguments accepted by the shortcode.
       *
       * option_list array arguments:
       *
       * Frontend
       *    - Default:        Default Value if Not Set
       *
       */
      function get_option_list( $option_list = array() ) {
     
         if ( ! isset( $this->option_list ) ) {

            $this->option_list = $option_list;
            
            // Populate the defaults - only aspected needed for Frontend
            $this->option_list['text']['Default'] = 'Amazon';
            $this->option_list['last']['Default'] = '30';
            $this->option_list['image_class']['Default'] = 'wishlist_image';
            $this->option_list['wishlist_template']['Default'] = 'Wishlist';
            $this->option_list['wishlist_items']['Default'] = 5;
            $this->option_list['wishlist_type']['Default'] = 'Similar';
            $this->option_list['default_cc']['Default'] = 'uk';
            $this->option_list['plugin_ids']['Default'] = '0';
            $this->option_list['localise']['Default'] = '1';
            $this->option_list['search_text']['Default'] = '%ARTIST% | %TITLE%';
            $this->option_list['search_text_s']['Default'] = '%ARTIST%S# | %TITLE%S#';
            $this->option_list['multi_cc']['Default'] = '1';
            $this->option_list['live']['Default'] = '1';
            $this->option_list['cache_age']['Default'] = '48';
            $this->option_list['sc_cache_age']['Default'] = '1';

            // Give plugins option of changing settings
            $this->option_list = apply_filters( 'amazon_link_option_list', $this->option_list );
         }
         return $this->option_list;
      }

      /*
       * Extract required ResponseGroups from the Keyword Table
       *
       * Content of Keywords array may be changed by plugins.
       */
      function get_response_groups() {
         
         if ( ! isset( $this->response_groups ) ) {
            $this->response_groups = array();
            foreach ( $this->get_keywords() as $key => $key_data ) {
               if ( isset( $key_data['Group'] ) ) {
                  $this->response_groups[$key_data['Group']] = true ;
               }
            }
            $this->response_groups = implode( ',', array_keys($this->response_groups) );
         }
         
         return $this->response_groups;
      }

      /*****************************************************************************************/
      // Various Options, Arguments, Templates and Channels Handling
      /*****************************************************************************************/

      /*
       * For backward compatibility this should return the 'local' settings
       */
      function getSettings() {
         
         if ( ! isset( $this->Settings ) ) {
            $this->Settings = $this->get_default_settings();
         }

         return $this->Settings;
      }

      /*
       * Normally Settings are populated from parsing user arguments, however some
       * external calls do not cause argument parsing (e.g. amazon_query). So this
       * ensures we have the defaults.
       */
      function get_default_settings() {
         
         if ( ! isset( $this->default_settings ) ) {
            $this->default_settings = get_option( self::optionName, array() );
            $option_list = $this->get_option_list();
            foreach ( $option_list as $key => $details ) {
               if ( ! isset( $this->default_settings[$key] ) && isset( $details['Default'] ) ) {
                  $this->default_settings[$key] = $details['Default'];
               }
            }
         }
         
         return $this->default_settings;
      }
  
      /*****************************************************************************************/
      // Templates

      function getTemplates ( $template = ' ' ) {
         if ( ! isset ( $this->Templates ) ) {
            $this->Templates = get_option ( self::templatesName, array() );
         }
         if ( ! empty ( $this->Templates[$template] ) ) {
            return $this->Templates[$template];
         } else if ( $template == ' ' ) {
            return $this->Templates;
         } else {
            return NULL;
         }
      }

      /*****************************************************************************************/
      // Channels
      
      /*
       * Get all the Affiliate ID Channels
       *
       * override fills any unset IDs with the defaults
       */
      function get_channels( $override = False ) {

         $settings = $this->get_default_settings();

         if ( ! isset( $this->channels ) || ! $override ) {
            $channels = get_option( self::channels_name, array() );

            if ( ! $override ) return $channels;

            if ( ! empty($settings['plugin_ids']) ) {
               /*
                * Only use the plugin ids for unpopulated locales if it has
                * been explicitly enabled by the user option.
                */
               $default_tags = array( 'tag_uk' => 'al-uk-21', 'tag_us' => 'al-us-20', 'tag_de' => 'al-de-21', 'tag_es' => 'al-es-21', 'tag_fr' => 'al-fr-21', 'tag_jp' => 'al-jp-22', 'tag_it' => 'al-it-21', 'tag_cn' => 'al-cn-23', 'tag_in' => 'al-in-21', 'tag_ca' => 'al-ca-20', 'tag_br' => 'al-br-20', 'tag_mx' => 'al-mx-20');
            } else {
               $default_tags = array();
            }

            $channels['default'] = array_filter( $channels['default'] );
            $this->channels = array();
            foreach ( $channels as $channel_id => $channel_data ) {
               $this->channels[$channel_id] = array_filter( $channel_data ) + $channels['default'] + $default_tags;
               $this->channels[$channel_id]['ID'] = $channel_id;
            }
         }

         return $this->channels;
      }
      
      /*
       * Check the channels in order until we get a match
       *
       */
      function get_channel( $settings ) {

         // get post ID if in post, needed for channel cache.
         if ( ! empty( $settings['in_post'] ) ) {
            
            global $post;
            $cache_index = $settings['asin']. $post->ID;
         } else {
            
            $post = NULL;
            $cache_index = $settings['asin'].'X';
         }
         $cache_index .= (isset($settings['chan']) ? $settings['chan'] : 'default');

         $channels = $this->get_channels( True );
         if ( isset( $this->channel_cache[$cache_index] ) ) {
            $channel_data = $channels[$this->channel_cache[$cache_index]];
         } else {
            $channel_data = apply_filters ( 'amazon_link_get_channel', array(), $channels, $post, $settings, $this );
            // No match found return default channel.
            if ( empty( $channel_data ) ) $channel_data = $channels['default'];
            $this->channel_cache[$cache_index] = $channel_data['ID'];
         }

         return $channel_data;
      }

      /*
       * If channel is manually set in the link then always apply here
       */
      function get_channel_by_setting ( $channel_data, $channels, $post, $settings ) {

         if ( ! empty( $channel_data ) ) return $channel_data;
         if ( isset( $settings['chan'] ) && isset( $channels[strtolower( $settings['chan'] )] ) ) {
            return $channels[strtolower( $settings['chan'] )];
         }

         return $channel_data;

      }

      /*   
       * Filter rules:
       *    cat = [category slug|category id]
       *    parent_cat = [category slug| category id]
       *    author = [author name|author id]
       *    tag = [tag name|tag id]
       *    type = [page|post|other = widget|template, etc]
       *    parent = [page/post id]
       *    random = 1-99
       *    empty rule = won't be used by this filter
       */
      function get_channel_by_rules ( $channel_data, $channels, $post, $settings ) {

         if ( ! empty( $channel_data ) ) return $channel_data;

         foreach ( $channels as $channel => $data ) {

            // Process the rules if they are defined
            if ( ! empty( $data['Rule'] ) ) {
               
               if ( isset( $data['Rule']['rand'] ) && ( $data['Rule']['rand'] > rand( 0, 99) ) )
                  return $data;

               if ( isset( $post ) ) {

                  if ( isset( $data['Rule']['cat'] ) && has_category( $data['Rule']['cat'], $post ) )
                     return $data;

                  if ( isset( $data['Rule']['tag'] ) && has_tag( $data['Rule']['tag'], $post ) )
                     return $data;

                  if ( isset( $data['Rule']['type'] ) && ( $post->post_type == $data['Rule']['type'] ) )
                     return $data;

                  if ( isset( $data['Rule']['author'] ) && ( $post->post_author == $data['Rule']['author'] ) )
                     return $data;
               }
            }
         }

         return $channel_data;

      }

      /*
       * If all previous filters have failed then look for User channel
       */
      function get_channel_by_user ( $channel_data, $channels, $post, $settings ) {

         if ( ! empty( $channel_data ) ) return $channel_data;

         // If no specific channel detected then check for author specific IDs via get_the_author_meta
         if ( isset( $post->post_author ) && isset( $channels['al_user_'.$post->post_author] ) ) {
            return $channels['al_user_'.$post->post_author];
         }

         return $channel_data;
      }

      /*****************************************************************************************/
      // Frontend Cache Facility

      function cache_update_item( $asin, $cc, &$data ) {
         
         global $wpdb;
         $settings = $this->get_default_settings();
         
         if ( ! empty( $settings['cache_enabled'] ) ) {
            /* Use SQL timestamp to avoid timezone difference between SQL and PHP */
            $result = $wpdb->get_row( "SELECT NOW() AS timestamp", ARRAY_A );
            $data['timestamp'] = $updated = $result['timestamp'];
            
            $cache_table = $wpdb->prefix . self::cache_table;
            $sql_data = array( 'asin' => $asin, 'cc' => $cc, 'xml' => serialize( $data ), 'updated' => $updated );
            $wpdb->replace( $cache_table, $sql_data );
         }
      }

      function cache_lookup_item( $asin, $cc ) {
         
         global $wpdb;
         
         $settings = $this->get_default_settings();

         if ( ! empty( $settings['cache_enabled'] ) ) {
            
            // Check if asin is already in the cache
            $cache_table = $wpdb->prefix . self::cache_table;
            if ( ! empty( $settings['cache_age'] ) ) {
               $sql = "SELECT xml FROM $cache_table WHERE asin LIKE '$asin' AND cc LIKE '$cc' AND  updated >= DATE_SUB(NOW(),INTERVAL " . $settings['cache_age']. " HOUR)";
            } else {
               $sql = "SELECT xml FROM $cache_table WHERE asin LIKE '$asin' AND cc LIKE '$cc'";
            }
            $result = $wpdb->get_row( $sql, ARRAY_A );
            if ($result !== NULL) {
               $data = unserialize( $result['xml'] );
               $data['cached'] = 1;
               return $data;
            }
         }
         return NULL;
      }

      /*****************************************************************************************/
      // Frontend Shortcode Cache Facility

      function sc_cache_update_item( $args, $cc, $postid, &$data ) {
         
         global $wpdb;
         
         $settings = $this->get_default_settings();
         
         if ( ! empty ( $args ) && ! empty( $settings['sc_cache_enabled'] ) ) {
            
            /* Use SQL timestamp to avoid timezone difference between SQL and PHP */
            $result= $wpdb->get_row( "SELECT NOW() AS timestamp", ARRAY_A );
            $updated = $result['timestamp'];
            $hash = hash( 'md5', $args );
            $postid = ( ! empty( $postid ) ? $postid : '0');
            $cache_table = $wpdb->prefix . self::sc_cache_table;
            $sql_data = array( 'hash' => $hash, 'cc' => $cc, 'postid' => $postid, 'args' => $args, 'content' => $data, 'updated' => $updated );
            $wpdb->replace( $cache_table, $sql_data );
         }
      }

      function sc_cache_lookup_item( $args, $cc, $postid ) {
         global $wpdb;
         
         $settings = $this->get_default_settings();
         
         if ( !empty ( $args ) && ! empty( $settings['sc_cache_enabled'] ) ) {
            
            $postid = ( ! empty( $postid ) ? $postid : '0' );
            $hash = hash( 'md5', $args );
            // Check if shortcode is already in the cache
            $cache_table = $wpdb->prefix . self::sc_cache_table;
            if ( ! empty( $settings['sc_cache_age'] ) ) {
               $sql = "SELECT content FROM $cache_table WHERE hash = '$hash' AND cc = '$cc' AND postid = '$postid' AND  updated >= DATE_SUB(NOW(),INTERVAL " . $settings['sc_cache_age']. " HOUR)";
            } else {
               $sql = "SELECT content FROM $cache_table WHERE hash = '$hash' AND cc = '$cc' AND postid = '$postid'";
            }
            $result = $wpdb->get_row( $sql, ARRAY_A );
            if ( $result !== NULL ) {
               return $result['content'];
            }

         }
         return NULL;
      }

      /*****************************************************************************************/
      /// Localise Link Facility

      function map_country( $cc ) {
         
         if ( $cc === NULL ) {
            $settings = $this->get_default_settings();
            return $settings['default_cc'];
         }
         
         // Pretty arbitrary mapping of domains to Amazon sites, default to 'com' - the 'international' site.
         $country_map = array( 'uk' => 'uk', 'ie' => 'uk', 'im' => 'uk', 'gi' => 'uk', 'gl' => 'uk', 'nl' => 'uk',
                               'vg' => 'uk', 'cy' => 'uk', 'gb' => 'uk', 'dk' => 'uk', 'gb' => 'uk',
                               'fr' => 'fr', 'be' => 'fr', 'bj' => 'fr', 'bf' => 'fr', 'bi' => 'fr', 'cm' => 'fr',
                               'cf' => 'fr', 'td' => 'fr', 'km' => 'fr', 'cg' => 'fr', 'dj' => 'fr', 'ga' => 'fr',
                               'gp' => 'fr', 'gf' => 'fr', 'gr' => 'fr', 'pf' => 'fr', 'tf' => 'fr', 'ht' => 'fr',
                               'ci' => 'fr', 'lu' => 'fr', 'mg' => 'fr', 'ml' => 'fr', 'mq' => 'fr', 'yt' => 'fr',
                               'mc' => 'fr', 'nc' => 'fr', 'ne' => 'fr', 're' => 'fr', 'sn' => 'fr', 'sc' => 'fr',
                               'tg' => 'fr', 'vu' => 'fr', 'wf' => 'fr',
                               'de' => 'de', 'at' => 'de', 'ch' => 'de', 'no' => 'de', 'dn' => 'de', 'li' => 'de',
                               'sk' => 'de',
                               'es' => 'es',
                               'it' => 'it', 'va' => 'it',
                               'cn' => 'cn',
                               'ca' => 'ca', 'pm' => 'ca',
                               'jp' => 'jp',
                               'in' => 'in',
                               'br' => 'br',
                               'mx' => 'mx');

         if ( ! empty( $country_map[$cc] ) ) {
            return $country_map[$cc];
         } else {
            return 'us';
         }
      }
      
      function get_country( $settings ) {

         if ( ! empty( $settings['localise'] ) && isset( $this->ip2n ) ) {
            if ( empty( $this->local_country ) ) {
               $this->local_country = apply_filters( 'amazon_link_map_country', $this->ip2n->get_cc(), $this );
            }
            return $this->local_country;
         }
         return $settings['default_cc'];
      }

      function get_local_info( $settings ) {

         return $this->get_country_data( $this->get_country( $settings ) );
         
      }
      
      function get_regex() {
         if ( empty ( $this->regex ) ) {
            /*
             * Default regex needs to match opening and closing brackets '['
             */
            $this->regex = apply_filters( 'amazon_link_regex',
                                          '~
                                           \[amazon\s+                  # "[amazon" with at least one space
                                           (?P<args>                    # capture everything that follows as a named expression "args"
                                            (?:(?>[^\[\]]*)             # argument name excluding any "[" or "]" character
                                             (?:\[(?>[a-z]*)\])?        # optional "[alphaindex]" phrase
                                            )*                          # 0 or more of these arguments
                                           )                            # end of "args" group
                                           \]                           # closing ]
                                           ~sx',
                                          $this);
         }
         return $this->regex;
      }

      /*****************************************************************************************/
      /* Actual Mechanics of the Shortcode Processing
       *
       *   - Filter the Content and Widget Text for Shortcodes
       *     * content_filter
       *     * widget_filter
       *
       *   - Either action the shortcode, or just extract ASINs
       *     * shortcode_expand
       *     * shortcode_extract_asins
       *       - parse_shortcode [to turn argument string into settings array
       *
       *   - Generate Output
       *     * make_links
       *     * show_recommendations
       *
      /*****************************************************************************************/      

      /*****************************************************************************************/
      // Searches through the_content for our 'Tag' and replaces it with the lists or links
      /*
       * Performs 2 functions:
       *   1. Process the content and replace the shortcode with amazon links and wishlists
       *   2. Search through the content and record any Amazon ASIN numbers ready to generate a wishlist.
       */
      function content_filter( $content, $create_shortcodes = True, $in_post = True ) {

         if ( $create_shortcodes ) {

            $this->in_post = $in_post;
            if ( $in_post ) {
               global $post;
               $this->post_ID = $post->ID;
            } else {
               $this->post_ID = '0';
            }
            $text = preg_replace_callback( $this->get_regex(), array($this,'shortcode_expand'), $content );
            if ( ( preg_last_error() != PREG_NO_ERROR ) ) echo '<!-- amazon-link pattern error: '. var_export( preg_last_error() ). '-->';
            return $text;
         } else {
            return preg_replace_callback( $this->get_regex(), array( $this, 'shortcode_extract_asins' ), $content );
         }
      }

      /*
       * Widget Text Filter - as Content Filter but not 'in_post'
       */
      function widget_filter( $content ) {
         return $this->content_filter( $content, True, False );
      }
      
      /*****************************************************************************************/
     
      /*
       * Expand shortcode arguments and action accordingly
       *
       * args is an array of options, with either option '1' or 'args' containing an shortcode arg string
       */
      function shortcode_expand ( $args ) {

         $args['in_post'] = $this->in_post;
         $settings = $this->parse_shortcode( $args );

         $this->inc_stats( 'shortcodes', 0 );

         $output='';
         $cc = $settings['local_cc'];
         
         if ( $settings[$cc]['debug'] ) {
            $output .= '<!-- Amazon Link: Version:' . $this->plugin_version . ' - Args: ' . $args . "\n";
            $output .= print_r( $settings, true ) . ' -->';
         }

         if ( empty( $settings[$cc]['cat'] ) && empty( $settings[$cc]['s_index'] ) && empty( $settings[$cc]['alt'] ) ) {

            // Standard shortcode
            
            // Save ASINs in global 'tags' tracker.
            if ( ! empty( $settings['asin'] ) ) {
               $this->tags = array_merge( $settings['asin'], $this->tags );
            }

            // Lookup Shortcode in Shortcode Cache, 
            $cached_output = $this->sc_cache_lookup_item( $args, $cc, $this->post_ID );

            if ( ! empty( $cached_output ) ) {
               $output .= $cached_output;
            } else {
               
               // TODO: Remove:
               $this->Settings = &$settings['global'];
               
               // Generate Amazon Link
               $output .= $this->make_links( $settings );
              
               // Save Shortcode
               $this->sc_cache_update_item( $args, $cc, $this->post_ID, $output );
            }            
         } else {

            // Generated list of ASINS, either via category, local items, or Amazon search
            $output .= $this->showRecommendations( $settings );
            
         }
         
         // Filter the shortcode output
         return apply_filters( 'amazon_link_shortcode_output', $output, $this );
      }

      /*
       * Expand shortcode arguments and record ASINs listed
       */
      function shortcode_extract_asins ( $split_content ) {
         
         $settings = $this->parse_shortcode( $split_content );
         $this->tags = array_merge( $settings['asin'], $this->tags );
         return '';
      }

      /*
       * Amazon Link version of parse_str
       */
      function parse_str( $args, &$settings ) {
         
         // Split string into arguments arg[cc]=data at each '&'
         $arguments = explode( '&', $args );
         
         foreach ( $arguments as $argument ) {
            
            // Split argument into arg[cc] and data at '='
            list( $arg, $data ) = explode( '=', $argument, 2 );
            list( $arg, $cc )   = preg_split( '/(\]|\[|$)/', $arg, 3 );
            if ( empty( $cc ) ) $cc = 'global';
            $arg = strtolower( $arg );
            $cc  = strtolower( $cc );
            
            if ( $arg == 'asin' ) {
               
               // ASIN we store outside the main settings
               $asins = explode( ',', $data );
               if ( $cc == 'global' ) {
                  $cc = $settings['global']['default_cc'];
               }
               foreach ( $asins as $i => $asin ) {
                  if (!empty($asin)) $settings['asin'][$i][$cc] = $asin;
               }
               
            } else if ( $arg == 'template_content' ) {
               
               // TEMPLATE_CONTENT does not want to be urldecoded
               $settings[$cc][$arg] = $data;
               
            } else if ( ! empty( $arg ) ) {
               // Strip off quotes and urldecode arguments
               $settings[$cc][$arg] = trim( urldecode( $data ), "\x22\x27 \t\n\r\0\x0B" );
            }
         }
      }

      /*
       * Extract settings from shortcode arguments
       * 
       *  We need to get the shortcode 'content' and 'args' in raw format, we also
       *  need to ensure output is not 'texturized' by the WP default filters.
       */
      function parse_shortcode( &$split ) {

         // Get global settings and default country data
         $settings = $this->get_country_data();
         $countries = array_keys( $settings );
         $settings['global'] = $this->get_default_settings();
         // If no ASIN supplied, ensure template is expanded at least once.
         $settings['asin'][0][$settings['global']['default_cc']] = '';
         /*
          * First get the main arguments string
          */
         if ( ! empty( $split['args'] ) ) {
            $args  = html_entity_decode( $split['args'], ENT_QUOTES, 'UTF-8' );
            unset ( $split['args'] );
         } else if ( ! empty( $split[1] ) ) {
            $args  = html_entity_decode( $split[1] , ENT_QUOTES, 'UTF-8');
            unset ( $split[1] );
         } 
         
         /*
          * Reverse some of the WordPress filters efforts & ensure '&#8217; => '�' characters are decoded
          */
          foreach ( $split as $arg => $data ) {
            if ( ! is_int( $arg ) ) {
               if ( $arg == 'asin' ) {
                  
                  // ASIN we store outside the main settings
                  $asins = explode ( ',', $data );
                  foreach ( $asins as $i => $asin ) {
                     if (!empty($asin)) $settings['asin'][$i][$settings['global']['default_cc']] = $asin;
                  }
                  
               } else {
                  
                  $settings['global'][$arg] = html_entity_decode( $data, ENT_QUOTES, 'UTF-8' );
                  
               }
            }
         }
         
         if ( ! empty( $args ) ) {
            $this->parse_str( $args, $settings );
            $split = $args;
         } else {
            $split = NULL;
         }
         $settings = apply_filters( 'amazon_link_process_args', $settings, $this );

         $settings['local_cc']   = $this->get_country( $settings['global'] );
         $settings['default_cc'] = $settings['home_cc'] = $settings['global']['default_cc'];
         $settings['global']['local_cc'] = $settings['local_cc'];
         $settings['global']['home_cc'] = $settings['global']['default_cc'];
         foreach ( $countries as $cc ) {
            // copy global settings into each locale
            $settings[$cc] += $settings['global'];
         }

         return $settings;
      }

      /*****************************************************************************************/
      /*
       * Generate Content
       */
      /*****************************************************************************************/

      function make_links( $settings )
      {
  
         $cc = $settings['local_cc'];

         /*
          * If a template is specified and exists then populate it
          */
         if ( isset( $settings[$cc]['template'] ) ) {
            $template = strtolower( $settings[$cc]['template'] );
            $template = $this->getTemplates( $template );
            if ( ! empty( $template ) ) {
               $settings[$cc]['template_content'] = $template['Content'];
               $settings[$cc]['template_type'] = $template['Type'];
            }
         }

         if ( ! isset( $settings[$cc]['template_content'] ) ) {

            // Backward Compatible Shortcode, just has image,thumb and text

            if ( ! empty( $settings[$cc]['image'] ) ) {
               $image = True;
               if ( strlen( $settings[$cc]['image'] ) < 5 ) unset( $settings[$cc]['image'] );
            }

            if ( ! empty( $settings[$cc]['thumb'] ) ) {
               $thumb = True;
               if ( strlen( $settings[$cc]['thumb'] ) < 5 ) unset( $settings[$cc]['thumb'] );
            }

            if ( isset( $thumb ) && isset( $image ) ) {
               $settings[$cc]['template_content'] = '<a href="%IMAGE%"><img class="%IMAGE_CLASS%" src="%THUMB%" alt="%TEXT%"></a>';
            } else if ( isset( $image ) ) {
               $settings[$cc]['template_content']= '%LINK_OPEN%<img class="%IMAGE_CLASS%" src="%THUMB%" alt="%TEXT%">%LINK_CLOSE%';
            } else if ( isset( $thumb ) ) {
               $settings[$cc]['template_content']= '%LINK_OPEN%<img class="%IMAGE_CLASS%" src="%THUMB%" alt="%TEXT%">%LINK_CLOSE%';
            } else {
               $settings[$cc]['template_content']= '%LINK_OPEN%%TEXT%%LINK_CLOSE%';
            }
            $settings[$cc]['template_type'] = 'Product';
         }

         $details = array();

         if ( empty( $settings[$cc]['template_type'] ) ) $settings[$cc]['template_type'] = 'Product';
             
         if ( $settings[$cc]['template_type'] == 'Multi' ) {
            
            /* Multi-product template collapse array back to a list, respecting country specific selection */
            
            $sep = ''; 
            $settings[$cc]['asins']='';
            foreach ( $settings['asin'] as $i => $asin ) {
               /* Skip this ASIN if the shortcode explicitly set to ignore '-' */
               if (empty($asin[$cc]) || ($asin[$cc] != '-'))
               {
                  $settings[$cc]['asins'] .= $sep .( is_array( $asin ) ? ( !empty( $asin[$cc] ) ? $asin[$cc] : $asin[$settings['default_cc']]) : $asin );
                  $sep=',';
               }
            }
            $output = $this->parse_template( $settings );
            
         } elseif ( $settings[$cc]['template_type'] == 'No ASIN' ) {
            
            /* No asin provided so don't try and parse it */
            
            $settings[$cc]['found'] = 1;
            $settings['asin'] = array();
            $output = $this->parse_template( $settings );
            
         } else {
            
            /* Usual case where user provides asin=X or asin=X,Y,Z */
            
            $asins = $settings['asin'];
            if ( count( $asins ) > 1) {
               $settings[$cc]['live'] = 1;
            }
            $output = '';

            $countries = array_keys( $this->get_country_data() );
            $count = 1;
            foreach ( $asins as $asin ) {
               // TODO: Do we need this loop?
               foreach ( $countries as $cc ) {
                  $settings[$cc]['asin'] = ! empty( $asin[$cc] ) ? $asin[$cc] : NULL;
                  $settings[$cc]['count'] = $count;
               }
               $settings['asin'] = $asin;
               $count++;

               $output .= $this->parse_template( $settings );
            }
         }
         return $output;
      }

      function showRecommendations ( $settings ) {
         return include('include/showRecommendations.php');
      }
      
      /*****************************************************************************************/
      /*
       * Parse Template - keyword filters
       */
      
      function get_channel_filter ( $channel, $keyword, $country, $data, $settings ) {

         if ( ! empty( $channel ) ) return $channel;

         $channel = $this->get_channel( $data[$country] );
         return $channel['ID'];
      }

      function get_tags_filter ( $tag, $keyword, $country, $data, $settings ) {

         if (!empty($tag)) return $tag;
         $channel = $this->get_channel( $data[$country] );
         return $channel['tag_'.$country];
      }

      function get_urls_filter ( $url, $keyword, $cc, $data, $settings ) {

         if ( ! empty( $url ) ) return $url;

         $type = ($keyword == 'url' ? 'A' : strtoupper($keyword[0]));

         $url = apply_filters( 'amazon_link_url', '', $type, $data, $data[$cc]['search_text_s'], $data[$cc]['cc'], $settings, $this );
         return $url;

      }

      function apply_link_attributes ( $attributes, $data ) {
         
         if ( ! empty( $data['new_window'] ) ) $attributes .= ' target="_blank"';
         if ( ! empty( $data['link_title'] ) ) $attributes .= ' title="'.addslashes( $data['link_title'] ).'"';
         
         return $attributes;
      }
      
      function get_links_filter ( $link, $keyword, $cc, $data, $settings ) {

         // TODO: Use $settings / $data[$cc]?, rationalise
         if ( empty( $this->temp_settings['multi_cc'] ) && ! empty( $link ) ) return $link;

         $type = ($keyword == 'link_open' ? 'A' : strtoupper($keyword[0]));

         $attributes = apply_filters( 'amazon_link_attributes', 'rel="nofollow"', $data[$cc], $this);
         //. ( $settings['new_window'] ? ' target="_blank"' : '' );
         $url = apply_filters( 'amazon_link_url', '', $type, $data, $data[$cc]['search_text_s'], $data[$cc]['cc'], $settings, $this );
         $text = "<a $attributes href=\"$url\">";
         if ( $settings['multi_cc'] ) {
            $multi_data = array( 'settings' => $data, 'asin' => $data['asin'], 'type' => $type, 'search' => $data[$cc]['search_text_s'], 'cc' => $data[$cc]['cc'] );
            $text = $this->create_popup( $multi_data, $text );
         }
         return $text;
      }

      /*
       * We need to run the regex multiple times to catch new template tags replacing old ones (LINK_OPEN)
       */
      function parse_template ( $item ) {

         $start_time = microtime( true );

         $countries_a = array_keys( $this->get_country_data() );

         $keywords_data = $this->get_keywords();
         $sep = $sepc = $keywords = $keywords_c = '';
         // TODO: Cache this
         foreach ( $keywords_data as $keyword => $key_data ) {
            if ( empty( $key_data['Calculated'] ) ) {
               $keywords .= $sep.$keyword;
               $sep = '|';
            } else {
               $keywords_c .= $sepc.$keyword;
               $sepc= '|';
            }
         }
         
         $input = htmlspecialchars_decode ( stripslashes( $item[$item['local_cc']]['template_content'] ) );

         // TODO: Do we really need both?
         $this->temp_settings = $item[$item['local_cc']];
         $this->temp_data = $item;

         $countries = implode( '|', $countries_a );
         do {
            $input = preg_replace_callback( "!(?>%($keywords)%)(?:(?>($countries))?(?>(S))?([0-9]+)?#)?!i", array( $this, 'parse_template_callback' ), $input, -1, $count );
         } while ( $count );

         $input = preg_replace_callback( "!(?>%($keywords_c)%)(?:(?>($countries))?(?>(S))?([0-9]+)?#)?!i", array( $this, 'parse_template_callback' ), $input );

         $time = microtime( true ) - $start_time;

         if ( ! empty( $item[$item['local_cc']]['debug'] ) ) $input .="<!-- Time Taken: $time. -->";
         
         // Clear out local settings and data, no longer needed
         unset( $this->temp_settings, $this->temp_data );

         return $input;
      }

      /*
       * Callback to process the preg_replace result where:
       *
       * - $args[1] => 'KEYWORD'
       * - $args[2] => 'CC'
       * - $args[3] => 'ESCAPE'
       * - $args[4] => 'INDEX'
       */
      function parse_template_callback ( $args ) {

         $keyword  = strtolower( $args[1] );

         // TODO: Just return key_data?
         $key_data = $this->get_keywords();
         $key_data = $key_data[$keyword];
         $settings = $this->temp_settings; // $data[$data['local_cc']]

         $default_country  = $settings['home_cc'];

         /*
          * Process Modifiers
          */
         if ( empty( $args[2] ) ) {
            $country = $settings['local_cc'];
         } else {
            // Manually set country, hard code to not localised
            $country = strtolower( $args[2] );
            $settings['multi_cc']  = 0;
            $settings['localise']  = 0;
            $settings['default_cc'] = $country;
         }
         $escaped        = ! empty( $args[3]);
         $keyword_index  = ( ! empty( $args[4] ) ? $args[4] : 0 );

         /*
          * Select the most appropriate ASIN for the locale
          * TODO: Pre-do this?
          */

         if ( empty( $this->temp_data[$country]['asin'] ) ) {
            $this->temp_data[$country]['asin'] = isset( $this->temp_data[$default_country]['asin'] ) ? $this->temp_data[$default_country]['asin'] : NULL;
         }
         $asin = $this->temp_data[$country]['asin'];

         /*
          * Prefetch product data if not already fetched and prefetch is enabled
          */
         if ( $settings['live'] && $settings['prefetch'] && empty( $this->temp_data[$country]['prefetched']) && ! empty($asin) ) {

            $this->temp_data[$country] += $this->get_item_data( $asin, $country, $settings );
            $this->temp_data[$country]['prefetched'] = 1;
         }

         /*
          * Apply any template_get filters for this keyword
          */
         $phrase = apply_filters( 'amazon_link_template_get_'. $keyword, isset($this->temp_data[$country][$keyword])?$this->temp_data[$country][$keyword]:NULL, $keyword, $country, $this->temp_data, $settings, $this);
         if ($phrase !== NULL) $this->temp_data[$country][$keyword] = $phrase;
   
         /*
          * If the keyword is not yet set then we need to populate it
          */
         if ( ! isset( $this->temp_data[$country][$keyword] ) ) {

            /*
             * If we can get it from Amazon then try and get it
             */
            if ( ! empty($key_data['Live'] ) && ( $settings['live'] ) ) {
               
               $this->temp_data[$country] += $this->get_item_data( $asin, $country, $settings );

            } else {
               
               /*
                * We can't retrieve it, so just use the default if set
                */
               $this->temp_data[$country][$keyword] = isset( $key_data['Default'] ) ? ( is_array( $key_data['Default'] ) ? $key_data['Default'][$country] : $key_data['Default'] ) : '-';
            }
         }

         /*
          * Run the 'process' filters to post process the keyword
          */
         $this->temp_data[$country][$keyword] = apply_filters( 'amazon_link_template_process_'. $keyword, isset( $this->temp_data[$country][$keyword] ) ? $this->temp_data[$country][$keyword]:NULL, $keyword, $country, $this->temp_data, $settings, $this );

         /*
          * If multiple results returned then select the one requested in the template
          */
         $phrase = $this->temp_data[$country][$keyword];
         if ( is_array( $phrase ) ) {
            $phrase = !empty($phrase[$keyword_index]) ? $phrase[$keyword_index] : $phrase[0];
         }
         
         /*
          * Special cases when data being displayed is not available in locale
          */
         if ( ! empty($key_data['National']) && ! empty($this->temp_data[$country]['not_found']) ) {
            if ( ($keyword == 'found') && ($settings['localise'] == 0) ) {
               $phrase = '0';
            } else {
               $phrase = isset( $key_data['Default'] ) ? ( is_array( $key_data['Default'] ) ? $key_data['Default'][$country] : $key_data['Default'] ) : '-';
            }
         }

         /*
          * This just needs to get the data through to the javascript, typical HTML looks like:
          * <a onmouseover="Function( {'arg': '%KEYWORD%'} )">
          * Need to ensure there are no unescaped ' or " characters or new lines
          * " => '&#34;'
          * 
          * Also for search links need to ensure & is escaped
          *
          * For keywords in the link title need to escape " 
          *
          * It is up to the receiving javascript to ensure that the data is present correctly for the next stage
          *  - in postedit -> strip out > and " and & and [ to ensure the shortcode is parsed correctly
          *  - in popup (do nothing?).
          */
         if ( $escaped ) {
            $phrase = str_replace( array( '"', "'", '&', "\r", "\n"  ), array( '%22', "%27", '%26', '&#13;','&#10;'  ), $phrase);
         }
         /*
          * Update unused_args to remove used keyword.
          */
         if ( ! empty( $this->temp_data[$country]['unused_args'] ) ) {
            $this->temp_data[$country]['unused_args'] = preg_replace( '!(&?)'.$keyword.'=[^&]*(\1?)&?!','\2', $this->temp_data[$country]['unused_args'] );
         }

         return $phrase;
      }
 
      /*****************************************************************************************/
      /// Helper Functions

      /*
       * Get Item data either locally or from default locale
      */
      function get_item_data ( $asin, $country, &$settings ) {
         
         $item_data = $this->cached_query( $asin, $settings, True );
         
         if ( $item_data['found'] ) {
            if ( empty( $this->temp_data['asin'][$country] ) ) {
               $this->temp_data['asin'][$country] = $asin;
            }
         } else if ( ! empty( $settings['localise'] ) && ( $country != $settings['home_cc'] ) ) {

            $settings['default_cc'] = $settings['home_cc'];
            $settings['localise']   = 0;
            $item_data = $this->cached_query( $asin, $settings, True );
               
            if ( ! empty($settings['home_links']) ) {
               // ***** Not available just show home data & links *****
               $this->temp_data[$country] = $this->temp_data[$settings['default_cc']];
            } else {
               $item_data['not_found'] = 1;
            }
         }
         
         if ( isset($settings['debug']) && isset( $item_data['aws_error'] ) ) {
            echo "<!-- amazon-link ERROR: "; print_r( $item_data ); echo "-->";
         }
         return $item_data;
      }
      
      /*
       * Use Templates to create appropriate URL
       */
      function get_url( $url, $type, $data, $search, $cc, $settings ) {

         // URL already created just drop out.
         if ($url != '') return $url;

         $link  = $this->get_link_type( $type, $data['asin'], $cc, $search, $data );
         /* If not standard localisation then populate the %MANUAL_CC% keyword */
         if ( empty( $setting['localise'] ) && ( $cc != $settings['home_cc'] ) ) {
            $manual_cc = $cc;
         } else {
            $manual_cc = '';
         }
         $links = $this->get_link_templates();
         $text  = $links[$link['type']];

         $text  = str_replace( array( '%ARG%', '%TLD%', '%TYPE%', '%CC%', '%MANUAL_CC%' ), array($link['term'], $data[$cc]['tld'], $link['type'], $cc, $manual_cc), $text);
         return $text;
      }
         

      function create_popup ( $data, $text ) {

         if ( ! $this->scripts_done ) {
             $this->scripts_done = True;
             add_action( 'wp_print_footer_scripts', array( $this, 'footer_scripts' ) );
         }

         // Need to check all locales...
         $sep = '';
         $term ='{';
         $countries = array_keys($this->get_country_data());

         foreach ( $countries as $country ) {
            $link = $this->get_link_type ( $data['type'], $data['asin'], $country, $data['search'], $data['settings'] );
            $term .= $sep. $country .' : \''.$link['type'].'-' . $link['term'] . '\'';
            $sep = ',';
         }
         $term .= '}';

         $script = 'onMouseOut="al_link_out()" onMouseOver="al_gen_multi('. rand() . ', ' . $term. ', \''. $data['cc']. '\', \'%CHAN%\');" ';
         $script = str_replace ( '<a', '<a ' . $script, $text );
         return $script;
      }

      function get_link_type ( $type, $asin, $cc, $search, $settings ) {
         
         $home_cc = $settings['home_cc'];

         if ($type == 'S' ) {
            $term = $search;
         } else {
            // If local ASIN exists then just use it
            if ( ! empty( $asin[$cc] ) ) {
               $term = $asin[$cc];
               
               // Product link && URL defined => URL[CC]
            } else if ( ( $type == 'A' ) &&
                        ! empty( $settings[$cc]['url'] ) )
            {
               $type = 'U';
               $term = $settings[$cc]['url'];
               
               // Search Links enabled and home ASIN available => search for ASIN[home]
            } else if ( ! empty( $settings[$cc]['search_link'] ) && ! empty( $asin[$home_cc] ) ) {
               $type = 'S';
               $term = $search;
               
               // No ASIN defined but URL defined
            } else if ( empty( $asin[$home_cc] ) &&
                        ! empty( $settings[$cc]['url'] ) )
            {
               $type = 'U';
               $term = $settings[$cc]['url'];
               
               // Home ASIN defined
            } else if ( ! empty( $asin[$home_cc] ) ) {
               $term = $asin[$home_cc];
               
            } else if ( ! empty( $settings[$cc]['search_link'] ) ) {
               $type = 'S';
               $term = $search;
            } else {
               $type = 'X';
               $term = ! empty( $settings[$home_cc]['url'] ) ?  $settings[$home_cc]['url'][$cc] : '';
            }
         }
         return array( 'type' => $type, 'term' => $term );
      }

      function inc_stats( $array, $element ) {
         $this->stats[$array][$element] = isset( $this->stats[$array][$element] ) ? $this->stats[$array][$element] + 1 : 1;
      }

      function format_list ( $array, $key_info = array() ) {

         /* Only process if it is an array, if it isn't then it probably has already been filtered. */
         if ( ! is_array( $array ) ) return $array;

         $class = isset( $key_info['Class'] ) ? $key_info['Class'] : 'al_'. $key_info['Keyword'];
         $ul = '<ul class="'. $class .'">';
         foreach ( $array as $item ) {
            $ul .= '<li>'. $item . '</li>';
         }
         $ul .= '</ul>';
         return $ul;
      }

      function grab( $data, $keys, $default ) {
         
         foreach ( $keys as $location ) {
            $result = $data;
            foreach ( $location as $key ) {
               if ( isset( $result[$key] ) ) {
                  $result = $result[$key];
               } else {
                  $result = NULL;
                  break;
               }
            }
            if ( isset( $result ) ) return $result;
         }
         if ( empty( $keys ) ) return $data; // If no keys then return the whole item
         return $default;
      }

      function cached_query( $request, $settings, $first_only = False ) {

         $cc = $this->get_country( $settings );
         $data = NULL;
         
         /* If not a request then must be a standard ASIN Lookup */
         if ( ! is_array( $request ) ) {
            
            $asin = $request;
            $this->inc_stats( 'lookups', $asin );
            
            // Try and retrieve from the cache
            $data[0] = $this->cache_lookup_item( $asin, $cc );
            if ($data[0] !== NULL) {
               $this->inc_stats( 'cache_hit', $asin );
               if (isset($data[0]['aws_error'])) $data['Error'] = $data[0]['aws_error'];
               return $first_only ? $data[0] : $data;
            }
            $this->inc_stats( 'cache_miss', $asin );

            // Create query to retrieve the an item
            $request = array();
            $request['Operation']     = 'ItemLookup';
            $request['ItemId']        = $asin;
            $request['IdType']        = 'ASIN';
            $request['ResponseGroup'] = $this->get_response_groups();
            if (!empty($settings['condition'])) {
               $request['Condition'] = $settings['condition'];
            }
         } else { 
            $request['ResponseGroup'] = $this->get_response_groups();
         }

         $pxml = $this->doQuery( $request, $settings );
         if ( ! empty( $pxml['Items']['Item'] ) ) {
            
            $data = array();

            if ( array_key_exists( 'ASIN', $pxml['Items']['Item'] ) ) {
               // Returned a single result (not in an array)
               $items = array( $pxml['Items']['Item'] );
               $this->inc_stats( 'aws_hit', $asin );
            } else {
               // Returned several results
               $items = $pxml['Items']['Item'];
            }

         } else {

            $this->inc_stats( 'aws_miss', $asin );
            // Failed to return any results

            $data['Error'] = ( isset( $pxml['Error'] )? $pxml['Error'] : 
                                 ( isset( $pxml['Items']['Request']['Errors']['Error'] ) ? 
                                      $pxml['Items']['Request']['Errors']['Error'] : array( 'Message' => 'No Items Found', 'Code' => 'NoResults') ) );
            $items = array( array( 'ASIN' => $asin, 'found' => 0, 'Error' => $data['Error'] ) );
         }

         $keywords = $this->get_keywords();
         $partial = False;

         /* Extract useful information from the xml */
         for ( $index = 0; $index < count( $items ); $index++ ) {
            $result = $items[$index];

            foreach ( $keywords as $keyword => $key_info ) {
               if ( ! empty( $key_info['Live'] ) &&                                      // Is a Live Keyword
                   isset( $key_info['Position'] ) && is_array( $key_info['Position'] ) ) // Has a pointer to what data to use
               {

                  if ( ! empty( $settings['skip_slow'] ) && ! empty( $key_info['Slow'] ) ) {
                     /* Slow Callbacks skipped so flag partial data so as not to cache it */
                     $partial = True;
                  } else {

                     $key_data = $this->grab( $result, 
                                              $key_info['Position'], 
                                              isset( $key_info['Default'] ) ? ( is_array( $key_info['Default'] ) ? $key_info['Default'][$cc] : $key_info['Default'] ) : '-');
                     $key_info['Keyword'] = $keyword;
                     if ( isset( $key_info['Callback'] ) ) {
                        $key_data = call_user_func( $key_info['Callback'], $key_data, $key_info, $this, $data[$index]);
                     } else if ( isset( $key_info['Filter'] ) ) {
                        $key_data = apply_filters( $key_info['Filter'], $key_data, $key_info, $this, $data[$index]);
                     }
                     $data[$index][$keyword] = $key_data;
                  }
               }
            }
            $data[$index]['found']   = isset( $result['found'] ) ? $result['found'] : '1';
            $data[$index]['partial'] = $partial;
            if (isset($result['Error'])) $data[$index]['error'] = $result['Error'];
            
            /* Save each item to the cache if it is enabled and got complete data */
            if ( ! $partial &&
                 ( $data[$index]['found'] || 
                   ( $result['Error']['Code'] == 'AWS.InvalidParameterValue' ) ||
                   ( $result['Error']['Code'] == 'AWS.ECommerceService.ItemNotAccessible' ) ) )
               $this->cache_update_item( $data[$index]['asin'], $cc, $data[$index] );
         }

         return $first_only ? $data[0] : $data;
      }

      function doQuery( $request, $settings )
      {

         $li  = $this->get_local_info( $settings );
         $tld = $li['tld'];

         /* 
          * It seems that although 'AssociateTag' is mandatory it is not currently
          * validated.
          */
         if ( ! isset( $request['AssociateTag'] ) ) $request['AssociateTag'] = 'dummy-tag';

         return $this->aws_signed_request($tld, $request, $settings['pub_key'], $settings['priv_key']);
      }
         

      function aws_signed_request($region, $params, $public_key, $private_key)
      {
         return include('include/awsRequest.php');
      }

   } // End Class
      
   // Create either Admin instance or Frontend instance of the Amazon Link Class.
   if ( is_admin() ) {
      include( 'include/amazonSearch.php' );
      include( 'include/displayForm.php' );
      include( 'include/amazon-link-admin-support.php' );
      $awlfw = new Amazon_Link_Admin_Support();
   } else {
      $awlfw = new AmazonWishlist_For_WordPress();
   }

} // End if exists
      
// Return a URL to Amazon given shortcode arguments 'args'.
function amazon_get_link( $args )
{
   global $awlfw;
   return $awlfw->shortcode_expand( array( 'args' => $args, 'template_content' => '%URL%' ) );
}

// Print the Amazon javascript to support the multinational popup
function amazon_scripts()
{
  global $awlfw;
  $awlfw->footer_scripts();
}

// Perform an AWS query given a request array.
function amazon_query( $request )
{
  global $awlfw;
  return $awlfw->doQuery( $request, $awlfw->get_default_settings() );   // Return response
}

// Perform cached query $request can be just an ASIN or a request array
function amazon_cached_query($request, $settings = NULL, $first_only = False)
{
   global $awlfw;
   
   if ($settings === NULL)
      $settings = $awlfw->get_default_settings();
   
   return $awlfw->cached_query($request, $settings, $first_only);
}

// Process shortcode args and return output.
function amazon_shortcode( $args )
{
   global $awlfw;
   $awlfw->in_post = False;
   $awlfw->post_ID = NULL;
   return $awlfw->shortcode_expand( array( 'args' => $args ) );
}

// Deprecated
function amazon_recommends( $categories = '1', $last = '30' )
{
   global $awlfw;
   return $awlfw->shortcode_expand( array( 'cat' => $categories, 'last' => $last ) );
}
      
// Deprecated
function amazon_make_links($args)
{
   return amazon_shortcode($args);
}

// vim:set ts=3 sts=3 sw=3 st: et:
?>
