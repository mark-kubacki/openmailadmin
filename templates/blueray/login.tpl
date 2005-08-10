<?php if(isset($login_error)) { ?>
    <div id="login_error">
	<?= $login_error ?>
    </div>
<?php } ?>
<div id="login">
    <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
    <h1><?= txt('122') ?></h1>
    <ul>
	<li>
	    <dl>
		<dt><?= txt('5') ?></dt>
		<dd><?= $input->text('mboxname', 16) ?></dd>
	    </dl>
	</li>
	<?php if(count($cfg['Servers']['verbose']) > 1) { ?>
	<li>
	    <dl>
		<dt><?= txt('102') ?></dt>
		<dd><?= $input->select('server', $cfg['Servers']['verbose'], $cfg['Servers']['number']) ?></dd>
	    </dl>
	</li>
	<?php } ?>
	<li>
	    <dl>
		<dt><?= txt('90') ?></dt>
		<dd><?= $input->password('password', 64) ?></dd>
	    </dl>
	</li>
    </ul>
    <?= $input->hidden('frm', 'login') ?>
    <?= $input->submit(txt('27')) ?>
    </form>
</div>