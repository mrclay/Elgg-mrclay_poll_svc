<?php

namespace MrClay\LightPolling;

class FileStorage {

	protected $dir;
	protected $key;

	/**
	 * @param string $dir Directory
	 * @param string $filename_key HMAC Key used to generate filenames
	 * @throws \InvalidArgumentException
	 */
	public function __construct($dir, $filename_key) {
		$dir = rtrim($dir, '/\\');
		if (!is_dir($dir) || !is_writable($dir)) {
			throw new \InvalidArgumentException("\$dir is not a writable path: $dir");
		}

		$this->dir = $dir;
		$this->key = $filename_key;
	}

	/**
	 * Get JSON file path
	 *
	 * @param string $client_id
	 * @return string
	 */
	public function getFilePath($client_id) {
		$mac = hash_hmac('md5', $client_id, $this->key, true);
		$mac = rtrim(strtr(base64_encode($mac), "+/", "-_"), '=');

		return "{$this->dir}/$mac.json";
	}

	/**
	 * Write the JSON file
	 *
	 * @param string $client_id
	 * @param ChannelCollection $collection
	 * @throws \RuntimeException
	 */
	public function write($client_id, ChannelCollection $collection) {
		$path = $this->getFilePath($client_id);

		if (file_exists($path) && !is_writable($path)) {
			$basename = basename($path);
			throw new \RuntimeException("channel file not writable: $basename");
		}

		$channels = $collection->getChannels();
		if ($channels) {
			$json = json_encode($collection->getChannels());
		} else {
			// make sure we get an object!
			$json = "{}";
		}

		// try atomic
		$tempnam = tempnam($this->dir, $client_id);
		if (!$tempnam) {
			file_put_contents($path, $json);
			return;
		}

		file_put_contents($tempnam, $json);
		if (!rename($tempnam, $path)) {
			// handle windows
			copy($tempnam, $path);
			unlink($tempnam);
		}
	}
}
