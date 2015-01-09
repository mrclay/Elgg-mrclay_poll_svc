<?php

namespace MrClay\LightPolling;

/**
 * Collection of pingable "channels". This object is designed to be serialized between requests
 */
class ChannelCollection {

	/**
	 * Do not change!
	 */
	const KEY_TIME = 't';

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
		$this->time_modified = time();
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
		$this->time_modified = time();
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
	 * @return int 0 means has never been modified
	 */
	public function getTimeModified() {
		return $this->time_modified;
	}
}
