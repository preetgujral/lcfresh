<?php
/*****************************************************************************************/

   /*
    * Quickstart Setup Admin Page
    */
   $opts         = $this->get_default_settings();
   $channels     = $this->get_channels();
   $full_options = $this->get_option_list();
   $country_data = $this->get_country_data();
   
   /* Create default channel if it does not exist */
   if ( empty ($channels['default']) ) {
      $channels['default'] = array('Name' => 'Default', 'Description' => 'Default Affiliate IDs', 'Filter' => '');
   }
   
   /*
    * Create a subset of the main options but also the default channel.
    *
    * Default Locale
    * Affiliate IDs
    * ip2nation (auto set localisation)
    * Support Development Option
    * AWS keys (auto set live data)
    * Product Cache
    */

   $options['nonce'] = $full_options['nonce'];
   $options['dc_head'] = array( 'Type' => 'section', 'Value' => __( '1. First select the country of the Amazon site that is most relevant to your site & language.', 'amazon-link'), 'Title_Class' => 'al_subhead2' );
   $options['default_cc'] = $full_options['default_cc'];
   $options['default_cc']['Description'] = __( 'Select the country that is the main market for your site. This controls which Amazon site is used to search for products as well as the default one used for retrieving live product information (price, rank, title, etc.).', 'amazon-link' );
   $options['default_cc']['Class'] = 'al_pad_top al_pad_bottom';
   $options['dc_end'] = array( 'Type' => 'end');
   
   $options['tg_head'] = array( 'Type' => 'section', 'Value' => __( '2. Enter your Amazon Affiliate IDs to earn commission on any Amazon sales. Valid Affiliate IDs can be obtained from the relevant Amazon sites: ', 'amazon-link'), 'Title_Class' => 'al_subhead2' );
   
   // Populate Country related options
   $pad = 'al_pad_top';
   $index = 1;
   foreach ($country_data as $cc => $data) {
      if ( $index == count($country_data) ) $pad = 'al_pad_bottom';
      $options['tag_' . $cc] = array('Type' => 'text', 'Default' => '', 'Class' => $pad,
                                     'Name' => '<img style="height:14px;" src="'. $data['flag'] . '"> ' . $data['country_name'],
                                     'Hint' => sprintf(__('Enter your affiliate tag for %1$s.', 'amazon-link'), $data['country_name'] ));
      $pad = ''; $index++;
      $options['tg_head']['Value'] .= '<a href="' . $data['site']. '">'. $data['country_name']. '</a>, ';
   }
   $options['tg_end'] = array( 'Type' => 'end');

   $options['lc_head'] = array( 'Type' => 'section', 'Value' => __( '3. Support visitors from other countries by enabling localisation and installing the ip2nation database.', 'amazon-link'), 'Title_Class' => 'al_subhead2' );   
   $options['localise'] = $full_options['localise'];
   $options['localise']['Class'] = 'al_pad_top al_pad_bottom';
   $options['lc_end'] = array( 'Type' => 'end');

   $options['dev_head'] = array( 'Type' => 'section', 'Value' => '4. ' . $full_options['plugin_ids']['Description'], 'Title_Class' => 'al_subhead2' );
   $options['plugin_ids'] = $full_options['plugin_ids'];
   $options['plugin_ids']['Description'] = '';
   $options['plugin_ids']['Class'] = 'al_pad_top al_pad_bottom';
   $options['dev_end'] = array( 'Type' => 'end');
   $options['xtr_head'] = array( 'Type' => 'section', 'Value' => '5. Plugin Extras Support - allows custom features to be added to the plugin. For example image and thumbnail size control, link cloaking, extra keywords (rating, live price, genre, editorial comment), and custom made features.', 'Title_Class' => 'al_subhead2' );
   $options['plugin_extras'] = array( 'Name' => __( 'Plugin Extra Feature', 'amazon-link'), 'Type' => 'checkbox', 'Read_Only' => '1');
   $options['plugin_extras']['Class'] = 'al_pad_top al_pad_bottom';
   $options['xtr_end'] = array( 'Type' => 'end');
   
   $options['aws_head'] = array( 'Type' => 'section', 'Value' => __( '6. To get the most out of the plugin it is recommended that you register for the Amazon Advertising API (its free!) and enter your keys here. Visit <a href="https://affiliate-program.amazon.com/gp/advertising/api/detail/main.html">Amazon Web Services</a> to sign up to get your own keys.', 'amazon-link'), 'Title_Class' => 'al_subhead2' );   
   $options['pub_key'] = $full_options['pub_key'];
   $options['pub_key']['Class'] = 'al_pad_top';
   $options['priv_key'] = $full_options['priv_key'];
   $options['priv_key']['Class'] = '';
   $options['aws_valid'] = $full_options['aws_valid'];
   $options['aws_valid']['Class'] = 'al_pad_bottom';
   $options['aws_end'] = array( 'Type' => 'end');
   
   $options['ch_head'] = array( 'Type' => 'section', 'Value' => __( '7. Improve site performance by enabled and installing the Amazon Link Cache.', 'amazon-link'), 'Title_Class' => 'al_subhead2' );   
   $options['cache_enabled'] = array( 'Type' => 'checkbox', 'Class' => 'al_pad_top al_pad_bottom', 'Name' => __('Enable Data Cache', 'amazon-link'), 'Description' => __('', 'amazon-link'));
   $options['ch_end'] = array( 'Type' => 'end');

   $options['button'] = $full_options['button'];
   
/*****************************************************************************************/

   // See if the user has posted us some information
   // If they did, the admin Nonce should be set.
   $Action = (isset($_POST[ 'AmazonLinkAction' ]) && check_admin_referer( 'update-AmazonLink-options')) ?
                      $_POST[ 'AmazonLinkAction' ] : 'No Action';
   
   $updates = array();
   if(  $Action == __('Update Options', 'amazon-link') ) {

      // Update subset of options
      foreach ($options as $opt => $opt_data) {
         if ( isset($opt_data['Name']) && empty($opt_data['Read_Only']) ) {
            
            if ( ! isset($_POST[$opt]) ) {
               $_POST[$opt] = NULL;
            }
            
            // Some options changes trigger futher actions below
            if ( ($opt == 'localise') ) {
               
               if ( empty($opts[$opt]) != empty($_POST[$opt]) ) {
                  $remove_ip2nation = ! $install_ip2nation = $_POST[$opt];
               }
               $opts[$opt] = stripslashes($_POST[$opt]);
               
            } else if ( ($opt == 'cache_enabled') ) {
               
               if ( empty($opts[$opt]) != empty($_POST[$opt]) ) {
                  $remove_cache = ! $install_cache = $_POST[$opt];
               }
               $opts[$opt] = stripslashes($_POST[$opt]);
               
            } else if ( ($opt == 'pub_key') || ($opt == 'priv_key') ) {
               
               if ( $opts[$opt] != stripslashes($_POST[$opt]) ) {
                  $AWS_keys_updated = 1;
               }
               $opts[$opt] = trim(stripslashes($_POST[$opt]));
               
            } else {
               $opts[$opt] = stripslashes($_POST[$opt]);
            }
         } 
      }
      // Update Default Channel
      foreach ( $country_data as $cc => $data ) {
         $channels['default']['tag_' . $cc] = $_POST['tag_' . $cc];
         unset($opts['tag_'. $cc]);
      }
      
      $updates[] = __('Options saved.', 'amazon-link' );
   }

/*****************************************************************************************/
   if ( ! empty($install_ip2nation) ) {

      // User requested installation of the ip2nation database
      $url = wp_nonce_url('admin.php?page=amazon-link-quickstart','update-AmazonLink-options');
      $args = array_keys($_POST);
      //echo "<PRE>args:"; print_r($args); echo "</pRE>";
      $install = $this->ip2n->install( $url, $args );
      if (!empty($install['HideForm'])) {
         echo "</br></br>";
         return;
      }
      $updates[] = $install['Message'];
      $opts['localise'] = $install['Success'];
      
   } else if ( ! empty ($remove_ip2nation) ) {

      // User requested Uninstallation of the ip2nation database
      $updates[] = $this->ip2n->uninstall();
   }
   
/*****************************************************************************************/

   // Cache Options
   if ( ! empty($install_cache) ) {
      if ($this->cache_install()) {
         $updates[] = __('Amazon Data Cache Enabled', 'amazon-link');
         $opts['cache_enabled'] = 1;
      } else {
         $opts['cache_enabled'] = 0;      
         $updates[] = __('Amazon Data Cache Failed to Install', 'amazon-link');
      }
   } else if ( ! empty($remove_cache) ) {
      if ($this->cache_remove()) {
         $updates[] = __('Amazon Data Cache Disabled and Removed', 'amazon-link');
         $opts['cache_enabled'] = 0;
      }
   }
   
   // If Amazon Data Cache Enabled then take the opportunity to flush old data
   if (!empty($opts['cache_enabled'])) {
      $this->cache_flush();
   }

/*****************************************************************************************/

   /* AWS Keys not yet validate, do a dummy request to see if we get any errors */
   if ( ! empty($opts['pub_key']) ) {
      if ( isset($AWS_keys_updated) || empty($opts['aws_valid']) ) {
         $result = $this->validate_keys($opts);
         $opts['aws_valid'] = $result['Valid'];

         $opts['live'] = $result['Valid'];
         if ( $result['Valid'] ) {
         } else {
            $options['aws_valid']['Description'] = '<span style="color:red">' .
                                                       __('AWS Request Failed, please check keys - Error Message: ','amazon-link') .
                                                       $result['Message'] . 
                                                       '</span>';
         }
         $updates[] = __( 'AWS Keys checked', 'amazon-link');
      }
   } else {
      $opts['aws_valid'] = 0;
      $opts['live'] = 0;
   }

/*****************************************************************************************/

   if ( ! empty ($updates) ) {

      // **********************************************************
      // Put an options updated message on the screen
      if (current_user_can('manage_options')) 
      {
         $this->saveOptions($opts);
         $this->save_channels($channels);
      }
      
      foreach ( $updates as $update ) {
?>
<div class="updated">
   <p><strong><?php echo $update; ?></strong></p>
</div>
<?php
      }
   }
 

/*****************************************************************************************/

   foreach ($country_data as $cc => $data) {
      if ( isset($channels['default']['tag_' . $cc]) ) {
         $opts['tag_' . $cc ] = $channels['default']['tag_' . $cc];
      }
   }

   /* localisation only 'enabled' if selected AND database is installed */
   $ip2n_status = $this->ip2n->status();
   $opts['localise'] = ($ip2n_status['Uninstall'] && $opts['localise']);
   $options['localise']['Description'] = $ip2n_status['Message'];

   /* plugin_extras only supported if plugin_ids is enabled. */
   if ( !empty($opts['plugin_ids']) ) {
      $opts['plugin_extras'] = '1';
      $options['plugin_extras']['Description'] = __( 'This option is enabled by default with developer support.', 'amazon-link');
   } else {
      unset($opts['plugin_extras']);
      $options['plugin_extras']['Description'] = __( 'This option is only enabled when developer support above is enabled.', 'amazon-link');
   }
/*****************************************************************************************/

   // **********************************************************
   // Now display the options editing screen

   $this->form->displayForm($options, $opts);

?>
