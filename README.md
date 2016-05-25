# SoundCloud Loader - Plugin for Craft CMS

'Synchronises' a SoundCloud account with a Craft CMS Section.

More specifically, SoundCloud Loader retrieves content from a given SoundCloud account, saves new content as individual entries, updates the play count for existing entries, and closes entries which have been removed from SoundCloud

The Section and Fields are automatically created on installation.

## Usage

* Download and extract the plugin files
* Copy `soundcloudloader/` to your site's `/craft/plugins/` directory
* Install the plugin
* Fill in the fields in the plugin's [settings](#settings)
* Load `http://[yourdomain]/actions/soundCloudLoader/entries/syncWithRemote` OR include `{{ craft.soundcloudloader.syncWithRemote() }}` in a template file and load it

## <a name="settings"></a>Settings

### Client Id, Client Secret

See [https://developer.soundcloud.com/](https://developer.soundcloud.com/) for registering a new app.

### Section Id, Entry Type Id

These are the ids of the Section and Entry Type used.

Automatically populated on plugin install.

### SoundCloud User Id

This is the id of the SoundCloud user you are retrieving content from.

### Categories

A comma separated list of SoundCloud tags to look out for when creating new Craft entries e.g. "speaker, topic". If a relevant tag is found for an entry, it will be saved as a category for it.

Tags on SoundCloud which correlate to this list should be in the format "#[category] - [content]" e.g. "#Speaker - John Smith", or "#Topic - Craft CMS".

Category groups are automatically created on saving the settings.