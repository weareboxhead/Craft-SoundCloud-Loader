<?php

namespace Craft;

class SoundCloudLoader_EntriesController extends BaseController
{
	protected $allowAnonymous = true;
	
	public function actionSyncWithRemote() {
		craft()->soundCloudLoader_entries->syncWithRemote();

		$this->renderTemplate('soundCloudLoader/empty');
	}
}