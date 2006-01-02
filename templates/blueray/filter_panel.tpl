<div id="filter">
	<form action="<?= mkSelfRef() ?>" method="post">
		<div id="filter_left">
			<?= $input->hidden('filtr', 'set') ?>
			<?= $input->checkbox('filtr_addr', '1', array('onchange' => 'submit()')) ?>
			<?= txt('97') ?>&nbsp;
			<?= $input->select('what', array(txt('18'), txt('19'), txt('55'), txt('83')), array('addr', 'target', 'domain', 'mbox')) ?>
			<?= $input->select('cond', array(txt('98'), txt('99'), txt('100')), array('has', 'begins', 'ends')) ?>&nbsp;
			<?= $input->_generate('text', 'cont', null, array('class' => 'filter')) ?>
		</div>
	</form>
	<form action="<?= mkSelfRef() ?>" method="post">
		<div id="filter_right">
			<?= txt('101') ?>&nbsp;
			<?= ChngS($input->select('limit', $amount_set)) ?>
		</div>
	</form>
</div>