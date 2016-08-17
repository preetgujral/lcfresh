<?php
/*****************************************************************************************/

/*
 * Admin Panel Processing
 *
 */
   $settings = $this->get_default_settings();
   $channels = $this->get_channels(false);
   $countries = array_keys($this->get_country_data());

   $progress = array();
   $tips = array( 'aws_keys' => 
      'To take advantage of all the features of this plugin it is recommended that you enrol in the Amazon Advertising API program,
       do this for free at <a href="https://affiliate-program.amazon.com/gp/flex/advertising/api/sign-in.html">Amazon Affiliate Program</a>.
       Once enrolled make a note of your \'Access Key ID\' and \'Secret Access Key\' and put these in the <a href="#pub_key">AWS Public key</a> and <a href="#priv_key">AWS Private key</a> settings.',
                  'aws_working' =>
                  'Your AWS keys are not yet valid, this could be because you copied them incorrectly or your account has not fully enrolled to the \'Product Advertising API\', 
                  go to <a href="https://portal.aws.amazon.com/gp/aws/manageYourAccount">AWS Portal</a> and check that you have \'Signed Up For\' the \'Product Advertising API\'.',
                  
                 );
   
   
   /* Affiliate IDs */
   $progress['local_associate'] = !empty($channels['default']['tag_'.$settings['default_cc']]);
   foreach ($countries as $cc) {
      if (!empty($channels['default']['tag_'.$cc]) && ($cc != $settings['default_cc'])) {
         $progress['international_associate'] = 1;
         break;
      }
   }
      
   /* Localisation Status */
   $ip2n_status = $this->ip2n->status();
   $progress['international'] = !empty($ip2n_status['Uninstall']);
   
   /* AWS Advertising API Access Status */
   $progress['aws_keys'] = !empty($settings['pub_key']) && !empty($settings['priv_key']);
   $progress['aws_working'] = !empty($settings['aws_valid']);

   /* Caches */
   $progress['performance'] = !empty($settings['cache_enabled']);
   
   /* Developer Support */
   $progress['dev_support'] = !empty($settings['plugin_ids']);
   
/*****************************************************************************************/


   echo "<PRE>"; print_r($progress); echo "</pRE>";
   echo "<P>"; print_r($tips); echo "</P>";
?>