<?php
    for($i = 0; isset($mailboxes[$i]); $i++) {
	if($mailboxes[$i]['mbox'] == $oma->authenticated_user['mbox'] || $mailboxes[$i]['mbox'] == $cuser['mbox'])
	    $tmp2 = '&nbsp;-&nbsp;';
	else
	    $tmp2 = $input->checkbox('user[]', $mailboxes[$i]['mbox']);
	if($cfg['mboxview_pers'])
	    $mailboxes[$i]['mbox']	= $tmp2.($mailboxes[$i]['active'] == 0 ? '<span class="deactivated">' : '')
					.'<a href="'.mkSelfRef(array('cuser' => $mailboxes[$i]['mbox'])).'" title="'.$mailboxes[$i]['mbox'].' ('.$mailboxes[$i]['canonical'].'); R='.$mailboxes[$i]['a_super'].':'.$mailboxes[$i]['a_admin_domains'].':'.$mailboxes[$i]['a_admin_user'].'">'.$mailboxes[$i]['person'].'</a>'
					.($mailboxes[$i]['active'] == 0 ? '</span>' : '');
	else
	    $mailboxes[$i]['mbox']	= $tmp2.($mailboxes[$i]['active'] == 0 ? '<span class="deactivated">' : '')
					.'<a href="'.mkSelfRef(array('cuser' => $mailboxes[$i]['mbox'])).'" title="'.$mailboxes[$i]['person'].' ('.$mailboxes[$i]['canonical'].'); R='.$mailboxes[$i]['a_super'].':'.$mailboxes[$i]['a_admin_domains'].':'.$mailboxes[$i]['a_admin_user'].'">'.$mailboxes[$i]['mbox'].'</a>'
					.($mailboxes[$i]['active'] == 0 ? '</span>' : '');
	$mailboxes[$i]['limits']	= $mailboxes[$i]['num_alias'].'/'.$mailboxes[$i]['max_alias']
					.(($mailboxes[$i]['num_regexp'] + $mailboxes[$i]['max_regexp'] == 0)
					    ? ''
					    : ', '.$mailboxes[$i]['num_regexp'].'/'.$mailboxes[$i]['max_regexp']);
    }
    $mailboxes = array_densify($mailboxes, array('pate'));
?>
<?php if($oma->authenticated_user['a_admin_user'] >= 1) { ?>
    <form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<?= caption(txt('79'), getPageList('<a href="'.mkSelfRef(array('mbox_page' => '%d')).'">%d</a>', $cuser['n_mbox'], $_SESSION['limit']['upper'], $_SESSION['limit'][$cuser['mbox']]['mbox_page']), 580) ?>
<?= $table->outer_shadow_start() ?>
<table border="0" cellpadding="1" cellspacing="1" width="580">
    <tr>
	<td class="std"><b><?= txt('5') ?></b></td>
	<td class="std"><b><?= txt('9') ?></b></td>
	<td class="std"><b><?= txt('87') ?> <sub>[MiB]</sub></b></td>
	<td class="std"><b><?= txt('17') ?></b></td>
	<td class="std"><b><?= txt('82') ?></b></td>
    </tr>
    <?php foreach($mailboxes as $mailbox) { ?>
	<tr>
	    <td class="std"><?= implode('<br />', $mailbox['mbox']) ?></td>
	    <td class="std"><?= $mailbox['pate'][0] ?></td>
	    <td class="std"><?= implode('<br />', $mailbox['quota']) ?></td>
	    <td class="std"><?= implode('<br />', $mailbox['limits']) ?></td>
	    <td class="std"><?= implode('<br />', $mailbox['lastlogin']) ?></td>
	</tr>
    <?php } ?>
</table>
<?= $table->outer_shadow_stop() ?>