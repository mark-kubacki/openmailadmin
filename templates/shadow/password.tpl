<form action="<?= mkSelfRef() ?>" method="post">
<?= caption(txt('40')) ?>
<?php outer_shadow_start(); ?>
<table border="0" cellpadding="1" cellspacing="1">
	<?php if($oma->current_user['mbox'] == $oma->authenticated_user['mbox']) { ?>
	<tr>
		<td class="std"><?= txt('41') ?></td>
		<td class="std"><?= $input->password('old_pass') ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td class="std" width="180"><?= txt('42') ?></td>
		<td class="std" width="400"><?= $input->password('new_pass1') ?></td>
	</tr>
	<tr>
		<td class="std"><?= txt('43') ?></td>
		<td class="std"><?= $input->password('new_pass2') ?></td>
	</tr>
	<tr>
		<td class="std" colspan="2" align="right">
			<?= $input->hidden('frm', 'pass') ?>
			<?= $input->hidden('action', 'change') ?>
			<?= $input->submit(txt('27')) ?>&nbsp;
		</td>
	</tr>
</table>
<?php outer_shadow_stop(); ?>
</form>
<br />