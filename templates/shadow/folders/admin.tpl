<div id="admin_panel">
<?php if($has_acl_a) { ?>
	<form action="<?= mkSelfRef() ?>" method="post">
	<table border="0" cellpadding="1" cellspacing="1">
		<tr>
			<td class="ed" width="180"><?= txt('105') ?></td>
			<td class="ed" width="400"><?= $_GET['folder'] ?></td>
		</tr>
		<tr>
			<td class="ed"><b><?= txt('20') ?></b></td>
			<td class="ed">
				<ul class="ed">
					<li><?= $input->radio('action', 'new') ?><?= txt('21') ?></li>
					<li><?= $input->radio('action', 'rights') ?><?= txt('106') ?></li>
					<li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
				</ul>
			</td>
		</tr>
		<tr>
			<td class="ed"><b><?= txt('107') ?></b></td>
			<td class="ed">
				<?= $input->_generate('text', 'subname', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '190')) ?>
			</td>
		</tr>
		<tr>
			<td class="ed"><b><?= txt('108') ?></b></td>
			<td class="ed">
				<?= $input->hidden('dummy', 'ACL') ?>
				<?php hsys_ACL_matrix($ACLs, true) ?>
			</td>
		</tr>
		<tr>
			<td class="ed" colspan="2" align="right"><?= $input->hidden('frm', 'ACL') ?><?= $input->submit(txt('27'))?>&nbsp;</td>
		</tr>
	</table>
	</form>
<?php } else { ?>
	<table border="0" cellpadding="1" cellspacing="1">
		<tr>
			<td class="ed" width="180"><?= txt('105') ?></td>
			<td class="ed" width="400"><?= $_GET['folder'] ?></td>
		</tr>
		<tr>
			<td class="ed"><b><?= txt('108') ?></b></td>
			<td class="ed">
				<?php hsys_ACL_matrix($ACLs) ?>
			</td>
		</tr>
	</table>
<?php } ?>
</div>
<br />