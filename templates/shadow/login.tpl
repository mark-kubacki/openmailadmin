<?php if(isset($login_error)) { ?>
	<div id="login_error">
		<?php error($login_error, 320); ?>
	</div>
<?php } ?>
<br />
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
<?= caption(txt('122').' - '.txt('121')) ?>
<?php outer_shadow_start(); ?>
<table border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="std" width="80"><b><?= txt('5') ?></b></td>
		<td class="std" width="230"><?= $input->text('mboxname', 16) ?></td>
	</tr>
	<?php if(count($cfg['Servers']['verbose']) > 1) { ?>
	<tr>
		<td class="std"><b><?= txt('102') ?></b></td>
		<td class="std"><?= $input->select('server', $cfg['Servers']['verbose'], $cfg['Servers']['number']) ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td class="std"><b><?= txt('90') ?></b></td>
		<td class="std"><?= $input->password('password', 64) ?></td>
	</tr>
	<tr>
		<td class="std"></td>
		<td class="std"><?= $input->hidden('frm', 'login') ?><?= $input->submit(txt('27')) ?></td>
	</tr>
</table>
<?php outer_shadow_stop(); ?>
<br />
</form>