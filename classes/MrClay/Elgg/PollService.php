<?php

namespace MrClay\Elgg;

use MrClay\LightPolling\ChannelCollection;
use MrClay\LightPolling\FileStorage;

class PollService {

	protected $storage;

	public function __construct(FileStorage $storage) {
		$this->storage = $storage;
	}

	/**
	 * Use a channel collection. This function will manage saving the collection afterwards.
	 *
	 * @param int $user_guid
	 * @param callable $func Function (will be passed a ChannelCollection)
	 */
	public function useCollection($user_guid, $func) {
		$collection = $this->loadCollection($user_guid);
		$old_time = $collection->getTimeModified();

		call_user_func($func, $collection);

		$new_time = $collection->getTimeModified();
		if ($new_time !== $old_time) {
			if (!$collection->getChannels()) {
				// deleted
				$this->deleteCollection($user_guid);
			} else {
				$this->saveCollection($user_guid, $collection);
			}
			$this->storage->write($user_guid, $collection);
		}
	}

	/**
	 * @param int $user_guid
	 * @param ChannelCollection $collection
	 */
	protected function saveCollection($user_guid, ChannelCollection $collection) {
		$serialized = serialize($collection);
		elgg_set_plugin_user_setting('collection', $serialized, $user_guid, 'mrclay_poll_svc');
	}

	/**
	 * @param int $user_guid
	 * @return ChannelCollection
	 */
	public function loadCollection($user_guid) {
		$serialized = elgg_get_plugin_user_setting('collection', $user_guid, 'mrclay_poll_svc');
		if ($serialized) {
			$collection = unserialize($serialized);
			if ($collection instanceof ChannelCollection) {
				return $collection;
			}
		}
		return new ChannelCollection();
	}

	/**
	 * @param int $user_guid
	 */
	protected function deleteCollection($user_guid) {
		elgg_unset_plugin_user_setting('collection', $user_guid, 'mrclay_poll_svc');
	}

	/**
	 * @return PollService
	 */
	public static function factory() {
		$dir = elgg_get_config('mrclay_poll_svc_public_dir');
		if ($dir) {
			$dir = rtrim($dir, '/\\');
		} else {
			$dir = elgg_get_plugins_path() . 'mrclay_poll_svc/public';
		}

		$key = elgg_get_plugin_setting('filename_key', 'mrclay_poll_svc');
		if (!$key) {
			$key = sha1(get_site_secret() . time() . "mrclay_poll_svc", true);
			$key = base64_encode($key);
			elgg_set_plugin_setting('filename_key', $key, 'mrclay_poll_svc');
		}
		$key = base64_decode($key);

		$storage = new FileStorage($dir, $key);
		return new self($storage);
	}
}
