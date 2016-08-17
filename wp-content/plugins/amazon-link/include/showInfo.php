<?php
   $info = get_plugin_data($this->filename);
?>
<div class="amazon-link-info">
<div class="amazon-link-info-heading"><?php echo $info['Name']?></div>

<p><?php echo $info['Description']?></p>
<dl>
<dt>Author:</dt>
<dd><?php echo $info['Author']?></dd>
</dl>
<dl>
<dt>Documentation:</dt>
<dd><a href="http://wordpress.org/extend/plugins/<?php echo $info['TextDomain']?>/">WordPress Plugin Page</a></dd>
</dl>
<dl>
<dt>Homepage:</dt>
<dd><?php echo $info['Title']?></dd>
</dl>
<dl>
<dt>Support:</dt>
<dd><a href="http://wordpress.org/support/plugin/<?php echo $info['TextDomain']?>/">WordPress Forum</a></dd>
</dl>
<dl>
<dl>
<dt>Review:</dt>
<dd><a href="http://wordpress.org/support/view/plugin-reviews/<?php echo $info['TextDomain']?>/">Plugin Review Page</a></dd>
</dl>
<dl>
<dt>Version:</dt>
<dd><?php echo $info['Version']?></dd>
</dl>
</div>