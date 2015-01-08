<?php

namespace MrClay\Elgg\PollService;

use MrClay\Elgg\PollService;

/**
 * Update the timestamp on a user's stream
 *
 * @api
 * @param int $user_guid
 * @param string $stream_name
 */
function update_stream($user_guid, $stream_name) {
	$coll = _poll_service()->getUserStreams($user_guid);
	$coll->touchStream($stream_name);
	$coll->save();
}

/**
 * Delete a stream that no longer needs to be tracked
 *
 * @api
 * @param int $user_guid
 * @param string $stream_name
 */
function delete_stream($user_guid, $stream_name) {
	$coll = _poll_service()->getUserStreams($user_guid);
	$coll->deleteStream($stream_name);
	$coll->save();
}

/**
 * @return PollService
 * @internal
 */
function _poll_service() {
	static $inst;
	if ($inst === null) {
		$inst = new PollService();
	}
	return $inst;
}

elgg_register_event_handler('init', 'system', function () {

});
