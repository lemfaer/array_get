<?php

$infile = __DIR__ . "/test.json";
$outfile = __DIR__ . "/test.php.zlib";
$c = file_get_contents($infile);
file_put_contents($outfile, gzcompress($c));
