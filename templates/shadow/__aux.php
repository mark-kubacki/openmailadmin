<?php
function outer_shadow_start() {
	global $cfg;
	include('./templates/'.$cfg['theme'].'/outer_shadow_start.tpl');
}

function outer_shadow_stop() {
	global $cfg;
	include('./templates/'.$cfg['theme'].'/outer_shadow_stop.tpl');
}
?>