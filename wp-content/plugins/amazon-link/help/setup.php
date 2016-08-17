<?php return array (
  'setup' => 
  array (
    'id' => 'amazon-link-setup-setup',
    'page' => 'setup',
    'title' => 'Setup',
    'content' => '
<p>Use the Amazon Link plugin\'s Setup page to quickly enable all the plugin\'s major features. It\'s a good place to start for new users.</p>
<p>None of the steps is compulsory but each step will improve the capability of the plugin so that you can take advantage of all the features the plugin has to offer.</p>
<p>To get the plugin working to its full potential there are currently 6 steps:</p>
<ol>
<li>Select your home or default country</li>
<li>Enter your Amazon Associate ID\'s</li>
<li>Enable localisation of your Amazon Links</li>
<li>Enable support for further plugin development and also enable (6)</li>
<li>Enable access to plugin extras</li>
<li>Enable access to the Amazon Product Advertising Database</li>
<li>Enable and Install the Amazon Product Cache</li>
</ol>
',
  ),
  'home country' => 
  array (
    'id' => 'amazon-link-setup-home-country',
    'page' => 'setup',
    'title' => 'Home Country',
    'content' => '
<p>This controls which Amazon site is the default for all searches, product lookups and links.</p>
<h5>Creating Links</h5>
<p>When creating links using the \'Add Amazon Link\' helper on the Post/Page edit screen this is the Amazon site that the plugin will use to search for items. It is important that this reflects the location of the visitors to your site as not all products (and their unique ASINs) are available on all Amazon locales.</p>
<h5>Displaying Links</h5>
<p>If localisation is not enabled then all product information displayed and all links created on your site will go to this country\'s Amazon site. Or if localisation is enabled but a product can not be found on a visitor\'s local Amazon site then the details from this locale will be displayed instead.</p>
',
  ),
  'amazon associate account' => 
  array (
    'id' => 'amazon-link-setup-amazon-associate-account',
    'page' => 'setup',
    'title' => 'Amazon Associate Account',
    'content' => '
<p>To actually earn commission from sales made through Amazon Links on your site you must sign up to the Amazon Associate program for your targeted locales. I recommend you at least sign up for your home locale and the United States. Sign up for an Associate Account at the following sites: </p>
<ul>
<li><a href="https://affiliate-program.amazon.co.uk/" title="UK Associate Program" target="_blank">Amazon.co.uk Associates</a></li>
<li><a href="https://affiliate-program.amazon.com/" title="US Associate Program" target="_blank">Amazon.com Associates</a></li>
<li><a href="https://associates.amazon.ca/" title="Canadian Associate Program" target="_blank">Amazon.ca Associates</a></li>
<li><a href="https://associates.amazon.cn/" title="Chinese Associate Program" target="_blank">Amazon.cn Associates</a></li>
<li><a href="https://partnernet.amazon.de/" title="German Associate Program" target="_blank">Amazon.de Associates</a></li>
<li><a href="https://afiliados.amazon.es/" title="Spanish Associate Program" target="_blank">Amazon.es Associates</a></li>
<li><a href="https://partenaires.amazon.fr/" title="French Associate Program" target="_blank">Amazon.fr Associates</a></li>
<li><a href="https://programma-affiliazione.amazon.it/" title="Italian Associate Program" target="_blank">Amazon.it Associates</a></li>
<li><a href="https://affiliate.amazon.co.jp/" title="UK Associate Program" target="_blank">Amazon.jp Associates</a></li>
<li><a href="https://associates.amazon.in/" title="Indian Associate Program" target="_blank">Amazon.in Associates</a></li>
<li><a href="https://associados.amazon.com.br/" title="Brasil Associate Program" target="_blank">Amazon.com.br Associates</a></li>
</ul>
<p>Once you have signed up for your home locale, Amazon may allow you to quickly sign up for associated accounts - e.g. Signing up for Amazon.co.uk will then prompt you to sign up for Germany, France, Spain and Italy. </p>
<p>At the end of this process you should have a set of \'Associates IDs\' that you need to enter into the plugin entering the IDs in the appropriate country field.</p>
',
  ),
  'link localisation' => 
  array (
    'id' => 'amazon-link-setup-link-localisation',
    'page' => 'setup',
    'title' => 'Link Localisation',
    'content' => '
<p>To take advantage of the localisation options and all those Associate Accounts you have just signed up for you should enable localisation. This will target the Amazon links to Amazon store local to your site visitors. </p>
<p>Enabling this option will install a database that is used to lookup visitor\'s IP address and map it to their country, this may take a few seconds to install so please be patient.</p>
<p>Every time a visitor views your site the plugin will detect their country of origin using their IP address. The plugin will then ensure that all Amazon Links displayed will direct them to their local Amazon site, where they are more likely to find products relevant to them.</p>
<p>Note: Periodically the ip2nation database is updated and you need to check the Amazon Link options page to see if you need to re-install it.</p>
',
  ),
  'developer support' => 
  array (
    'id' => 'amazon-link-setup-developer-support',
    'page' => 'setup',
    'title' => 'Developer Support',
    'content' => '
<p>This plugin has been developed over several years in my spare time and any donation is appreciated. Your donations and support plays a crucial role in Free and Open Source Software projects. </p>
<p>By enabling the \'Plugin Associate IDs\' option you are helping to support the plugin with no cost to yourself. Once this option is enabled the plugin will use its own Associate IDs <strong>but only on locales for which you have not entered an associate ID</strong>. That handful of Yen or Euros you have earned but can not collect from Amazon would go a long way to supporting future plugin development. </p>
<p>As a developer the more donations and support I receive the more time I can invest in working on Free and Open Source Software projects. Donations help cover the cost of hardware for development and to pay hosting bills. This is critical to the development of free software. </p>
<p>Enabling this option will also enable access to Plugin Extras such as extra keywords, Kindle prices, custom developed features and much more.</p>
<p>I greatly appreciate any support or direct donation you can make to help further development of this plugin. </p>
<p>You have my thanks in advance.</p>
',
  ),
  'amazon product advertising database' => 
  array (
    'id' => 'amazon-link-setup-amazon-product-advertising-database',
    'page' => 'setup',
    'title' => 'Amazon Product Advertising Database',
    'content' => '
<p>Enabling access to the Amazon Advertising API is free and allows the plugin to fetch live information on all Amazon products.</p>
<p>Once your Access keys have been validated this enables the following features of the plugin:</p>
<ul>
<li>The ability to search for products to insert into your posts</li>
<li>It allows the plugin to fetch live up to date product data for every visitor</li>
<li>It adds the ability to automatically create lists of related products</li>
<li>The ability to create live lists of products based on a keyword search</li>
<li>To automatically detect if a product is available on the visitors local Amazon site.</li>
</ul>
<p>To sign up to the Advertising API and get your access keys you need to go to the Associates Central Home page. From their navigate to the \'Product Advertising API\' page and click on the \'Sign Up Now\' button this should take you to a sign up page, e.g for the UK it is here: <a href="https://affiliate-program.amazon.co.uk/gp/flex/advertising/api/sign-in.html" title="Advertising API Sign In" target="_blank">https://affiliate-program.amazon.co.uk/gp/flex/advertising/api/sign-in.html</a>.</p>
<p>Sign in using the account details set up for your Associate Account. Once you have successfully registered (thankfully only once for all domains) click on \'Manage Your Account\' then \'Access Identifiers\'. This should take you to the AWS portal.</p>
<p>Log in to the <a href="https://portal.aws.amazon.com/gp/aws/manageYourAccount" title="AWS Portal" target="_blank">AWS Portal</a> using the same account details as before. Under the \'Services You\'re Signed Up For\' section it should list at least \'Product Advertising API\'.</p>
<p>On this page select \'Security Credentials\' then scroll down to \'Access Credentials\' -> \'Access Keys\'. The \'Access Key ID\' and the \'Secret Access Key\' need to be copied into the Amazon Link Settings Page -> Options -> Amazon Affiliate Information -> \'AWS Public Key\' and \'AWS Private key\' Settings. Update the settings and the \'AWS Keys Validated\' should now be ticked.</p>
<p>Congratulations you now have a full set of Amazon Credentials to drive the Amazon Link Plugin!</p>
',
  ),
  'amazon product cache' => 
  array (
    'id' => 'amazon-link-setup-amazon-product-cache',
    'page' => 'setup',
    'title' => 'Amazon Product Cache',
    'content' => '
<p>If you use the plugin to its full extent and display a lot of content retrieved from the Amazon Web Service it is recommended that you enable the Amazon Product Cache.</p>
<p>Enabling the cache will improve page load times for visitors as the plugin does not need to retrieve live data from the Amazon Web Service for every page hit. It does this by keeping a local copy of the product information in the WordPress database.</p>
<p>The plugin will ensure that the data stored in the cache is refreshed periodically to help ensure that the product information displayed is always up to date.</p>

',
  ),
);?>