<?php

namespace Craft;

class SoundCloudLoader_DurationService extends BaseApplicationComponent
{
	function __construct()
	{
		
	}

	private function padTime($val)
	{
		// Add a '0' if the length is less than 2
		if (strlen($val) < 2) {
			$val = '0' . $val; 
		}

		return $val; 
	}

	public function formatTime($time)
	{
		// Convert to seconds
		$time = $time / 1000; 
		// Get seconds
		$seconds = $this->padTime(strval(floor($time % 60)));
		// Reduce to minutes
		$time = $time / 60; 
		// Get minues
		$mins = $this->padTime(strval(floor($time % 60))); 
		// Reduce to hours
		$time = $time / 60; 
		// Get hours
		$hours = $time % 60; 

		// Assume just minutes and seconds
		$time = $mins . ':' . $seconds; 

		// If there are hours
		if ($hours >= 1)
		{
			// Format correctly and prepend to time string
			$hours = $this->padTime(strval(floor($hours))); 
			$time = $hours . ':' . $time; 
		}

		return $time; 
	}
}