<?php

$wishlist_template = htmlspecialchars ('
<div class="al_found%FOUND%">
 <div class="amazon_prod">
  <div class="amazon_img_container">
   %LINK_OPEN%<img class="%IMAGE_CLASS%" src="%THUMB%">%LINK_CLOSE%
  </div>
  <div class="amazon_text_container">
   <p>%LINK_OPEN%%TITLE%%LINK_CLOSE%</p>
   <div class="amazon_details">
     <p>by %ARTIST% [%MANUFACTURER%]<br />
        Rank/Rating: %RANK%/%RATING%<br />
        <b>Price: <span class="amazon_price">%PRICE%</span></b>
    </p>
   </div>
  </div>
 </div>
<img src="http://www.assoc-amazon.%TLD%/e/ir?t=%TAG%&l=as2&o=%MPLACE_ID%&a=%ASIN%" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
</div>');

$multinational_template = htmlspecialchars ('
<div class="amazon_prod">
 <div class="amazon_img_container">
  %LINK_OPEN%<img class="%IMAGE_CLASS%" src="%THUMB%">%LINK_CLOSE%
 </div>
 <div class="amazon_text_container">
  <p>%LINK_OPEN%%TITLE%%LINK_CLOSE%</p>
  <div class="amazon_details">
   <p>by %ARTIST% [%MANUFACTURER%]<br />
    <b>Price: <span class="amazon_price">
     <span class="al_found%FOUND%uk#">%LINK_OPEN%uk# <img style="height:10px" src="%FLAG%uk#"> %PRICE%uk#%LINK_CLOSE%</span>
     <span class="al_found%FOUND%fr#">%LINK_OPEN%FR# <img style="height:10px" src="%FLAG%fr#"> %PRICE%FR#%LINK_CLOSE%</span>
     <span class="al_found%FOUND%de#">%LINK_OPEN%de# <img style="height:10px" src="%FLAG%de#"> %PRICE%DE#%LINK_CLOSE%</span>
     <span class="al_found%FOUND%es#">%LINK_OPEN%es# <img style="height:10px" src="%FLAG%es#"> %PRICE%es#%LINK_CLOSE%</span>
    </b>
   </p>
  </div>
 </div>
</div>
<img src="http://www.assoc-amazon.%TLD%/e/ir?t=%TAG%&l=as2&o=%MPLACE_ID%&a=%ASIN%" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
');

$carousel_template = htmlspecialchars ('
<script type=\'text/javascript\'>
var amzn_wdgt={widget:\'Carousel\'};
amzn_wdgt.tag=\'%TAG%\';
amzn_wdgt.widgetType=\'ASINList\';
amzn_wdgt.ASIN=\'%ASINs%\';
amzn_wdgt.title=\'%TEXT%\';
amzn_wdgt.marketPlace=\'%MPLACE%\';
amzn_wdgt.width=\'600\';
amzn_wdgt.height=\'200\';
</script>
<script type=\'text/javascript\' src=\'http://wms-%REGION%.amazon-adsystem.com/20070822/%MPLACE%/js/swfobject_1_5.js\'>
</script>');


$iframe_template = htmlspecialchars ('
<iframe src="http://rcm-%REGION%.amazon-adsystem.com/e/cm?lt1=_blank&bc1=000000&IS2=1&bg1=FFFFFF&fc1=000000&lc1=0000FF&t=%TAG%&o=%MPLACE_ID%&p=8&l=as4&m=amazon&f=ifr&ref=ss_til&asins=%ASIN%&MarketPlace=%MPLACE%" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
');

$iframe2_template = htmlspecialchars ('
<iframe style="width:120px;height:240px;" marginwidth="0" marginheight="0" scrolling="no" frameborder="0" src="http://ws-%REGION%.amazon-adsystem.com/widgets/q?region=%MPLACE%&ServiceVersion=20070822&OneJS=1&Operation=GetAdHtml&MarketPlace=%MPLACE%&source=ss&ref=ss_til&ad_type=product_link&tracking_id=%TAG%&marketplace=amazon&placement=%ASIN%&asins=%ASIN%&linkId=DGJQFSEETVP4VISP&show_border=true&link_opens_in_new_window=true">
</iframe>');

$image_template = htmlspecialchars ('
<div class="al_found%FOUND%">
 %LINK_OPEN%<img alt="%TITLE%" title="%TITLE%" src="%IMAGE%" class="%IMAGE_CLASS%">%LINK_CLOSE%
<img src="http://www.assoc-amazon.%TLD%/e/ir?t=%TAG%&l=as2&o=%MPLACE_ID%&a=%ASIN%" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
</div>
');


$mp3_clips_template = htmlspecialchars ('
<script type=\'text/javascript\'>
var amzn_wdgt={widget:\'MP3Clips\'};
amzn_wdgt.tag=\'%TAG%\';
amzn_wdgt.widgetType=\'ASINList\';
amzn_wdgt.ASIN=\'%ASINS%\';
amzn_wdgt.title=\'%TEXT%\';
amzn_wdgt.width=\'250\';
amzn_wdgt.height=\'250\';
amzn_wdgt.shuffleTracks=\'True\';
amzn_wdgt.marketPlace=\'%MPLACE%\';
</script>
<script type=\'text/javascript\' src=\'http://wms-%REGION%.amazon-adsystem.com/20070822/%MPLACE%/js/swfobject_1_5.js\'>
</script>');


$my_favourites_template = htmlspecialchars ('
<script type=\'text/javascript\'>
var amzn_wdgt={widget:\'MyFavorites\'};
amzn_wdgt.tag=\'%TAG%\';
amzn_wdgt.columns=\'1\';
amzn_wdgt.rows=\'3\';
amzn_wdgt.title=\'%TEXT%\';
amzn_wdgt.width=\'250\';
amzn_wdgt.ASIN=\'%ASINS%\';
amzn_wdgt.showImage=\'True\';
amzn_wdgt.showPrice=\'True\';
amzn_wdgt.showRating=\'True\';
amzn_wdgt.design=\'5\';
amzn_wdgt.colorTheme=\'White\';
amzn_wdgt.headerTextColor=\'#FFFFFF\';
amzn_wdgt.marketPlace=\'%MPLACE%\';
</script>
<script type=\'text/javascript\' src=\'http://wms-%REGION%.amazon-adsystem.com/20070822/%MPLACE%/js/AmazonWidgets.js\'>
</script>');


$thumbnail_template = htmlspecialchars ('
<div class="al_found%FOUND%">
 %LINK_OPEN%<img alt="%TITLE%" title="%TITLE%" src="%THUMB%" class="%IMAGE_CLASS%">%LINK_CLOSE%
<img src="http://www.assoc-amazon.%TLD%/e/ir?t=%TAG%&l=as2&o=%MPLACE_ID%&a=%ASIN%" width="1" height="1" border="0" alt="" style="border:none !important; margin:0px !important;" />
</div>');

$preview_script_template = ('
<script type="text/javascript" src="http://wms-%REGION%.amazon-adsystem.com/20070822/%MPLACE%/js/link-enhancer-common.js?tag=%TAG%">
</script>
<noscript>
    <img src="http://wms-%REGION%.amazon-adsystem.com/20070822/%MPLACE%/img/noscript.gif?tag=%TAG%" alt="" />
</noscript>');

$easy_banner_template = htmlspecialchars ('
<iframe src="http://rcm-%REGION%.amazon-adsystem.com/e/cm?t=%TAG%&o=%MPLACE_ID%&p=48&l=ur1&category=amazonhomepage&f=ifr&linkID=TUCSMEO7D7NEDG5M" width="728" height="90" scrolling="no" border="0" marginwidth="0" style="border:none;" frameborder="0"></iframe>');

$add_to_cart_template = htmlspecialchars ('
<form method="GET" action="http://www.amazon.%TLD%/gp/aws/cart/add.html">
 <input type="hidden" name="AssociateTag" value="%TAG%"/>
 <input type="hidden" name="SubscriptionId" value="%PUB_KEY%"/>
 <input type="hidden" name="ASIN.1" value="%ASIN%"/>
 <input type="hidden" name="Quantity.1" value="1"/>
 <input type="image" name="add" value="Buy from Amazon.%TLD%" border="0" alt="Buy from Amazon.%TLD%" src="%BUY_BUTTON%">
</form>');


         $this->default_templates = array (
            'add to cart' => array ( 'Name' => 'Add To Cart', 'Description' => __('Buy From Amazon Button', 'amazon-link'), 
                                 'Content' => $add_to_cart_template, 'Version' => '2', 'Notice' => 'Remove line breaks', 'Type' => 'Product', 'Preview_Off' => 0 ),
            'banner easy' => array ( 'Name' => 'Banner Easy', 'Description' => __('Easy Banner (468x60)', 'amazon-link'), 
                                 'Content' => $easy_banner_template, 'Version' => '2', 'Notice' => 'Upgrade for Amazon Ad Migration (1st August 2015).', 'Type' => 'No ASIN', 'Preview_Off' => 0 ),
            'carousel' => array ( 'Name' => 'Carousel', 'Description' => __('Amazon Carousel Widget (limited locales)', 'amazon-link'), 
                                  'Content' => $carousel_template, 'Version' => '2', 'Notice' => 'Upgrade for Amazon Ad Migration (1st August 2015).', 'Type' => 'Multi', 'Preview_Off' => 0 ),
            'iframe image' => array ( 'Name' => 'Iframe Image', 'Description' => __('Standard Amazon Image Link', 'amazon-link'), 
                                  'Content' => $iframe_template, 'Type' => 'Product', 'Version' => '2', 'Notice' => 'Upgrade for Amazon Ad Migration (1st August 2015).', 'Preview_Off' => 0 ),
            'iframe image2' => array ( 'Name' => 'Iframe Image', 'Description' => __('Standard Amazon Image Link', 'amazon-link'),
                                  'Content' => $iframe2_template, 'Type' => 'Product', 'Version' => '1', 'Notice' => 'New Style iFrame Image.', 'Preview_Off' => 0 ),
            'image' => array ( 'Name' => 'Image', 'Description' => __('Localised Image Link', 'amazon-link'), 
                                  'Content' => $image_template, 'Type' => 'Product', 'Version' => '2', 'Notice' => 'Add impression tracking', 'Preview_Off' => 0 ),
            'mp3 clips' => array ( 'Name' => 'MP3 Clips', 'Description' => __('Amazon MP3 Clips Widget (limited locales)', 'amazon-link'), 
                                  'Content' => $mp3_clips_template, 'Version' => '2', 'Notice' => 'Upgrade for Amazon Ad Migration (1st August 2015).', 'Type' => 'Multi', 'Preview_Off' => 0 ),
            'my favourites' => array ( 'Name' => 'My Favourites', 'Description' => __('Amazon My Favourites Widget (limited locales)', 'amazon-link'), 
                                  'Content' => $my_favourites_template, 'Version' => '2', 'Notice' => 'Upgrade for Amazon Ad Migration (1st August 2015).', 'Type' => 'Multi', 'Preview_Off' => 0 ),
            'preview script' => array ( 'Name' => 'Preview Script', 'Description' => __('Add Amazon Preview Pop-up script (limited locales)', 'amazon-link'),
                                  'Content' => $preview_script_template, 'Version' => '2', 'Notice' => 'Upgrade for Amazon Ad Migration (1st August 2015).', 'Type' => 'No ASIN', 'Preview_Off' => 1 ),
            'thumbnail' => array ( 'Name' => 'Thumbnail', 'Description' => __('Localised Thumb Link', 'amazon-link'), 
                                  'Content' => $thumbnail_template, 'Type' => 'Product', 'Version' => '2', 'Notice' => 'Add impression tracking', 'Preview_Off' => 0 ),
            'wishlist' => array ( 'Name' => 'Wishlist', 'Description' => __('Used to generate the wishlist', 'amazon-link'), 
                                  'Content' => $wishlist_template, 'Type' => 'Product', 'Version' => '2', 'Notice' => 'Add impression tracking', 'Preview_Off' => 0),
            'multinational' => array ( 'Name' => 'Multinational', 'Description' => __('Example Multinational Template', 'amazon-link'), 
                                  'Content' => $multinational_template, 'Type' => 'Product', 'Version' => '3', 'Notice' => 'Use style to modify height', 'Preview_Off' => 0)

         );

?>
