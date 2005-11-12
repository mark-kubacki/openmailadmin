<table border="0" cellspacing="0" cellpadding="0">
    <tr>
	<?php foreach($arr_navmenu as $entry) { ?>
	<td>
	    <?php include('templates/shadow/navigation/entry.tpl'); ?>
	</td>
	<?php } ?>
    </tr>
</table>
<img border="0" src="<?= $cfg['images_dir'] ?>/ver_gy1.png" height="1" width="580" alt="" />
<table border="0" cellspacing="0" cellpadding="0" width="580">
    <tr>
	<td class="ed">
	    <b><?= txt('128') ?>: </b><a href="<?= mkSelfRef(array('cuser' => $oma->authenticated_user['mbox'])) ?>"><?= $oma->authenticated_user['person'] ?></a><?php
		if($oma->authenticated_user['pate'] != $oma->authenticated_user['mbox']) { ?>, <b><?= txt('9')?>: </b>
		<?php $oma->authenticated_user['user']['pate'] = &$oma->get_user_row($oma->authenticated_user['pate']); ?><?= $oma->authenticated_user['user']['pate']['person'] ?>
		<?php } ?>
	    <?php if($oma->current_user['mbox'] != $oma->authenticated_user['mbox']) { ?><br />
		<b><?= txt('113') ?>: </b><a href="<?= mkSelfRef(array('cuser' => $oma->current_user['mbox'])) ?>"><?= $oma->current_user['person'] ?></a><?php
		    if($cpate['mbox'] != $oma->authenticated_user['mbox']) { ?>, <b><?= txt('9')?>: </b>
		    <a href="<?= mkSelfRef(array('cuser' => $cpate['mbox'])) ?>"><?= $cpate['person'] ?></a>
		    <?php } ?>
	    <?php } ?>
	</td>
	<td class="ed" align="right">
	    <a href="index.php?login=change"><?= txt('124') ?></a>
	</td>
    </tr>
</table>
<br />&nbsp;