<div id="admin">
<span class="quasi_btn" id="admin_show"><?= txt('30') ?> &raquo;</span>
<div id="admin_panel">
	<dl>
		<dt><?= txt('20') ?></dt>
		<dd>
			<ul>
				<li><?= $input->radio('action', 'new') ?> <?= txt('21') ?></li>
				<li><?= $input->radio('action', 'delete') ?> <?= txt('22') ?></li>
				<li><?= $input->radio('action', 'dest') ?> <?= txt('23') ?></li>
				<li><?= $input->radio('action', 'active') ?> <?= txt('24') ?></li>
				<li><?= $input->radio('action', 'probe') ?> <?= txt('37') ?></li>
			</ul>
		</dd>
	</dl>
	<dl>
		<dt><?= txt('38') ?></dt>
		<dd><?= $input->_generate('text', 'probe', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '255')) ?></dd>
	</dl>
	<dl>
		<dt><?= txt('35') ?></dt>
		<dd><?= $input->_generate('text', 'reg_exp', null, array('class' => 'textwhite', 'style' => 'width: 98%', 'maxlength' => '255')) ?></dd>
	</dl>
	<dl>
		<dt><?= txt('19') ?><span class="annotation"><?= txt('25') ?></span></dt>
		<dd>
			<?= $input->checkbox('dest_is_mbox', '1') ?> <?= txt('5') ?> <b><?= txt('26') ?>:</b><br />
			<?= $input->textarea('dest', 5) ?>
		</dd>
	</dl>
	<span class="quasi_btn" id="admin_hide">&laquo; <?= txt('60') ?></span>
	<?= $input->hidden('frm', 'virtual_regexp') ?>
	<?= $input->submit(txt('27'))?>
</div>
</div>
</form>