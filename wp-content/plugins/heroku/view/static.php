<?php
if(!defined('LINK_PREVIEW')) return;
?>
<table id="lp-wrapper">
	<tr>
		<td class="lp-thumb-preview">
			<div class="lp-thumb-holder" style="background-image: url('<?php echo $object->image;?>');"></div>
			<a rel="nofollow" target="_blank" href="<?php echo $object->url;?>"></a>
		</td>
		<td class="lp-content">
			<span class="lp-content-title"><?php echo $object->title;?></span>
			<p class="lp-content-description"><?php echo $object->description;?></p>
			<p class="lp-content-source"><a rel="nofollow" href="<?php echo $object->url;?>" target="_blank"><?php echo $object->host_url;?></a></p>
		</td>
	</tr>
</table>