<div id="admin">
<div id="admin_panel">
<?php if($has_acl_a) { ?>
    <form action="<?= mkSelfRef() ?>" method="post">
	<dl>
	    <dt><?= txt('105') ?></dt>
	    <dd><?= $_GET['folder'] ?></dd>
	</dl>
	<dl>
	    <dt><?= txt('20') ?></dt>
	    <dd>
		<?= $input->radio('action', 'new') ?><?= txt('21') ?>
		| <?= $input->radio('action', 'delete') ?><?= txt('22') ?>
		| <?= $input->radio('action', 'rights') ?><?= txt('106') ?>
	    </dd>
	</dl>
	<dl>
	    <dt><?= txt('107') ?></dt>
	    <dd>
		<?= $input->_generate('text', 'subname', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '190')) ?>
	    </dd>
	</dl>
	<dl>
	    <dt><?= txt('108') ?></dt>
	    <dd>
		<?= hsys_ACL_matrix($ACLs, true) ?>
	    </dd>
	</dl>
    <?= $input->hidden('frm', 'ACL') ?>
    <?= $input->submit(txt('27'))?>
    </form>
<?php } else { ?>
	<dl>
	    <dt><?= txt('105') ?></dt>
	    <dd><?= $_GET['folder'] ?></dd>
	</dl>
	<dl>
	    <dt><?= txt('108') ?></dt>
	    <dd>
		<?= hsys_ACL_matrix($ACLs) ?>
	    </dd>
	</dl>
<?php } ?>
</div>
</div>