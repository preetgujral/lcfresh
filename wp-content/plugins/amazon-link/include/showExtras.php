<?php
/*****************************************************************************************/

/*
 * Admin Extras Panel Processing
 *
 */

/*****************************************************************************************/

   // Create Array of Potential amazon-link-extra Plugins from 'extras' directory
   // 
   // Check Directory Exists
   // Get List of Files
   // Get Plugin Details for Each File
   $avail_plugins = array();
   $files = glob($this->extras_dir .'*');

   foreach ((array)$files as $file) {
      $plugin = basename($file);
      $avail_plugins[$plugin] = get_file_data($file, array( 'Name' => 'Plugin Name',
 'PluginURI' => 'Plugin URI',
 'Version' => 'Version',
 'Description' => 'Description',
 'Author' => 'Author',
 'AuthorURI' => 'Author URI',
 'TextDomain' => 'Text Domain'));
   }

   // Create Array of Installed amazon-link-extra Plugins from 'plugin' directory
   // 
   // Get List of Plugins matching 'amazon-link-*'
   // Get Plugin Details for Each File and Activation Status
   $installed_plugins = array();
   $files = glob(WP_PLUGIN_DIR .'/amazon-link-*');
   foreach ((array)$files as $file) {
      $plugin = basename($file);
      $installed_plugins[$plugin] = get_file_data($file, array( 'Name' => 'Plugin Name',
 'PluginURI' => 'Plugin URI',
 'Version' => 'Version',
 'Description' => 'Description',
 'Author' => 'Author',
 'AuthorURI' => 'Author URI',
 'TextDomain' => 'Text Domain'));
      $installed_plugins[$plugin]['Activated'] = is_plugin_active($plugin);
   }


/*****************************************************************************************/

   // Get the Plugin ID if selected.
   if (isset($_POST['ID'])) {
      $plugin_ID=$_POST['ID'];
   }

   $action = (isset($_POST[ 'ALExtrasAction' ]) && check_admin_referer( 'update-AmazonLink-extras')) ?
                      $_POST[ 'ALExtrasAction' ] : 'No Action';

   // See if the user has posted us some information
   // If they did, the admin Nonce should be set.
   $update = False;
   $error  = False;

   // **********************************************************
   // Install the plugin into the WordPress plugin directory

   if(  ($action == __('Install', 'amazon-link') ) || ( $action == __('Update','amazon-link'))) {
      $result = copy ($this->extras_dir . $plugin_ID, WP_PLUGIN_DIR .'/'. $plugin_ID);
      if ($result) {
         $update = sprintf( __('Plugin %1$s - has been Installed', 'amazon-link'), $avail_plugins[$plugin_ID]['Name'] );
         $installed_plugins[$plugin_ID] = $avail_plugins[$plugin_ID];
         $action = __('Activate','amazon-link');
      } else {
         $error = sprintf( __('Plugin %1$s - failed to Install', 'amazon-link'), $avail_plugins[$plugin_ID]['Name'] );
      }
   } 

   // **********************************************************
   // Activate the selected plugin
   
   if ( $action == __('Activate','amazon-link')) {
      $result = activate_plugins($plugin_ID);
      if (!is_wp_error($result)) {
         if ($update !== False) {
            $update .= __(' and Activated', 'amazon-link');
         } else {
            $update = sprintf( __('Plugin %1$s - has been Activated', 'amazon-link'), $installed_plugins[$plugin_ID]['Name'] );
         }
         $installed_plugins[$plugin_ID]['Activated'] = True;
      } else {
         $error = $plugin_ID . "---" .$result->get_error_message();
         $installed_plugins[$plugin_ID]['Activated'] = False;
      }

   // **********************************************************
   // Uninstall the selected plugin

   } 

   // **********************************************************
   // Deactivate the selected plugin

   if ( $action == __('Deactivate', 'amazon-link') || ( $action == __('Uninstall', 'amazon-link'))) {
      $result = deactivate_plugins($plugin_ID);
      if (!is_wp_error($result)) {
         $update = sprintf( __('Plugin %1$s - has been Deactivated', 'amazon-link'), $installed_plugins[$plugin_ID]['Name'] );
         if (isset($installed_plugins[$plugin_ID])) $installed_plugins[$plugin_ID]['Activated'] = False;
      } else {
         $error = $result->get_error_message();
      }
   }
   
   if ( $action == __('Uninstall', 'amazon-link')) {
      $result = delete_plugins((array)$plugin_ID);
      if (!is_wp_error($result)) {
         if ($update !== False) {
            $update .= __(' and Uninstalled', 'amazon-link');
         } else {
            $update = sprintf( __('Plugin %1$s - has been Uninstalled', 'amazon-link'), $installed_plugins[$plugin_ID]['Name'] );
         }
         unset($installed_plugins[$plugin_ID]);
      } else {
         $error = $result->get_error_message();
      }
   } 

   // **********************************************************

/*****************************************************************************************/

   // **********************************************************
   // Put an update/error message on the screen as appropriate
   if ($update !== False) {
      echo '<div id="message" class="updated"><p>' . $update. '</p></div>';
      $this->cache_empty();
   }

   if ($error !== False) {
      echo '<div id="message" class="error"><p>' . $error . '</p></div>';
   }

/*****************************************************************************************/

   // Create a Merged List of all Plugin 'ID's
   $plugins = array_merge((array)$installed_plugins, (array)$avail_plugins);


   $plugin_opts = array( 
         'nonce'       => array ( 'Type' => 'nonce', 'Value' => 'update-AmazonLink-extras' ),

         'ID'          => array ( 'Default' => '', 'Type' => 'hidden'),
         'title'       => array ( 'Type' => 'subhead', 'Value' => '', 'Class' => 'al_section', 'Title_Class' => 'al_subhead'),
         );

   // Create Options Table Listing Each Plugin
   // For each ID from the Merged List:
   // ID
   // Title including:
   //    Value = Name
   //    Description = Title + Versions (Installed/Available)
   //    Buttons =
   //       Install (If not in Installed List)
   //       Update (If in both but version mismatch)
   //       Activate  (If in Installed but not activated)
   //       Deactivate (If in Installed and activated)
   //       Uninstall (If in Installed)
   foreach ($plugins as $plugin => $plugin_data) {
      unset($plugin_opts['title']['Buttons'][__('Install', 'amazon-link')]);
      unset($plugin_opts['title']['Buttons'][__('Update', 'amazon-link')]);
      unset($plugin_opts['title']['Buttons'][__('Activate', 'amazon-link')]);
      unset($plugin_opts['title']['Buttons'][__('Deactivate', 'amazon-link')]);
      unset($plugin_opts['title']['Buttons'][__('Uninstall', 'amazon-link')]);
      $installed = False;
      if (array_key_exists($plugin,$installed_plugins)) {
         $installed = $installed_plugins[$plugin]['Version'];
      }
      $available = False;
      if (array_key_exists($plugin,$avail_plugins)) {
         $available = $avail_plugins[$plugin]['Version'];
      }
      $version = sprintf( __( '( Version installed: %1$s / available: %2$s )', 'amazon-link' ), $installed, $available );
      $plugin_opts['ID']['Default'] = $plugin;
      $plugin_opts['title']['Value'] = '<b>'. $plugin_data['Name'] .'</b>';
      $plugin_opts['title']['Description'] = $plugin_data['Description'] .' - '. $version;

      $plugin_opts['title']['Class'] =  'al_section';
      if (!$installed) {
         $plugin_opts['title']['Buttons'][__('Install', 'amazon-link')] = array( 'Action' => 'ALExtrasAction', 'Hint' => __( 'Install this plugin', 'amazon-link'), 'Class' => 'button-secondary');
      }
      elseif ($available > $installed) {
         $plugin_opts['title']['Buttons'][__('Update', 'amazon-link')] = array( 'Action' => 'ALExtrasAction', 'Hint' => __( 'Upgrade this plugin to the latest version', 'amazon-link'), 'Class' => 'button-secondary');
         $plugin_opts['title']['Class'] =  'al_section al_highlight';
      }
      if ($installed) {
         if ($installed_plugins[$plugin]['Activated']) {
            $plugin_opts['title']['Buttons'][__('Deactivate', 'amazon-link')] = array( 'Action' => 'ALExtrasAction', 'Hint' => __( 'Activate this plugin', 'amazon-link'), 'Class' => 'button-secondary');
         } else {
            $plugin_opts['title']['Buttons'][__('Activate', 'amazon-link')] = array( 'Action' => 'ALExtrasAction', 'Hint' => __( 'Activate this plugin', 'amazon-link'), 'Class' => 'button-secondary');
         }
         if ($available) {
            $plugin_opts['title']['Buttons'][__('Uninstall', 'amazon-link')] = array( 'Action' => 'ALExtrasAction', 'Hint' => __( 'Uninstall this plugin - delete the plugin file', 'amazon-link'), 'Class' => 'button-secondary');
         }
      }

      $this->form->displayForm($plugin_opts, array());
   }

?>
