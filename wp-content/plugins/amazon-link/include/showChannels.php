<?php
/*****************************************************************************************/

/*
 * Channel Option Panel Processing
 *
 */
   $channels = $this->get_channels(False);
   $settings = $this->get_default_settings();

   $channel_opts = array( 
         'nonce'       => array ( 'Type' => 'nonce', 'Value' => 'update-AmazonLink-channels' ),

         'ID'          => array ( 'Type' => 'hidden'),
         'title'       => array ( 'Type' => 'section', 'Value' => '', 'Class' => '', 'Section_Class' => 'al_subhead'),
         'Name'        => array ( 'Type' => 'text', 'Name' => __('Channel Name', 'amazon-link'), 'Default' => 'Channel', 'Size' => '40'),
         'Description' => array ( 'Type' => 'text', 'Name' => __('Channel Description', 'amazon-link'), 'Default' => 'Channel Description', 'Size' => '80'),
         'Filter'      => array ( 'Type' => 'textbox', 'Name' => __('Channel Filter', 'amazon-link'), 'Rows' => 5, 'Description' => __('Channel Filter Rules', 'amazon-link'), 'Default' => '' ),
         'ids'         => array ( 'Type' => 'title', 'Value' => __('Affiliate IDs', 'amazon-link'), 'Title_Class' => 'al_subheading', 'Description' => __('Valid affiliate IDs from all Amazon locales can be obtained from the relevant Amazon sites: ', 'amazon-link'), 'Class' => 'al_pad al_border'),
         );

   $country_data = $this->get_country_data();
   // Populate Country related options
   foreach ($country_data as $cc => $data) {
      $channel_opts['tag_' . $cc] = array('Type' => 'text', 'Default' => '',
                                          'Name' => '<img style="height:14px;" src="'. $data['flag'] . '"> ' . $data['country_name'],
                                          'Hint' => sprintf(__('Enter your affiliate tag for %1$s.', 'amazon-link'), $data['country_name'] ));
      $channel_opts['ids']['Description'] .= '<a href="' . $data['site']. '">'. $data['country_name']. '</a>, ';
   }
   $channel_opts['plugin_ids'] = array ('Type' => 'title', 'Title_Class' => 'al_pad');

   $channel_opts['Buttons1'] = array ( 'Type' => 'buttons', 'Buttons' => 
                                           array ( __('Copy', 'amazon-link') => array( 'Action' => 'ALChannelAction', 'Class' => 'button-secondary'),
                                                   __('Update', 'amazon-link') => array( 'Action' => 'ALChannelAction', 'Class' => 'button-secondary'),
                                                   __('New', 'amazon-link') => array( 'Action' => 'ALChannelAction', 'Class' => 'button-secondary'),
                                                   __('Delete', 'amazon-link') => array( 'Action' => 'ALChannelAction', 'Class' => 'button-secondary') ));
   $channel_opts['end']      = array ( 'Type' => 'end');

/*****************************************************************************************/


   $action = (isset($_POST[ 'ALChannelAction' ]) && check_admin_referer( 'update-AmazonLink-channels')) ?
                      $_POST[ 'ALChannelAction' ] : 'No Action';

   // Get the Channel ID if selected.
   if (isset($_POST['ID'])) {
      $channel_id =$_POST['ID'];
   }

   $notify_update= False;
   // See if the user has posted us some information
   // If they did, the admin Nonce should be set.
   if(  $action == __('Update', 'amazon-link') ) {

      // Update Channel settings

      // Check for clash of ID with other Channels
      $new_channel_id = strtolower($_POST['Name']);
      if ($channel_id !== $new_channel_id ) {
         $new_id= '';
         while (isset($channels[ $new_channel_id . $new_id]))
            $new_id++;
         unset($channels[$channel_id ]);
         $channel_id = $new_channel_id . $new_id;
         $_POST['Name'] = $_POST['Name']. $new_id;
      }

      $channels[$channel_id] = array();

      foreach ($channel_opts as $Setting => $Details) {
         if (isset($Details['Default'])) {
            // Read their posted value
            $channels[$channel_id][$Setting] = stripslashes($_POST[$Setting]);
         }
      }
      $notify_update  = True;
      $update_message[] = sprintf (__('Channel %s Updated','amazon-link'), $channel_id);

      /*
       * Check if all locales populated for the default channel if not then issue a
       * warning to the user.
       */
      if ($channel_id == 'default') {
         $all_ids = False;
         foreach ($country_data as $cc => $data) {
            if (empty($channels[$channel_id]['tag_'.$cc])) {
               $locales[] = $data['country_name'];
            }
         }
         if (!empty($locales)) {
            $update_message[] = sprintf( __( 'PLEASE NOTE: You will not earn commission from visitors from these locales: %s, if localisation is enabled.','amazon-link'), implode( $locales, ', '));
         }
      }

   } else if (  $action == __('Delete', 'amazon-link') ) {
      unset($channels[$channel_id]);
      $notify_update  = True;
      $update_message[] = sprintf (__('Channel "%s" deleted.','amazon-link'), $channel_id);
   } else if (  $action == __('Copy', 'amazon-link') ) {
      $new_id = 1;
      while (isset($channels[ $channel_id . $new_id ]))
         $new_id++;
      $channels[$channel_id. $new_id ] = $channels[$channel_id];
      $channels[$channel_id. $new_id ]['Name'] = $channel_id. $new_id ;
      $notify_update  = True;
      $update_message[] = sprintf (__('Channel "%s" created from "%s".','amazon-link'), $channel_id. $new_id, $channel_id);
   } else if (  $action == __('New', 'amazon-link') ) {
      $new_id = '';
      while (isset($channels[ __('channel', 'amazon-link') . $new_id ]))
         $new_id ++;
      $channels[__('channel', 'amazon-link') . $new_id ] = array('Name' => __('Channel', 'amazon-link') . $new_id , 'Filter' => '', 'Description' => __('Channel Description', 'amazon-link'));
      $notify_update  = True;
      $update_message[] = sprintf (__('Channel "%s" created.','amazon-link'), __('channel', 'amazon-link') . $new_id );
   }

/*****************************************************************************************/

   /*
    * If first run need to create a default channel
    */
   if(!isset($channels['default'])) {
      $channels['default'] = array('Name' => 'Default', 'Description' => 'Default Affiliate IDs', 'Filter' => '');
      $notify_update  = True;
      $update_message[] = sprintf (__('Default Channel Created - Note: \'default\' channel must exist.','amazon-link'));
   }


/*****************************************************************************************/

   if ($notify_update && current_user_can('manage_options')) {
      $this->save_channels($channels);
            
      // **********************************************************
      // Put an options updated message on the screen
      foreach ((array)$update_message as $message) {
?>

<div class="updated">
 <p><strong><?php echo $message ; ?></strong></p>
</div>

<?php
      }
   }

/*****************************************************************************************/

   // **********************************************************
   // Now display the options editing screen
   foreach ($channels as $channel_id => $channel_details) {
      if ( ! isset( $channel_details['user_channel'] ) ) {

         /*
          * For the default channel we add a statement to tell the user what happens
          * if no associate ID is provided for a locale.
          */
         if ( $channel_id == 'default' ) {
            $link = '<a href="admin.php?page=amazon-link-settings#plugin_ids">Plugin Associate IDs</a>';
            if ($settings['plugin_ids']) {
               $channel_opts['plugin_ids']['Value'] = '<b>Thankyou for helping to support this plugin by allowing it to provide Associate IDs for locales for which you are not affiliated. You can disable this behaviour by changing the '.$link.' setting.</b>';
            } else {
               $channel_opts['plugin_ids']['Value'] = '<b>Note: For locales that do not have an associate ID you will not earn commission. However if you would like to help support future developments at no cost to you, the plugin can insert its own IDs for those locales. You can enable this behaviour by changing the '.$link.' setting.</b>';
            }
         } else {
            $channel_opts['plugin_ids']['Value'] = '';
         }

         $channel_opts ['ID']['Default'] = $channel_id;
         $channel_opts ['title']['Value'] = sprintf(__('<b>%s</b> - %s','amazon-link'), $channel_id, isset($channel_details['Description'])?$channel_details['Description']:'User Channel');
         $this->form->displayForm($channel_opts , $channels[$channel_id]);
      }
   }


?>
