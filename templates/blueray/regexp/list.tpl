<form action="<?= mkSelfRef() ?>" method="post">
<div id="data">
<h2><?= txt('33') ?> <?= $oma->current_user['used_regexp'] ?>/<?= $oma->current_user['max_regexp'] ?></h2>
<span class="pagelist"><?= getPageList('<a href="'.mkSelfRef(array('regx_page' => '%d')).'">%d</a>', $oma->current_user['used_regexp'], $_SESSION['limit']['upper'], $_SESSION['limit'][$oma->current_user['mbox']]['regx_page']) ?></span>
<table class="data">
    <tr>
	<th colspan="2"><?= txt('34') ?>&nbsp;&nbsp;&nbsp;<?= $oma->current_user['reg_exp'] ?></th>
    </tr>
    <tr>
	<th><?= txt('18') ?></td>
	<th><?= txt('19') ?></td>
    </tr>
    <?php foreach($regexp as $entry) { ?>
	<?php if($entry['matching']) { ?>
	<tr class="matching">
	<?php } else { ?>
	<tr>
	<?php } ?>
	    <td class="regexp">
		<?= $input->checkbox('expr[]', $entry['ID']) ?>
		<?php if($entry['active'] != 1) { ?>
		    <span class="deactivated"><?= $entry['reg_exp'] ?></span>
		<?php } else { ?>
		    <span><?= $entry['reg_exp'] ?></span>
		<?php } ?>
	    </td>
	    <td>
		<?php if(count($entry['dest']) >= $cfg['address']['hide_threshold']) { ?>
		    <span class="quasi_btn"><?= sprintf(txt('96'), count($entry['dest'])) ?> &raquo;</span>
		    <div>
		    <span class="quasi_btn">&laquo; <?= sprintf(txt('96'), count($entry['dest'])) ?></span>
		<?php } ?>
		<ul class="nomargin">
		    <?php foreach($entry['dest'] as $destination) { ?>
			<li><?= $destination ?></li>
		    <?php } ?>
		</ul>
		<?php if(count($entry['dest']) >= 5) { ?>
		    </div>
		<?php } ?>
	    </td>
	</tr>
    <?php } ?>
</table>
</div>