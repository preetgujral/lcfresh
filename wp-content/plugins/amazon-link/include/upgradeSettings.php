<?php

// Options structure changed so need to update the 'version' option and upgrade as appropriate...
$Opts = get_option(self::optionName, array());
   
/*
 * Move from version 1.2 to 1.3 of the plugin (Option Version Null => 1)
 */
if (!isset($Opts['version'])) {
   $cc_map = array('co.uk' => 'uk', 'com' => 'us', 'fr' => 'fr', 'de' => 'de', 'ca' => 'ca', 'jp' => 'jp');

   if (isset($Opts['tld'])) {
      $cc = isset($cc_map[$Opts['tld']]) ? $cc_map[$Opts['tld']] : 'uk';
      $Opts['default_cc'] = $cc;
      if (isset($Opts['tag'])) $Opts['tag_' . $cc] = $Opts['tag'];
   }
   unset($Opts['tld']);
   unset($Opts['tag']);
   $Opts['version'] = 1;
   $this->saveOptions($Opts);
}

/*
 * Upgrade from 1 to 2:
 * force Template ids to lower case & update 'wishlist_template'.
 */
if ($Opts['version'] == 1) {
   $Templates = $this->getTemplates();
   if (!empty($Templates)) {
      foreach ($Templates as $Name => $value)
      {
         $renamed_templates[strtolower($Name)] = $value;
      }
      $this->saveTemplates($renamed_templates);
      $Templates = $renamed_templates;
   }
   if (isset($Opts['wishlist_template']))
      $Opts['wishlist_template'] = strtolower($Opts['wishlist_template']);
   $Opts['version'] = 2;
   $this->saveOptions($Opts);
}

/*
 * Upgrade from 2 to 3:
 * copy affiliate Ids to new channels section.
 */
if ($Opts['version'] == 2) {
   $country_data = $this->get_country_data();
   foreach ($country_data as $cc => $data)
   {
      $channels['default']['tag_'.$cc] = isset($Opts['tag_'.$cc]) ? $Opts['tag_'.$cc] : '';
   }
   $channels['default']['Name'] = 'Default';
   $channels['default']['Description'] = 'Default Affiliate Tags';
   $channels['default']['Filter'] = '';
   $Opts['version'] = 3;
   $this->save_channels($channels);
   $this->saveOptions($Opts);
}

/* 
 * Upgrade from 3 to 4:
 * Add Template 'Type' field and 'Version'
 */
if ($Opts['version'] == 3) {
   $Templates = $this->getTemplates();
   foreach ($Templates as $Name => $Data)
   {
      if (preg_match('/%ASINS%/i', $Data['Content'])) {
         $Templates[$Name]['Type'] = 'Multi';
      } else {
         $Templates[$Name]['Type'] = 'Product';
      }
      $Templates[$Name]['Version'] = '1';
      $Templates[$Name]['Preview_Off'] = '0';
   }

   $this->saveTemplates($Templates);
   $Opts['version'] = 4;
   $this->saveOptions($Opts);
}

/*
 * Upgrade from 4 to 5:
 * Add 'aws_valid' to indicate validity of the AWS keys.
 * Correct invalid %AUTHOR% keyword in search_text option.
 */
if ($Opts['version'] == 4) {
   $result = $this->validate_keys($Opts);
   $Opts['aws_valid'] = $result['Valid'];
   if (!empty($Opts['search_text'])) $Opts['search_text'] = preg_replace( '!%AUTHOR%!', '%ARTIST%', $Opts['search_text']);
   $Opts['version'] = 5;
   $this->saveOptions($Opts);
}

/* 
 * Upgrade from 5 to 6:
 * Re-install the cache database ('xml' column now a blob, and content must be flushed)
 * revalidate keys as aws_valid not being saved in options screen
 */
if ($Opts['version'] == 5) {

   if (!empty($Opts['cache_enabled'])) {
      $this->cache_remove();
      $this->cache_install();
   }
   $result = $this->validate_keys($Opts);
   $Opts['aws_valid'] = $result['Valid'];
   $Opts['version'] = 6;
   $this->saveOptions($Opts);
}


/* 
 * Upgrade from 6 to 7:
 * Save options to cause creation of 'search_text_s' option
 * Add the default Templates if they do not exist
 */
if ($Opts['version'] == 6) {

   /*
    * If first run need to create a default templates
    */
   $templates = $this->getTemplates();
   if(!isset($templates['wishlist'])) {
      $default_templates = $this->get_default_templates();
      foreach ($default_templates as $template_name => $template_details) {
         if(!isset($templates[$template_name])) {
            $templates[$template_name] = $template_details;
         }
      }
      $this->saveTemplates($templates);
   }

   $Opts['version'] = 7;
   $this->saveOptions($Opts);
}

/*
 * Upgrade from 7 to 8:
 * Move Channel Data from User Options into Main Channels Option
 */
if ($Opts['version'] == 7) {
   // Save User Channel tags in global channel settings
   $channels  = $this->get_channels();
   $countries = array_keys($this->get_country_data());
   $users = get_users(array('fields' => 'ID'));
   foreach ($users as $user => $ID) {
      $user_options = get_the_author_meta( 'amazonlinkoptions', $ID );
      if (is_array($user_options)) {
         $user_options = array_filter($user_options);
         if (!empty($user_options)) {
            $channels['al_user_' . $ID] = $user_options;
            $channels['al_user_' . $ID]['user_channel'] = 1;
         }
         // Hold off removal from user options in case some users want to downgrade.
         //update_usermeta( $ID, 'amazonlinkoptions',  NULL );
      }
   }
   $this->save_channels($channels);
   $Opts['version'] = 8;
   $this->saveOptions($Opts);
}
   /*
    * Set the 'do_channels' option if more than default channel is set
    */
   if ($Opts['version'] == 8) {
      
      // Resave Channels - this will set 'do_channels' for us, but will need to refetch options
      $channels = $this->get_channels();
      $this->save_channels($channels);
      
      $Opts = get_option(self::optionName, array());
      $Opts['version'] = 9;
      $this->saveOptions($Opts);
   }
   
   if ($Opts['version'] == 9) {
      
      // Enable plugin_extras
      $Opts = get_option(self::optionName, array());
      $Opts['plugin_extras'] = $Opts['plugin_ids'];
      $Opts['version'] = 10;
      $this->saveOptions($Opts);
   }
   
?>
