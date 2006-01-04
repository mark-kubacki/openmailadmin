<div class="setup_outer">
<div class="setup_head_outer"><div class="setup_head">
	<h1>Openmailadmin installation</h1>
	<h2>Step 2 - database initialisation</h2>
</div></div>
<div class="setup_body">
	<h3>available databases</h3>
	<p>Here come example DSN for connecting with found database drivers:
	<dl>
	<?php foreach($available_db as $example) { ?>
		<dt><?= $example[0] ?></dt>
		<dd><code><?= $example[1] ?></code></dd>
	<?php } ?>
	</dl>
	</p>

<form action="setup.php?step=3" method="post">
	<h3>db connection settings</h3>
	<p>Please provide the required DSN for connecting to your desired database.</p>
	<p>Required tables will be created in the next step, after having tried to connect.</p>
	<table class="settings">
	<tr>
		<th class="w140">field</th>
		<th>value</th>
	</tr>
	<tr>
		<td>DSN</td>
		<td><input type="text" name="dsn" value="" /></td>
	</tr>
	<tr>
		<td>tablenames' prefix</td>
		<td><input type="text" name="prefix" value="" title="oma_" /></td>
	</tr>
	</table>

	<h3>IMAP connection settings</h3>
	<p><cite>Imap admin</cite> and his <cite>password</cite> are the settings required for IMAP.
	They will be added to DB in step 3. Don't mix them up with your first superuser</p>
	<table class="settings two">
	<tr>
		<th class="w140">field</th>
		<th>value</th>
	</tr>
	<tr>
		<td>type</td>
		<td><select name="imap_type"><option value="cyrus">Cyrus imapd</option><option value="fake-imap">demo - database backend</option></select></td>
	</tr>
	<tr>
		<td>host</td>
		<td><input type="text" name="imap_host" name="imap_host" value="" title="127.0.0.1" /></td>
	</tr>
	<tr>
		<td>port</td>
		<td><input type="text" name="imap_port" value="" title="143" /></td>
	</tr>
	<tr>
		<td>imap admin</td>
		<td><input type="text" name="imap_user" id="iu" value="" title="i.e. 'cyrus'" onchange="if(document.getElementById('iu').value == document.getElementById('au').value) { alert('Both usernames must differ!');document.getElementById('iu').className=document.getElementById('au').className='bad'; } else { document.getElementById('iu').className=document.getElementById('au').className=''; }" /></td>
	</tr>
	<tr>
		<td>... password</td>
		<td><input type="text" name="imap_pass" value="" title="i.e. '<?= md5(time().rand()) ?>'" /></td>
	</tr>
	<tr>
		<td>mailbox of superuser</td>
		<td><input type="text" name="admin_user" id="au" value="" title="i.e. 'admin'" onchange="if(document.getElementById('iu').value == document.getElementById('au').value) { alert('Both usernames must differ!');document.getElementById('iu').className=document.getElementById('au').className='bad'; } else { document.getElementById('iu').className=document.getElementById('au').className=''; }" /></td>
	</tr>
	<tr>
		<td>... password</td>
		<td><input type="text" name="admin_pass" value="" title="i.e. 'supersecret'" /></td>
	</tr>
	</table>

	<h3>next step</h3>
	<div class="next_step"><input type="submit" value="proceed to step 3" class="fake_a" /></div>
</form>
</div>