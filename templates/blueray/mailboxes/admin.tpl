<div id="admin">
<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
	<dl>
	    <dt><?= txt('20') ?></dt>
	    <dd>
		<ul>
		<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
		<li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
		<li><?= $input->radio('action', 'change') ?><?= txt('59') ?></li>
		<li><?= $input->radio('action', 'active') ?><?= txt('24') ?></li>
		</ul>
	    </dd>
	</dl>
	<dl>
	    <dt>
		<?php if(doubleval($cyr->getversion()) >= 2.2) { ?>
		    <?= $input->checkbox('c_mbox', '1') ?>
		<?php } ?>
		<?= txt('83') ?>
	    </dt>
	    <dd><?= $input->text('mbox', 16) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_pate', '1') ?><?= txt('9') ?></dt>
	    <dd><?= $input->select('pate', $_SESSION['paten'][$cuser['mbox']]) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_person', '1') ?><?= txt('84') ?></dt>
	    <dd><?= $input->text('person', 100) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_canon', '1') ?><?= txt('7') ?></dt>
	    <dd><?= $input->text('canonical', 100) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_domains', '1') ?><?= txt('86') ?></dt>
	    <dd><?= $input->text('domains', 100) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_quota', '1') ?><?= txt('87') ?></dt>
	    <dd><?= $input->text('quota', 7) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_alias', '1') ?><?= txt('88') ?></dt>
	    <dd><?= $input->text('max_alias', 4) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_regexp', '1') ?><?= txt('89') ?></dt>
	    <dd><?= $input->text('max_regexp', 4) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_reg_exp', '1') ?><?= txt('34') ?></dt>
	    <dd><?= $input->text('reg_exp', 100) ?></dd>
	</dl>
	<?php if($authinfo['a_super'] > 1 || $authinfo['a_admin_user'] > 1 || $authinfo['a_admin_domains'] > 1) { ?>
	<div id="admin_acl">
		<table class="admin">
		    <tr>
			<th class="left"><?= txt('77') ?></th>
			<th><?= txt('95') ?></th>
			<th><?= txt('73') ?></th>
			<th><?= txt('74') ?></th>
		    </tr>
		    <?php if($authinfo['a_super'] > 0) { ?>
		    <tr>
			<td class="left"><?= $input->checkbox('c_super', '1') ?><?= txt('68') ?></td>
			<td><?= $input->radio('a_super', '0') ?></td>
			<td><?= $input->radio('a_super', '1') ?></td>
			<td>
			    <?php if($authinfo['a_super'] > 1) { ?>
			    <?= $input->radio('a_super', '2') ?>
			    <?php } ?>
			</td>
		    </tr>
		    <?php } ?>
		    <?php if($authinfo['a_admin_domains'] > 0) { ?>
		    <tr>
			<td class="left"><?= $input->checkbox('c_a_dom', '1') ?><?= txt('50') ?></td>
			<td><?= $input->radio('a_a_dom', '0') ?></td>
			<td><?= $input->radio('a_a_dom', '1') ?></td>
			<td>
			    <?php if($authinfo['a_admin_domains'] > 1) { ?>
			    <?= $input->radio('a_a_dom', '2') ?>
			    <?php } ?>
			</td>
		    </tr>
		    <?php } ?>
		    <?php if($authinfo['a_admin_user'] > 0) { ?>
		    <tr>
			<td class="left"><?= $input->checkbox('c_a_usr', '1') ?><?= txt('70') ?></td>
			<td><?= $input->radio('a_a_usr', '0') ?></td>
			<td><?= $input->radio('a_a_usr', '1') ?></td>
			<td>
			    <?php if($authinfo['a_admin_user'] > 1) { ?>
			    <?= $input->radio('a_a_usr', '2') ?>
			    <?php } ?>
			</td>
		    </tr>
		    <?php } ?>
		</table>
	</div>
	<?php } ?>
    <span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span>
    <?= $input->hidden('frm', 'user') ?>
    <?= $input->submit(txt('27')) ?>
</div>
</div>
</form>