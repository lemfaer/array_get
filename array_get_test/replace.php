<?php

$n = 25;
$f = __DIR__ . "/test.json";
$f2 = __DIR__ . "/test2.json";
$c = file_get_contents($f);
$t = json_decode($c, true);
$in = array();
$c = 0;

for ($i = 0; $i < count($t); $i++) { 
	list($s, $r) = $t[$i];
	list($sa, $so, $se) = $s;

	if (
		$sa === 0
		&& $se < 0
	) {
		if (isset($in[":$so:$se"])) {
			$t[$i][1] = $in[":$so:$se"];
			$c++;
		} else {
			$in["0:$so:$se"] = $i;
		}
	}

	if (
		$sa === null
		&& $se < 0
	) {
		if (isset($in["0:$so:$se"])) {
			$j = $in["0:$so:$se"];
			$t[$j][1] = $r;
			$c++;
		} else {
			$in[":$so:$se"] = $r;
		}
	}
}

file_put_contents($f, json_encode($t));
file_put_contents($f2, json_encode($t, JSON_PRETTY_PRINT));
var_export($c);
exit;
