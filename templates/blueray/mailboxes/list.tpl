<?php
for($i = 0; isset($mailboxes[$i]); $i++) {
	$mailboxes[$i]['limits']
	= $mailboxes[$i]['num_alias'].'/'.$mailboxes[$i]['max_alias']
		.(($mailboxes[$i]['num_regexp'] + $mailboxes[$i]['max_regexp'] == 0)
			? ''
			: ', '.$mailboxes[$i]['num_regexp'].'/'.$mailboxes[$i]['max_regexp']);
}
count_same_cols($mailboxes, 'pate', 'n_paten');
?>
<?php if($oma->authenticated_user['a_admin_user'] >= 1) { ?>
	<form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<div id="data">
<h2><?= txt('79') ?></h2>
<span class="pagelist"><?= getPageList('<a href="'.mkSelfRef(array('mbox_page' => '%d')).'">%d</a>', $oma->current_user['n_mbox'], $_SESSION['limit'], $_SESSION['offset']['mbox_page']) ?></span>
<table class="data">
	<tr>
		<th><?= txt('5') ?></th>
		<th><?= txt('9') ?></th>
		<th><?= txt('87') ?> <sub>[MiB]</sub></th>
		<th><?= txt('17') ?></th>
		<th><?= txt('82') ?></th>
	</tr>
	<?php foreach($mailboxes as $mailbox) { ?>
		<?php if(time() - $mailbox['lastlogin'] < $cfg['mboxview_sec']) { ?>
		<tr class="recent_login">
		<?php } else { ?>
		<tr>
		<?php } ?>
			<td>
				<?php if($mailbox['mbox'] == $oma->authenticated_user['mbox'] || $mailbox['mbox'] == $oma->current_user['mbox']) { ?>
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
			<?php if(isset($mailbox['n_paten'])) { ?>
				<td rowspan="<?= $mailbox['n_paten'] ?>"><?= $mailbox['pate'] ?></td>
			<?php } ?>
			<td><?= $imap->get_users_quota($mailbox['mbox']) ?></td>
			<td>
				<?= $mailbox['num_alias'] ?>/<?= $mailbox['max_alias'] ?>
				<?php if($mailbox['num_regexp'] + $mailbox['max_regexp'] > 0) { ?>
					| <span title="<?= txt('33') ?>"><?= $mailbox['num_regexp'] ?>/<?= $mailbox['max_regexp'] ?></span>
				<?php } ?>
			</td>
			<td>
				<?php if($mailbox['lastlogin'] < 3000) { ?>
					<?= txt('132') ?>
				<?php } else { ?>
					<?= date($cfg['date_format'], $mailbox['lastlogin']) ?>
				<?php } ?>
			</td>
		</tr>
	<?php } ?>
</table>
</div>