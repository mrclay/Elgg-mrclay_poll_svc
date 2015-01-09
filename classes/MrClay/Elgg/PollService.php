<?php

namespace MrClay\Elgg;

use MrClay\LightPolling\Connection;
use MrClay\LightPolling\FileStorage;

class PollService {

	const METADATA_KEY = 'poll_svc_connection';

	protected $storage;

	protected $connection_guids = array();

	public function __construct(FileStorage $storage) {
		$this->storage = $storage;
	}

	/**
	 * @param \ElggEntity $entity
	 */
	public function requestConnection(\ElggEntity $entity) {
		$this->connection_guids[$entity->guid] = true;
	}

	/**
	 * Create an empty connection (and file) if they don't exist
	 *
	 * @param \ElggEntity $entity
	 */
	public function initConnection(\ElggEntity $entity) {
		$this->useConnection($entity, function (Connection $conn) {
			if (!$conn->getTimeModified()) {
				$conn->touch();
			}
		});
	}

	/**
	 * @return int[]
	 */
	public function getRequestedConnections() {
		return array_keys($this->connection_guids);
	}

	/**
	 * Use a channel collection. This function will manage persisting the connection afterwards.
	 *
	 * @param \ElggEntity $entity
	 * @param callable $func Function (will be passed the Connection)
	 */
	public function useConnection(\ElggEntity $entity, $func) {
		if (!$entity->guid) {
			throw new \InvalidArgumentException('$entity must be saved first.');
		}

		$collection = $this->loadConnection($entity);
		$old_time = $collection->getTimeModified();

		call_user_func($func, $collection);

		$new_time = $collection->getTimeModified();
		if ($new_time !== $old_time) {
			if (!$collection->getChannels()) {
				// deleted
				$this->deleteConnection($entity);
			} else {
				$this->saveConnection($entity, $collection);
			}
			$this->storage->write($entity->guid, $collection);
		}
	}

	/**
	 * @param int $guid
	 * @return string
	 */
	public function getConnectionFile($guid) {
		return $this->storage->getFilePath($guid);
	}

	/**
	 * @param \ElggEntity $entity
	 * @param Connection $collection
	 */
	protected function saveConnection(\ElggEntity $entity, Connection $collection) {
		$serialized = serialize($collection);
		$ia = elgg_set_ignore_access(true);
		$entity->setMetadata(self::METADATA_KEY, $serialized, '', false, 0, ACCESS_PUBLIC);
		elgg_set_ignore_access($ia);
	}

	/**
	 * @param \ElggEntity $entity
	 * @return Connection
	 */
	public function loadConnection(\ElggEntity $entity) {
		$serialized = $entity->getMetadata(self::METADATA_KEY);
		if ($serialized) {
			$collection = unserialize($serialized);
			if ($collection instanceof Connection) {
				return $collection;
			}
		}
		return new Connection();
	}

	/**
	 * @param \ElggEntity $entity
	 */
	protected function deleteConnection(\ElggEntity $entity) {
		$ia = elgg_set_ignore_access(true);
		$entity->deleteMetadata(self::METADATA_KEY);
		elgg_set_ignore_access($ia);
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
