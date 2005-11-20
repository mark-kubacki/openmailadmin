<table class="acl_matrix">
<tr>
	<th><?= txt('6') ?></th>
	<th><?= implode('</th><th>', $rights) ?></th>
</tr>
<?php foreach($ACL as $ACL_user => $ACL_given) { ?>
<tr>
	<td><?= $ACL_user ?></td>
	<?php foreach($rights as $key=>$right) { ?>
		<td>
		<?php if(stristr($ACL_given, $right)) { ?>
			<img border="0" src="<?= $cfg['images_dir'] ?>/acl/yes.png" alt="yes" />
		<?php } else { ?>
			<img border="0" src="<?= $cfg['images_dir'] ?>/acl/not.png" alt="no" />
		<?php } ?>
		</td>
	<?php } ?>
</tr>
<?php } ?>
<?php if($editable) { ?>
<tr>
	<td rowspan="2">
		<?= $input->_generate('text', 'moduser', null, array('class' => 'textwhite', 'style' => 'width: 120px', 'maxlength' => '64')) ?>
	</td>
	<?php foreach($rights as $key=>$right) { ?>
		<td><?= $input->checkbox('modacl[]', $right) ?></td>
	<?php } ?>
</tr>
<tr>
	<td colspan="<?= count($rights) ?>">
		<?= $input->select('modaclsel', array_values($presets), array_keys($presets)) ?>
	</td>
</tr>
<?php } ?>
</table>