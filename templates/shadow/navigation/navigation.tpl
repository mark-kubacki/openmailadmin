<table border="0" cellspacing="0" cellpadding="0">
    <tr>
	<?php foreach($arr_navmenu as $entry) { ?>
	<td>
	    <?php include('./templates/shadow/navigation/entry.tpl'); ?>
	</td>
	<?php } ?>
    </tr>
</table>
<img border="0" src="<?= $cfg['images_dir'] ?>/ver_gy1.png" height="1" width="580" alt="" />
<table border="0" cellspacing="0" cellpadding="0" width="580">
	<tr>
		<td class="ed">
			<b><?= txt('128') ?>: </b><a href="<?= mkSelfRef(array('cuser' => $oma->authenticated_user->ID)) ?>"><?= $oma->authenticated_user->person ?></a><?php
				$apate = $oma->authenticated_user->get_pate();
				if($oma->authenticated_user != $apate) { ?>, <b><?= txt('9')?>: </b>
					<?= $apate->person ?>
				<?php } ?>
			<?php if($oma->current_user != $oma->authenticated_user) { ?><br />
				<b><?= txt('113') ?>: </b><a href="<?= mkSelfRef(array('cuser' => $oma->current_user->ID)) ?>"><?= $oma->current_user->person ?></a><?php
				if($cpate != $oma->authenticated_user) { ?>, <b><?= txt('9')?>: </b>
				<a href="<?= mkSelfRef(array('cuser' => $cpate->ID)) ?>"><?= $cpate->person ?></a>
				<?php } ?>
			<?php } ?>
		</td>
		<td class="ed" align="right">
			<a href="index.php?login=change"><?= txt('124') ?></a>
		</td>
	</tr>
</table>
<br />&nbsp;
