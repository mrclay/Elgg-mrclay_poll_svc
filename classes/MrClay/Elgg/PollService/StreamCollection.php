<?php

namespace MrClay\Elgg\PollService;

class StreamCollection {

	/**
	 * @warning do not change. Part of file format
	 */
	const KEY_TIME = 't';

	/**
	 * @var int
	 */
	protected $user_guid;

	/**
	 * @var string
	 */
	protected $file_path;

	/**
	 * @warning This must be JSON-encodable
	 *
	 * @var array map of "name" => ["t" => timestamp]
	 */
	protected $streams = array();

	/**
	 * @var bool
	 */
	protected $needs_save = false;

	/**
	 * Constructor
	 *
	 * @param int $user_guid
	 * @param string $file_path
	 * @param array $streams
	 * @param bool $needs_save
	 */
	public function __construct($user_guid, $file_path, array $streams, $needs_save) {
		$this->user_guid = $user_guid;
		$this->file_path = $file_path;
		$this->streams = $streams;
		$this->needs_save = (bool)$needs_save;
	}

	/**
	 * Update the modification time on a stream. Don't forget to save()!
	 *
	 * @param string $name
	 * @param int $time
	 */
	public function touchStream($name, $time = null) {
		if (!$time) {
			$time = time();
		}
		$this->streams[$name][self::KEY_TIME] = (int)$time;
		$this->needs_save = true;
	}

	/**
	 * Delete a stream. Don't forget to save()!
	 *
	 * @param string $name
	 */
	public function deleteStream($name) {
		if (!isset($this->streams[$name])) {
			return;
		}

		unset($this->streams[$name]);
		$this->needs_save = true;
	}

//	/**
//	 * Does the stream exist?
//	 *
//	 * @param string $name
//	 * @return bool
//	 */
//	public function hasStream($name) {
//		return isset($this->streams[$name]);
//	}
//
//	/**
//	 * Get the last modification time of a stream
//	 *
//	 * @param string $name
//	 * @return int 0 if the stream does not exist
//	 */
//	public function getStreamLastModified($name) {
//		return $this->hasStream($name) ? (int)$this->streams[$name][self::KEY_TIME] : 0;
//	}

	/**
	 * Save all streams
	 */
	public function save() {
		if (!$this->needs_save) {
			return;
		}
		$serialized = serialize($this->streams);
		elgg_set_plugin_user_setting('streams', $serialized, $this->user_guid, 'mrclay_poll_svc');
		$this->needs_save = false;

		$dir = dirname($this->file_path);
		if (!is_dir($dir) || !is_writable($dir)) {
			elgg_log("mrclay_poll_svc: stream dir not writable: $dir", "WARNING");
			return;
		}
		if (is_file($this->file_path) && !is_writable($this->file_path)) {
			$basename = basename($this->file_path);
			elgg_log("mrclay_poll_svc: stream file not writable: $basename", "WARNING");
			return;
		}

		// try atomic write
		$tempnam = tempnam($dir, $this->user_guid);
		if (!$tempnam) {
			file_put_contents($this->file_path, json_encode($this->streams));
			return;
		}

		file_put_contents($tempnam, json_encode($this->streams));
		if (!rename($tempnam, $this->file_path)) {
			// handle windows
			copy($tempnam, $this->file_path);
			unlink($tempnam);
		}
	}

	/**
	 * Create a loaded stream collection (avoids work in constructor)
	 *
	 * @param int $user_guid
	 * @param string $file_path
	 * @return StreamCollection
	 */
	public static function factory($user_guid, $file_path) {
		$needs_save = false;
		$serialized = elgg_get_plugin_user_setting('streams', $user_guid, 'mrclay_poll_svc');
		$streams = $serialized ? unserialize($serialized) : false;
		if (!is_array($streams)) {
			$needs_save = true;
			$streams = array();
		}
		return new self($user_guid, $file_path, $streams, $needs_save);
	}
}
