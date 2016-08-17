<?php

   if ( !current_user_can( 'edit_user', $user ) )
      return false;

   $channel_opts = $this->get_user_option_list();

   foreach ( $channel_opts as $opt => $data) {
      if (isset($data['Default'])) {
         $options[$opt] = $_POST[$opt];
      }
   }

   // Save Channel tags in global channel settings
   $channels  = $this->get_channels();
   $countries = array_keys($this->get_country_data());
   $tags = array();
   foreach ($countries as $cc) {
      if ( !empty($options['tag_'.$cc]) ) {
         $tags['tag_' . $cc] = $options['tag_'.$cc];
      }
      unset($options['tag_'.$cc]);
   }
   if (!empty($tags)) {
      $channels['al_user_' . $user] = $tags;
      $channels['al_user_' . $user]['user_channel'] = 1;
   } else {
      unset($channels['al_user_'. $user]);
   }
   $this->save_channels($channels);
   
   $this->save_user_options($user, $options);
?>