<?php

$path = elgg_extract('public_path', $vars);
$file = elgg_extract('file', $vars);

if (!is_file($file)) {
	echo '"no file"';
	return;
}

$json = file_get_contents($file);
if (!$json) {
	echo '"failed to read file"';
	return;
}

// TODO don't make this view depend on 2 directory levels
$basename = basename($file);
$subdir = basename(dirname($file));

$url = "$path/$subdir/$basename";
echo "{\"url\":\"$url\",\"init\":$json}";
