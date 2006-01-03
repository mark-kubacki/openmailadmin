<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $lang ?>" lang="<?= $lang ?>">
<head>
	<title><?= txt('121') ?></title>
	<script src="<?= $cfg['design_dir'] ?>/treeview.js" type="text/javascript"></script>
	<script src="<?= $cfg['design_dir'] ?>/openmailadmin.js" type="text/javascript"></script>
	<link rel="stylesheet" href="<?= $cfg['design_dir'] ?>/treeview.css" type="text/css" />
	<link rel="stylesheet" href="<?= $cfg['design_dir'] ?>/shadow.css" type="text/css" title="shadow" />
	</head>
<body onload="init_tree(); init_oma()">