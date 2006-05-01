<?php
for($i = 0; isset($domains[$i]); $i++) {
	if($domains[$i]['selectable'])
		$domains[$i]['domain']	= $input->checkbox('dom[]', $domains[$i]['ID']).$domains[$i]['domain'];
	else
		$domains[$i]['domain']	= '&nbsp;&nbsp;&nbsp;'.$domains[$i]['domain'];
}
count_same_cols($domains, 'owner', 'n_owner');
count_same_cols($domains, 'a_admin', 'n_admin');
?><?php if($oma->authenticated_user['a_admin_domains'] > 0) { ?>
	<form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<div id="data">
<h2><?= txt('54') ?></h2>
<span class="pagelist"><?= getPageList('<a href="'.mkSelfRef(array('dom_page' => '%d')).'">%d</a>', $oma->current_user['n_domains'], $_SESSION['limit'], $_SESSION['offset']['dom_page']) ?></span>
<table class="data">
	<tr>
		<th><?= txt('55') ?></th>
		<th><?= txt('56') ?></th>
		<th><?= txt('57') ?></th>
		<th><?= txt('58') ?></th>
	</tr>
	<?php foreach($domains as $domain) { ?>
	<tr>
		<td><?= $domain['domain'] ?></td>
		<?php if(isset($domain['n_owner'])) { ?>
			<td rowspan="<?= $domain['n_owner'] ?>"><?= $domain['owner'] ?></td>
		<?php } ?>
		<?php if(isset($domain['n_admin'])) { ?>
			<td rowspan="<?= $domain['n_admin'] ?>"><?= $domain['a_admin'] ?></td>
		<?php } ?>
		<td><?= $domain['categories'] ?></td>
	</tr>
	<?php } ?>
</table>
</div>