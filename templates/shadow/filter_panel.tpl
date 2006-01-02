<table border="0" cellpadding="1" cellspacing="1">
	<tr>
		<td class="ed" width="450" style="white-space: nowrap">
			<form action="<?= mkSelfRef() ?>" method="post">
				<?= $input->hidden('filtr', 'set') ?>
				<?= $input->checkbox('filtr_addr', '1', array('onchange' => 'submit()')) ?>
				<?= txt('97') ?>&nbsp;
				<?= $input->select('what', array(txt('18'), txt('19'), txt('55'), txt('83')), array('addr', 'target', 'domain', 'mbox')) ?>
				<?= $input->select('cond', array(txt('98'), txt('99'), txt('100')), array('has', 'begins', 'ends')) ?>&nbsp;
				<?= $input->_generate('text', 'cont', null, array('class' => 'textwhite')) ?>
			</form>
		</td>
		<td class="ed" width="130" align="right">
			<form action="<?= mkSelfRef() ?>" method="post">
				<?= txt('101') ?>&nbsp;
				<?= ChngS($input->select('limit', $amount_set)) ?>
			</form>
		</td>
	</tr>
</table>
<br />