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
	 * @param string $connection_name
	 * @return string
	 */
	public function getFilePath($connection_name) {
		$mac = hash_hmac('md5', $connection_name, $this->key, true);
		$mac = rtrim(strtr(base64_encode($mac), "+/", "-_"), '=');

		$subdir = substr($mac, 0, 2);
		$base = substr($mac, 2);

		return "{$this->dir}/$subdir/$base.json";
	}

	/**
	 * Write the JSON file
	 *
	 * @param string $connection_name
	 * @param Connection $collection
	 * @throws \RuntimeException
	 */
	public function write($connection_name, Connection $collection) {
		$path = $this->getFilePath($connection_name);

		if (file_exists($path) && !is_writable($path)) {
			$basename = basename($path);
			throw new \RuntimeException("connection file not writable: $basename");
		}

		$channels = $collection->getChannels();
		if ($channels) {
			$json = json_encode($collection->getChannels());
		} else {
			// make sure we get an object!
			$json = "{}";
		}

		$subdir = dirname($path);
		if (!is_dir($subdir)) {
			mkdir($subdir);
		}

		// try atomic
		$tempnam = tempnam($this->dir, $connection_name);
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
