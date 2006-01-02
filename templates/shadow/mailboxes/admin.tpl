<?php
	$input->arrProperties['text']	= array('style' => 'width: 98%');
	$input->arrClass['text']		= 'textwhite';
?>
<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
<table border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="ed" width="180"><b><?= txt('20') ?></b></td>
		<td class="ed" width="400">
			<ul class="ed">
				<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
				<li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
				<li><?= $input->radio('action', 'change') ?><?= txt('59') ?></li>
				<li><?= $input->radio('action', 'active') ?><?= txt('24') ?></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="ed">
			<?php if(version_compare($imap->getversion(), '2.2.0') >= 0) { ?>
				<?= $input->checkbox('change[]', 'mbox') ?>
			<?php } ?>
			<b><?= txt('83') ?></b>
		</td>
		<td class="ed"><?= $input->text('mbox', 16) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'pate') ?><b><?= txt('9') ?></b></td>
		<td class="ed"><?= $input->select('pate', $selectable_paten) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'person') ?><b><?= txt('84') ?></b></td>
		<td class="ed"><?= $input->text('person', 100) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'canonical') ?><b><?= txt('7') ?></b></td>
		<td class="ed"><?= $input->text('canonical', 100) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'domains') ?><b><?= txt('86') ?></b></td>
		<td class="ed"><?= $input->text('domains', 100) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'quota') ?><b><?= txt('87') ?></b></td>
		<td class="ed"><?= $input->text('quota', 7) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'max_alias') ?><b><?= txt('88') ?></b></td>
		<td class="ed"><?= $input->text('max_alias', 4) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'max_regexp') ?><b><?= txt('89') ?></b></td>
		<td class="ed"><?= $input->text('max_regexp', 4) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'reg_exp') ?><b><?= txt('34') ?></b></td>
		<td class="ed"><?= $input->text('reg_exp', 100) ?></td>
	</tr>
	<?php if($oma->authenticated_user['a_super'] > 1 || $oma->authenticated_user['a_admin_user'] > 1 || $oma->authenticated_user['a_admin_domains'] > 1) { ?>
	<tr>
		<td class="ed" colspan="2">
			<table border="0" cellpadding="1" cellspacing="1">
				<tr>
					<td class="ed" width="180"><b><?= txt('77') ?></b></td>
					<td class="ed" width="134"><?= txt('95') ?></td>
					<td class="ed" width="133"><?= txt('73') ?></td>
					<td class="ed" width="133"><?= txt('74') ?></td>
				</tr>
				<?php if($oma->authenticated_user['a_super'] > 0) { ?>
				<tr>
					<td class="ed"><?= $input->checkbox('change[]', 'a_super') ?><?= txt('68') ?></td>
					<td class="ed"><?= $input->radio('a_super', '0') ?></td>
					<td class="ed"><?= $input->radio('a_super', '1') ?></td>
					<td class="ed">
						<?php if($oma->authenticated_user['a_super'] > 1) { ?>
						<?= $input->radio('a_super', '2') ?>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
				<?php if($oma->authenticated_user['a_admin_domains'] > 0) { ?>
				<tr>
					<td class="ed"><?= $input->checkbox('change[]', 'a_admin_domains') ?><?= txt('50') ?></td>
					<td class="ed"><?= $input->radio('a_admin_domains', '0') ?></td>
					<td class="ed"><?= $input->radio('a_admin_domains', '1') ?></td>
					<td class="ed">
						<?php if($oma->authenticated_user['a_admin_domains'] > 1) { ?>
						<?= $input->radio('a_admin_domains', '2') ?>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
				<?php if($oma->authenticated_user['a_admin_user'] > 0) { ?>
				<tr>
					<td class="ed"><?= $input->checkbox('change[]', 'a_admin_user') ?><?= txt('70') ?></td>
					<td class="ed"><?= $input->radio('a_admin_user', '0') ?></td>
					<td class="ed"><?= $input->radio('a_admin_user', '1') ?></td>
					<td class="ed">
						<?php if($oma->authenticated_user['a_admin_user'] > 1) { ?>
						<?= $input->radio('a_admin_user', '2') ?>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
			</table>
		</td>
	</tr>
	<?php } ?>
	<tr>
		<td class="ed"><span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span></td>
		<td class="ed" align="right"><?= $input->hidden('frm', 'user') ?><?= $input->submit(txt('27'))?>&nbsp;</td>
	</tr>
</table>
</div>
</form>
<br />