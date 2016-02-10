<?php namespace Styla\Magazine;

/**
 * The plugin.php file (called the plugin initialization script) defines the plugin information class.
 */

use System\Classes\PluginBase;

class Plugin extends PluginBase
{

    public function pluginDetails()
    {
        return [
            'name'        => 'styla.magazine',
            'description' => 'Embeds a magazine, fetches crawable content and puts it on the site.',
            'author'      => 'Christian Korndoerfer',
            'icon'        => 'icon-newspaper-o'
        ];
    }

    public function registerComponents()
    {
        return [
            '\Styla\Magazine\Components\Magazine' => 'Magazine'
        ];
    }
}