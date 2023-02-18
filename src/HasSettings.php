<?php

namespace Smartisan\Settings;

use Settings;
use Smartisan\Settings\Settings as SettingsManager;

trait HasSettings
{
    /**
     * Retrieve the settings manager instance for this model.
     */
    public function settings(): SettingsManager
    {
        return Settings::for($this);
    }
}
