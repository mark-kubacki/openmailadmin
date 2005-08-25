<div id="admin">
<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
	<dl>
	    <dt><?= txt('20') ?></dt>
	    <dd>
		<?= $input->radio('action', 'new') ?> <?= txt('21') ?>
		| <?= $input->radio('action', 'delete') ?><?= txt('22') ?>
		| <?= $input->radio('action', 'change') ?><?= txt('59') ?>
		| <?= $input->radio('action', 'active') ?><?= txt('24') ?>
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
	    <dd><?= ChkS($input->select('pate', $_SESSION['paten'][$cuser['mbox']]), 'c_pate') ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_person', '1') ?><?= txt('84') ?></dt>
	    <dd><?= ChkS($input->text('person', 100), 'c_person') ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_canon', '1') ?><?= txt('7') ?></dt>
	    <dd><?= ChkS($input->text('canonical', 100), 'c_canon') ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_domains', '1') ?><?= txt('86') ?></dt>
	    <dd><?= ChkS($input->text('domains', 100), 'c_domains') ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_quota', '1') ?><?= txt('87') ?></dt>
	    <dd><?= ChkS($input->text('quota', 7), 'c_quota') ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_alias', '1') ?><?= txt('88') ?></dt>
	    <dd><?= ChkS($input->text('max_alias', 4), 'c_alias') ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_regexp', '1') ?><?= txt('89') ?></dt>
	    <dd><?= ChkS($input->text('max_regexp', 4), 'c_regexp') ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('c_reg_exp', '1') ?><?= txt('34') ?></dt>
	    <dd><?= ChkS($input->text('reg_exp', 100), 'c_reg_exp') ?></dd>
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
			<td><?= $input->radio('a_super', '0', array('onchange' => 'c_super.checked=true')) ?></td>
			<td><?= $input->radio('a_super', '1', array('onchange' => 'c_super.checked=true')) ?></td>
			<td>
			    <?php if($authinfo['a_super'] > 1) { ?>
			    <?= $input->radio('a_super', '2', array('onchange' => 'c_super.checked=true')) ?>
			    <?php } ?>
			</td>
		    </tr>
		    <?php } ?>
		    <?php if($authinfo['a_admin_domains'] > 0) { ?>
		    <tr>
			<td class="left"><?= $input->checkbox('c_a_dom', '1') ?><?= txt('50') ?></td>
			<td><?= $input->radio('a_a_dom', '0', array('onchange' => 'c_a_dom.checked=true')) ?></td>
			<td><?= $input->radio('a_a_dom', '1', array('onchange' => 'c_a_dom.checked=true')) ?></td>
			<td>
			    <?php if($authinfo['a_admin_domains'] > 1) { ?>
			    <?= $input->radio('a_a_dom', '2', array('onchange' => 'c_a_dom.checked=true')) ?>
			    <?php } ?>
			</td>
		    </tr>
		    <?php } ?>
		    <?php if($authinfo['a_admin_user'] > 0) { ?>
		    <tr>
			<td class="left"><?= $input->checkbox('c_a_usr', '1') ?><?= txt('70') ?></td>
			<td><?= $input->radio('a_a_usr', '0', array('onchange' => 'c_a_usr.checked=true')) ?></td>
			<td><?= $input->radio('a_a_usr', '1', array('onchange' => 'c_a_usr.checked=true')) ?></td>
			<td>
			    <?php if($authinfo['a_admin_user'] > 1) { ?>
			    <?= $input->radio('a_a_usr', '2', array('onchange' => 'c_a_usr.checked=true')) ?>
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