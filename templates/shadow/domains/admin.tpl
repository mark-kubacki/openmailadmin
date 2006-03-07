<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
<br />
<table border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="ed" width="180"><b><?= txt('20') ?></b></td>
		<td class="ed" width="400">
			<ul class="ed">
			<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
			<?php if($oma->editable_domains > 0) { ?>
				<li><?= $input->radio('action', 'change') ?><?= txt('59') ?></li>
				<li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'domain') ?><b><?= txt('55') ?></b></td>
		<td class="ed"><?= $input->_generate('text', 'domain', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '64')) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'owner') ?><b><?= txt('56') ?></b></td>
		<td class="ed"><?= $input->_generate('text', 'owner', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '16')) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'a_admin') ?><b><?= txt('57') ?></b></td>
		<td class="ed"><?= $input->_generate('text', 'a_admin', null, array('class' => 'textwhite', 'style' => 'width: 98%')) ?></td>
	</tr>
	<tr>
		<td class="ed"><?= $input->checkbox('change[]', 'categories') ?><b><?= txt('58') ?></b></td>
		<td class="ed"><?= $input->_generate('text', 'categories', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '100')) ?></td>
	</tr>
	<tr>
		<td class="ed"><span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span></td>
		<td class="ed" align="right"><?= $input->hidden('frm', 'domains') ?><?= $input->submit(txt('27'))?>&nbsp;</td>
	</tr>
</table>
</div>
</form>
<br />