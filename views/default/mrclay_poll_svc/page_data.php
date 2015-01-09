<?php
/**
 * Here we feed the client the URL and contents of the poll file(s).
 * Having the initial data means that, on the first poll, we're be able to
 * detect a change on the first poll.
 */

$svc = \MrClay\Elgg\PollService\_poll_service();

$user = elgg_get_logged_in_user_entity();
if ($user) {
	$svc->initConnection($user);
	$svc->requestConnection($user);
}

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

if (false): ?><script><?php endif;

?>
var mrclay_poll_svc_data = <?= $json ?>;
