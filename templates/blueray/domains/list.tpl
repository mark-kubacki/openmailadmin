<?php
    for($i = 0; isset($domains[$i]); $i++) {
	if($domains[$i]['selectable'])
	    $domains[$i]['domain']	= $input->checkbox('dom[]', $domains[$i]['ID']).$domains[$i]['domain'];
	else
	    $domains[$i]['domain']	= '&nbsp;&nbsp;&nbsp;'.$domains[$i]['domain'];
    }
    $domains = array_densify($domains, array('owner', 'a_admin'));
?>
<?php if($oma->authenticated_user['a_admin_domains'] > 0) { ?>
    <form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<div id="data">
<h2><?= txt('54') ?></h2>
<span class="pagelist"><?= getPageList('<a href="'.mkSelfRef(array('dom_page' => '%d')).'">%d</a>', $oma->current_user['n_domains'], $_SESSION['limit']['upper'], $_SESSION['limit'][$oma->current_user['mbox']]['dom_page']) ?></span>
<table class="data">
    <tr>
	<th><?= txt('55') ?></th>
	<th><?= txt('56') ?></th>
	<th><?= txt('57') ?></th>
	<th><?= txt('58') ?></th>
    </tr>
    <?php foreach($domains as $domain) { ?>
	<tr>
	    <td><?= implode('<br />', $domain['domain']) ?></td>
	    <td><?= $domain['owner'][0] ?></td>
	    <td><?= $domain['a_admin'][0] ?></td>
	    <td><?= implode('<br />', $domain['categories']) ?></td>
	</tr>
    <?php } ?>
</table>
</div>