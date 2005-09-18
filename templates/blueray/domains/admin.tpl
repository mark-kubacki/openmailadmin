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
	    <dt><?= $input->checkbox('change[]', 'domain') ?><?= txt('55') ?></dt>
	    <dd><?= $input->_generate('text', 'domain', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '64')) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('change[]', 'owner') ?><?= txt('56') ?></dt>
	    <dd><?= $input->_generate('text', 'owner', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '16')) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('change[]', 'a_admin') ?><?= txt('57') ?></dt>
	    <dd><?= $input->_generate('text', 'a_admin', null, array('class' => 'textwhite', 'style' => 'width: 98%')) ?></dd>
	</dl>
	<dl>
	    <dt><?= $input->checkbox('change[]', 'categories') ?><?= txt('58') ?></dt>
	    <dd><?= $input->_generate('text', 'categories', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '100')) ?></dd>
	</dl>
    <span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span>
    <?= $input->hidden('frm', 'domains') ?>
    <?= $input->submit(txt('27'))?>
</div>
</div>
</form>