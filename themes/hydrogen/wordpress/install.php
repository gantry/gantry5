<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die;

class G5_HydrogenInstallerScript
{
    /**
     * Called by TemplateInstaller to customize post-installation.
     *
     * @param \Gantry\Framework\ThemeInstaller $installer
     */
    public function installDefaults(Gantry\Framework\ThemeInstaller $installer)
    {
        // Create default outlines etc.
        $installer->createDefaults();
    }

    /**
     * Called by TemplateInstaller to customize sample data creation.
     *
     * @param \Gantry\Framework\ThemeInstaller $installer
     */
    public function installSampleData(Gantry\Framework\ThemeInstaller $installer)
    {
        // Create sample data.
        $installer->createSampleData();
    }
}
