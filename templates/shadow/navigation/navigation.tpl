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
	    <b><?= txt('128') ?>: </b><a href="<?= mkSelfRef(array('cuser' => $authinfo['mbox'])) ?>"><?= $authinfo['person'] ?></a><?php
		if($authinfo['pate'] != $authinfo['mbox']) { ?>, <b><?= txt('9')?>: </b>
		<?php $authinfo['user']['pate'] = &$oma->get_user_row($authinfo['pate']); ?><?= $authinfo['user']['pate']['person'] ?>
		<?php } ?>
	    <?php if($cuser['mbox'] != $authinfo['mbox']) { ?><br />
		<b><?= txt('113') ?>: </b><a href="<?= mkSelfRef(array('cuser' => $cuser['mbox'])) ?>"><?= $cuser['person'] ?></a><?php
		    if($cpate['mbox'] != $authinfo['mbox']) { ?>, <b><?= txt('9')?>: </b>
		    <a href="<?= mkSelfRef(array('cuser' => $cpate['mbox'])) ?>"><?= $cpate['person'] ?></a>
		    <?php } ?>
	    <?php } ?>
	</td>
	<td class="ed" align="right">
	    <a href="index.php4?login=change"><?= txt('124') ?></a>
	</td>
    </tr>
</table>
<br />&nbsp;