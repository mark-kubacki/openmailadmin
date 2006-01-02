<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
<br />
<table border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="ed" width="180"><b><?= txt('20') ?></b></td>
		<td class="ed" width="400">
			<ul class="ed">
				<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
				<li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
				<li><?= $input->radio('action', 'dest') ?><?= txt('23') ?></li>
				<li><?= $input->radio('action', 'active') ?><?= txt('24') ?></li>
				<li><?= $input->radio('action', 'probe') ?><?= txt('37') ?></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="ed"><b><?= txt('38') ?></b></td>
		<td class="ed"><?= $input->_generate('text', 'probe', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '255')) ?></td>
	</tr>
	<tr>
		<td class="ed"><b><?= txt('35') ?></b></td>
		<td class="ed"><?= $input->_generate('text', 'reg_exp', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '255')) ?></td>
	</tr>
	<tr>
		<td class="ed"><b><?= txt('19') ?></b><?= txt('25') ?></td>
		<td class="ed">
			<?= $input->checkbox('dest_is_mbox', '1') ?> <?= txt('5') ?> <b><?= txt('26') ?>:</b><br />
			<?= $input->textarea('dest', 5) ?>
		</td>
	</tr>
	<tr>
		<td class="ed"><span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span></td>
		<td class="ed" align="right"><?= $input->hidden('frm', 'virtual_regexp') ?><?= $input->submit(txt('27'))?>&nbsp;</td>
	</tr>
</table>
</div>
</form>
<br />