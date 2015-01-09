<?php

namespace MrClay\Elgg\PollService;

use MrClay\Elgg\PollService;
use MrClay\LightPolling\ChannelCollection;

/**
 * Ping a channel so the client is notified changes are available
 *
 * @api
 * @param int $user_guid
 * @param string|string[] $channel
 */
function ping_channel($user_guid, $channel) {
	_poll_service()->useCollection($user_guid, function (ChannelCollection $collection) use ($channel) {
		foreach ((array)$channel as $name) {
			$collection->pingChannel($name);
		}
	});
}

/**
 * Delete a channel no longer needed
 *
 * @api
 * @param int $user_guid
 * @param string|string[] $channel
 */
function delete_channel($user_guid, $channel) {
	_poll_service()->useCollection($user_guid, function (ChannelCollection $collection) use ($channel) {
		foreach ((array)$channel as $name) {
			$collection->deleteChannel($name);
		}
	});
}

/**
 * Read the last ping time of each channel tracked
 *
 * @api
 * @param int $user_guid
 * @return \int[] name => timestamp
 */
function get_channel_times($user_guid) {
	return _poll_service()->loadCollection($user_guid)->getChannelTimes();
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
	
});
