<?= caption(txt('3')) ?>
<?php outer_shadow_start(); ?>
<table border="0" cellpadding="1" cellspacing="1">
	<?php foreach($information as $entry) { ?>
	<tr>
		<td class="std" width="180"><b><?= $entry[0] ?></b></td>
		<td class="std" width="400"><?= $entry[1] ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td class="std" width="180"><b><?= txt('9') ?></b></td>
		<td class="std" width="400">
			<?= $cpate->person ?> (<a href="<?= mkSelfRef(array('cuser' => $cpate->ID)) ?>"><?= $cpate->mbox ?></a><?php if($cpate != $oma->authenticated_user && $oma->current_user != $oma->authenticated_user) { ?>
				-&gt;<a href="<?= mkSelfRef(array('cuser' => $oma->authenticated_user->ID)) ?>"><?= $oma->authenticated_user->mbox ?></a>
			<?php } ?>)
		</td>
	</tr>
</table>
<?php outer_shadow_stop(); ?>
<br />
