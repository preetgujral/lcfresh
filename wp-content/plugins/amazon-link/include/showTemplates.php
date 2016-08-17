<?php
/*****************************************************************************************/


   function export_templates($filename, $templates) {

      $slug = str_replace('-', '_', sanitize_title(get_bloginfo()));
      $content = '<?php
/*
Plugin Name: Amazon Link Extra - Exported Templates
Plugin URI: http://www.houseindorset.co.uk/plugins/amazon-link/
Description: Templates Exported from Amazon Link on ' . date("F j, Y, g:i a") . '
Version: 1.0
Author: Amazon Link User
Author URI: ' . get_site_url() .'
*/

function alx_'.$slug.'_default_templates ($templates) {
';
      foreach($templates as $id => $data) {
         if (!isset($data['Version'])) $data['Version'] = 1;
         if (!isset($data['Notice'])) $data['Notice'] = 'New Template';
         unset($data['nonce'], $data['nonce1'], $data['nonce2']);
         $content .= " \$templates['$id'] = \n  array(";
         foreach ($data as $item => $details) {
            if ($item == 'Content') {
               $content .= "   '$item' => htmlspecialchars (". var_export(htmlspecialchars_decode($details), true) . "),\n";
            } else {
               $content .= "   '$item' => ". var_export($details, true) . ",\n";
            }
         }
         $content .= "  );\n";
      }
      $content .= "  return \$templates;\n}\nadd_filter( 'amazon_link_default_templates', 'alx_${slug}_default_templates');\n?>";
      $result = file_put_contents( $filename, $content);
      if ($result === FALSE) {
         return array ( 'Success' => 0, 'Message' => "Export Failed could not write to: <em>$filename</em>" );
      } else {
         return array ( 'Success' => 1, 'Message' => "Templates exported to file: <em>$filename</em>, <em>$result</em> bytes written." );
      }
   }
   
/*
 * Template Panel Processing
 *
 */
   $Templates = $this->getTemplates();
   $default_templates = $this->get_default_templates();

   $templateOpts = array( 
         'nonce'       => array ( 'Type' => 'nonce', 'Value' => 'update-AmazonLink-templates' ),

         'ID'          => array ( 'Default' => '', 'Type' => 'hidden'),
         'title'       => array ( 'Type' => 'section', 'Value' => '', 'Class' => 'hidden', 'Section_Class' => 'al_subhead'),
         'Name'        => array ( 'Type' => 'text', 'Name' => __('Template Name', 'amazon-link'), 'Default' => 'Template', 'Size' => '40'),
         'Description' => array ( 'Type' => 'text', 'Name' => __('Template Description', 'amazon-link'), 'Default' => 'Template Description', 'Size' => '80'),
         'Type'        => array ( 'Type' => 'selection', 'Name' => __('Template Type', 'amazon-link'), 'Hint' => __('Type of Template, \'Multi\' templates are for multi-product ones that contain the %ASINS% keyword, \'No ASIN\' templates are for non-product specific ones, e.g. Banners and Scripts', 'amazon-link'), 'Default' => 'Product', 'Options' => array('Product', 'No ASIN', 'Multi') ),
         'Preview_Off' => array ( 'Type' => 'checkbox', 'Name' => __('Disable Preview', 'amazon-link'), 'Hint' => __('Disable the preview if it contains a script that might conflict with the options pages.', 'amazon-link'), 'Default' => '0'),
         'Content'     => array ( 'Type' => 'textbox', 'Name' => __('The Template', 'amazon-link'), 'Rows' => 5, 'Description' => __('Template Content', 'amazon-link'), 'Default' => '' ),
         'Buttons1'    => array ( 'Type' => 'buttons', 'Buttons' => 
                                           array ( __('Copy', 'amazon-link') => array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Make a Copy of this template', 'amazon-link'), 'Class' => 'button-secondary'),
                                                   __('Update', 'amazon-link') => array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Save changes made to this template', 'amazon-link'), 'Class' => 'button-secondary'),
                                                   __('New', 'amazon-link') => array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Create a New blank template', 'amazon-link'), 'Class' => 'button-secondary'),
                                                   __('Delete', 'amazon-link') => array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Delete this template', 'amazon-link'), 'Class' => 'button-secondary') )),
         'preview'     => array ( 'Type' => 'title', 'Value' => '', 'Title_Class' => ''),
         'end'         => array ( 'Type' => 'end')
         );

   $global_opts = array( 
         'nonce'       => array ( 'Type' => 'nonce', 'Value' => 'update-AmazonLink-templates' ),
         'Buttons1'    => array ( 'Type' => 'buttons', 'Buttons' => 
                                           array ( __('Export', 'amazon-link') => array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Export Templates to a Amazon Link Extra Plugin', 'amazon-link'), 'Class' => 'button-secondary'),
                                                   __('New', 'amazon-link') => array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Create a New blank template', 'amazon-link'), 'Class' => 'button-secondary') )),
         );

/*****************************************************************************************/

   $Action = (isset($_POST[ 'ALTemplateAction' ]) && check_admin_referer( 'update-AmazonLink-templates')) ?
                      $_POST[ 'ALTemplateAction' ] : (
                      (isset($_POST[ 'ALDefTemplateAction' ]) && check_admin_referer( 'update-AmazonLink-def-templates')) ?
                                  $_POST[ 'ALDefTemplateAction' ] : 'No Action');

   // Get the Template ID if selected.
   if (isset($_POST['ID'])) {
      $templateID=$_POST['ID'];
   }

   $NotifyUpdate = False;
   // See if the user has posted us some information
   // If they did, the admin Nonce should be set.
   if(  $Action == __('Update', 'amazon-link') ) {

      // Update Template settings

      // Check for clash of ID with other templates
      $NewTemplateID = strtolower($_POST['Name']);
      if ($templateID !== $NewTemplateID) {
         $NewID = '';
         while (isset($Templates[ $NewTemplateID . $NewID ]))
            $NewID++;
         unset($Templates[$templateID]);
         $templateID = $NewTemplateID . $NewID;
         $_POST['Name'] = $_POST['Name']. $NewID;
      }


      foreach ($templateOpts as $Setting => $Details) {
         if (isset($Details['Name'])) {
            // Read their posted value
            $Templates[$templateID][$Setting] = isset($_POST[$Setting]) ? stripslashes($_POST[$Setting]) : NULL;
         }
      }
      $NotifyUpdate = True;
      $UpdateMessage = sprintf (__('Template %s Updated','amazon-link'), $templateID);

   } else if (  $Action == __('Delete', 'amazon-link') ) {
      unset($Templates[$templateID]);
      $NotifyUpdate = True;
      $UpdateMessage = sprintf (__('Template "%s" deleted.','amazon-link'), $templateID);
   } else if (  $Action == __('Copy', 'amazon-link') ) {
      $NewID = 1;
      while (isset($Templates[ $templateID . $NewID ]))
         $NewID++;
      $Templates[$templateID. $NewID] = $Templates[$templateID];
      $Templates[$templateID. $NewID]['Name'] = $templateID. $NewID;
      $NotifyUpdate = True;
      $UpdateMessage = sprintf (__('Template "%s" created from "%s".','amazon-link'), $templateID. $NewID, $templateID);
   } else if (  $Action == __('New', 'amazon-link') ) {
      $NewID = '';
      while (isset($Templates[ __('template', 'amazon-link') . $NewID ]))
         $NewID++;
      $Templates[__('template', 'amazon-link') . $NewID] = array('Name' => __('Template', 'amazon-link') . $NewID, 'Content' => '', 'Type' => 'Product', 'Description' => __('Template Description', 'amazon-link'));
      $NotifyUpdate = True;
      $UpdateMessage = sprintf (__('Template "%s" created.','amazon-link'), __('template', 'amazon-link') . $NewID);
   } else if ($Action == __('Install', 'amazon-link') ) {
      $NewID = '';
      while (isset($Templates[ $templateID . $NewID ]))
         $NewID++;
      $Templates[$templateID. $NewID] = $default_templates[$templateID];
      $Templates[$templateID. $NewID]['Name'] = $templateID. $NewID;
      $NotifyUpdate = True;
      $UpdateMessage = sprintf (__('Template "%s" created from "%s".','amazon-link'), $templateID. $NewID, $templateID);
   } else if (($Action == __('Upgrade', 'amazon-link') ) || ($Action == __('Reset', 'amazon-link') )) {
      $Templates[$templateID] = $default_templates[$templateID];
      $NotifyUpdate = True;
      $UpdateMessage = sprintf (__('Template "%s" overwritten with default version.','amazon-link'), $templateID);
   } else if (($Action == __('Export', 'amazon-link') )) {
      $result= export_templates($this->extras_dir . 'amazon-link-exported-templates.php', $Templates);
      $NotifyUpdate = True;
      $UpdateMessage = $result['Message'];
   }

/*****************************************************************************************/

   /*
    * If first run need to create a default templates
    */
   if(count($Templates) == 0) {
      foreach ($default_templates as $templateName => $templateDetails) {
         if(!isset($Templates[$templateName])) {
            $Templates[$templateName] = $templateDetails;
            $NotifyUpdate = True;
            $UpdateMessage = sprintf (__('Default Templates Created - Must have at least one Template.','amazon-link'));
         }
      }
   }


/*****************************************************************************************/

   if ($NotifyUpdate && current_user_can('manage_options')) {
      $this->saveTemplates($Templates);
      $Templates = $this->getTemplates();
      // **********************************************************
      // Put an options updated message on the screen
?>

<div class="updated">
 <p><strong><?php echo $UpdateMessage; ?></strong></p>
</div>

<?php
   }

/*****************************************************************************************/

   // **********************************************************
   // Now display the options editing screen
   
   $this->in_post = False;
   $this->post_ID = 0;
   unset($templateOpts['Template']);
   $settings = $this->getSettings();
   foreach ($Templates as $templateID => $templateDetails) {
      $templateOpts['ID']['Default'] = $templateID;
      $templateOpts['title']['Value'] = sprintf(__('<b>%s</b> - %s','amazon-link'), $templateID, (isset($templateDetails['Description'])?$templateDetails['Description']:''));
      unset($templateOpts['Buttons1']['Buttons'][__('Upgrade', 'amazon-link')]);
      unset($templateOpts['Buttons1']['Buttons'][__('Reset', 'amazon-link')]);
      if (array_key_exists($templateID,$default_templates)) {
         if (empty($templateDetails['Version']) || ($default_templates[$templateID]['Version'] > $templateDetails['Version'])) {
            $templateOpts['Buttons1']['Buttons'][__('Upgrade', 'amazon-link')] = array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Upgrade this template to the new default version', 'amazon-link'), 'Class' => 'button-secondary');
         } else {
            $templateOpts['Buttons1']['Buttons'][__('Reset', 'amazon-link')] = array( 'Action' => 'ALTemplateAction', 'Hint' => __( 'Reset this template back to the default version', 'amazon-link'), 'Class' => 'button-secondary');
         }
      }


      $options = array();
      $options['text1'] = 'User Text 1';
      $options['text2'] = 'User Text 2';
      $options['text3'] = 'User Text 3';
      $options['text4'] = 'User Text 4';
      $options['text']  = 'Text Item';

      $options['template_type'] = isset($templateDetails['Type'])?$templateDetails['Type']:'Product';
      $options['template_content'] = htmlspecialchars_decode(isset($templateDetails['Content'])?$templateDetails['Content']:'');

      $asins = explode(',',$settings['template_asins']);
      if ( $templateDetails['Type'] == 'Multi' ) {
         $options['live'] = 0;
      } else if ( $templateDetails['Type'] == 'Product' ) {
         $asins = array($asins[0]);
         $options['live'] = 1;
      } else {
         $options['live'] = 0;
         $asins = array();
      }
      $options['asin'] = implode(',',$asins);

      if (empty($templateDetails['Preview_Off'])) {
         $templateOpts['preview']['Value'] = $this->shortcode_expand($options). '</br><br style="clear:both"\>';
      } else {
         $templateOpts['preview']['Value'] = '';
      }

      $this->form->displayForm($templateOpts, $Templates[$templateID]);
   }

   $this->form->displayForm($global_opts, array());


?>