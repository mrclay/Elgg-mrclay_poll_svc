<?php

namespace MrClay\LightPolling;

/**
 * Collection of channels that can receive messages or pings. This object is designed to be
 * serialized between requests.
 */
class Connection {

	/**
	 * Do not change!
	 */
	const KEY_TIME = 't';
	const KEY_MESSAGES = 'm';

	/**
	 * @warning This must be JSON-encodable
	 *
	 * @var array map of "name" => ["t" => timestamp]
	 */
	protected $channels = array();

	/**
	 * @var int
	 */
	protected $time_modified = 0;

	/**
	 * Add a channel if doesn't yet exist
	 *
	 * @param string $name Name
	 */
	public function addChannel($name) {
		if (!isset($this->channels[(string)$name])) {
			$this->channels[(string)$name][self::KEY_TIME] = 0;
			$this->touch();
		}
	}

	/**
	 * Ping a channel
	 *
	 * @param string $name
	 * @param int $time
	 */
	public function pingChannel($name, $time = null) {
		if (!$time) {
			$time = time();
		}
		$this->channels[(string)$name][self::KEY_TIME] = (int)$time;
		if (empty($this->channels[(string)$name][self::KEY_MESSAGES])) {
			$this->channels[(string)$name][self::KEY_MESSAGES] = [];
		}
		$this->touch();
	}

	/**
	 * Send a message to a channel
	 *
	 * @param string $channel_name  Channel name
	 * @param mixed  $message       Message (must be JSON-encodable)
	 * @param int    $storage_limit How many messages to keep in channel history
	 */
	public function addMessage($channel_name, $message, $storage_limit = 10) {
		if (!isset($this->channels[(string)$channel_name][self::KEY_MESSAGES])) {
			$this->channels[(string)$channel_name][self::KEY_MESSAGES] = [];
		}

		$msgs =& $this->channels[(string)$channel_name][self::KEY_MESSAGES];
		array_unshift($msgs, $message);
		$msgs = array_slice($msgs, 0, $storage_limit);

		$this->pingChannel($channel_name);
	}

	/**
	 * @param string $name
	 * @return int|null
	 */
	public function getChannelTime($name) {
		return isset($this->channels[$name]) ? $this->channels[$name][self::KEY_TIME] : null;
	}

	/**
	 * Delete a channel
	 *
	 * @param string $name
	 */
	public function deleteChannel($name) {
		unset($this->channels[$name]);
		$this->touch();
	}

	/**
	 * @return array
	 */
	public function getChannels() {
		return $this->channels;
	}

	/**
	 * @return int[]
	 */
	public function getChannelTimes() {
		return array_map(function ($channel) {
			return $channel[self::KEY_TIME];
		}, $this->channels);
	}

	/**
	 * Mark the connection as refreshed. write() should be called on storage so clients
	 * see the change.
	 */
	public function touch() {
		$this->time_modified = time();
	}

	/**
	 * @return int 0 means has never been modified
	 */
	public function getTimeModified() {
		return $this->time_modified;
	}
}
