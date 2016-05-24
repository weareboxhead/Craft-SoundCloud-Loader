<?php

namespace Craft;

class SoundCloudLoaderVariable
{
    public function syncWithRemote()
    {
        craft()->soundCloudLoader_entries->syncWithRemote();
    }
}