<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<?php if($entry['active']) { ?>
					<td class="caption">
					<?php } else { ?>
					<td class="caption_dea">
					<?php } ?>
						&nbsp;<a href="<?= $entry['link'] ?>" class="white"><?= $entry['caption'] ?></a>&nbsp;
					</td>
				</tr>
			</table>
		</td>
		<td valign="top" class="sh_hor" style="width: 6px">
			<img border="0" src="<?= $cfg['images_dir'] ?>/sh_lu.gif" width="6" height="6" alt="\" />
		</td>
	</tr>
</table>