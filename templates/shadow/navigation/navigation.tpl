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
	    <?= txt('9') ?>: <?= $cpate['person'] ?> (<a href="<?= mkSelfRef(array('cuser' => $cpate['mbox'])) ?>"><?= $cpate['mbox'] ?></a>
	    <?php if($cpate['mbox'] != $authinfo['mbox'] && $cuser['mbox'] != $authinfo['mbox']) { ?>
		-&gt;<a href="<?= mkSelfRef(array('cuser' => $authinfo['mbox'])) ?>"><?= $authinfo['mbox'] ?></a>
	    <?php } ?>)
	</td>
	<td class="ed" align="right">
	    <a href="index.php4?login=change"><?= txt('124') ?></a>
	</td>
    </tr>
</table>
<br />&nbsp;