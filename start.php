<?php

namespace MrClay\Elgg\PollService;

use MrClay\Elgg\PollService;
use MrClay\LightPolling\Connection;

/**
 * Add a message to be delivered to client(s). The channel used will be pinged.
 *
 * @note Clients may not receive every message.
 *
 * @api
 * @param \ElggEntity $entity
 * @param mixed       $message Message (must be JSON-encodable)
 * @param string      $channel
 * @param int         $storage_limit Max number of messages to store in connection file
 */
function add_message(\ElggEntity $entity, $message, $channel = 'default', $storage_limit = 10) {
	_poll_service()->useConnection($entity, function (Connection $connection) use ($message, $channel, $storage_limit) {
		$connection->addMessage($channel, $message, $storage_limit);
	});
}

/**
 * Ping a channel(s) so the client is notified changes are available
 *
 * @note A client will only be aware of the latest ping in its polling interval.
 *
 * @api
 * @param \ElggEntity $entity
 * @param string|string[] $channel
 */
function ping_channel(\ElggEntity $entity, $channel = 'default') {
	_poll_service()->useConnection($entity, function (Connection $connection) use ($channel) {
		foreach ((array)$channel as $name) {
			$connection->pingChannel($name);
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
	_poll_service()->useConnection($entity, function (Connection $connection) use ($channel) {
		foreach ((array)$channel as $name) {
			$connection->deleteChannel($name);
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
 * Make sure a connection has the following channels so clients can listen on them
 *
 * @api
 * @param \ElggEntity     $entity
 * @param string|string[] $channels
 */
function add_channels(\ElggEntity $entity, $channels) {
	_poll_service()->addChannels($entity, $channels);
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

function _page_handler($segments) {
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
}

function _init() {
	elgg_register_page_handler('mrclay_poll_svc', __NAMESPACE__ . '\\_page_handler');

	elgg_extend_view('js/initialize_elgg', 'mrclay_poll_svc/page_data');

	// testing code
	if (false) {
		// pages with comments: set up stream monitoring
		if (elgg_is_logged_in()) {
			elgg_register_plugin_hook_handler('view', 'page/elements/comments', function ($h, $t, $v, $p) {
				$entity = $p['vars']['entity'];

				elgg_require_js('mrclay_poll_svc_test');

				// tell server about needed channels and to push current channel data
				// to the client so it doesn't need to poll immediately.
				add_channels($entity, ['comments', 'likes']);
				request_connection($entity);
			});
		}

		// push comment excerpts into stream
		elgg_register_event_handler('create', 'object', function ($e, $t, \ElggObject $object) {
			if (!$object instanceof \ElggComment) {
				return;
			}

			$owner = $object->getOwnerEntity();
			/* @var \ElggUser $owner */
			$msg = [
				'owner' => [
					'guid' => $owner->guid,
					'name' => $owner->name,
					'username' => $owner->username,
					'icon' => $owner->getIconURL(['size' => 'tiny']),
				],
				'excerpt' => elgg_get_excerpt($object->description),
				'guid' => $object->guid,
			];
			add_message($object->getContainerEntity(), $msg, 'comments');
		});

		// push likes into stream
		elgg_register_event_handler('create', 'annotation', function ($e, $t, \ElggAnnotation $a) {
			if ($a->name !== 'likes') {
				return;
			}

			$owner = $a->getOwnerEntity();
			/* @var \ElggUser $owner */
			$msg = [
				'owner' => [
					'guid' => $owner->guid,
					'name' => $owner->name,
					'username' => $owner->username,
					'icon' => $owner->getIconURL(['size' => 'tiny']),
				],
			];
			add_message($a->getEntity(), $msg, 'likes');
		});
	}
}

function _flush() {
	_poll_service()->deleteAll();
}

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\_init');
elgg_register_event_handler('cache:flush', 'system', __NAMESPACE__ . '\\_flush');
