<?php
ob_start('ob_gzhandler');
include('openmailadmin/inc/config.inc.php4');
include('openmailadmin/inc/format_shadow_classes.inc.php4');

echo('<html>');
echo('<head><title>Mailsystem Administration</title>');
echo('<link rel="stylesheet" href="'.$cfg['design_dir'].'/shadow.css" type="text/css" />');
echo('</head><body>');

$table	= new _table_shadow();
$input	= new _input();
$error	= new _font();
    $table->images_dir		= $cfg['images_dir'];
    $error->arrProperties	= array('class'		=> 'error');

$table->echo_caption('Verwaltung');
echo($table->outer_shadow_start());
echo($table->table($table->tr($table->td('<b>Mail-Verwaltung</b> mit <a href="/openmailadmin">Openmailadmin</a><br />Diese Oberfläche steht jedem mit einem Mailkonto zur Verfügung.<br />Hier kann das Kennwort geändert werden!', 'a', array('width' => '580')))
		    .$table->tr($table->td('<b>Filterregeln</b> mit <a href="/smartsieve">SmartSieve</a><br />Hier kann jeder Benutzer seine Email-Regeln und Filter einstellen und ändern.'))
    ));
echo($table->outer_shadow_stop());
echo('<br />');

$table->echo_caption('Webmailer');
echo($table->outer_shadow_start());
echo($table->table($table->tr($table->td('<a href="/moregroupware"><b>MoreGroupware</b></a><br />Webmailer für verschiedene Accounts, Kalender, Foren, Links...<br />Hierzu wird eine gesonderte Freischaltung benötigt!', 'a', array('width' => '580')))
		    .$table->tr($table->td('<a href="/horde/imp"><b>IMP aus Horde</b></a><br />Ein mächtiger Email-Client. Adressen werden mittels Turba verwaltet.'))
    ));
echo($table->outer_shadow_stop());
echo('<br />');

$table->echo_caption('Monitoring');
echo($table->outer_shadow_start());
echo($table->table($table->tr($table->td('<a href="/sensor_data.png"><b>Sensorauswertung</b></a><br />Auswertung der periodisch gesammelten Sensordaten.<br />Darstellung ist konsekutiv, nicht isochronolog.', 'a', array('width' => '580')))
    ));
echo($table->outer_shadow_stop());

echo('</body></html>');
ob_end_flush();
?>
