<?php return array (
  'extras' => 
  array (
    'id' => 'amazon-link-extras-extras',
    'page' => 'extras',
    'title' => 'Extras',
    'content' => '
<p>On the Amazon Link Extras Settings page you can manage plugins that add extra functionality to the main Amazon Link plugin. These plugins are either user provided or have been requested by users of the Amazon Link plugin. However although useful they may come with some performance or database impact. As such they are not built into the Amazon Link plugin by default.</p>
<p>The plugins use the filters and action hooks built into the main Amazon Link plugin to modify its behaviour (see the \'Filters\' section), any changes made on this page will cause the Amazon Link Cache to be emptied.</p>
<p>It is recommended that if you wish to modify the behaviour of Amazon Link plugin then create your own plugins (using the provided ones as a template). They are independent of the main plugin and so will survive any upgrades to the main plugin.</p>
<p>Currently there are a handful of Amazon Link Extra plugins:</p>
<ul>
<li><strong>Convert Links</strong> - A BETA release of a utility to convert all amazon-link shortcodes into another format, e.g. static content or hidden html.</li>
<li><strong>Editorial Content</strong> - Grab the Editorial content from Amazon and use it to populate the %EDITORIAL% keyword, can be a large amount of data to download and store in the cache.</li>
<li><strong>Images</strong> - Grab all available images from Amazon, enable dynamic sizing of Thumbnails and Images and provide a method of displaying all the images in a template using the keywords %IMAGES% and %THUMBS%.</li>
<li><strong>Redirect Links</strong> - A BETA release of a plugin that creates links of the form http://www.example.com/al/0123456789 that will redirect to the appropriate Amazon web page.</li>
<li><strong>References</strong> - A BETA release of a plugin that allows you to create predefined shortcodes (e.g. for links that located on multiple pages) and add them to pages using a \'reference\' keyword.</li>
<li><strong>Spoof Locale</strong> - Allows you to view the site as if visiting from another locale to check localisation is working for your site.</li>
</ul>
',
  ),
  'filters' => 
  array (
    'id' => 'amazon-link-extras-filters',
    'page' => 'extras',
    'title' => 'Filters',
    'content' => '
<p>The plugin exposes a number of filters that can be accessed via the standard WordPress <a href="http://codex.wordpress.org/Plugin_API#Filters">Filter</a> API:</p>
<ul>
<li>amazon_link_keywords</li>
<li>amazon_link_option_list</li>
<li>amazon_link_user_option_list</li>
<li>amazon_link_default_templates</li>
<li>amazon_link_admin_menus</li>
<li>amazon_link_regex</li>
<li>amazon_link_url</li>
<li>amazon_link_process_args</li>
<li>amazon_link_save_channel_rule</li>
<li>amazon_link_get_channel</li>
<li>amazon_link_template_get_%KEYWORD%</li>
<li>amazon_link_template_process_%KEYWORD</li>
</ul>
<p>It is also possible to add your own filters to process individual data items returned via Amazon by adding a \'Filter\' item using the \'amazon_link_keywords\' filter. See the \'Editorial Content\' plugin for an example of how to do this.</p>
<p>The plugin exposes one action hook that can be used via the standard WordPress Action API:</p>
<ul>
<li>amazon_link_init($settings)</li>
</ul>
',
  ),
  'keywords filter' => 
  array (
    'id' => 'amazon-link-extras-keywords-filter',
    'page' => 'extras',
    'title' => 'Keywords Filter',
    'content' => '
<p><strong>amazon_link_keywords</strong> - This filter allows developers the ability to change the template keywords used by the plugin, it passes an array with a entry for each keyword. This allows developers to add new keywords, change existing ones or remove unwanted keywords.</p>
<p>Each keyword has the following elements:</p>
<p><em>keyword</em><br />
This is the index in the keywords array and is used to identify the keyword and is what is searched for in the template. Must be lower case.</p>
<p><em>Description</em><br />
This is the textual description that is displayed in the Template Help section.</p>
<p><em>User</em><br />
This indicates that this is a text field that the user can populate.</p>
<p><em>Live</em><br />
This is set if the keyword is retrieved from the Amazon Web Service API.</p>
<p><em>Default</em><br />
This is the default value if no data is entered by the user or populated by the AWS query.</p>
<p>The following elements are only required for \'Live\' items.</p>
<p><em>Position</em><br />
This is an array of arrays (in order of preference) determining how to traverse the AWS Item to get the the AWS information.</p>
<p><em>Group</em><br />
This is a comma separated list of the AWS Response Group(s) needed to return this item\'s details in the AWS data.</p>
<p><em>Filter</em><br />
This is any filter that should be applied to the returned AWS data before storing in the cache and being used in the template. See the \'amazon_link_editorial\' example below.</p>
<p>Example:</p>
<pre lang=\'php\'>

function my_keywords_filter($keywords) {
 $keywords[\'artist\'] = array(\'Description\' => \'Item\'s Author, Artist or Creator\',
                             \'live\' => \'1\', 
                             \'Group\' => \'Small\', 
                             \'Default\' => \'-\',
                             \'Position\' => array( array(\'ItemAttributes\',\'Artist\'),
                                                  array(\'ItemAttributes\',\'Author\'),
                                                  array(\'ItemAttributes\',\'Director\'),
                                                  array(\'ItemAttributes\',\'Creator\'),
                                                  array(\'ItemAttributes\',\'Brand\')))
 return $keywords;
}
add_filter(\'amazon_link_keywords\', \'my_keywords_filter\', 1);
</pre>
<p>If you add any filters of your own you must flush the Plugin\'s Product Cache to remove stale data.</p>
',
  ),
  'options filters' => 
  array (
    'id' => 'amazon-link-extras-options-filters',
    'page' => 'extras',
    'title' => 'Options Filters',
    'content' => '
<p><strong>amazon_link_option_list</strong> - This filter allows developers the ability to change the options used by the plugin, it passes an array with a entry for each option. This allows developers to add new options (or even change existing ones or remove unwanted options - not recommended!).</p>
<p><strong>amazon_link_user_option_list</strong> - This filter allows the developer to change the options displayed on the User\'s profile page.</p>
<p>Each option has the following elements:</p>
<p><em>Name</em><br />
Name of the Option.<br />
<em>Description</em><br />
Short Description of the option.<br />
<em>Hint</em><br />
Hint that is shown if the user hovers the mouse over this option (e.g. on a selection option).<br />
<em>Default</em><br />
The default value this option has if it is not set.<br />
<em>Type</em><br />
What type of option is this. Can be one of:</p>
<ul>
<li>text</li>
<li>checkbox</li>
<li>selection</li>
<li>hidden</li>
<li>title</li>
<li>textbox</li>
<li>radio</li>
</ul>
<p><em>Class</em><br />
Class of the option as displayed on the options page.<br />
<em>Options</em><br />
An array of options for the \'selection\' and \'radio\' type of option.<br />
<em>Length</em><br />
Length of the \'text\' option type.<br />
<em>Rows</em><br />
Number of rows in the \'textbox\' option type.<br />
<em>Read_Only</em><br />
Set to 1 if this option can not be modified by the user.</p>
',
  ),
  'templates filter' => 
  array (
    'id' => 'amazon-link-extras-templates-filter',
    'page' => 'extras',
    'title' => 'Templates Filter',
    'content' => '
<p><strong>amazon_link_default_templates</strong> - If you have built up a library of templates you can use this filter to add those templates to the defaults the Amazon Link plugin provides. If you do a new install or have multiple sites it provides a way to keep the same templates on all sites.</p>
<p>The filter is passed the default templates array in the form:</p>
<pre lang=\'php\'>
   \'image\' =>     array ( \'Name\' => \'Image\', 
                          \'Description\' => \'Localised Image Link\', 
                          \'Content\' => $image_template, 
                          \'Type\' => \'Product\',
                          \'Version\' => \'2\', 
                          \'Notice\' => \'Add impression tracking\', 
                          \'Preview_Off\' => 0 ),
   \'mp3 clips\' => array ( \'Name\' => \'MP3 Clips\', 
                          \'Description\' => \'Amazon MP3 Clips Widget (limited locales)\',
                          \'Content\' => $mp3_clips_template, 
                          \'Version\' => \'1\', 
                          \'Notice\' => \'\', 
                          \'Type\' => \'Multi\', 
                          \'Preview_Off\' => 0 )
</pre>
<p>Use the filter to change the defaults or add your own default templates. Each template has the following elements:</p>
<p><em>Name</em><br />
The name of the template usually matches the template ID used in the index.<br />
<em>Description</em><br />
A short description of the template.<br />
<em>Content</em><br />
The actual template content it is recommend that it is run through the \'htmlspecialchars\' function to ensure any odd characters are escaped properly.<br />
<em>Version</em><br />
The current version of this template, should be a number, e.g. \'2.1\'.<br />
<em>Notice</em><br />
An upgrade notice, what has changed since the last version.<br />
<em>Type</em><br />
The type of the template usually \'Product\', can be:</p>
<ul>
<li>Product</li>
<li>No ASIN</li>
<li>Multi</li>
</ul>
<p><em>Preview_Off</em><br />
If this template should not be previewed on the Options page, e.g. it is javascript.</p>
',
  ),
  'admin menu filter' => 
  array (
    'id' => 'amazon-link-extras-admin-menu-filter',
    'page' => 'extras',
    'title' => 'Admin Menu Filter',
    'content' => '
<p><strong>amazon_link_admin_menus</strong> - Use this filter to add a new Administrative Settings Sub-Menu to the Amazon Link Menu.</p>
<p>The filter is passed the default Sub-Menu structure in the form of an array one entry per sub-menu:</p>
<pre lang=\'php\'>
 \'amazon-link-channels\' => array( \'Slug\' => \'amazon-link-channels\', 
                                  \'Help\' => \'help/channels.php\',
                                  \'Description\' => \'Short Description of Settings Page.\',
                                  \'Title\' =>\'Manage Amazon Associate IDs\', 
                                  \'Label\' => \'Associate IDs\', 
                                  \'Icon\' => \'plugins\',
                                  \'Capability\' => \'manage_options\',
                                  \'Metaboxes\' => array( \'alBox1\' => array( \'Title\' => \'Title\',
                                                                           \'Callback\' => array (&$this, \'show_box1\' ), 
                                                                           \'Context\' => \'normal\', 
                                                                           \'Priority\' => \'core\')))
</pre>
',
  ),
  'content filter' => 
  array (
    'id' => 'amazon-link-extras-content-filter',
    'page' => 'extras',
    'title' => 'Content Filter',
    'content' => '
<p><strong>amazon_link_regex</strong> - Use this filter to change the regular expression that the plugin uses to find the Amazon Link shortcodes. See the <a href="/manual/en/book.pcre.php" title="PCRE Documenation">PHP documentation</a> on Regular Expressions for more info.</p>
<p>The regular expression must return named items for the key elements of the \'shortcode\'. The default Regular Expression \'<code>/[amazon +(?&lt;args>(?:[^[]]*(?:[[a-z]*]){0,1})*)]/</code>\' returns the shortcode \'args\' as a named item.</p>
<p>All other named items are passed as extra arguments as if they are part of the shortcode:</p>
<p><strong>args</strong> - The shortcode arguments in the form of setting=value&setting=value...</p>
',
  ),
  'link url filter' => 
  array (
    'id' => 'amazon-link-extras-link-url-filter',
    'page' => 'extras',
    'title' => 'Link URL Filter',
    'content' => '
<p><strong>amazon_link_url</strong> - Use this filter to change the way in which the actual Links to the Amazon pages are created.</p>
<p>This filter is passed 6 arguments to help create the Links:</p>
<p><strong>URL</strong> - The current URL to be used.</p>
<p><strong>Type</strong> - The type of link required - \'A\' = product, \'S\' = search or \'R\' = review.</p>
<p><strong>Data</strong> - The product datain the form of a country specific array e.g. (\'us\' => (\'ASIN\',\'Title\',...) \'uk\' => (\'ASIN\', \'Title\',...).</p>
<p><strong>search [depreciated]</strong> - Search string to use if this is a search link.</p>
<p><strong>Country</strong> - The Localised Country Code (\'us\', \'uk\', \'ca\', etc.)</p>
<p><strong>settings</strong> - The Amazon Link settings incorporating any shortcode arguments.</p>
',
  ),
  'advanced filters' => 
  array (
    'id' => 'amazon-link-extras-advanced-filters',
    'page' => 'extras',
    'title' => 'Advanced Filters',
    'content' => '
<p><strong>amazon_link_process_args</strong> - Use this filter to arguments passed in via the shortcode, is passed two arguments: $arguments and $amazon_link.</p>
<p>The $arguments is an associative array reflecting the parsed shortcode arguments, e.g. [amazon asin=0123456789&title=Title] will create an array ( \'asin\' => \'0123456789\', \'title\' => \'Title\' ).</p>
<p>The $amazon_link is the amazon link class instance, to allow access to internal functions.</p>
<p><strong>amazon_link_save_channel_rule</strong> - This filter provides access to the processing of the Channel \'Rules\', where the content of the \'Filter\' textbox entered by the user is converted into \'Rules\'. The filter is passed 4 arguments: $rules, $channel, $channel_data, $amazon_link.</p>
<p>$rules is an array of rules to be tested in the amazon_link_get_channel filters.</p>
<p>$channel is the name of the channel being processed.</p>
<p>$channel_data is the options associated with the channel, one of which is the Filter ($channel_data[\'Filter\']).</p>
<p>$amazon_link is the amazon link class instance, to allow access to internal functions.</p>
<p><strong>amazon_link_get_channel</strong> - Is the filter used to help select which channel should be used for a particular shortcode. Is passed 4 arguments: $selected_channel_data, $channels, $post, $settings, $amazon_link.</p>
<p><strong>amazon_link_template_get_%KEYWORD%</strong> & <strong>amazon_link_template_process_%KEYWORD%</strong> - These filters are applied to every instance of a keyword as it is encountered in the template. The \'get\' filter is applied before any data is retrieved from Amazon or the product Cache, the \'process\' filter is applied immediately after data retrieval.</p>
<p>So to alter how the %PRICE% keyword is displayed you would add a filter with the tag \'amazon_link_template_process_price\'.</p>
',
  ),
);?>