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
		<?= $input->radio('action', 'new') ?> <?= txt('21') ?>
		&nbsp;| <?= $input->radio('action', 'delete') ?><?= txt('22') ?>
		&nbsp;| <?= $input->radio('action', 'change') ?><?= txt('59') ?>
		&nbsp;| <?= $input->radio('action', 'active') ?><?= txt('24') ?>
	    </td>
	</tr>
	<tr>
	    <td class="ed">
		<?php if(doubleval($cyr->getversion()) >= 2.2) { ?>
		    <?= $input->checkbox('c_mbox', '1') ?>
		<?php } ?>
		<b><?= txt('83') ?></b>
	    </td>
	    <td class="ed"><?= $input->text('mbox', 16) ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_pate', '1') ?><b><?= txt('9') ?></b></td>
	    <td class="ed"><?= ChkS($input->select('pate', $_SESSION['paten'][$cuser['mbox']]), 'c_pate') ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_person', '1') ?><b><?= txt('84') ?></b></td>
	    <td class="ed"><?= ChkS($input->text('person', 100), 'c_person') ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_canon', '1') ?><b><?= txt('7') ?></b></td>
	    <td class="ed"><?= ChkS($input->text('canonical', 100), 'c_canon') ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_domains', '1') ?><b><?= txt('86') ?></b></td>
	    <td class="ed"><?= ChkS($input->text('domains', 100), 'c_domains') ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_quota', '1') ?><b><?= txt('87') ?></b></td>
	    <td class="ed"><?= ChkS($input->text('quota', 7), 'c_quota') ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_alias', '1') ?><b><?= txt('88') ?></b></td>
	    <td class="ed"><?= ChkS($input->text('max_alias', 4), 'c_alias') ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_regexp', '1') ?><b><?= txt('89') ?></b></td>
	    <td class="ed"><?= ChkS($input->text('max_regexp', 4), 'c_regexp') ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_reg_exp', '1') ?><b><?= txt('34') ?></b></td>
	    <td class="ed"><?= ChkS($input->text('reg_exp', 100), 'c_reg_exp') ?></td>
	</tr>
	<?php if($authinfo['a_super'] > 1 || $authinfo['a_admin_user'] > 1 || $authinfo['a_admin_domains'] > 1) { ?>
	<tr>
	    <td class="ed" colspan="2">
		<table border="0" cellpadding="1" cellspacing="1">
		    <tr>
			<td class="ed" width="180"><b><?= txt('77') ?></b></td>
			<td class="ed" width="134"><?= txt('95') ?></td>
			<td class="ed" width="133"><?= txt('73') ?></td>
			<td class="ed" width="133"><?= txt('74') ?></td>
		    </tr>
		    <?php if($authinfo['a_super'] > 0) { ?>
		    <tr>
			<td class="ed"><?= $input->checkbox('c_super', '1') ?><?= txt('68') ?></td>
			<td class="ed"><?= $input->radio('a_super', '0', array('onchange' => 'c_super.checked=true')) ?></td>
			<td class="ed"><?= $input->radio('a_super', '1', array('onchange' => 'c_super.checked=true')) ?></td>
			<td class="ed">
			    <?php if($authinfo['a_super'] > 1) { ?>
			    <?= $input->radio('a_super', '2', array('onchange' => 'c_super.checked=true')) ?>
			    <?php } ?>
			</td>
		    </tr>
		    <?php } ?>
		    <?php if($authinfo['a_admin_domains'] > 0) { ?>
		    <tr>
			<td class="ed"><?= $input->checkbox('c_a_dom', '1') ?><?= txt('50') ?></td>
			<td class="ed"><?= $input->radio('a_a_dom', '0', array('onchange' => 'c_a_dom.checked=true')) ?></td>
			<td class="ed"><?= $input->radio('a_a_dom', '1', array('onchange' => 'c_a_dom.checked=true')) ?></td>
			<td class="ed">
			    <?php if($authinfo['a_admin_domains'] > 1) { ?>
			    <?= $input->radio('a_a_dom', '2', array('onchange' => 'c_a_dom.checked=true')) ?>
			    <?php } ?>
			</td>
		    </tr>
		    <?php } ?>
		    <?php if($authinfo['a_admin_user'] > 0) { ?>
		    <tr>
			<td class="ed"><?= $input->checkbox('c_a_usr', '1') ?><?= txt('70') ?></td>
			<td class="ed"><?= $input->radio('a_a_usr', '0', array('onchange' => 'c_a_usr.checked=true')) ?></td>
			<td class="ed"><?= $input->radio('a_a_usr', '1', array('onchange' => 'c_a_usr.checked=true')) ?></td>
			<td class="ed">
			    <?php if($authinfo['a_admin_user'] > 1) { ?>
			    <?= $input->radio('a_a_usr', '2', array('onchange' => 'c_a_usr.checked=true')) ?>
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