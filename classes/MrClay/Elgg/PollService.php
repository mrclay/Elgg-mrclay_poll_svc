<?php

namespace MrClay\Elgg;

use MrClay\Elgg\PollService\StreamCollection;

class PollService {

	/**
	 * Get a user's stream collection. It's your responsibility to save() the collection
	 * if you modify it.
	 *
	 * @param int $user_guid
	 * @return StreamCollection
	 */
	public function getUserStreams($user_guid) {
		return StreamCollection::factory($user_guid, $this->getFilePath($user_guid));
	}

	/**
	 * Get stream file path
	 *
	 * To change the public directory, set $CONFIG->mrclay_poll_svc_public_dir
	 *
	 * @param int $user_guid
	 * @return string
	 */
	public function getFilePath($user_guid) {
		static $dir;
		static $key;

		if ($dir === null) {
			$dir = elgg_get_config('mrclay_poll_svc_public_dir');
			if ($dir) {
				$dir = rtrim($dir, '/\\');
			} else {
				$dir = elgg_get_plugins_path() . 'mrclay_poll_svc/public';
			}
		}
		if ($key === null) {
			$key = $this->getFilenameKey();
		}

		$mac = hash_hmac('md5', $user_guid, $key, true);
		$mac = rtrim(strtr(base64_encode($mac), "+/", "-_"), '=');

		return "$dir/$mac.json";
	}

	/**
	 * Get binary key used to generate filenames. The site secret is used to generate
	 * this, but changing the site secret doesn't change it.
	 *
	 * @return string
	 */
	protected function getFilenameKey() {
		$key = elgg_get_plugin_setting('filename_key', 'mrclay_poll_svc');
		if (!$key) {
			$key = sha1(get_site_secret() . time() . "mrclay_poll_svc", true);
			$key = base64_encode($key);
			elgg_set_plugin_setting('filename_key', $key, 'mrclay_poll_svc');
		}
		return base64_decode($key);
	}
}
