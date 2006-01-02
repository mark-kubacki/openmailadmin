<?php if(!is_null($right) && $right != '' && !is_null($width)) { ?>
<table border="0" cellpadding="0" cellspacing="0" width="<?= $width ?>">
<?php } else { ?>
<table border="0" cellpadding="0" cellspacing="0">
<?php } ?>
	<tr>
		<td>
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td class="caption">&nbsp;<?= $text ?>&nbsp;</td>
				</tr>
			</table>
		</td>
		<td class="sh_hor" style="width: 6px">
			<img border="0" src="<?= $cfg['images_dir'] ?>/sh_lu.gif" width="6" height="6" alt="\" />
		</td>
		<?php if(!is_null($right) && $right != '') { ?>
		<td align="right" width="95%" class="ed"><?= $right ?></td>
		<?php } ?>
	</tr>
</table>