<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
    <br />
    <table border="0" cellpadding="1" cellspacing="1">
	<tr>
	    <td class="ed" width="180"><b><?= txt('20') ?></b></td>
	    <td class="ed" width="400">
		<ul class="ed">
		<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
		<?php if($editable_domains > 0) { ?>
		    <li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
		    <li><?= $input->radio('action', 'change') ?><?= txt('59') ?></li>
		<?php } ?>
		</ul>
	    </td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_name', '1') ?><b><?= txt('55') ?></b></td>
	    <td class="ed"><?= $input->_generate('text', 'domain', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '64', 'onchange' => 'c_name.checked=true')) ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_owner', '1') ?><b><?= txt('56') ?></b></td>
	    <td class="ed"><?= $input->_generate('text', 'owner', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '16', 'onchange' => 'c_owner.checked=true')) ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_admin', '1') ?><b><?= txt('57') ?></b></td>
	    <td class="ed"><?= $input->_generate('text', 'a_admin', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'onchange' => 'c_admin.checked=true')) ?></td>
	</tr>
	<tr>
	    <td class="ed"><?= $input->checkbox('c_cat', '1') ?><b><?= txt('58') ?></b></td>
	    <td class="ed"><?= $input->_generate('text', 'categories', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '100', 'onchange' => 'c_cat.checked=true')) ?></td>
	</tr>
	<tr>
	    <td class="ed"><span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span></td>
	    <td class="ed" align="right"><?= $input->hidden('frm', 'domains') ?><?= $input->submit(txt('27'))?>&nbsp;</td>
	</tr>
    </table>
</div>
</form>
<br />