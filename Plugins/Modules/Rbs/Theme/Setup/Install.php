<?php
namespace Rbs\Theme\Setup;

/**
 * @name \Rbs\Theme\Setup\Install
 */
class Install
{
    /**
     * @param \Change\Plugins\Plugin $plugin
     */
    public function finalize($plugin)
    {
        $plugin->setConfigurationEntry('locked', true);
    }
}