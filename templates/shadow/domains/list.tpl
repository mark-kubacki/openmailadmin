<?php if($oma->authenticated_user['a_admin_domains'] > 0) { ?>
    <form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<?= caption(txt('54'), getPageList('<a href="'.mkSelfRef(array('dom_page' => '%d')).'">%d</a>', $oma->current_user['n_domains'], $_SESSION['limit']['upper'], $_SESSION['limit'][$oma->current_user['mbox']]['dom_page'])) ?>
<?= $table->outer_shadow_start() ?>
<table border="0" cellpadding="1" cellspacing="1">
    <tr>
	<td class="std" width="190"><b><?= txt('55') ?></b></td>
	<td class="std" width="80"><b><?= txt('56') ?></b></td>
	<td class="std" width="160"><b><?= txt('57') ?></b></td>
	<td class="std" width="150"><b><?= txt('58') ?></b></td>
    </tr>
    <?php foreach($domains as $domain) { ?>
	<tr>
	    <td class="std">
		<?= $input->checkbox('dom[]', $domain['ID']) ?>
		<?= $domain['domain'] ?>
	    </td>
	    <td class="std"><?= $domain['owner'] ?></td>
	    <td class="std"><?= $domain['a_admin'] ?></td>
	    <td class="std"><?= $domain['categories'] ?></td>
	</tr>
    <?php } ?>
</table>
<?= $table->outer_shadow_stop() ?>