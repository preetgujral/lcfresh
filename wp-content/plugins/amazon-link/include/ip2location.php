<?php

if (!class_exists('AmazonWishlist_ip2nation')) {
   class AmazonWishlist_ip2nation {

/*****************************************************************************************/

      /// Set up paths and other constants

      function __construct() {

         $this->db = 'ip2country';
         $this->remote_file = 'http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip';
         $upload_dir = wp_upload_dir();
         $this->temp_dir  = $upload_dir['basedir'] . '/ip2nation';
         $this->temp_file = $this->temp_dir . '/GeoIPCountryWhois.csv';
      }

      function init() {
         // Not currently needed
      }
/*****************************************************************************************/

      /// Check if the database is install and if it is up-to-date, and
      /// construct an appropriate status message

      function status () {
         global $wpdb;

         $sql = "SHOW TABLE STATUS WHERE Name LIKE '". $this->db ."'";
         $db_info = $wpdb->get_row($sql);
         if ($db_info != NULL) {
            $ip2nationdb_ts = ($db_info->Update_time != NULL) ? strtotime($db_info->Update_time) : strtotime($db_info->Create_time);
            $ip2nationdb_time = date('D, d M Y H:i:s', $ip2nationdb_ts);
            $uninstall = True;
         } else {
            $ip2nationdb_ts = False;
            $uninstall = False;
         }

         $result = wp_remote_head($this->remote_file, array('timeout' => 1));
         if (is_wp_error($result))
         {
            $ip2nationfile_ts = False;
         } else {
            $ip2nationfile_ts = strtotime($result['headers']['last-modified']);
            $ip2nationfile_time = date('D, d M Y H:i:s', $ip2nationfile_ts);
            $ip2nationfile_length = $result['headers']['content-length'];
         }

         $install = False;
         if (!$ip2nationdb_ts) {
            if (!$ip2nationfile_ts) {
               $message = __('You currently do not have <b>ip2nation</b> installed, and the remote file is unavailable', 'amazon-link');
            } else {
               $install = True;
               $message = sprintf(__('You currently do not have <b>ip2nation</b> installed, the latest version available is dated: %s','amazon-link'),$ip2nationfile_time);
            }
         } else {
            if (!$ip2nationfile_ts) {
               $message = sprintf(__('Your <b>ip2nation</b> database was last updated on %1$s, the remote file is unavailable.', 'amazon-link'), $ip2nationdb_time);
            } else {
               if ($ip2nationfile_ts > $ip2nationdb_ts) {
                  $message = sprintf(__('<b>WARNING!</b> Your <b>ip2nation</b> database is out of date. It was last updated on %1$s, the latest version available is dated: %2$s', 'amazon-link'), $ip2nationdb_time, $ip2nationfile_time);
                  $install = True;
               } else {
                  $message = sprintf(__('Your <b>ip2nation</b> database is up to date. (It was last updated on %1$s, the latest version available is dated: %2$s).', 'amazon-link'), $ip2nationdb_time, $ip2nationfile_time);
               }
            }
         }

         return array( 'Uninstall' => $uninstall, 'Install' => $install, 'Message' => $message);
      }

/*****************************************************************************************/

      /// Download and install the ip2nation mysql database

      function install ($url, $args) {
         global $wpdb, $wp_filesystem;
         
         /*
          * Use WordPress WP_Filesystem methods to install DB
          */
         
         // Check Credentials
         if (false === ($creds = request_filesystem_credentials($url,NULL,false,false,$args))) {
            // Not yet valid, a form will have been presented - drop out.
            return array ( 'HideForm' => true);
         }
         
         if ( ! WP_Filesystem($creds) ) {
            // our credentials were no good, ask the user for them again
            request_filesystem_credentials($url,NULL,true,false,$args);
            return array ( 'HideForm' => true);
         }
         
         /* Skip the download if it has already been done */
         if ( ! is_readable ( $this->temp_file ) ) {
            
            $temp_file = download_url($this->remote_file);
            if (is_wp_error($temp_file))
               return array ( 'Success' => False, 'Message' => __('ip2nation install: Failed to download file: ','amazon-link') . $temp_file->get_error_message());
            
            $result = unzip_file($temp_file, $this->temp_dir);
            if (is_wp_error($result)) {
               unlink ($temp_file);
               return array ( 'Success' => False, 'Message' => __('ip2nation install: Failed to unzip file: ','amazon-link') . $result->get_error_message());
            }
         }
         
         // Install the database
         // This can take a while on slow servers, disable aborts until
         // I do a proper jquery progress version.
         set_time_limit(0);
         ignore_user_abort(true);
         
         // Create the database Table
         $query = 'DROP TABLE IF EXISTS '. $this->db .';';
         if ($wpdb->query($query) === FALSE) {
            return array( 'Success' => False, 'Message' => sprintf(__('ip2nation uninstall: Database failed to uninstall [%s]','amazon-link'), $wpdb->last_error));
         }
         $query = "CREATE TABLE " . $this->db . " (
                   ip int(11) unsigned NOT NULL default '0',
                   country char(2) NOT NULL default '',
                   KEY ip (ip));";
         if ($wpdb->query($query) === FALSE) {
            return array( 'Success' => False, 'Message' => sprintf(__('ip2nation uninstall: Database failed to uninstall [%s]','amazon-link'), $wpdb->last_error));
         }
         
         // Process database file
         $lines = $wp_filesystem->get_contents_array($this->temp_file);

         $queries = 0;
         foreach ($lines as $line) {

            $items = explode(',',trim($line));
            if ( array($items) && (count($items) > 5) ) {
               $ip = trim( $items[2], '"');
               $cc = strtolower(trim( $items[4], '"'));
               if ( ! empty ($ip) && ! empty ($cc) ) {
                  $data = array ( 'ip' => $ip, 'country' => $cc );
                  if ( $wpdb->insert( $this->db, $data) === FALSE ) {
                     return array ( 'Success' => False, 'Message' => '='. print_r($line,true).'='.sprintf(__('ip2nation install: Database downloaded and unzipped but failed to install [%s]','amazon-link'), $wpdb->last_error));
                  }
                  $queries++;
               }
            }
         }

         $wp_filesystem->delete($this->temp_dir,true);
         return array ( 'Success' => True, 'Message' =>  sprintf(__('ip2nation install: Database downloaded and installed successfully. %s queries executed.','amazon-link'), $queries));
      }
      
/*****************************************************************************************/

      function uninstall () {
         global $wpdb;
         $query = 'DROP TABLE IF EXISTS '. $this->db .';';
         if ($wpdb->query($query) === FALSE) {
            return sprintf(__('ip2nation uninstall: Database failed to uninstall [%s]','amazon-link'), $wpdb->last_error);
         } else {
            return sprintf(__('ip2nation uninstall: Databases successfully uninstalled.','amazon-link'));
         }
      }

/*****************************************************************************************/

      function get_cc ($ip = FALSE) {
         global $wpdb, $_SERVER;

         if ($ip === FALSE)
            $ip = $_SERVER['REMOTE_ADDR'];

         $sql = "SHOW TABLE STATUS WHERE Name LIKE '". $this->db ."'";
         $db_info = $wpdb->get_row($sql);
         if ($db_info != NULL) {
            $sql = 'SELECT country FROM ' . $this->db .' WHERE ip < INET_ATON(%s) ORDER BY ip DESC LIMIT 0,1';
            return $wpdb->get_var($wpdb->prepare($sql, $ip));
         } else {
            return NULL;
         }

      }

/*****************************************************************************************/

   }
}

?>