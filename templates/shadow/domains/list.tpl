<?php
count_same_cols($domains, 'owner', 'n_owner');
count_same_cols($domains, 'a_admin', 'n_admin');
?>
<?php if($oma->authenticated_user->a_admin_domains > 0) { ?>
	<form action="<?= mkSelfRef() ?>" method="post">
<?php } ?>
<?= caption(txt('54'), getPageList('<a href="'.mkSelfRef(array('dom_page' => '%d')).'">%d</a>', $oma->current_user->get_number_domains(), $_SESSION['limit'], $_SESSION['offset']['dom_page'])) ?>
<?php outer_shadow_start(); ?>
<table border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="std" width="190"><b><?= txt('55') ?></b></td>
		<td class="std" width="80"><b><?= txt('56') ?></b></td>
		<td class="std" width="160"><b><?= txt('57') ?></b></td>
		<td class="std" width="150"><b><?= txt('58') ?></b></td>
	</tr>
	<?php foreach($domains as $domain) { ?>
		<tr>
			<td class="std">
				<?php if($domain['selectable']) { ?>
					<?= $input->checkbox('dom[]', $domain['ID']) ?>
				<?php } else { ?>
					<?= $input->checkbox('dom[]', $domain['ID'], array('disabled' => '1')) ?>
				<?php } ?>
				<?= $domain['domain'] ?>
			</td>
			<?php if(isset($domain['n_owner'])) { ?>
				<td class="std" rowspan="<?= $domain['n_owner'] ?>"><?= $domain['owner'] ?></td>
			<?php } ?>
			<?php if(isset($domain['n_admin'])) { ?>
				<td class="std" rowspan="<?= $domain['n_admin'] ?>"><?= $domain['a_admin'] ?></td>
			<?php } ?>
			<td class="std"><?= $domain['categories'] ?></td>
		</tr>
	<?php } ?>
</table>
<?php outer_shadow_stop(); ?>