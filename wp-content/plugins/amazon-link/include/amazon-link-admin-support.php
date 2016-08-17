<?php

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

if ( ! class_exists ( 'Amazon_Link_Admin_Support' ) ) {
   class Amazon_Link_Admin_Support extends AmazonWishlist_For_WordPress {

      var $menu_slug         = 'amazon-link-quickstart';
      
      // Constructor for the Admin Support
      function __construct () {
         
         // Call Parent Constructor - still need all frontend operations
         parent::__construct (); 

         $this->icon       = plugins_url('images/amazon-icon.png', $this->filename);
         $this->base_name  = plugin_basename( $this->filename );
         $this->plugin_dir = dirname( $this->base_name );
         $this->extras_dir = WP_PLUGIN_DIR . '/'. $this->plugin_dir. '/extras/';

         // Hook in the admin menu registration
         add_action ( 'admin_menu', array ( $this, 'admin_menu' ) );
         
         // Register hook to perform options installation
         register_activation_hook( __FILE__, array($this, 'install'));

      }

      /*
       * Install the Plugin Options.
       *
       * On activation of plugin - used to create default settings.
       */
      function install() {
         $opts = $this->get_default_settings();
         $this->saveOptions( $opts );
      }

      /*****************************************************************************************/
      // On WordPress initialisation - load text domain and register styles & scripts
      function init () {

         // Register default channel rule creation filter - needed for Upgrading Settings 7->8
         add_filter( 'amazon_link_save_channel_rule', array($this, 'create_channel_rules'), 10,4 );
         
         // Call Parent Inititialisation - still need to do frontend initialisation
         parent::init();
         
         /* load localisation  */
         load_plugin_textdomain( 'amazon-link', $this->plugin_dir . '/i18n', $this->plugin_dir . '/i18n' );

         /* Initialise dependent classes */
         $this->form = new AmazonWishlist_Options;
         $this->form->init( $this );                        // Need to register form styles
         
         if ( empty( $this->search ) ) {
            $this->search = new AmazonLinkSearch;
            $this->search->init( $this );                   // Need to register scripts & ajax callbacks
         }
         if ( empty( $this->ip2n ) ) {
            $this->ip2n = new AmazonWishlist_ip2nation;
            $this->ip2n->init( $this );
         }

         /* Register backend scripts */
         $edit_script  = $this->URLRoot."/postedit.js";
         $admin_script = $this->URLRoot."/include/amazon-admin.js";
         wp_register_script( 'amazon-link-edit-script',  $edit_script,  array('jquery', 'amazon-link-search'), $this->plugin_version );
         wp_register_script( 'amazon-link-admin-script', $admin_script, false, $this->plugin_version );
         
      }

      // If in admin section then register options page and required styles & metaboxes
      function admin_menu () {
         
         $submenus = $this->get_menus();

         // Add plugin options page, with load hook to bring in meta boxes and scripts and styles
         $this->menu = add_menu_page( __('Amazon Link Options', 'amazon-link'), __('Amazon Link', 'amazon-link'), 'manage_options',  $this->menu_slug, NULL, $this->icon, '102.375' );

         foreach ( $submenus as $slug => $menu ) {
            $ID = add_submenu_page( $this->menu_slug, $menu['Title'], $menu['Label'], $menu['Capability'],  $slug, array($this, 'show_settings_page'));
            $this->pages[$ID] = $menu;
            add_action( 'load-'.$ID, array(&$this, 'load_settings_page'));
            add_action( 'admin_print_styles-' . $ID, array($this,'amazon_admin_styles') );
            add_action( 'admin_print_scripts-' . $ID, array($this,'amazon_admin_scripts') );

            if ( isset( $menu['Scripts'] ) ) {
               foreach ( $menu['Scripts'] as $script )
                  add_action( 'admin_print_scripts-' . $ID, $script );
            }
            
            if ( isset( $menu['Styles'] ) ) {
               add_action( 'admin_print_styles-' . $ID, $menu['Styles'] );
            }
         }

         // Add support for Post edit metabox, this requires our styles and post edit AJAX scripts.
         $post_types = get_post_types();
         foreach ( $post_types as $post_type ) {
            add_meta_box( 'amazonLinkID', 'Add Amazon Link', array($this,'insertForm'), $post_type, 'normal' );
         }

         add_action( 'admin_print_scripts-post.php', array( $this,'edit_scripts' ) );
         add_action( 'admin_print_scripts-post-new.php', array( $this,'edit_scripts' ) );
         add_action( 'admin_print_styles-post-new.php', array( $this,'amazon_admin_styles' ) );
         add_action( 'admin_print_styles-post.php', array( $this,'amazon_admin_styles' ) );

         add_filter( 'plugin_row_meta', array( $this, 'register_plugin_links' ), 10, 2 );  // Add extra links to plugins page

         $settings = $this->get_default_settings();
         if ( ! empty( $settings['user_ids'] ) ) {
            add_action( 'show_user_profile', array( $this, 'show_user_options' ) );        // Display User Options
            add_action( 'edit_user_profile', array( $this, 'show_user_options' ) );        // Display User Options
            add_action( 'personal_options_update', array( $this, 'update_user_options' ) ); // Update User Options
            add_action( 'edit_user_profile_update', array( $this, 'update_user_options' ) );// Update User Options
         }
         
      }

      // Hooks required to bring up options page with meta boxes:
      function load_settings_page() {

         $screen = get_current_screen();

         if ( ! isset( $this->pages[$screen->id] ) ) return;

         $page = $this->pages[$screen->id];
         $slug = $page['Slug'];

         // Set default screen columns for this page.
         add_screen_option( 'layout_columns', array( 'default' => 2, 'max' => 2 ) );
                            
         wp_enqueue_script( 'common' );
         wp_enqueue_script( 'wp-lists' );
         wp_enqueue_script( 'postbox' );

         if ( isset( $page['Metaboxes'] ) ) {
            foreach( $page['Metaboxes'] as $id => $data ) {
               add_meta_box( $id, $data['Title'], $data['Callback'], $screen->id, $data['Context'], $data['Priority'], $this);
            }
         }

         add_meta_box( 'alInfo', __( 'About', 'amazon-link' ), array (&$this, 'show_info' ), $screen->id, 'side', 'core' );

         /* Help TABS only supported after version 3.3 */
         if ( ! method_exists( $screen, 'add_help_tab' ) ) {
            return;
         }

         // Add Contextual Help
         if ( isset( $page['Help'] ) ) {
            $tabs = include( $page['Help'] );
            foreach ( $tabs as $tab ) $screen->add_help_tab( $tab );
         }

         $screen->set_help_sidebar('<p><b>'. __('For more information:', 'amazon-link'). '</b></p>' .
                                   '<p><a target="_blank" href="'. $this->plugin_home . '">' . __('Plugin Home Page','amazon-link') . '</a></p>' .
                                   '<p><a target="_blank" href="'. $this->plugin_home . 'faq/">' . __('Plugin FAQ','amazon-link') . '</a></p>' .
                                   '<p><a target="_blank" title= "Guide on how to sign up for the various Amazon Programs" href="'. $this->plugin_home . 'getting-started">' . __('Getting Started','amazon-link') . '</a></p>' .
                                   '<p><a target="_blank" href="'. $this->plugin_home . 'faq/#channels">' . __('Channels Help','amazon-link') . '</a></p>' .
                                   '<p><a target="_blank" href="'. $this->plugin_home . 'faq/#templates">' . __('Template Help','amazon-link') . '</a></p>');

      }

      function amazon_admin_styles() {
         wp_enqueue_style('amazon-link-style');
         $this->form->enqueue_styles();
      }

      function amazon_admin_scripts() {
         wp_enqueue_script('amazon-link-admin-script');
      }

      function edit_scripts() {
         wp_enqueue_script('amazon-link-edit-script');
         /*
         * Need to pass details about all templates to the javascript...
         * - 'lang' => For translation extension
         * - 'T_<NAME>  => Keywords in template (So we can populate the shortcode with appropriate data)
         * - 'TC_<NAME> => Content of template (So we can insert template into post)
         * - 'template_live_keywords' => Live Keywords
         * - 'template_user_keywords' => User Keywords
         * - 'shortcode_template' => Template for inserted shortcode
         */
         $j_data = array();
         foreach ($this->get_country_data() as $cc => $data) {
            $j_data['lang'][$cc] = $data['lang'];
         }
         
         $Templates = $this->getTemplates();
         foreach ($Templates as $templateName => $Details) {
            $template_data = array();
            foreach ($this->get_keywords() as $keyword => $details) {
               if ((isset($details['Live']) || isset($details['User'])) && (stripos($Details['Content'], '%'.$keyword.'%')!==FALSE))
                  $template_data[] = $keyword;
            }
            $j_data['templates'][$templateName]['keywords'] = implode(',',$template_data);
            $j_data['templates'][$templateName]['content'] = htmlspecialchars_decode($Details['Content']);
         }
         
         $live_data = array();
         $user_data = array();
         foreach ($this->get_keywords() as $keyword => $details) {
            if (isset($details['Live']))
               $live_data[] = $keyword;
            if (isset($details['User']))
               $user_data[] = $keyword;
         }
         $j_data['template_live_keywords'] = implode(',',$live_data);
         $j_data['template_user_keywords'] = implode(',',$user_data);
         $j_data['templates'][' '] = array( 'keywords' => 'text', 'content' => '');
         $j_data['shortcode_template'] = apply_filters( 'amazon_link_shortcode_template', '[amazon %ARGS%]', $this);
         wp_localize_script( 'amazon-link-edit-script', 'AmazonLinkData', $j_data);
      }

      function register_plugin_links($links, $file) {
         if ($file == $this->base_name) {
            foreach ($this->pages as $page => $data) {
               $links[] = '<a href="admin.php?page=' . $data['Slug'].'">' . $data['Label'] . '</a>';
            }
         }
         return $links;
      }

/*****************************************************************************************/
      /// Display Content, Widgets and Pages
/*****************************************************************************************/

      function show_settings_page() {

         global $screen_layout_columns;
         $screen = get_current_screen();

         if ( ! isset( $this->pages[$screen->id] ) ) return;

         $page        = $screen->id;
         $data        = $this->pages[$page];
         $title       = $data['Title'];
         $description = $data['Description'];

         wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
         wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

?>
<div class="wrap">
  <h2><?php echo $title ?></h2>
   <p><?php echo $description ?></p>
   <div id="poststuff">
    <div id="post-body" class="metabox-holder columns-<?php echo $screen_layout_columns; ?>" >
     <div id="post-body-content">
      <?php do_meta_boxes( $page, 'normal',0 ); ?>
     </div>
     <div id="postbox-container-1" class="postbox-container">
      <?php do_meta_boxes( $page, 'side',0 ); ?>
     </div>
     <div id="postbox-container-2" class="postbox-container">
      <?php do_meta_boxes( $page, 'advanced',0 ); ?>
     </div>
    </div>
   <br class="clear"/>
  </div>
 </div>
<script type="text/javascript">
//<![CDATA[
 jQuery(document).ready( function($) {
  // close postboxes that should be closed
  $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
  // postboxes setup
  postboxes.add_postbox_toggles('<?php echo $page; ?>');
 });
//]]>
</script>
<?php
      }

/*****************************************************************************************/

      // Advanced Options Page
      function show_options() {
         include('showOptions.php');
      }

      // Basic Options Page
      function show_setup() {
         include('showSetup.php');
      }

      function show_settings_progress() {
         include( 'showProgress.php' );
      }
      // Extras Management Page
      function show_extras() {
         include('showExtras.php');
      }

      // User Options Page Hooks
      function show_user_options($user) {
         include('showUserOptions.php');
      }
      function update_user_options($user) {
         include('updateUserOptions.php');
      }

/*****************************************************************************************/

      function show_default_templates() {
         include('showDefaultTemplates.php');
      }

      function show_templates() {
         include('showTemplates.php');
      }

      function show_channels() {
         include('showChannels.php');
      }

      function show_info() {
         include('showInfo.php');
      }

/*****************************************************************************************/

      function show_template_help() {
         /*
          * Populate the help popup.
          */
         $text = __('<p>Hover the mouse pointer over the keywords for more information.</p>', 'amazon-link');

         $keywords = $this->get_keywords();
         ksort($keywords);

         foreach ( $keywords as $keyword => $details) {
            $title = (!empty($details['Description']) ? 'title="'. htmlspecialchars($details['Description']) .'"' : '');
            $text = $text . '<p><abbr '.$title.'>%' . strtoupper($keyword) . '%</abbr></p>';
         }
         echo $text;
      }

/*****************************************************************************************/

      // Page/Post Edit Screen Widget
      function insertForm($post, $args) {
         include('insertForm.php');
      }
      
      function get_default_templates() {

         if (!isset($this->default_templates)) {
            // Default templates
            include('defaultTemplates.php');
            $this->default_templates= apply_filters('amazon_link_default_templates', $this->default_templates);
         }
         return $this->default_templates;
      }

      /*****************************************************************************************/
      // User Options

      function get_user_options($ID) {
         $options = get_user_meta( self::user_options, $ID );
         return $options;
      }

      function save_user_options($ID, $options ) {
         $options = array_filter($options);
         if ( empty($options)) {
            delete_user_meta( $ID, self::user_options );
         } else {
            update_user_meta( $ID, self::user_options, $options );
         }
      }

      function get_user_option_list() {
        $option_list = array( 
            'title'       => array ( 'Type' => 'subhead', 'Value' => __('Amazon Link Associate IDs', 'amazon-link'), 'Description' => __('Valid associate IDs from all Amazon locales can be obtained from the relevant Amazon sites: ', 'amazon-link'), 'Class' => 'al_pad al_border'),
         );

         $country_data = $this->get_country_data();
         // Populate Country related options
         foreach ($country_data as $cc => $data) {
            $option_list ['tag_' . $cc] = array('Type' => 'text', 'Default' => '',
                                                'Name' => '<img style="height:14px;" src="'. $data['flag'] . '"> ' . $data['country_name'],
                                                'Hint' => sprintf(__('Enter your associate tag for %1$s.', 'amazon-link'), $data['country_name'] ));
            $option_list ['title']['Description'] .= '<a href="' . $data['site']. '">'. $data['country_name']. '</a>, ';
         }
         $option_list = apply_filters('amazon_link_user_option_list', $option_list, $this);
         return $option_list;
      }

      /*
       * Get all possible plugin options, these are also the arguments accepted by the shortcode.
       *
       * option_list array arguments:
       * Backend
       *    - Type:           Indicates how displayed on Options page (hidden options not saved to DB)
       *    - Value:          Usually the Data to be displayed (e.g. for title/nonce/section)
       *    - Class:          Class of Item in Form
       *    - Title_Class:    Class of Title in Form
       *    - Section_Class:  Class of a Section in Form
       *    - Name:           Label in Form for Item
       *    - Description:    Detailed Description of Item
       *    - Size:           Size of Text Item
       *    - Hint:           Detailed hint on mouse over
       *    - Options:        Options for Selection Item
       * Frontend
       *    - Default:        Default Value if Not Set
       *
       */
      function get_option_list( $option_list = array() ) {
     
         if (!isset($this->option_list)) {
            
            $option_list = array(
               
               /* Hidden Options - not saved in Settings */
               
               'nonce'             => array( 'Type' => 'nonce', 'Value' => 'update-AmazonLink-options' ),
               'cat'               => array( 'Type' => 'hidden' ),
               'last'              => array( 'Type' => 'hidden' ),
               'template'          => array( 'Type' => 'hidden' ),
               'chan'              => array( 'Type' => 'hidden' ),
               's_index'           => array( 'Type' => 'hidden' ),
               's_title'           => array( 'Type' => 'hidden' ),
               's_author'          => array( 'Type' => 'hidden' ),
               's_page'            => array( 'Type' => 'hidden' ),
               'template_content'  => array( 'Type' => 'hidden' ),
               'do_channels'       => array( 'Type' => 'calculated' ),
               /* Options that change how the items are displayed */
               'hd1s'              => array( 'Type' => 'section', 'Value' => __('Display Options', 'amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __('Change the default appearance and behaviour of the Links.','amazon-link'), 'Section_Class' => 'al_subhead1'),
               //               'text'              => array( 'Name' => __('Link Text', 'amazon-link'), 'Description' => __('Default text to display if none specified', 'amazon-link'), 'Type' => 'text', 'Size' => '40', 'Class' => 'al_border' ),
               'image_class'       => array( 'Name' => __('Image Class', 'amazon-link'), 'Description' => __('Style Sheet Class of image thumbnails', 'amazon-link'), 'Type' => 'text', 'Size' => '40', 'Class' => 'alternate al_border' ),
               'wishlist_template' => array( 'Name' => __('Wishlist Template', 'amazon-link') , 'Description' => __('Default template to use for the wishlist <em>* <a href="#aws_notes" title="AWS Access keys required for full functionality">AWS</a> *</em>', 'amazon-link'), 'Type' => 'selection', 'Class' => 'al_border'  ),
               'wishlist_items'    => array( 'Name' => __('Wishlist Length', 'amazon-link'), 'Description' => __('Maximum number of items to display in a wishlist (Amazon only returns a maximum of 5, for the \'Similar\' type of list) <em>* <a href="#aws_notes" title="AWS Access keys required for full functionality">AWS</a> *</em>', 'amazon-link'), 'Type' => 'text', 'Class' => 'alternate al_border' ),
               'wishlist_type'     => array( 'Name' => __('Wishlist Type', 'amazon-link'), 'Description' => __('Default type of wishlist to display, \'Similar\' shows items similar to the ones found, \'Random\' shows a random selection of the ones found <em>* <a href="#aws_notes" title="AWS Access keys required for full functionality">AWS</a> *</em>', 'amazon-link'), 'Options' => array('Similar', 'Random', 'Multi'), 'Type' => 'selection', 'Class' => 'al_border'  ),
               'new_window'        => array( 'Name' => __('New Window Link', 'amazon-link'), 'Description' => __('When link is clicked on, open it in a new browser window', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'alternate al_border' ),
               'link_title'        => array( 'Name' => __('Link Title Text', 'amazon-link'), 'Description' => __('The text to put in the link \'title\' attribute, can use the same keywords as in the Templates (e.g. %TITLE% %ARTIST%), leave blank to not have a link title.', 'amazon-link'), 'Type' => 'text', 'Size' => '40', 'Class' => 'al_border' ),
               'media_library'     => array( 'Name' => __('Use Media Library', 'amazon-link'), 'Description' => __('The plugin will look for and use thumbnails and images in the WordPress media library that are marked with an Amazon ASIN.', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'alternate' ),
               'hd1e'              => array( 'Type' => 'end'),

               /* Options that change how the Add Amazon Link Form behaves */
               'hd1as'             => array( 'Type' => 'section', 'Value' => __('\'Add Amazon Link\' Form Settings', 'amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __('Settings that change the behaviour of the \'Add Amazon Link\' helper form, located on the Post/Page edit screen.','amazon-link'), 'Section_Class' => 'al_subhead1'),
               'text'              => array( 'Name' => __('Link Text', 'amazon-link'), 'Description' => __('Default text to display if none specified', 'amazon-link'), 'Type' => 'text', 'Size' => '40', 'Class' => 'al_border' ),
               'form_template'     => array( 'Name' => __('Default Template', 'amazon-link'), 'Description' => __('The default template selected on the Add Amazon Link form', 'amazon-link'), 'Type' => 'selection', 'Size' => '40', 'Class' => 'alternate al_border' ),
               'form_channel'      => array( 'Name' => __('Default Channel', 'amazon-link') , 'Description' => __('The default channel to use when inserting new links. Note you do not need to set this for \'default\' channel to be automatically used.', 'amazon-link'), 'Type' => 'selection', 'Class' => 'al_border'  ),
               'form_s_index'      => array( 'Name' => __('Default Search Index', 'amazon-link'), 'Description' => __('The default search index to use when searching for products.', 'amazon-link'), 'Type' => 'selection', 'Class' => 'alternate al_border' ),
               'hd1ae'             => array( 'Type' => 'end'),

               /* Options that control localisation */
               'hd2s'          => array( 'Type' => 'section', 'Value' => __('Localisation Options', 'amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __('Control the localisation of data displayed and links created.','amazon-link'), 'Section_Class' => 'al_subhead1'),
               'ip2n_message'  => array( 'Type' => 'title', 'Title_Class' => 'al_para', 'Class' => 'al_pad al_border'),
               'default_cc'    => array( 'Name' => __('Default Country', 'amazon-link'), 'Hint' => __('The Amazon Associate Tags should be entered in the \'Associate IDs\' settings page.', 'amazon-link'),'Description' => __('Which country\'s Amazon site to use by default', 'amazon-link'), 'Type' => 'selection', 'Class' => 'alternate al_border' ),
               'localise'      => array( 'Name' => __('Localise Amazon Link', 'amazon-link'), 'Description' => __('Make the link point to the user\'s local Amazon website, (you must have ip2nation installed for this to work).', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'al_border' ),

               'plugin_ids'    => array( 'Name' => __('Plugin Associate IDs', 'amazon-link'), 'Description' => __('Support future plugin development by using the plugin\'s own associate IDs for locales for which you have not registered. This gives back to the developer and is free to you!', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'al_border' ),
               'search_link'   => array( 'Name' => __('Create Search Links', 'amazon-link'), 'Description' => __('Generate links to search for the items by "Artist Title" for non local links, rather than direct links to the product by ASIN.', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'alternate al_border' ),
               'home_links'    => array( 'Name' => __('Create Default Country Links', 'amazon-link'), 'Description' => __('If a product can not be found in the visitor\'s locale then provide links to the default Amazon locale instead. For this to work reliably you may need to enable the \'<a href="#prefetch">Prefetch Data</a>\'  option below.', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'al_border' ),
               'search_text'   => array( 'Name' => __('Default Search String', 'amazon-link'), 'Description' => __('Default items to search for with "Search Links", uses the same system as the Templates below.', 'amazon-link'), 'Type' => 'text', 'Size' => '40', 'Class' => 'alternate al_border' ),
               'search_text_s' => array( 'Type' => 'calculated' ),
               'multi_cc'      => array( 'Name' => __('Multinational Link', 'amazon-link'), 'Description' => __('Insert links to all other Amazon sites after primary link.', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'al_border'),
               'hd2e'          => array( 'Type' => 'end'),
               
               /* Options related to the Amazon backend */
               'hd3s'          => array( 'Type' => 'section', 'Id' => 'aws_notes', 'Value' => __('Amazon Associate Information','amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __('The AWS Keys are required for some of the features of the plugin to work (The ones marked with AWS above), visit <a href="http://aws.amazon.com/">Amazon Web Services</a> to sign up to get your own keys.', 'amazon-link'), 'Section_Class' => 'al_subhead1'),
               'pub_key'       => array( 'Name' => __('AWS Public Key', 'amazon-link'), 'Description' => __('Access Key ID provided by your AWS Account, found under Security Credentials/Access Keys of your AWS account', 'amazon-link'), 'Type' => 'text', 'Size' => '40', 'Class' => '' ),
               'priv_key'      => array( 'Name' => __('AWS Private key', 'amazon-link'), 'Description' => __('Secret Access Key ID provided by your AWS Account.', 'amazon-link'), 'Type' => 'text', 'Size' => '40', 'Class' => 'alternate' ),
               'aws_valid'     => array( 'Type' => 'checkbox', 'Read_Only' => 1, 'Name' => 'AWS Keys Validated', 'Class' => 'al_border'),
               'live'          => array( 'Name' => __('Live Data', 'amazon-link'), 'Description' => __('When creating Amazon links, use live data from the Amazon site, otherwise populate the shortcode with static information. <em>* <a href="#aws_notes" title="AWS Access keys required for full functionality">AWS</a> *</em>', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'al_border' ),
               'condition'     => array( 'Name' => __('Condition', 'amazon-link'), 'Description' => __('By default Amazon only returns Offers for \'New\' items, change this to return items of a different condition.', 'amazon-link'), 'Type' => 'selection',
                                         'Options' => array( '' => array('Name' => 'Use Default'), 'All' => array ('Name' => 'All'), 'New' => array('Name' => 'New'),'Used' => array('Name' => 'Used'),'Collectible' => array('Name' => 'Collectible'),'Refurbished' => array('Name' => 'Refurbished')),
                                         'Class' => 'alternate al_border' ),
               'prefetch'      => array( 'Name' => __('Prefetch Data', 'amazon-link'), 'Description' => __('For every product link, prefetch the data from the Amazon Site - use of the cache essential for this option! <em>* <a href="#aws_notes" title="AWS Access keys required for full functionality">AWS</a> *</em>', 'amazon-link'), 'Type' => 'checkbox', 'Class' => '' ),
               'user_ids'      => array( 'Name' => __('User Associate IDs', 'amazon-link'), 'Description' => __('Allow all users to have their own Associate IDs accessible from their profile page', 'amazon-link'), 'Type' => 'checkbox', 'Class' => 'alternate' ),
               'hd3e'          => array( 'Type' => 'end'),
               
               'hd4s'          => array( 'Type' => 'section', 'Value' => __('Amazon Caches','amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __('Improve page performance by caching Amazon product data and shortcode output.','amazon-link'), 'Section_Class' => 'al_subhead1'),
               'title3'        => array( 'Type' => 'title', 'Value' => __(' Product Cache','amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __(' Improve page performance when using large numbers of links by caching Amazon Product lookups.','amazon-link'), 'Class' => 'alternate'),
               'cache_age'     => array( 'Name' => __('Cache Data Age', 'amazon-link'), 'Description' => __('Max age in hours of the data held in the Amazon Link Cache', 'amazon-link'), 'Type' => 'text' ),
               'cache_enabled' => array( 'Type' => 'backend' ),
               'cache_c'       => array( 'Type' => 'buttons', 'Class' => 'al_border', 'Buttons' => array( __('Enable Cache', 'amazon-link' ) => array( 'Hint' => __('Install the sql database table to cache data retrieved from Amazon.', 'amazon-link'), 'Class' => 'button-secondary', 'Action' => 'AmazonLinkAction'),
                                                                                                          __('Disable Cache', 'amazon-link' ) => array( 'Hint' => __('Remove the Amazon Link cache database table.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'AmazonLinkAction'),
                                                                                                          __('Flush Cache', 'amazon-link' ) => array( 'Hint' => __('Delete all data in the Amazon Link cache.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'AmazonLinkAction'),
                                                                                                         )),
               'title4'           => array( 'Type' => 'title', 'Value' => __(' Shortcode Cache','amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __(' Reduce server load for high traffic sites by caching the shortcode expansion.','amazon-link'), 'Class' => 'alternate'),
               'sc_cache_age'     => array( 'Name' => __('SC Cache Data Age', 'amazon-link'), 'Description' => __('Max age in hours of the data held in the Amazon Link Shortcode Cache.', 'amazon-link'), 'Type' => 'text' ),
               'sc_cache_enabled' => array( 'Type' => 'backend' ),
               'sc_cache_c'       => array( 'Type' => 'buttons', 'Class' => 'al_border', 'Buttons' => array( __('Enable SC Cache', 'amazon-link' ) => array( 'Hint' => __('Install the sql database table to cache shortcode output.', 'amazon-link'), 'Class' => 'button-secondary', 'Action' => 'AmazonLinkAction'),
                                                                                                             __('Disable SC Cache', 'amazon-link' ) => array( 'Hint' => __('Remove the Amazon Link Shortcode cache database table.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'AmazonLinkAction'),
                                                                                                             __('Flush SC Cache', 'amazon-link' ) => array( 'Hint' => __('Delete all data in the Amazon Link Shortcode cache.', 'amazon-link'),'Class' => 'button-secondary', 'Action' => 'AmazonLinkAction'),
                                                                                                            )),
               'hd4e'          => array( 'Type' => 'end'),
               
               'hd5s'           => array( 'Type' => 'section', 'Value' => __('Advanced Options','amazon-link'), 'Title_Class' => 'al_section_head', 'Description' => __('Further options for debugging and Amazon Extras.','amazon-link'), 'Section_Class' => 'al_subhead1'),
               'template_asins' => array( 'Name' => __('Template ASINs', 'amazon-link'), 'Description' => __('ASIN values to use when previewing the templates in the templates manager.', 'amazon-link'), 'Default' => '0893817449,0500410607,050054199X,0500286426,0893818755,050054333X,0500543178,0945506562', 'Type' => 'text', 'Size' => '40', 'Class' => 'al_border' ),
               'debug'          => array( 'Name' => __('Debug Output', 'amazon-link'), 'Description' => __('Adds hidden debug output to the page source to aid debugging. <b>Do not enable on live sites</b>.', 'amazon-link'), 'Type' => 'checkbox', 'Size' => '40', 'Class' => 'alternate al_border' ),
               'full_uninstall' => array( 'Name' => __('Purge on Uninstall', 'amazon-link'), 'Description' => __('On uninstalling the plugin remove all Settings, Templates, Associate Tracking IDs, Cache Data & ip2nation Data .<b>Use when removing the plugin for good</b>.', 'amazon-link'), 'Type' => 'checkbox', 'Size' => '40', 'Class' => '' ),
               'hd5e'           => array( 'Type' => 'end')
            );
            
            $country_data = $this->get_country_data();
            // Populate Country related options
            foreach ($country_data as $cc => $data) {
               $option_list['default_cc']['Options'][$cc]['Name'] = $data['country_name'];
            }
            
         }
         
         parent::get_option_list($option_list);

         // Add submit button on Settings page
         $this->option_list['button'] = array( 'Type' => 'buttons', 'Buttons' => array( __('Update Options', 'amazon-link' ) => array( 'Class' => 'button-primary', 'Action' => 'AmazonLinkAction')));
         return $this->option_list;
      }

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

            $keywords = array(
             'link_open'    => array( 'Description' => __('Create an Amazon link to a product with user defined content, of the form %LINK_OPEN%My Content%LINK_CLOSE%', 'amazon-link') ),
             'rlink_open'   => array( 'Description' => __('Create an Amazon link to product reviews with user defined content, of the form %RLINK_OPEN%My Content%LINK_CLOSE%', 'amazon-link') ),
             'blink_open'   => array( 'Description' => __('Create an Amazon link to the authors biography page, of the form %BLINK_OPEN%My Content%LINK_CLOSE%', 'amazon-link') ),
             'slink_open'   => array( 'Description' => __('Create an Amazon link to a search page with user defined content, of the form %SLINK_OPEN%My Content%LINK_CLOSE%', 'amazon-link') ),
             'link_close'   => array( 'Description' => __('Must follow a LINK_OPEN (translates to "</a>").', 'amazon-link') ),

             'asin'         => array( 'Description' => __('Item\'s unique ASIN', 'amazon-link') ),
             'asins'        => array( 'Description' => __('Comma seperated list of ASINs', 'amazon-link') ),
             'product'      => array( 'Description' => __('Item\'s Product Group', 'amazon-link') ),
             'binding'      => array( 'Description' => __('Item\'s Format (Paperbook, MP3 download, etc.)', 'amazon-link') ),
             'features'     => array( 'Description' => __('Item\'s Features', 'amazon-link') ),
             'title'        => array( 'Description' => __('Item\'s Title', 'amazon-link') ),
             'artist'       => array( 'Description' => __('Item\'s Author, Artist or Creator', 'amazon-link') ),
             'manufacturer' => array( 'Description' => __('Item\'s Manufacturer', 'amazon-link') ),
             'thumb'        => array( 'Description' => __('URL to Thumbnail Image', 'amazon-link') ),
             'image'        => array( 'Description' => __('URL to Full size Image', 'amazon-link') ),
             'image_class'  => array( 'Description' => __('Class of Image as defined in settings', 'amazon-link') ),
             'search_text_s'=> array( 'Description' => __('Search Link Text (Escaped) from Settings Page', 'amazon-link') ),
             'search_text'  => array( 'Description' => __('Search Link Text from Settings Page', 'amazon-link') ),
             'url'          => array( 'Description' => __('The raw URL for a item\'s product page', 'amazon-link') ),
             'surl'         => array( 'Description' => __('The raw URL for a item\'s search page', 'amazon-link') ),
             'burl'         => array( 'Description' => __('The raw URL for a item\'s biography page', 'amazon-link') ),
             'rurl'         => array( 'Description' => __('The raw URL for a item\'s review page', 'amazon-link') ),
             'rank'         => array( 'Description' => __('Amazon Rank', 'amazon-link') ),
             'rating'       => array( 'Description' => __('Numeric User Rating - (No longer Available)', 'amazon-link') ),
             'offer_price'  => array( 'Description' => __('Best Offer Price of Item', 'amazon-link') ),
             'list_price'   => array( 'Description' => __('List Price of Item', 'amazon-link') ),
             'price'        => array( 'Description' => __('Price of Item (Combination of Offer then List Price)', 'amazon-link') ),

             'text'         => array( 'Description' => __('User Defined Text string', 'amazon-link') ),
             'text1'        => array( 'Description' => __('User Defined Text string', 'amazon-link') ),
             'text2'        => array( 'Description' => __('User Defined Text string', 'amazon-link') ),
             'text3'        => array( 'Description' => __('User Defined Text string', 'amazon-link') ),
             'text4'        => array( 'Description' => __('User Defined Text string', 'amazon-link') ),
             'pub_key'      => array( 'Description' => __('Amazon Web Service Public Access Key ID', 'amazon-link') ),
             'mplace'       => array( 'Description' => __('Localised Amazon Marketplace Code (US, GB, etc.)', 'amazon-link') ),
             'mplace_id'    => array( 'Description' => __('Localised Numeric Amazon Marketplace Code (2=uk, 8=fr, etc.)', 'amazon-link') ),
             'rcm'          => array( 'Description' => __('Localised RCM site host domain (rcm.amazon.com, rcm-uk.amazon.co.uk, etc.) DEPRECIATED', 'amazon-link') ),
             'region'       => array( 'Description' => __('Localised Amazon subdomain region for serving adverts, banners, and iframes ( eu, na, fe, cn, etc. )', 'amazon-link') ),
             'imp'          => array( 'Description' => __('Localised Amazon subdomain for processing impression tracking ( ir-uk, ir-na, etc. )', 'amazon-link') ),
             'buy_button'   => array( 'Description' => __('Localised Buy from Amazon Button URL', 'amazon-link') ),
             'language'     => array( 'Description' => __('Localised language (English,  etc.)', 'amazon-link') ),
                                                                               
             'tag'          => array( 'Description' => __('Localised Amazon Associate Tag', 'amazon-link') ),
             'chan'         => array( 'Description' => __('The ID of the channel used to generate this link', 'amazon-link') ),
             'cc'           => array( 'Description' => __('Localised Country Code (us, uk, etc.)', 'amazon-link') ),
             'flag'         => array( 'Description' => __('Localised Country Flag Image URL', 'amazon-link') ),
             'tld'          => array( 'Description' => __('Localised Top Level Domain (.com, .co.uk, etc.)', 'amazon-link') ),

             'downloaded'   => array( 'Description' => __('1 if Images are in the local WordPress media library', 'amazon-link') ),
             'found'        => array( 'Description' => __('1 if product was found doing a live data request (also 1 if live not enabled).', 'amazon-link') ),
             'count'        => array( 'Description' => __('When multiple ASIN\'s are used this is the count of which one is being shown', 'amazon-link') ),
             'timestamp'    => array( 'Description' => __('Date and time of when the Amazon product data was retrieved from Amazon.', 'amazon-link') )
            );
            
            parent::get_keywords( $keywords );
         }
         return $this->keywords;
      }

      function get_menus() {
         $menus = array('amazon-link-quickstart' => array( 'Slug' => 'amazon-link-quickstart', 
                                                           'Help' => WP_PLUGIN_DIR . '/'.$this->plugin_dir .'/'.'help/setup.php',
                                                           'Description' => __('Use this page to quickly get the main features of the plugin up and running. Use the Contextual Help tab above for more information about the settings.','amazon-link'),
                                                           'Title' => __('Amazon Link Setup', 'amazon-link'), 
                                                           'Label' =>__('Setup', 'amazon-link'), 
                                                           'Capability' => 'manage_options',
                                                           'Metaboxes' => array( 'alOptions' => array( 'Title' => __( 'Setup', 'amazon-link' ),
                                                                                                       'Callback' => array (&$this, 'show_setup' ), 
                                                                                                       'Context' => 'normal', 
                                                                                                       'Priority' => 'core'))
                                                           ),
                        'amazon-link-settings'   => array( 'Slug' => 'amazon-link-settings',
                                                           'Help' => WP_PLUGIN_DIR . '/'.$this->plugin_dir .'/'.'help/settings.php',
                                                           'Description' => __('Use this page to update the main Amazon Link settings to control the basic behaviour of the plugin, the appearance of the links and control the additional features such as localisation and the data cache. Use the Contextual Help tab above for more information about the settings.','amazon-link'),
                                                           'Title' => __('Amazon Link Settings', 'amazon-link'),
                                                           'Label' =>__('Settings', 'amazon-link'),
                                                           'Capability' => 'manage_options',
                                                           'Metaboxes' => array( /*'alStatus' => array( 'Title' => __( 'Status', 'amazon-link' ),
                                                                                                       'Callback' => array (&$this, 'show_settings_progress' ),
                                                                                                       'Context' => 'normal',
                                                                                                       'Priority' => 'high'),*/
                                                                                 'alOptions' => array( 'Title' => __( 'Options', 'amazon-link' ),
                                                                                                       'Callback' => array (&$this, 'show_options' ),
                                                                                                       'Context' => 'normal',
                                                                                                       'Priority' => 'core'))
                                                           ),
                        'amazon-link-channels'   => array( 'Slug' => 'amazon-link-channels', 
                                                           'Help' => WP_PLUGIN_DIR . '/'.$this->plugin_dir .'/'.'help/channels.php',
                                                           'Description' => __('If you have joined the Amazon Affiliate Program then on this page you can enter your Amazon Associate Tracking Identities. If you have more than one Tracking ID on each locale then you can create extra Channels to manage them.','amazon-link'),
                                                           'Title' => __('Manage Amazon Associate IDs', 'amazon-link'), 
                                                           'Label' =>__('Associate IDs', 'amazon-link'), 
                                                           'Capability' => 'manage_options',
                                                           'Metaboxes' => array( 'alChannels' => array( 'Title' => __( 'Amazon Tracking ID Channels', 'amazon-link' ),
                                                                                                        'Callback' => array (&$this, 'show_channels' ), 
                                                                                                        'Context' => 'normal', 
                                                                                                        'Priority' => 'core'))
                                                           ),
                        'amazon-link-templates'  => array( 'Slug' => 'amazon-link-templates',
                                                           'Help' => WP_PLUGIN_DIR . '/'.$this->plugin_dir .'/'.'help/templates.php',
                                                           'Description' => __('Use this page to manage your templates - pre-designed html and javascript code that can be used to quickly create consistant page content. Use the editor to modify existing templates, make copies, delete or add new ones of your own design.','amazon-link'),
                                                           'Title' => __('Manage Amazon Link Templates', 'amazon-link'), 
                                                           'Label' =>__('Templates', 'amazon-link'),
                                                           'Capability' => 'manage_options',
                                                           'Metaboxes' => array( 'alTemplateHelp' => array( 'Title' => __( 'Template Help', 'amazon-link' ),
                                                                                                       'Callback' => array (&$this, 'show_template_help' ), 
                                                                                                       'Context' => 'side', 
                                                                                                       'Priority' => 'low'),
                                                                                 'alTemplates' => array( 'Title' => __( 'Templates', 'amazon-link' ),
                                                                                                       'Callback' => array (&$this, 'show_templates' ), 
                                                                                                       'Context' => 'normal', 
                                                                                                       'Priority' => 'core'),
                                                                                 'alManageTemplates' => array( 'Title' => __( 'Default Templates', 'amazon-link' ),
                                                                                                       'Callback' => array (&$this, 'show_default_templates' ), 
                                                                                                       'Context' => 'normal', 
                                                                                                       'Priority' => 'low'))
                                                           ),
                        'amazon-link-extras'     => array( 'Slug' => 'amazon-link-extras',
                                                           'Help' => WP_PLUGIN_DIR . '/'.$this->plugin_dir .'/'.'help/extras.php',
                                                           'Description' => __('On this page you can manage user provided or requested extra functionality for the Amazon Link plugin. These items are not part of the main Amazon Link plugin as they provide features that not every user wants and may have a negative impact on your site (e.g. reduced performance, extra database usage, etc.).', 'amazon-link'),
                                                           'Title' => __('Manage Amazon Link Extras', 'amazon-link'), 
                                                           'Label' => __('Extras', 'amazon-link'), 
                                                           'Capability' => 'activate_plugins',
                                                           'Metaboxes' => array( 'alExtras' => array( 'Title' => __( 'Extras', 'amazon-link' ),
                                                                                                       'Callback' => array (&$this, 'show_extras' ), 
                                                                                                       'Context' => 'normal', 
                                                                                                       'Priority' => 'core'))
                                                           ));
         if ( empty($this->plugin_extras) ) unset($menus['amazon-link-extras']);
         return apply_filters( 'amazon_link_admin_menus', $menus, $this);
      }


/*****************************************************************************************/
      // Various Options, Arguments, Templates and Channels Handling
/*****************************************************************************************/

      /*
       * Admin version of get_default_settings, to check for upgrade
       */
      function get_default_settings() {
         
         if ( ! isset( $this->default_settings ) ) {
            parent::get_default_settings();

            if ( ! isset( $this->default_settings['version'] ) ||
                 ( $this->default_settings['version'] < $this->option_version ) )
            {
               $this->upgrade_settings();
            }
            parent::get_default_settings();
         }
            
         return $this->default_settings;
      }

      function saveOptions( $options ) {
         $option_list = $this->get_option_list();
         if ( ! is_array( $options ) ) {
            return;
         }
         // Ensure hidden items are not stored in the database
         foreach ( $option_list as $optName => $optDetails ) {
            if ($optDetails['Type'] == 'hidden') unset($options[$optName]);
         }
   
         if (!empty($options['search_text'])) {

            $search_text_s = $options['search_text'];
            $keywords = $this->get_keywords();
            foreach ($keywords as $keyword => $key_data) {
               $keyword = strtoupper($keyword);
               $search_text_s = str_ireplace('%'.$keyword. '%', '%' .$keyword. '%S#', $search_text_s);
            }
            $options['search_text_s'] = $search_text_s;
         }
         $options['plugin_extras'] = $options['plugin_ids'];
         update_option(self::optionName, $options);
         $this->default_settings = $options;
      }

      function upgrade_settings() {
         include('upgradeSettings.php');
      }

      /*
       * Store Templates array in WordPress options
       *
       */
      function saveTemplates ( $templates ) {
         if ( ! is_array ( $templates ) ) {
            return;
         }
         ksort ( $templates );
         update_option ( self::templatesName, $templates );
         $this->Templates = $templates;
      }

      function create_channel_rules($rules, $channel, $data, $al)
      {
         // Extract rules 'rand = xx <CR> cat = aa,bb,cc <CR> tag = dd,ee,ff <CR> author = ID <CR> type = TYPE'

         if (empty($data['Filter'])) return $rules;

         preg_match('~rand\s*=\s*(?P<rand>\d*)~i', $data['Filter'], $matches);
         if (!empty($matches['rand']))
            $rules['rand'] = $matches['rand'];

         $author = preg_match('~author\s*=\s*(?P<author>\w*)~i', $data['Filter'], $matches);
         if (!empty($matches['author'])) {
            if ( ! is_numeric($matches['author'])) {
               $author = get_user_by('slug', $matches['author']);
               if ($author) $matches['author'] = $author->ID;
            }
            $rules['author'] = $matches['author'];
         }

         $type   = preg_match('~type\s*=\s*(?P<type>\w*)~i', $data['Filter'], $matches);
         if (!empty($matches['type']))
            $rules['type'] = $matches['type'];

         $cat    = preg_match('~cat\s*=\s*(?P<cat>(\w*)(\s*,\s*(\w*))*)~i', $data['Filter'], $matches);
         if (!empty($matches['cat']))
            $rules['cat'] = array_map('trim',explode(",",$matches['cat']));

         $tag    = preg_match('~tag\s*=\s*(?P<tag>(\w*)(\s*,\s*(\w*))*)~i', $data['Filter'], $matches);
         if (!empty($matches['tag']))
            $rules['tag'] = array_map('trim',explode(",",$matches['tag']));

         return $rules;

      }

      function save_channels($channels) {
         
         if (!is_array($channels)) {
            return;
         }
         $options = get_option( self::optionName, array() );
         
         $defaults = $channels['default'];
         unset($channels['default']);
         ksort($channels);
         $channels = array('default' => $defaults) + $channels;
         $options['do_channels'] = 0;
         foreach ($channels as $channel => &$data) {
            $data = array_filter($data);
            $data['Rule'] = apply_filters( 'amazon_link_save_channel_rule', array(), $channel, $data, $this);
            
            // If multiple channels enabled then enable channel filters
            if ( ($channel != 'default') && ( empty($data['user_channel']) || ! empty($options['user_ids'] ) ) ) {
               $options['do_channels'] = 1;
            }
         }
         
         update_option( self::channels_name, $channels );
         update_option( self::optionName, $options );
         unset($this->default_settings);

         $this->channels = $channels;
      }
      
      function validate_keys($Settings = NULL) {
         if ($Settings === NULL) $Settings = $this->getSettings();

         $result['Valid'] = 0;
         if (empty($Settings['pub_key']) || empty($Settings['priv_key'])) {
            $result['Message'] = "Keys not set";
            return $result;
         }
         $result['Message'] = 'AWS query failed to get a response - try again later.';
         $request = array('Operation'     => 'ItemSearch', 
                          'ResponseGroup' => 'ItemAttributes',
                          'SearchIndex'   =>  'All', 'Keywords' => 'el|la');
         //$Settings['default_cc'] = 'uk';
         $Settings['localise'] = '0';
         $pxml = $this->doQuery($request, $Settings);

         if (isset($pxml['Items']['Item'])) {
            $result['Valid'] = 1;
         } else if (isset($pxml['Items']['Request']['Errors']['Error'])) {
            $result['Valid'] = 0;
            $result['Message'] = $pxml['Items']['Request']['Errors']['Error']['Message'];
         } else if (isset($pxml['Error'])) {
            $result['Valid'] = 0;
            $result['Message'] = $pxml['Error']['Message'];
         }
         return $result;
      }

/*****************************************************************************************/
      // Cache Facility
/*****************************************************************************************/

      function cache_install() {
         global $wpdb;
         $settings = $this->get_default_settings();
         if (!empty($settings['cache_enabled'])) return False;
         $cache_table = $wpdb->prefix . self::cache_table;
         $sql = "CREATE TABLE $cache_table (
                 asin varchar(10) NOT NULL,
                 cc varchar(5) NOT NULL,
                 updated datetime NOT NULL,
                 xml blob NOT NULL,
                 PRIMARY KEY  (asin, cc)
                 );";
         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         dbDelta($sql);
         $settings['cache_enabled'] = 1;
         $this->saveOptions($settings);
         return True;
      }

      function cache_remove() {
         global $wpdb;

         $settings = $this->get_default_settings();
         if (empty($settings['cache_enabled'])) return False;
         $settings['cache_enabled'] = 0;
         $this->saveOptions($settings);

         $cache_table = $wpdb->prefix . self::cache_table;
         $sql = "DROP TABLE $cache_table;";
         $wpdb->query($sql);
         return True;
      }

      function cache_empty() {
         global $wpdb;

         $settings = $this->get_default_settings();
         if (empty($settings['cache_enabled'])) return False;

         $cache_table = $wpdb->prefix . self::cache_table;
         $sql = "TRUNCATE TABLE $cache_table;";
         $wpdb->query($sql);
         return True;
      }

      function cache_flush() {
         global $wpdb;
         $settings = $this->get_default_settings();
         if (empty($settings['cache_enabled']) || empty($settings['cache_age'])) return False;
         $cache_table = $wpdb->prefix . self::cache_table;
         $sql = "DELETE FROM $cache_table WHERE updated < DATE_SUB(NOW(),INTERVAL " . $settings['cache_age']. " HOUR);";
         $wpdb->query($sql);
      }

/*****************************************************************************************/
      // Shortcode Cache Facility
/*****************************************************************************************/

      function sc_cache_install() {
         global $wpdb;
         $settings = $this->get_default_settings();
         if (!empty($settings['sc_cache_enabled'])) return False;
         $cache_table = $wpdb->prefix . self::sc_cache_table;
         $sql = "CREATE TABLE $cache_table (
                 cc varchar(5) NOT NULL,
                 postid bigint(20) NOT NULL,
                 hash varchar(32) NOT NULL,
                 updated datetime NOT NULL,
                 args text NOT NULL,
                 content blob NOT NULL,
                 PRIMARY KEY  (hash, cc, postid)
                 );";
         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         dbDelta($sql);
         $settings['sc_cache_enabled'] = 1;
         $this->saveOptions($settings);
         return True;
      }

      function sc_cache_remove() {
         global $wpdb;

         $settings = $this->get_default_settings();
         if (empty($settings['sc_cache_enabled'])) return False;
         $settings['sc_cache_enabled'] = 0;
         $this->saveOptions($settings);

         $cache_table = $wpdb->prefix . self::sc_cache_table;
         $sql = "DROP TABLE $cache_table;";
         $wpdb->query($sql);
         return True;
      }

      function sc_cache_empty() {
         global $wpdb;

         $settings = $this->get_default_settings();
         if (empty($settings['sc_cache_enabled'])) return False;

         $cache_table = $wpdb->prefix . self::sc_cache_table;
         $sql = "TRUNCATE TABLE $cache_table;";
         $wpdb->query($sql);
         return True;
      }

      function sc_cache_flush() {
         global $wpdb;
         $settings = $this->get_default_settings();
         if (empty($settings['sc_cache_enabled']) || empty($settings['sc_cache_age'])) return False;
         $cache_table = $wpdb->prefix . self::sc_cache_table;
         $sql = "DELETE FROM $cache_table WHERE updated < DATE_SUB(NOW(),INTERVAL " . $settings['sc_cache_age']. " HOUR);";
         $wpdb->query($sql);
      }

   } // End Class

} // End if exists

// vim:set ts=4 sts=4 sw=4 st:
?>
