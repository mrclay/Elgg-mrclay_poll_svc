<?php

$connection_guids = elgg_extract('guids', $vars);
$path = elgg_extract('public_path', $vars);
$svc = elgg_extract('svc', $vars);

if (!$svc instanceof \MrClay\Elgg\PollService) {
	return;
}

$data = array();

foreach ($connection_guids as $guid) {
	$file = $svc->getConnectionFile($guid);

	$view_vars = array(
		'public_path' => $path,
		'file' => $file,
	);
	$data[] = "\"$guid\":" . elgg_view('mrclay_poll_svc/connection', $view_vars, false, false, 'json');
}

echo "{" . implode(',', $data) . "}";
