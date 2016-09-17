<?php

namespace MrClay\Elgg;

use MrClay\LightPolling\Connection;
use MrClay\LightPolling\FileStorage;
use ElggFileCache;

class PollService {

	const METADATA_KEY = 'poll_svc_connection';

	protected $storage;
	protected $cache;

	protected $connection_guids = array();

	public function __construct(FileStorage $storage, ElggFileCache $cache) {
		$this->storage = $storage;
		$this->cache = $cache;
	}

	/**
	 * @param \ElggEntity $entity
	 */
	public function requestConnection(\ElggEntity $entity) {
		$this->connection_guids[$entity->guid] = true;
	}

	/**
	 * Add channel(s) to a connection
	 *
	 * @param \ElggEntity     $entity
	 * @param string|string[] $channels
	 */
	public function addChannels(\ElggEntity $entity, $channels) {
		$channels = (array)$channels;
		$this->useConnection($entity, function (Connection $connection) use ($channels) {
			foreach ($channels as $channel) {
				$connection->addChannel($channel);
			}
		});
	}

	/**
	 * Create an empty connection (and file) if they don't exist
	 *
	 * @param \ElggEntity $entity
	 */
	public function initConnection(\ElggEntity $entity) {
		$this->useConnection($entity, function (Connection $connection) {
			if (!$connection->getTimeModified()) {
				$connection->touch();
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

		$connection = $this->loadConnection($entity);
		$old_time = $connection->getTimeModified();

		call_user_func($func, $connection);

		$new_time = $connection->getTimeModified();
		if ($new_time !== $old_time) {
			// TODO under what conditions should we deleteConnection()? Problem is that until the
			// first ping, there won't be a connection for the client to listen to.
			$this->saveConnection($entity, $connection);
			$this->storage->write($entity->guid, $connection);
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
	 * @param Connection  $connection
	 */
	protected function saveConnection(\ElggEntity $entity, Connection $connection) {
		$serialized = serialize($connection);
		$this->cache->save("poll_svc_{$entity->guid}", $serialized);
	}

	/**
	 * @param \ElggEntity $entity
	 * @return Connection
	 */
	public function loadConnection(\ElggEntity $entity) {
		$serialized = $this->cache->load("poll_svc_{$entity->guid}");
		if ($serialized) {
			$connection = unserialize($serialized);
			if ($connection instanceof Connection) {
				return $connection;
			}
		}
		return new Connection();
	}

	public function deleteAll() {
		$this->storage->deleteAll();
	}

	/**
	 * @param \ElggEntity $entity
	 */
	protected function deleteConnection(\ElggEntity $entity) {
		$this->cache->delete("poll_svc_{$entity->guid}");
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
		return new self($storage, elgg_get_system_cache());
	}
}
