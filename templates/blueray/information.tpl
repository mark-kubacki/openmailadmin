<div id="data">
<h2><?= txt('3') ?></h2>
    <?php foreach($information as $entry) { ?>
    <dl>
	<dt><?= $entry[0] ?></dt>
	<dd><?= $entry[1] ?></dd>
    </dl>
    <?php } ?>
    <dl>
	<dt><?= txt('9') ?></dt>
	<dd>
	    <?= $cpate['person'] ?> (<a href="<?= mkSelfRef(array('cuser' => $cpate['mbox'])) ?>" title="<?= txt('9') ?>"><?= $cpate['mbox'] ?></a>
	    <?php if($cpate['mbox'] != $authinfo['mbox'] && $cuser['mbox'] != $authinfo['mbox']) { ?>
		-&gt;<a href="<?= mkSelfRef(array('cuser' => $authinfo['mbox'])) ?>" title="<?= txt('6') ?>"><?= $authinfo['mbox'] ?></a>
	    <?php } ?>)
	</dd>
    </dl>
</div>