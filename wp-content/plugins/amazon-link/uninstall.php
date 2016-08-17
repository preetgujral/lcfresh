<?php
   
   if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
      exit();
   }
   
   include ('amazon.php');
   
   /*
    * Uninstall the Plugin Options.
    *
    * Called on removal of plugin - used to delete all related database entries.
    */
         
   $opts = get_option(AmazonWishlist_For_WordPress::optionName, array());
   if ($opts['full_uninstall']) {
      
      global $wpdb;
      
      /* Remove Product Cache */
      if (!empty($opts['cache_enabled'])) {
         $cache_table = $wpdb->prefix . AmazonWishlist_For_WordPress::cache_table;
         $sql = "DROP TABLE $cache_table;";
         $wpdb->query($sql);
      }
      
      /* Remove Shortcode Cache */
      if (!empty($opts['sc_cache_enabled'])) {
         $cache_table = $wpdb->prefix . AmazonWishlist_For_WordPress::sc_cache_table;
         $sql = "DROP TABLE $cache_table;";
         $wpdb->query($sql);
      }
      
      delete_option(AmazonWishlist_For_WordPress::optionName);
      delete_option(AmazonWishlist_For_WordPress::channels_name);
      delete_option(AmazonWishlist_For_WordPress::templatesName);
   }
?>
