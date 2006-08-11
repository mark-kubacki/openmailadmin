<?php
for($i = 0; isset($alias[$i]); $i++) {
	if($alias[$i]['active'] == 1) {
		$alias[$i]['alias']	= $input->checkbox('address[]', $alias[$i]['ID']).$alias[$i]['alias'];
	}
	else {
		$alias[$i]['alias']	= $input->checkbox('address[]', $alias[$i]['ID']).'<span class="deactivated">'.$alias[$i]['alias'].'</span>';
	}
}
$alias = array_densify($alias, array('domain', 'dest'));
for($i = 0; isset($alias[$i]); $i++) {
	if(count($alias[$i]['dest'][0]) < $cfg['address']['hide_threshold'])
		$alias[$i]['dest'][0] = implode('<br />', $alias[$i]['dest'][0]);
	else
		$alias[$i]['dest'][0] = '<span class="quasi_btn">'.sprintf(txt('96'), count($alias[$i]['dest'][0])).' &raquo;</span><div><span class="quasi_btn">&laquo; '.sprintf(txt('96'), count($alias[$i]['dest'][0])).'</span><br />'.implode('<br />', $alias[$i]['dest'][0]).'</div>';
}
?>
<form action="<?= mkSelfRef() ?>" method="post">
<?= caption(txt('17').'&nbsp;'.$oma->current_user->get_used_alias().'/'.$oma->current_user->max_alias, getPageList('<a href="'.mkSelfRef(array('addr_page' => '%d')).'">%d</a>', $oma->current_user->get_used_alias(), $_SESSION['limit'], $_SESSION['offset']['addr_page']), 580) ?>
<?php outer_shadow_start(); ?>
<table border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="std" colspan="2" width="320"><b><?= txt('18') ?></b></td>
		<td class="std" width="260"><b><?= txt('19') ?></b></td>
	</tr>
	<?php foreach($alias as $entry) { ?>
		<tr>
			<td class="std"><?= implode('<br />', $entry['alias']) ?></td>
			<td class="std">@<?= $entry['domain'][0] ?></td>
			<td class="std addr_dest"><?= $entry['dest'][0] ?></td>
		</tr>
	<?php } ?>
</table>
<?php outer_shadow_stop(); ?>