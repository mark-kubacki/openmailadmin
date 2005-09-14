<form action="<?= mkSelfRef() ?>" method="post">
<div id="data">
<h2><?= txt('17') ?> <?= $oma->current_user['used_alias'] ?>/<?= $oma->current_user['max_alias'] ?></h2>
<span class="pagelist"><?= getPageList('<a href="'.mkSelfRef(array('addr_page' => '%d')).'">%d</a>', $oma->current_user['used_alias'], $_SESSION['limit']['upper'], $_SESSION['limit'][$oma->current_user['mbox']]['addr_page']) ?></span>
<table class="data">
    <tr>
	<th colspan="2"><?= txt('18') ?></th>
	<th><?= txt('19') ?></th>
    </tr>
    <?php foreach($alias as $entry) { ?>
	<tr>
	    <td class="alias">
		<?= $input->checkbox('address[]', $entry['address']) ?>
		<?php if($entry['active'] != 1) { ?>
		    <span class="deactivated"><?= $entry['alias'] ?></span>
		<?php } else { ?>
		    <span><?= $entry['alias'] ?></span>
		<?php } ?>
	    </td>
	    <td>@<?= $entry['domain'] ?></td>
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