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

	<h3>how to store passwords</h3>
	<p>Below you will be able to select which hashing algorithm should be applied on passwords which will be stored in DB.</p>
	<p>If in doubt, leave at <code>MD5</code>.</p>
	<table class="settings two">
	<tr>
		<th class="w140">field</th>
		<th>value</th>
	</tr>
	<tr>
		<td>hashed with</td>
		<td><select name="hashing_strategy">
			<option value="PasswordMD5">MD5</option>
			<option value="PasswordCrypt">Crypt</option>
			<option value="PasswordSHA1">SHA1</option>
			<option value="PasswordPlaintext">plaintext (not hashed)</option>
			</select></td>
	</tr>
	</table>

	<h3>IMAP connection settings</h3>
	<p><cite>Imap admin</cite> and his <cite>password</cite> are the settings required for IMAP maintenance by this tool.
	This is not the user you will login as administrator - that is the user to be specified in <cite>first superuser</cite>.</p>
	<table class="settings two">
	<tr>
		<th class="w140">field</th>
		<th>value</th>
	</tr>
	<tr>
		<td>type</td>
		<td><select name="imap_type" onchange="var state=this.value=='fake-imap'?'none':''; document.getElementById('ih').style.display=document.getElementById('ip').style.display=document.getElementById('iau').style.display=document.getElementById('iap').style.display=state;">
			<option value="cyrus">Cyrus imapd</option>
			<option value="fake-imap">demo - database backend</option>
			</select></td>
	</tr>
	<tr id="ih">
		<td>host</td>
		<td><input type="text" name="imap_host" name="imap_host" value="" title="127.0.0.1" /></td>
	</tr>
	<tr id="ip">
		<td>port</td>
		<td><input type="text" name="imap_port" value="" title="143" /></td>
	</tr>
	<tr id="iau">
		<td>imap admin</td>
		<td><input type="text" name="imap_user" id="iu" value="" title="i.e. 'cyrus'" onchange="if(document.getElementById('iu').value == document.getElementById('au').value) { alert('Both usernames must differ!');document.getElementById('iu').className=document.getElementById('au').className='bad'; } else { document.getElementById('iu').className=document.getElementById('au').className=''; }" /></td>
	</tr>
	<tr id="iap">
		<td>... password</td>
		<td><input type="text" name="imap_pass" value="" title="i.e. '<?= md5(time().rand()) ?>'" /></td>
	</tr>
	</table>
	<p>In case you have selected as IMAP-type something other than <cite>demo</cite>
	make sure you have already configured <b><cite>PAM</cite></b> or any other means of authentication.</p>

	<h3>first superuser</h3>
	<p>This will be the first superuser.</p>
	<p>As he won't have any special rights if connected through IMAP oder POP,
	you can enter the data for your first mailbox without worrying much.</p>
	<p>The user need not exist as he will be created after this step.</p>
	<table class="settings three">
	<tr>
		<th class="w140">field</th>
		<th>value</th>
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