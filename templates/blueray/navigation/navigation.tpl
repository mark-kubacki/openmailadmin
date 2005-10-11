<div id="header">
    <div id="header_img"><img src="<?= $cfg['images_dir'] ?>/blueray/logo_img.png" alt="Openmailadmin logo - the ray" /></div>
    <h1>Openmailadmin.org</h1>
    <div id="header_info">
	<?= txt('6') ?> <a href="<?= mkSelfRef(array('cuser' => $oma->authenticated_user['mbox'])) ?>" title="<?= txt('6') ?>"><?= $oma->authenticated_user['person'] ?></a> | <a href="index.php4?login=change" title="<?= txt('124') ?>"><?= txt('124') ?></a>
    </div>
    <ul>
	<?php foreach($arr_navmenu as $entry) { ?>
	    <?php if($entry['active']) { ?>
	    <li class="active">
	    <?php } else { ?>
	    <li>
	    <?php } ?>
		<a href="<?= $entry['link'] ?>" title="<?= $entry['caption'] ?>"><?= $entry['caption'] ?></a>
	    </li>
	<?php } ?>
    </ul>
</div>

<div id="sidebar">
    <div id="userlist" class="cigarblock">
	<ul>
	    <li>
		<dl>
		    <dt><?= txt('128') ?></dt>
		    <dd><?= $oma->authenticated_user['person'] ?></dd>
		    <dd><a href="<?= mkSelfRef(array('cuser' => $oma->authenticated_user['mbox'])) ?>" title="<?= txt('6') ?>"><?= $oma->authenticated_user['mbox'] ?></a></dd>
		</dl>
	    </li>
	    <?php if($oma->authenticated_user['pate'] != $oma->authenticated_user['mbox']) { ?>
	    <li>
		<dl>
		    <dt><?= txt('9')?></dt>
		    <dd><?php $oma->authenticated_user['user']['pate'] = &$oma->get_user_row($oma->authenticated_user['pate']); ?><?= $oma->authenticated_user['user']['pate']['person'] ?></dd>
		    <dd><a href="<?= mkSelfRef(array('cuser' => $oma->authenticated_user['pate'])) ?>" title="<?= txt('9')?>"><?= $oma->authenticated_user['pate'] ?></a></dd>
		</dl>
	    </li>
	    <?php } ?>
	</ul>
	<?php if($cuser['mbox'] != $oma->authenticated_user['mbox']) { ?>
	    <ul>
		<li>
		    <dl>
			<dt><?= txt('113') ?></dt>
			<dd><?= $cuser['person'] ?></dd>
			<dd><a href="<?= mkSelfRef(array('cuser' => $cuser['mbox'])) ?>" title="<?= txt('113') ?>"><?= $cuser['mbox'] ?></a></dd>
		    </dl>
		</li>
	    <?php if($cpate['mbox'] != $oma->authenticated_user['mbox']) { ?>
	    <li>
		<dl>
		    <dt><?= txt('9')?></dt>
		    <dd><?= $cpate['person'] ?></dd>
		    <dd><a href="<?= mkSelfRef(array('cuser' => $cpate['mbox'])) ?>" title="<?= txt('9')?>"><?= $cpate['mbox'] ?></a></dd>
		</dl>
	    </li>
	    <?php } ?>
	    </ul>
	<?php } ?>
    </div>
</div>

<div id="content">