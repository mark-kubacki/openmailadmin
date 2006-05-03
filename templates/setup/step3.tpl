<div class="setup_outer">
<div class="setup_head_outer"><div class="setup_head">
	<h1>Openmailadmin installation</h1>
	<h2>Step 3 - table creation and configuration file</h2>
</div></div>
<div class="setup_body">
	<h3>connection tests</h3>
	<?php if(isset($failure)) { ?>
		<p class="bad"><?= $failure ?></p>
	<?php } else { ?>
		<p class="good">Success.</p>

		<h3>table and index creation</h3>
		<table class="settings">
		<tr><th>tablename</th><th>creation</th></tr>
		<?php foreach($status as $row) { ?>
		<tr>
			<td><?= $row[0] ?></td>
			<?php if($row[1] == 2) { ?>
				<td class="good">successfull</td>
			<?php } else if($row[1] == 1) { ?>
				<td class="tolerated">partially failed - does table already exist?</td>
			<?php } else { ?>
				<td class="bad">failed</td>
			<?php } ?>
		</tr>
		<?php } ?>
		</table>
		<?php if($_POST['admin_user'] == ''
			|| ($_POST['imap_type'] != 'fake-imap' && ($_POST['imap_user'] == '')) ) { ?>
		<p>You didn't provide all necessary data. Please hit 'back' on your browser.</p>
		<?php } else { ?>
			<h3>configuration file</h3>
			<?php if($written) { ?>
				<p>A configuration file has been created with this content:</p>
			<?php } else { ?>
				<p>Please create a configuration file <cite>inc/config.local.inc.php</cite> with this content:</p>
			<?php } ?>
			<div class="code"><pre><code><?= htmlspecialchars($config) ?></code></pre></div>
			<h3>finished</h3>
			<p>Congratulations! After that you can log in as superuser with:
			<cite><?= $_POST['admin_user'] ?></cite>:<cite><?= htmlspecialchars($_POST['admin_pass']) ?></cite>
			</p>
			<div class="next_step"><a href="index.php" title="go to OMA">go ahead and log in</a></div>
		<?php } ?>
	<?php } ?>
</div>