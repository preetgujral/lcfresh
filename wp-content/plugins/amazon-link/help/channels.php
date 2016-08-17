<?php return array (
  'associate ids' => 
  array (
    'id' => 'amazon-link-channels-associate-ids',
    'page' => 'channels',
    'title' => 'Associate IDs',
    'content' => '
<p>The site owner can enter their Amazon Associate IDs on the Amazon Link Associate IDs Settings page. Enter your IDs for all the locales that you have registered for in the default channel.</p>
<p>Any user of your site (including the owner/administrator) can also add their Affiliate IDs to their User Profile.</p>
<p>It is recommended that the Affiliate IDs in the default channel are set up, as some sections of the site (e.g. shortcodes inserted in sidebar widgets) do not have an \'author\'.</p>
<p>If some of the IDs are not supplied in a User\'s profile, or in a particular Channel, then the ones in the default channel will be used instead. Only if no affiliate IDs are provided for a particular locale will the plugins built-in IDs be used.</p>
',
  ),
  'amazon link channels' => 
  array (
    'id' => 'amazon-link-channels-amazon-link-channels',
    'page' => 'channels',
    'title' => 'Amazon Link Channels',
    'content' => '
<p>The plugin allows the site author to create any number of \'Amazon Tracking ID Channels\' that specify a different set of Affiliate Tracking IDs. This allows the user to monitor how effective particular sections of the site are for generating referrals to Amazon.</p>
<p>The user can generate extra Amazon Tracking IDs by managing their options at Amazon Associates > Your Account > Manage Tracking IDs <a href="https://affiliate-program.amazon.com/gp/associates/network/your-account/manage-tracking-ids.html">US</a>, <a href="https://affiliate-program.amazon.co.uk/gp/associates/network/your-account/manage-tracking-ids.html">UK</A>.</p>
<p>Then on the Amazon Link Associate IDs page create a new Channel and enter the tracking IDs created. </p>
<p>Individual Wordpress users can also add their own tracking IDs to their User profile. Any page of post that they are the author of will automatically use their Affiliate IDs.</p>
<p>When generating Amazon Link shortcodes, either manually add \'chan=channel_id\' to the shortcode or use the drop down selector in the Link Tool to choose one of the available channels. Leave blank to adopt the page author\'s tracking IDs or use the ones defined in the \'Default\' channel.</p>
<p>If the user wants a particular set of Associate IDs to be used for posts of a particular type, category or tag then they can enter rules into the \'Channel Filter\' settings to do this.</p>
',
  ),
  'channel rules' => 
  array (
    'id' => 'amazon-link-channels-channel-rules',
    'page' => 'channels',
    'title' => 'Channel Rules',
    'content' => '
<p>In the Channel settings, there is a text box called \'Channel Filter\', this supports the following strings:</p>
<ul>
<li><code>rand=x%</code> - x% of \'shortcodes\' will use this Channel/set of Associate IDs</li>
<li><code>author=ID</code> - Posts with Author ID \'ID\' will use this Channel</li>
<li><code>cat=X,Y,Z</code> - Posts in these Categories (X,Y,Z) will use this Channel</li>
<li><code>type=[post|page]</code> - Posts of this type will use this Channel</li>
<li><code>tag=A,B,C</code> - Posts with these tags will use this Channel</li>
</ul>
<p>Notes:</p>
<p>You can put multiple rules in the text box (as long as they are different types), and if the post meets any of the rules it will use that channel. For example:</p>
<p><code>cat=food,20,drink<br />
tag=booze</code></p>
<p>Will match all posts in categories with slug \'food\', \'drink\' or ID 20, as well as posts with the tag \'booze\'.</p>
<p>The manual shortcode setting \'chan=MyChannel\' will still override these Rules, see ID Priority below.</p>
<p>The category matching is done by the WordPress function <a href="http://codex.wordpress.org/Function_Reference/has_category">has_category</a> and the tag matching is done by the WordPress function <a href="http://codex.wordpress.org/Function_Reference/has_tag">has_tag</a>. So the items can be the Name, Slug or ID.</p>
',
  ),
  'associate id priority' => 
  array (
    'id' => 'amazon-link-channels-associate-id-priority',
    'page' => 'channels',
    'title' => 'Associate ID Priority',
    'content' => '
<p>If the author of a post specifies a Channel in the Amazon Link shortcode then this will always be used (if it exists).</p>
<p>If the shortcode is in a post that meets one of the specified channel filter rules then this channel will be used.</p>
<p>If the post or page is authored by a user who has specified their own affiliate ids then these will be used next.</p>
<p>Otherwise the Affiliate IDs in the \'default\' channel will be used.</p>
<p>If the Channel selected does not contain affiliate IDs for all locales then ones from the \'default\' Channel will be automatically inserted. If the \'default\' Channel does not have affiliate IDs for that locale then the IDs hardcoded into the plugin will be used.</p>

',
  ),
);?>