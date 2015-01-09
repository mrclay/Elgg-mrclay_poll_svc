<?php

namespace MrClay\Elgg\PollService;

use MrClay\Elgg\PollService;
use MrClay\LightPolling\Connection;

/**
 * Ping a channel so the client is notified changes are available
 *
 * @api
 * @param \ElggEntity $entity
 * @param string|string[] $channel
 */
function ping_channel(\ElggEntity $entity, $channel = 'default') {
	_poll_service()->useConnection($entity, function (Connection $collection) use ($channel) {
		foreach ((array)$channel as $name) {
			$collection->pingChannel($name);
		}
	});
}

/**
 * Delete a channel no longer needed
 *
 * @api
 * @param \ElggEntity $entity
 * @param string|string[] $channel
 */
function delete_channel(\ElggEntity $entity, $channel = 'default') {
	_poll_service()->useConnection($entity, function (Connection $collection) use ($channel) {
		foreach ((array)$channel as $name) {
			$collection->deleteChannel($name);
		}
	});
}

/**
 * Read the last ping time of each channel tracked
 *
 * @api
 * @param \ElggEntity $entity
 * @return \int[] name => timestamp
 */
function get_channel_times(\ElggEntity $entity) {
	return _poll_service()->loadConnection($entity)->getChannelTimes();
}

/**
 * Add an available Connection for the client to listen to. You should only
 * use entities the user has access to!
 *
 * @api
 * @param \ElggEntity $entity
 */
function request_connection(\ElggEntity $entity) {
	_poll_service()->requestConnection($entity);
}

/**
 * @return PollService
 * @internal
 */
function _poll_service() {
	static $inst;
	if ($inst === null) {
		$inst = PollService::factory();
	}
	return $inst;
}

elgg_register_event_handler('init', 'system', function () {
	elgg_register_page_handler('mrclay_poll_svc', function ($segments) {

		if (elgg_extract(0, $segments) !== 'fetchConnection') {
			return;
		}

		$guid = (int)elgg_extract(1, $segments);
		if (!$guid) {
			return;
		}

		header('Content-Type: application/json;charset=utf-8');

		$entity = get_entity($guid);
		if (!$entity) {
			echo '"no access"';
			return true;
		}

		$svc = _poll_service();

		// note we don't call $svc->requestConnection($entity) because we don't want to let clients create
		// a connection if it doesn't already exist

		$path = rtrim(elgg_get_config('mrclay_poll_svc_public_path'), '/');
		if (!$path) {
			$path = "mod/mrclay_poll_svc/public";
		}

		$view_vars = array(
			'public_path' => $path,
			'file' => $svc->getConnectionFile($guid),
		);
		echo elgg_view('mrclay_poll_svc/connection', $view_vars, false, false, 'json');

		return true;
	});

	elgg_extend_view('js/initialize_elgg', 'mrclay_poll_svc/page_data');

	// test code
//	elgg_require_js('mrclay_poll_svc');
//	elgg_register_plugin_hook_handler('view', 'page/elements/comments', function ($h, $t, $v, $p) {
//		request_connection($p['vars']['entity']);
//	});
//	elgg_register_event_handler('create', 'object', function ($e, $t, \ElggObject $object) {
//		if ($object instanceof \ElggComment) {
//			ping_channel($object->getContainerEntity(), 'comments');
//		}
//	});
});
