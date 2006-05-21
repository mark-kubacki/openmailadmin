<?php
count_same_cols($mailboxes, 'pate', 'n_paten');
?>
<?php if($oma->authenticated_user->a_admin_user >= 1) { ?>
	<form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<?= caption(txt('79'), getPageList('<a href="'.mkSelfRef(array('mbox_page' => '%d')).'">%d</a>', $oma->current_user->n_mbox, $_SESSION['limit'], $_SESSION['offset']['mbox_page']), 580) ?>
<?php outer_shadow_start(); ?>
<table border="0" cellpadding="1" cellspacing="1" width="580">
	<tr>
		<td class="std"><b><?= txt('5') ?></b></td>
		<td class="std"><b><?= txt('9') ?></b></td>
		<td class="std" colspan="2"><b><?= txt('87') ?> <sub>[MiB]</sub></b></td>
		<td class="std"><b><?= txt('17') ?></b></td>
		<td class="std"><b><?= txt('82') ?></b></td>
	</tr>
	<?php foreach($mailboxes as $mailbox) { ?>
		<?php if(time() - $mailbox['lastlogin'] < $cfg['mboxview_sec']) { ?>
		<tr class="recent_login">
		<?php } else { ?>
		<tr>
		<?php } ?>
			<td class="std">
				<?php if($mailbox['mbox'] == $oma->authenticated_user->mbox || $mailbox['mbox'] == $oma->current_user->mbox) { ?>
					&nbsp;-&nbsp;
				<?php } else { ?>
					<?= $input->checkbox('user[]', $mailbox['mbox']) ?>
				<?php } ?>
				<?php if($mailbox['active'] == 0) { ?>
					<span class="deactivated">
				<?php } else { ?>
					<span class="active">
				<?php } ?>
					<?php if($cfg['mboxview_pers']) { ?>
						<a href="<?= mkSelfRef(array('cuser' => $mailbox['mbox'])) ?>" title="<?= $mailbox['mbox'].' ('.$mailbox['canonical'].'); R='.$mailbox['a_super'].':'.$mailbox['a_admin_domains'].':'.$mailbox['a_admin_user'] ?>"><?= $mailbox['person'] ?></a>
					<?php } else { ?>
						<a href="<?= mkSelfRef(array('cuser' => $mailbox['mbox'])) ?>" title="<?= $mailbox['person'].' ('.$mailbox['canonical'].'); R='.$mailbox['a_super'].':'.$mailbox['a_admin_domains'].':'.$mailbox['a_admin_user'] ?>"><?= $mailbox['mbox'] ?></a>
					<?php } ?>
					</span>
			</td>
			<?php if(isset($mailbox['n_paten'])) { ?>
				<td class="std" rowspan="<?= $mailbox['n_paten'] ?>"><?= $mailbox['pate'] ?></td>
			<?php } ?>
			<td class="std"><?= $imap->get_users_quota($mailbox['mbox'])->format('</td><td class="std">') ?></td>
			<td class="std">
				<?= $mailbox['num_alias'].'/'.$mailbox['max_alias'] ?><?= ($mailbox['num_regexp'] + $mailbox['max_regexp'] == 0) ? '' : ', '.$mailbox['num_regexp'].'/'.$mailbox['max_regexp'] ?>
			</td>
			<td class="std">
				<?php if($mailbox['lastlogin'] < 3000) { ?>
					<?= txt('132') ?>
				<?php } else { ?>
					<?= date($cfg['date_format'], $mailbox['lastlogin']) ?>
				<?php } ?>
			</td>
		</tr>
	<?php } ?>
</table>
<?php outer_shadow_stop(); ?>