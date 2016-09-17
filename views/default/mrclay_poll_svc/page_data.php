<?php
/**
 * Here we feed the client the URL and contents of the poll file(s).
 * Having the initial data means that, on the first poll, we're be able to
 * detect a change on the first poll.
 */

$svc = \MrClay\Elgg\PollService\_poll_service();
/* @var \MrClay\Elgg\PollService $svc */

$path = rtrim(elgg_get_config('mrclay_poll_svc_public_path'), '/');
if (!$path) {
	$path = "mod/mrclay_poll_svc/public";
}

$view_vars = array(
	'guids' => $svc->getRequestedConnections(),
	'public_path' => $path,
	'svc' => $svc,
);
$json = elgg_view('mrclay_poll_svc/page_data', $view_vars, false, false, 'json');

?>
//<script>
var mrclay_poll_svc_data = <?= $json ?>;
