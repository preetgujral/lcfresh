<?php
/*****************************************************************************************/

/*
 * User Channel Option Panel Processing
 *
 * Move User Channels into main Channel database (13/1/2014)
 */
   $channels = $this->get_channels();
   $channel  = !empty($channels['al_user_' . $user->ID]) ? $channels['al_user_' . $user->ID] : array();
   $channel_opts = $this->get_user_option_list();

/*****************************************************************************************/

   // **********************************************************
   // Now display the options editing screen
   $this->form->displayForm($channel_opts , $channel, True, True, True, False);

?>