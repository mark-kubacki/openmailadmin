<div id="admin">
<form action="<?= mkSelfRef() ?>" method="post">
<h2><?= txt('40') ?></h2>
    <?php if($cuser['mbox'] == $authinfo['mbox']) { ?>
    <dl>
	<dt><?= txt('41') ?></dt>
	<dd><?= $input->password('old_pass') ?></dd>
    </dl>
    <?php } ?>
    <dl>
	<dt><?= txt('42') ?></dt>
	<dd><?= $input->password('new_pass1') ?></dd>
    </dl>
    <dl>
	<dt><?= txt('43') ?></dt>
	<dd><?= $input->password('new_pass2') ?></dd>
    </dl>
<?= $input->hidden('frm', 'pass') ?>
<?= $input->hidden('action', 'change') ?>
<?= $input->submit(txt('27')) ?>
</form>
</div>