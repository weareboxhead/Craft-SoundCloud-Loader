<?php

namespace Craft;

class SoundCloudLoader_StringService extends BaseApplicationComponent
{
	public function camelCase($string)
	{
		$parts = explode(' ', strtolower($string));

		$string = '';

		foreach ($parts as $part) {
			if (strlen($string)) {
				$part = ucfirst($part);
			}

			$string .= $part;
		}

		return $string;
	}

	public function stuldyCase($string) {
		return ucfirst($this->camelCase($string));
	}
}

?>