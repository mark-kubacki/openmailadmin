<div id="admin">
<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
	<dl>
		<dt><?= txt('20') ?></dt>
		<dd>
			<ul>
			<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
			<?php if(count($mailboxes) > 0) { ?>
			<li><?= $input->radio('action', 'change') ?><?= txt('59') ?></li>
			<li><?= $input->radio('action', 'active') ?><?= txt('24') ?></li>
			<li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
			<?php } ?>
			</ul>
		</dd>
	</dl>
	<dl>
		<dt>
		<?php if(version_compare($imap->getversion(), '2.2.0') >= 0) { ?>
			<?= $input->checkbox('change[]', 'mbox') ?>
		<?php } ?>
		<?= txt('83') ?>
		</dt>
		<dd><?= $input->text('mbox', 16) ?></dd>
	</dl>
	<?php if(count($selectable_paten) > 1) { ?>
	<dl>
		<dt><?= $input->checkbox('change[]', 'pate') ?><?= txt('9') ?></dt>
		<dd><?= $input->hidden('dummy2', 'pate') ?><?= $input->select('pate', $selectable_paten) ?></dd>
	</dl>
	<?php } ?>
	<dl>
		<dt><?= $input->checkbox('change[]', 'person') ?><?= txt('84') ?></dt>
		<dd><?= $input->text('person', 100) ?></dd>
	</dl>
	<dl>
		<dt><?= $input->checkbox('change[]', 'canonical') ?><?= txt('7') ?></dt>
		<dd><?= $input->text('canonical', 100) ?></dd>
	</dl>
	<dl>
		<dt><?= $input->checkbox('change[]', 'domains') ?><?= txt('86') ?></dt>
		<dd><?= $input->text('domains', 100, $oma->current_user['domains']) ?></dd>
	</dl>
	<dl>
		<dt><?= $input->checkbox('change[]', 'quota') ?><?= txt('87') ?></dt>
		<dd><?= $input->text('quota', 7) ?></dd>
	</dl>
	<dl>
		<dt><?= $input->checkbox('change[]', 'max_alias') ?><?= txt('88') ?></dt>
		<dd><?= $input->text('max_alias', 4) ?></dd>
	</dl>
	<?php if($oma->authenticated_user['max_regexp'] > 0) { ?>
	<dl>
		<dt><?= $input->checkbox('change[]', 'max_regexp') ?><?= txt('89') ?></dt>
		<dd><?= $input->text('max_regexp', 4) ?></dd>
	</dl>
	<?php } ?>
	<?php if($oma->authenticated_user['a_super'] > 1 || $oma->authenticated_user['a_admin_user'] > 1 || $oma->authenticated_user['a_admin_domains'] > 1) { ?>
	<div id="admin_acl">
		<table class="admin">
			<?= $input->hidden('dummy', 'rights') ?>
			<tr>
				<th class="left"><?= txt('77') ?></th>
				<th><?= txt('95') ?></th>
				<th><?= txt('73') ?></th>
				<th><?= txt('74') ?></th>
			</tr>
			<?php if($oma->authenticated_user['a_super'] > 0) { ?>
			<tr>
				<td class="left"><?= $input->checkbox('change[]', 'a_super') ?><?= txt('68') ?></td>
				<td><?= $input->radio('a_super', '0') ?></td>
				<td><?= $input->radio('a_super', '1') ?></td>
				<td>
					<?php if($oma->authenticated_user['a_super'] > 1) { ?>
					<?= $input->radio('a_super', '2') ?>
					<?php } ?>
				</td>
			</tr>
			<?php } ?>
			<?php if($oma->authenticated_user['a_admin_domains'] > 0) { ?>
			<tr>
				<td class="left"><?= $input->checkbox('change[]', 'a_admin_domains') ?><?= txt('50') ?></td>
				<td><?= $input->radio('a_admin_domains', '0') ?></td>
				<td><?= $input->radio('a_admin_domains', '1') ?></td>
				<td>
					<?php if($oma->authenticated_user['a_admin_domains'] > 1) { ?>
					<?= $input->radio('a_admin_domains', '2') ?>
					<?php } ?>
				</td>
			</tr>
			<?php } ?>
			<?php if($oma->authenticated_user['a_admin_user'] > 0) { ?>
			<tr>
				<td class="left"><?= $input->checkbox('change[]', 'a_admin_user') ?><?= txt('70') ?></td>
				<td><?= $input->radio('a_admin_user', '0') ?></td>
				<td><?= $input->radio('a_admin_user', '1') ?></td>
				<td>
					<?php if($oma->authenticated_user['a_admin_user'] > 1) { ?>
					<?= $input->radio('a_admin_user', '2') ?>
					<?php } ?>
				</td>
			</tr>
			<?php } ?>
		</table>
	</div>
	<?php } ?>
	<span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span>
	<?php if(count($selectable_paten) <= 1) { ?>
		<?= $input->hidden('pate', $oma->current_user['mbox']) ?>
	<?php } ?>
	<?= $input->hidden('frm', 'user') ?>
	<?= $input->submit(txt('27')) ?>
</div>
</div>
</form>