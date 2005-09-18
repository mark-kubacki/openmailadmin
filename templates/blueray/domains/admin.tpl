<div id="admin">
<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
	<dl>
	    <dt><?= txt('20') ?></dt>
	    <dd>
		<ul>
		<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
		<?php if($oma->editable_domains > 0) { ?>
		    <li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
		    <li><?= $input->radio('action', 'change') ?><?= txt('59') ?></li>
		<?php } ?>
		</ul>
	    </dd>
	</dl>
	<dl>
	    <dt><?= addProp($input->checkbox('change[]', 'domain'), array('id' => 'c_name')) ?><?= txt('55') ?></dt>
	    <dd><?= $input->_generate('text', 'domain', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '64', 'onchange' => 'c_name.checked=true')) ?></dd>
	</dl>
	<dl>
	    <dt><?= addProp($input->checkbox('change[]', 'owner'), array('id' => 'c_owner')) ?><?= txt('56') ?></dt>
	    <dd><?= $input->_generate('text', 'owner', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '16', 'onchange' => 'c_owner.checked=true')) ?></dd>
	</dl>
	<dl>
	    <dt><?= addProp($input->checkbox('change[]', 'a_admin'), array('id' => 'c_admin')) ?><?= txt('57') ?></dt>
	    <dd><?= $input->_generate('text', 'a_admin', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'onchange' => 'c_admin.checked=true')) ?></dd>
	</dl>
	<dl>
	    <dt><?= addProp($input->checkbox('change[]', 'categories'), array('id' => 'c_cat')) ?><?= txt('58') ?></dt>
	    <dd><?= $input->_generate('text', 'categories', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '100', 'onchange' => 'c_cat.checked=true')) ?></dd>
	</dl>
    <span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span>
    <?= $input->hidden('frm', 'domains') ?>
    <?= $input->submit(txt('27'))?>
</div>
</div>
</form>