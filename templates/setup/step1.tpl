<div class="setup_outer">
<div class="setup_head_outer"><div class="setup_head">
	<h1>Openmailadmin installation</h1>
	<h2>Step 1 - Environment</h2>
</div></div>
<div class="setup_body">
	<h3>installed software</h3>
	<table class="settings">
	<?php foreach($checks as $check) { ?>
	<tr>
		<td><?= $check[0] ?></td>
		<?php if($check[1]) { ?>
			<td class="good">yes</td>
		<?php } else { ?>
			<td class="bad">no</td>
		<?php } ?>
	</tr>
	<?php } ?>
	</table>
	<h3>configuration settings</h3>
	<table class="settings">
	<tr>
		<th>configuration setting</th>
		<th>expected</th>
		<th>currently is</th>
		<th>result</th>
	</tr>
	<?php foreach($reality as $row) { ?>
	<tr>
		<td><?= $row['value'] ?></td>
		<?php if($row['expected']) { ?>
			<td>On</td>
		<?php } else { ?>
			<td>Off</td>
		<?php } ?>
		<?php if($row['is']) { ?>
			<td>On</td>
		<?php } else { ?>
			<td>Off</td>
		<?php } ?>
		<?php if($row['okay']) { ?>
			<td class="good">good</td>
		<?php } else if(!$row['mandatory']) { ?>
			<td class="tolerated">tolerated</td>
		<?php } else { ?>
			<td class="bad">failed</td>
		<?php } ?>
	</tr>
	<?php } ?>
	</table>
	</div>
</div>