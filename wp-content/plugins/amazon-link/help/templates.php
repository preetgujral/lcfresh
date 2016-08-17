<?php return array (
  'templates' => 
  array (
    'id' => 'amazon-link-templates-templates',
    'page' => 'templates',
    'title' => 'Templates',
    'content' => '
<p>If you want to display anything more complex than a simple text link then you can take advantage of the built in templates or create your own.</p>
<p>Use the Amazon Link > Templates Settings page to display all the available templates. On this page you can create, delete and copy templates. The template content is based on standard html with additional keywords that are surrounded by ‘%’ characters. These keywords are automatically filled in with the relevant Amazon product information.</p>
<p>See the Template Help section for a description of each of the keywords that can be used.</p>
',
  ),
  'template settings' => 
  array (
    'id' => 'amazon-link-templates-template-settings',
    'page' => 'templates',
    'title' => 'Template Settings',
    'content' => '
<p><strong>Template Type</strong></p>
<p>You must ensure that the template type is set correctly when creating the template, it should be one of:</p>
<ul>
<li>Product</li>
<li>Multi</li>
<li>No ASIN</li>
</ul>
<p>For most templates this should be \'Product\', which is used to create links about a single Amazon product using the ASIN entered in the shortcode.</p>
<p>If the template accepts a list of ASINS (e.g. like the Carousel widget) then the type should be set to \'Multi\', and the template should include the keyword <code>\'%ASINS%\'</code> instead of <code>\'%ASIN%\'</code>.</p>
<p>If the template does not require an ASIN to be specified then set the type to \'No ASIN\', typically used for Banners, Scripts and more general items.</p>
<p><strong>Disable Preview</strong></p>
<p>If you do not want the template to be shown on the Template Options page (e.g. It\'s full of javascript or incomplete HTML tags) then select this option to stop it being rendered.</p>
',
  ),
  'keywords' => 
  array (
    'id' => 'amazon-link-templates-keywords',
    'page' => 'templates',
    'title' => 'Keywords',
    'content' => '
<p>Most of the keywords are self explanatory: <code>\'%TITLE%\'</code> will expand to be the product\'s title, <code>\'%PRICE%\'</code> the formatted product\'s price, etc.</p>
<p>However links can be created by using the keyword pair <code>\'%LINK_OPEN%\'</code> and <code>\'%LINK_CLOSE%\'</code> with the subject of the link being placed between them. For example <code>\'%LINK_OPEN%Amazon Product%LINK_CLOSE%\'</code>. The link produced will comply with whatever settings you have used, i.e. localised to the user\'s country or produce a multinational popup, it will also use the appropriate Amazon associate IDs.</p>
<p>There are a number of other keywords that are also localised these include: <code>\'%LINK_OPEN%\'</code> - as described above, <code>\'%TLD%\'</code> the Top Level Domain to be used \'.co.uk\', \'.it\', \'.com\', etc.; <code>\'%MPLACE%\'</code> - the Amazon Market place to use \'GB\', \'IT\', \'US\', etc.; <code>\'%CC%\'</code> - the localised country code \'uk\', \'it\', \'us\'; <code>\'%TAG%\'</code> - The amazon associate tag to use.</p>
<p>There are a number of general purpose keywords that can be used to enter data directly in the shortcode; these are <code>\'%TEXT%\'</code>, <code>\'%TEXT1%\'</code>, <code>\'%TEXT2%\'</code>, <code>\'%TEXT3%\'</code>, <code>\'%TEXT4%\'</code>.</p>
',
  ),
  'country modifiers' => 
  array (
    'id' => 'amazon-link-templates-country-modifiers',
    'page' => 'templates',
    'title' => 'Country Modifiers',
    'content' => '
<p>If you want an item in the template to be forced to output information from a specific locale you can apply country modifiers to the template keywords.</p>
<p>For example you wish to show the prices from a number of locales.</p>
<p>This is possible by adding a country modifier to the end of the Template keyword, one of (UK#, CA#, CN#, DE#, ES#, FR#, IN# IT#, JP#, US#). This can be applied to any of the template keywords.</p>
<p>So <code>%PRICE%UK#, %PRICE%DE#, %PRICE%FR#</code> would generate a list of prices from the UK, Germany and France.</p>
',
  ),
  'default templates' => 
  array (
    'id' => 'amazon-link-templates-default-templates',
    'page' => 'templates',
    'title' => 'Default Templates',
    'content' => '
<p>The plugin contains a set of basic templates to show how the keywords can be used. The easiest way to create new ones is to copy one of the existing ones and update it to meet your needs.</p>
<p>Most of these templates were created by using the Amazon Affiliate site, generating the widget or link required then copying the generated output into the \'Template Content\' box. Then quickly replacing any of the static data with template keywords, e.g. the product ASIN, Title, etc.</p>
<p>Currently the plugin has default templates for:</p>
<ul>
<li>Add To Cart Button</li>
<li>Thumbnail link</li>
<li>Image link</li>
<li>Multinational Template</li>
<li>Amazon Iframe based Image link</li>
<li>Amazon Carousel Widget</li>
<li>Amazon My Favourites Widget</li>
<li>Amazon MP3 Clips Widget</li>
<li>Amazon Enhanced Pop-up Script</li>
<li>Amazon Easy Banner</li>
<li>Wishlist Template</li>
</ul>
<p>Bear in mind that the Amazon templates are based on javascript hosted on the Amazon site, as such are often blocked by adblocker software. Also some of these \'Widget Source\' based templates are not available in certain locales (Canada, China, Italy and Spain).</p>
<p>The \'Wishlist\' template is the default template used for any lists created by shortcodes, as such it must exist. </p>
',
  ),
  'template management' => 
  array (
    'id' => 'amazon-link-templates-template-management',
    'page' => 'templates',
    'title' => 'Template Management',
    'content' => '
<p>The plugin provides access to the \'Default Templates\', these are the ones provided by the plugin. If you accidentally delete a template that you still want or have edited one and it stops working, you can use this page to re-install the default templates.</p>
<p>There are two options, either \'Install\' which will add the template to your existing list (this creates a new copy of the default template, e.g. \'Thumbnail2\' if it already exists). If the plugin detects that the default version has been upgraded then the \'Upgrade\' option will be available this which will overwrite any existing template with the same Name with the new version.</p>
<p>If you are viewing your existing installed templates there is also an option to \'Reset\' the template back to its default content.</p>
<p>The plugin includes some basic version tracking, based on the Name of the template and the version installed. If you rename the template then it will not be recognised as one of the defaults. The plugin does not track any changes you make to the template, so if you \'Upgrade\' or \'Reset\' a template your changes will be lost.</p>
<p><strong>Exporting Templates</strong></p>
<p>If you wish to backup your templates or move them to another WordPress installation then select the \'Export\' button at the bottom of the Templates section. This will export the details of your templates to a plugin file located in \'amazon-link/extras/amazon-link-exported-templates.php\'. Copy and activate this plugin to add your templates to the list of Default Templates.</p>

',
  ),
);?>