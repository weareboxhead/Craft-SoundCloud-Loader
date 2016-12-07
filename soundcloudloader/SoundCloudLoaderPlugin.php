<?php

namespace Craft;

class SoundCloudLoaderPlugin extends BasePlugin
{
	function getName()
	{
		return Craft::t('SoundCloud Loader');
	}

	function getVersion()
	{
		return '0.2';
	}

	function getDeveloper()
	{
		return 'Boxhead';
	}

	function getDeveloperUrl()
	{
		return 'http://boxhead.io';
	}

	function getSourceLanguage()
	{
		return 'en_gb';
	}

	function onAfterInstall()
	{
		// SoundCloud field group

		Craft::log('Creating the SoundCloud field group.');

		$group = new FieldGroupModel();
		$group->name = 'SoundCloud';

		if (craft()->fields->saveGroup($group))
		{
			Craft::log('SoundCloud field group created successfully.');
		}
		else
		{
			Craft::log('Could not save the SoundCloud field group.', LogLevel::Error);

			return false;
		}

		Craft::log('Creating the basic SoundCloud Fields.');

		$fields = array(
			'soundCloudFileId'				=>		'File Id',
			'soundCloudUserPermalink'		=>		'User Permalink',
			'soundCloudPermalink'			=>		'Track Permalink',
			'soundCloudPermalinkUrl'		=>		'Track Permalink URL',
			'soundCloudSharing'				=>		'Sharing',
			'soundCloudEmbeddableBy'		=>		'Embeddable By',
			'soundCloudPurchaseUrl'			=>		'Purchase URL',
			'soundCloudArtwork500'			=>		'Artwork 500px',
			'soundCloudArtwork400'			=>		'Artwork 400px',
			'soundCloudArtwork300'			=>		'Artwork 300px',
			'soundCloudArtwork100'			=>		'Artwork 100px',
			'soundCloudArtwork67'			=>		'Artwork 67px',
			'soundCloudArtwork47'			=>		'Artwork 47px',
			'soundCloudArtwork32'			=>		'Artwork 32px',
			'soundCloudArtwork20'			=>		'Artwork 20px',
			'soundCloudArtwork18'			=>		'Artwork 18px',
			'soundCloudArtwork16'			=>		'Artwork 16px',
			'soundCloudDescription'			=>		'Description',
			'soundCloudDuration'			=>		'Duration',
			'soundCloudDurationFormatted'	=>		'Duration Formatted',
			'soundCloudRelease'				=>		'Release',
			'soundCloudStreamable'			=>		'Streamable',
			'soundCloudDownloadable'		=>		'Downloadable',
			'soundCloudTrackType'			=>		'Track Type',
			'soundCloudWaveformUrl'		 	=>		'Waveform URL',
			'soundCloudDownloadUrl'			=>		'Download URL',		
			'soundCloudStreamUrl'			=>		'Stream URL',
			'soundCloudVideoUrl'			=>		'Video URL',
			'soundCloudBpm'					=>		'BPM',
			'soundCloudCommentable'			=>		'Commentable',
			'soundCloudCommentCount'		=>		'Comment Count',
			'soundCloudDownloadCount'		=>		'Download Count',
			'soundCloudPlaybackCount'		=>		'Playback Count',
			'soundCloudFavoritingsCount'	=>		'Favouritings Count',
			'soundCloudOriginalFormat'		=>		'Original Format',
			'soundCloudContentSize'			=>		'Content Size',
		);

		$soundCloudLayoutContent = array();

		foreach($fields as $handle => $name) {
			Craft::log('Creating the ' . $name . ' field.');

			$field = new FieldModel();
			$field->groupId	  = $group->id;
			$field->name		 = $name;
			$field->handle	   = $handle;
			$field->translatable = true;
			$field->type		 = 'PlainText';

			if (craft()->fields->saveField($field))
			{
				Craft::log($name . ' field created successfully.');

				$soundCloudLayoutContent[] = $field->id;
			}
			else
			{
				Craft::log('Could not save the ' . $name . ' field.', LogLevel::Error);

				return false;
			}
		}

		// SoundCloud Channel

		Craft::log('Creating the SoundCloud Channel.');

		$soundCloudLayout = craft()->fields->assembleLayout(
			array(
				'SoundCloud' => $soundCloudLayoutContent,
			),
			array()
		);

		$soundCloudLayout->type = ElementType::Entry;

		$soundCloudChannelSection = new SectionModel();
		$soundCloudChannelSection->name = 'SoundCloud';
		$soundCloudChannelSection->handle = 'soundCloud';
		$soundCloudChannelSection->type = SectionType::Channel;
		$soundCloudChannelSection->hasUrls = false;
		$soundCloudChannelSection->enableVersioning = false;

		$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
		$locales[$primaryLocaleId] = new SectionLocaleModel(array(
			'locale'		  => $primaryLocaleId,
		));

		$soundCloudChannelSection->setLocales($locales);

		// Save it
		if (craft()->sections->saveSection($soundCloudChannelSection))
		{
			Craft::log('SoundCloud Channel created successfully.');
		}
		else
		{
			Craft::log('Could not save the SoundCloud Channel.', LogLevel::Error);

			return false;
		}

		$soundCloudEntryTypes = $soundCloudChannelSection->getEntryTypes();
		$soundCloudEntryType = $soundCloudEntryTypes[0];
		$soundCloudEntryType->hasTitleField = true;
		$soundCloudEntryType->titleLabel = 'Title';
		$soundCloudEntryType->setFieldLayout($soundCloudLayout);

		if (craft()->sections->saveEntryType($soundCloudEntryType))
		{
			Craft::log('SoundCloud Channel entry type saved successfully.');
		}
		else
		{
			Craft::log('Could not save the SoundCloud Channel entry type.', LogLevel::Error);

			return false;
		}

		// Save the settings based on the section and entry type we just created
		craft()->plugins->savePluginSettings($this,
			array(
				'sectionId'	 => $soundCloudChannelSection->id,
				'entryTypeId'   => $soundCloudEntryType->id,
			)
		);
	}

	protected function defineSettings()
	{
		return array(
			'clientId'		 	=> array(AttributeType::String, 'default' => ''),
			'clientSecret'	 	=> array(AttributeType::String, 'default' => ''),
			'sectionId'		 	=> array(AttributeType::String, 'default' => ''),
			'entryTypeId'	   	=> array(AttributeType::String, 'default' => ''),
			'soundCloudUserId'  => array(AttributeType::String, 'default' => ''),
			'categories'		=> array(AttributeType::String, 'default' => ''),
		);
	}

	public function prepSettings($settings)
	{
		Craft::log('Updating settings.');

		if (empty($settings['categories']))
		{
			Craft::log('No categories set.');

			return $settings;
		}

		$categories = explode(',', $settings['categories']);

		foreach ($categories as $category) {
			$category = trim($category);
			$categoryGroupHandle = craft()->soundCloudLoader_string->camelCase($category);
			$fieldHandle = 'soundCloudCategories' . craft()->soundCloudLoader_string->stuldyCase($category);

			Craft::log('Checking category field exists for ' . $category . '(' . $fieldHandle . ')');

			// If this field doesn't exist
			if (!craft()->fields->getFieldByHandle($fieldHandle))
			{
				Craft::log('Creating category field for ' . $category);

				// Check if a category group for this category exits
				if (!$categoryGroup = craft()->categories->getGroupByHandle($categoryGroupHandle))
				{
					Craft::log('Creating ' . $category . ' category group.');

					// Create...
					$categoryGroup = new CategoryGroupModel();
					$categoryGroup->name = $category;
					$categoryGroup->handle = $categoryGroupHandle;
					$categoryGroup->hasUrls = false;

					if (craft()->categories->saveGroup($categoryGroup))
					{
						Craft::log($category . ' category group created successfully.');
					}
					else
					{
						Craft::log('Could not save the ' . $category . ' category group.', LogLevel::Warning);
					}
				}

				// Create it
				$field = new FieldModel();
				$field->groupId			= craft()->fields->getFieldByHandle('soundCloudFileId')->group->id;
				$field->name			= $category . ' Categories';
				$field->handle	 		= $fieldHandle;
				$field->translatable 	= true;
				$field->type		 	= 'Categories';
				$field->settings 		= array(
					'source' => 'group:' . $categoryGroup->id,
				);

				if (craft()->fields->saveField($field))
				{
					Craft::log($category . ' Categories field created successfully.');
				}
				else
				{
					Craft::log('Could not save the ' . $category . ' Categories field.', LogLevel::Warning);
				}

				// Get the entry type as defined in our settings
				$entryType = craft()->sections->getEntryTypeById($this->getSettings()->entryTypeId);

				// Get the ids for each of teh fields
				$entryTypeFieldIds = $entryType->getFieldLayout()->getFieldIds();

				$entryTypeFieldIds[] = $field->id;

				$entryType->setFieldLayout(craft()->fields->assembleLayout(
					array(
						'SoundCloud' => $entryTypeFieldIds,
					),
					array()
				));

				if (craft()->sections->saveEntryType($entryType))
				{
					Craft::log('SoundCloud Channel entry type saved successfully.');
				}
				else
				{
					Craft::log('Could not save the SoundCloud Channel entry type.', LogLevel::Warning);
				}
			}
		}

		return $settings;
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('soundcloudloader/settings', array(
			'settings' => $this->getSettings()
		));
	}
}
