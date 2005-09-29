<div id="header">
    <div id="header_img"><img src="<?= $cfg['images_dir'] ?>/blueray/logo_img.png" alt="Openmailadmin logo - the ray" /></div>
    <h1>Openmailadmin.org</h1>
    <div id="header_info">
	<?= txt('6') ?> <a href="<?= mkSelfRef(array('cuser' => $authinfo['mbox'])) ?>" title="<?= txt('6') ?>"><?= $authinfo['person'] ?></a> | <a href="index.php4?login=change" title="<?= txt('124') ?>"><?= txt('124') ?></a>
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
		    <dd><?= $authinfo['person'] ?></dd>
		    <dd><a href="<?= mkSelfRef(array('cuser' => $authinfo['mbox'])) ?>" title="<?= txt('6') ?>"><?= $authinfo['mbox'] ?></a></dd>
		</dl>
	    </li>
	    <?php if($authinfo['pate'] != $authinfo['mbox']) { ?>
	    <li>
		<dl>
		    <dt><?= txt('9')?></dt>
		    <dd><?php $authinfo['user']['pate'] = &$oma->get_user_row($authinfo['pate']); ?><?= $authinfo['user']['pate']['person'] ?></dd>
		    <dd><a href="<?= mkSelfRef(array('cuser' => $authinfo['pate'])) ?>" title="<?= txt('9')?>"><?= $authinfo['pate'] ?></a></dd>
		</dl>
	    </li>
	    <?php } ?>
	</ul>
	<?php if($cuser['mbox'] != $authinfo['mbox']) { ?>
	    <ul>
		<li>
		    <dl>
			<dt><?= txt('113') ?></dt>
			<dd><?= $cuser['person'] ?></dd>
			<dd><a href="<?= mkSelfRef(array('cuser' => $cuser['mbox'])) ?>" title="<?= txt('113') ?>"><?= $cuser['mbox'] ?></a></dd>
		    </dl>
		</li>
	    <?php if($cpate['mbox'] != $authinfo['mbox']) { ?>
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