<div id="admin">
<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
	<dl>
	    <dt><?= txt('20') ?></dt>
	    <dd>
		<ul>
		<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
		<li><?= $input->radio('action', 'delete') ?><?= txt('22') ?></li>
		<li><?= $input->radio('action', 'dest') ?><?= txt('23') ?></li>
		<li><?= $input->radio('action', 'active') ?><?= txt('24') ?></li>
		</ul>
	    </dd>
	</dl>
	<dl>
	    <dt><?= txt('18') ?></dt>
	    <dd>
		<?= $input->_generate('text', 'alias', null, array('class' => 'email_alias', 'maxlength' => '190')) ?>
		@<?= addProp($input->select('domain', $oma->current_user['domain_set']), array('class' => 'email_domain')) ?>
	    </dd>
	</dl>
	<dl>
	    <dt><?= txt('19') ?><span class="annotation"><?= txt('25') ?></span></dt>
	    <dd>
		<?= $input->checkbox('dest_is_mbox', '1') ?> <?= txt('5') ?> <span class="bold"><?= txt('26') ?>:</span><br />
		<?= $input->textarea('dest', 5) ?>
	    </dd>
	</dl>
    <span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span>
    <?= $input->hidden('frm', 'virtual') ?>
    <?= $input->submit(txt('27'))?>
</div>
</div>
</form>