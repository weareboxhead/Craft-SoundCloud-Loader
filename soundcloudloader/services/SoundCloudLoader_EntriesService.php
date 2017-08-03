<?php

namespace Craft;

class SoundCloudLoader_EntriesService extends BaseApplicationComponent
{
	private $callLimit = 200;
	private $sectionId;
	private $entryTypeId;
	private $soundCloud;
	private $soundCloudUserId;
	private $categoryGroups;

	function __construct()
	{
		// Get the wrapper for our authenticated call
		require_once craft()->path->getPluginsPath() . 'soundcloudloader/wrapper/Services/Soundcloud.php';

		$settings = craft()->plugins->getPlugin('soundCloudLoader')->getSettings();

		// These settings are required for the process to work
		$requiredSettings = array('clientId', 'clientSecret', 'sectionId', 'entryTypeId', 'soundCloudUserId');

		// Foreach required setting
		foreach ($requiredSettings as $setting) {
			// If it is empty
			if (empty($settings->{$setting})) {
				Craft::log('No ' . $setting . ' provided in settings', LogLevel::Error);

				// Don't go on
				return false;
			}
		}

		$clientId 				= $settings->clientId;
		$clientSecret 			= $settings->clientSecret;

		$this->sectionId 		= $settings->sectionId;
		$this->entryTypeId 		= $settings->entryTypeId;
		$this->soundCloudUserId = $settings->soundCloudUserId;

		// Get each of the categories, splitting by the comma and trimming each part of the array
		$this->categoryGroups 	= array_map('trim', explode(',', $settings->categories));

		// Create our calling object
		$this->soundCloud 		= new \Services_Soundcloud($clientId, $clientSecret);

		return;
	}

	private function getRemoteTracks($soundCloud, $offset)
	{
		// Get the user's tracks
		$tracks = json_decode($this->soundCloud->get('users/' . $this->soundCloudUserId . '/tracks', array(
				'limit' => $this->callLimit,
				'offset' => $offset,
			)
		), true);

		return $tracks; 
	}

	private function getRemoteData()
	{
		$data = array(
			'ids'		=>	array(),
			'tracks'	=>	array(),
		);

		$tracks = array(); 
		$num_tracks = 0; 

		// While the SoundCloud API is still returning tracks (call limit $callLimit), keep appending to the array 
		do {
			// Set the offset to the current count
			$offset = $num_tracks;
			// Return tracks and merge with the existing
			$tracks = array_merge($tracks, $this->getRemoteTracks($this->soundCloud, $offset));
			// Count the new array
			$num_tracks = count($tracks);
		} while ($num_tracks % $this->callLimit === 0 && $offset !== $num_tracks);

		// For each track, add it to our data object with its id as the key
		foreach ($tracks as $track) {
			$id = $track['id'];

			// Add this id to our array
			$data['ids'][]			= $id;
			// Add this track to our array
			$data['tracks'][$id] 	= $track;
		}

		return $data;
	}

	private function getLocalData()
	{
		$data = array(
			'ids'		=>	array(),
			'tracks'	=>	array(),
		);

		// Create a Craft Element Criteria Model
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		// Restrict the parameters to the correct channel
		$criteria->sectionId = $this->sectionId;
		// Restrict the parameters to the correct entry type
		$criteria->type = $this->entryTypeId;
		// Include closed entries
		$criteria->status = [null];
		// Don't limit the number of entries
		$criteria->limit = null;

		// For each track, add it to our data object with its id as the key
		foreach ($criteria as $track) {
			$id = $track->soundCloudFileId;

			// Add this id to our array
			$data['ids'][]			= $id;
			// Add this track to our array
			$data['tracks'][$id] 	= $track;
		}

		return $data;
	}

	public function getPublishDate($data)
	{
		$release_year 	= $data['release_year']; 
		$release_month 	= $data['release_month'];
		$release_day 	= $data['release_day']; 

		// If there is a custom release date set, use this instead of the 'created_at' date
		if (!empty($release_year) && !empty($release_month) && !empty($release_day)) {
			$string = $release_year . '/' .  $release_month . '/' .  $release_day;
		} else {
			$string = $data['created_at'];
		}

		return strtotime($string); 
	}

	private function getStandardContent($data) {
		// Check for whether this track has its own artwork, and if not, use the avatar
		if (!empty($data['artwork_url'])) {
			$artworkUrl = $data['artwork_url'];
		} else {
			$artworkUrl = $data['user']['avatar_url']; 
		}

		$content = array(
			'soundCloudFileId'				=>	$data['id'],
			'soundCloudUserPermalink'		=>	$data['user']['permalink'],
			'soundCloudPermalink'			=>	$data['permalink'],
			'soundCloudPermalinkUrl'		=>	$data['permalink_url'],
			'soundCloudSharing'				=>	$data['sharing'],
			'soundCloudEmbeddableBy'		=>	$data['embeddable_by'],
			'soundCloudPurchaseUrl'			=>	$data['purchase_url'],
			'soundCloudArtwork500'			=> 	str_replace('large.jpg', 't500x500.jpg', 	$artworkUrl),
			'soundCloudArtwork400'			=> 	str_replace('large.jpg', 'crop.jpg', 		$artworkUrl),
			'soundCloudArtwork300'			=> 	str_replace('large.jpg', 't300x300.jpg',	$artworkUrl),
			'soundCloudArtwork100'			=> 	$artworkUrl,
			'soundCloudArtwork67'			=> 	str_replace('large.jpg', '67x67.jpg', 		$artworkUrl),
			'soundCloudArtwork47'			=> 	str_replace('large.jpg', '47x47.jpg', 		$artworkUrl),
			'soundCloudArtwork32'			=> 	str_replace('large.jpg', '32x32.jpg', 		$artworkUrl),
			'soundCloudArtwork20'			=> 	str_replace('large.jpg', '20x20.jpg', 		$artworkUrl),
			'soundCloudArtwork18'			=> 	str_replace('large.jpg', '18x18.jpg', 		$artworkUrl),
			'soundCloudArtwork16'			=> 	str_replace('large.jpg', '16x16.jpg', 		$artworkUrl),
			'soundCloudDescription'			=>	$data['description'],
			'soundCloudDuration'			=>	$data['duration'],
			'soundCloudDurationFormatted'	=>	craft()->soundCloudLoader_duration->formatTime($data['duration']),
			'soundCloudRelease'				=>	$data['release'],
			'soundCloudStreamable'			=>	$data['streamable'],
			'soundCloudDownloadable'		=>	$data['downloadable'],
			'soundCloudTrackType'			=>	$data['track_type'],
			'soundCloudWaveformUrl'			=>  $data['waveform_url'],
			// This doesn't exist for all so check to prevent errors. Empty is fine, but not set throws an error
			'soundCloudDownloadUrl'			=>	isset($data['download_url']) ? $data['download_url'] : '',
			'soundCloudStreamUrl'			=>  isset($data['stream_url']) ? $data['stream_url'] : '',
			'soundCloudVideoUrl'			=>	$data['video_url'],
			'soundCloudBpm'					=>	$data['bpm'],
			'soundCloudCommentable'			=>	$data['commentable'],
			'soundCloudCommentCount'		=>	$data['comment_count'],
			'soundCloudDownloadCount'		=>	$data['download_count'],
			'soundCloudPlaybackCount'		=>	$data['playback_count'],
			'soundCloudFavoritingsCount'	=>	$data['favoritings_count'],
			'soundCloudOriginalFormat'		=>	$data['original_format'],
			'soundCloudContentSize'			=>	$data['original_content_size'],
		);

		return $content;
	}

	private function createEntry($data)
	{
		// Create a new instance of the Craft Entry Model
		$entry = new EntryModel();
		// Set the section id
		$entry->sectionId = $this->sectionId;
		// Set the entry type
		$entry->typeId 	= $this->entryTypeId;
		// Set the author as super admin
		$entry->authorId = 1;
		// Set disabled to begin with
		$entry->enabled = false;
		// Set the publish date as post date
		$entry->postDate = $this->getPublishDate($data);
		// Set the title
		$entry->getContent()->title = $data['title'];

		// The standard content
		$content = $this->getStandardContent($data);

		// Merge the parsed categories into the content
		$content = array_merge($content, craft()->soundCloudLoader_categories->getCategories($data, $this->categoryGroups));

		// Set the other content
		$entry->setContentFromPost($content);
		// Save the entry!
		$this->saveEntry($entry);
	}

	private function updateEntry($localEntry, $remoteEntry)
	{
		$updatable = $this->getStandardContent($remoteEntry);

		// Set up a null variable for the new title
		$newTitle = null;
		// Set up an empty array for our updating content
		$updating = array();

		// Check through each of the standard text fields
		foreach ($updatable as $fieldHandle => $soundCloudValue) {
			// If our local value is not the same as the remote value
			// (allow coercion of variable type)
			if ($localEntry->{$fieldHandle} != $soundCloudValue) {
				// Null values need converting to empty strings so they are updated correctly
				if (is_null($soundCloudValue)) {
					$soundCloudValue = '';
				}

				// Add this to the updating array
				$updating[$fieldHandle] = $soundCloudValue;
			}
		}

		// If our title is different to the remote one
		if ($localEntry->title !== $remoteEntry['title']) {
			$newTitle = $remoteEntry['title'];
		}

		// If we have no updating content, don't update the entry
		if (!count($updating) && !$newTitle)
		{
			return true;
		}

		if ($newTitle) {
			$localEntry->getContent()->title = $newTitle;
		}

		$localEntry->setContentFromPost($updating);

		$this->saveEntry($localEntry);
	}

	private function closeEntry($entry)
	{
		// Set the status to disabled
		$entry->enabled = false;
		// Save it
		$this->saveEntry($entry);
	}
	
	private function saveEntry($entry)
	{
		$success = craft()->entries->saveEntry($entry);

		// If the attempt failed
		if (!$success) {
			Craft::log('Couldn’t save entry ' . $entry->getContent()->id, LogLevel::Warning);
		}
	}

	public function syncWithRemote()
	{
		// If we don't have the required connection, don't do this
		if (!$this->soundCloud) {
			Craft::log('No connection established!', LogLevel::Error);

			return false;
		}

		Craft::log('Getting remote data');

		// Get remote data
		$remoteData = $this->getRemoteData();

		Craft::log('Getting local data');

		// Get local data
		$localData 	= $this->getLocalData();

		// Determine which entries we are missing by id
		$missingTracks 	= 	array_diff($remoteData['ids'], $localData['ids']);
		// Determine which entries we shouldn't have by id
		$removedTracks 	= 	array_diff($localData['ids'], $remoteData['ids']);
		// Determine which entries need updating (all active tracks which we aren't about to create)
		$updatingTracks =	array_diff($remoteData['ids'], $missingTracks);

		Craft::log('Creating missing tracks');

		// For each missing id
		foreach ($missingTracks as $id)
		{
			// Create this entry
			$this->createEntry($remoteData['tracks'][$id]);
		}

		Craft::log('Closing removed tracks');

		// For each redundant entry
		foreach ($removedTracks as $id)
		{
			// Disable it
			$this->closeEntry($localData['tracks'][$id]);
		}

		Craft::log('Updating other tracks');

		// For each updating track
		foreach ($updatingTracks as $id) {
			$this->updateEntry($localData['tracks'][$id], $remoteData['tracks'][$id]);
		}
	}
}