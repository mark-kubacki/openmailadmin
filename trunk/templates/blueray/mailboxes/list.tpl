<?php
    for($i = 0; isset($mailboxes[$i]); $i++) {
	$mailboxes[$i]['limits']	= $mailboxes[$i]['num_alias'].'/'.$mailboxes[$i]['max_alias']
					.(($mailboxes[$i]['num_regexp'] + $mailboxes[$i]['max_regexp'] == 0)
					    ? ''
					    : ', '.$mailboxes[$i]['num_regexp'].'/'.$mailboxes[$i]['max_regexp']);
    }
?>
<?php if($authinfo['a_admin_user'] >= 1) { ?>
    <form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<div id="data">
<h2><?= txt('79') ?></h2>
<span class="pagelist"><?= getPageList('<a href="'.mkSelfRef(array('mbox_page' => '%d')).'">%d</a>', $cuser['n_mbox'], $_SESSION['limit']['upper'], $_SESSION['limit'][$cuser['mbox']]['mbox_page']) ?></span>
<table class="data">
    <tr>
	<th><?= txt('5') ?></th>
	<th><?= txt('9') ?></th>
	<th><?= txt('87') ?> <sub>[MiB]</sub></th>
	<th><?= txt('17') ?></th>
	<th><?= txt('82') ?></th>
    </tr>
    <?php foreach($mailboxes as $mailbox) { ?>
	<tr>
	    <td>
		<?php if($mailbox['mbox'] == $authinfo['mbox'] || $mailbox['mbox'] == $cuser['mbox']) { ?>
		    &nbsp;-&nbsp;
		<?php } else { ?>
		    <?= $input->checkbox('user[]', $mailbox['mbox']) ?>
		<?php } ?>
		<?php if($mailbox['active'] == 0) { ?>
		    <span class="deactivated">
		<?php } else { ?>
		    <span>
		<?php } ?>
		<?php if($cfg['mboxview_pers']) { ?>
		    <a href="<?= mkSelfRef(array('cuser' => $mailbox['mbox'])) ?>"
		       title="<?= $mailbox['mbox'] ?> (<?= $mailbox['canonical'] ?>);
		              R=<?= $mailbox['a_super'] ?>:<?= $mailbox['a_admin_domains'] ?>:<?= $mailbox['a_admin_user'] ?>">
			<?= $mailbox['person'] ?>
		    </a>
		<?php } else { ?>
		    <a href="<?= mkSelfRef(array('cuser' => $mailbox['mbox'])) ?>"
		       title="<?= $mailbox['person'] ?> (<?= $mailbox['canonical'] ?>);
		              R=<?= $mailbox['a_super'] ?>:<?= $mailbox['a_admin_domains'] ?>:<?= $mailbox['a_admin_user'] ?>">
			<?= $mailbox['mbox'] ?>
		    </a>
		<?php } ?>
		</span>
	    </td>
	    <td><?= $mailbox['pate'] ?></td>
	    <td><?= $mailbox['quota'] ?></td>
	    <td>
		<?= $mailbox['num_alias'] ?>/<?= $mailbox['max_alias'] ?>
		<?php if($mailbox['num_regexp'] + $mailbox['max_regexp'] > 0) { ?>
		    | <span title="<?= txt('33') ?>"><?= $mailbox['num_regexp'] ?>/<?= $mailbox['max_regexp'] ?></span>
		<?php } ?>
	    </td>
	    <td><?= $mailbox['lastlogin'] ?></td>
	</tr>
    <?php } ?>
</table>
</div>