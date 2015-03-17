<?php
defined('_JEXEC') or die;

/**
 * Gantry5 package installer script.
 */
class Pkg_Gantry5InstallerScript {
    /**
     * List of supported versions. Newest version first!
     * @var array
     */
    protected $versions = array(
        'PHP' => array (
            '5.4' => '5.4.0',
            '0' => '5.5.9' // Preferred version
        ),
        'Joomla!' => array (
            '3.4' => '3.4.0',
            '0' => '3.4.0' // Preferred version
        )
    );
    /**
     * List of required PHP extensions.
     * @var array
     */
    protected $extensions = array ('json', 'pcre');

    public function install($parent)
    {
        return true;
    }

    public function discover_install($parent)
    {
        return self::install($parent);
    }

    public function update($parent)
    {
        return self::install($parent);
    }

    public function uninstall($parent)
    {
        return true;
    }

    public function preflight($type, $parent)
    {
        /** @var JInstallerComponent $parent */
        $manifest = $parent->getParent()->getManifest();

        // Prevent installation if requirements are not met.
        if (!$this->checkRequirements($manifest->version)) {
            return false;
        }

        // Remove old log file before installation.
        $logFile = JFactory::getConfig()->get('log_path') . '/gantry5-install.php';

        if (is_file($logFile)) {
            @unlink($logFile);
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        // Clear Joomla system cache.
        /** @var JCache|JCacheController $cache */
        $cache = JFactory::getCache();
        $cache->clean('_system');

        // Remove all compiled files from APC cache.
        if (function_exists('apc_clear_cache')) {
            @apc_clear_cache();
        }

        if ($type == 'uninstall') return true;

        $this->enablePlugin('system', 'gantry5');
        $this->enablePlugin('quickicon', 'gantry5');

        return true;
    }

    // Internal functions

    protected function enablePlugin($group, $element)
    {
        $plugin = JTable::getInstance('extension');

        if (!$plugin->load(array('type' => 'plugin', 'folder' => $group, 'element' => $element))) {
            return false;
        }

        $plugin->enabled = 1;

        return $plugin->store();
    }

    protected function checkRequirements($version)
    {
        $pass  = $this->checkVersion('PHP', phpversion());
        $pass &= $this->checkVersion('Joomla!', JVERSION);
        $pass &= $this->checkExtensions($this->extensions);

        return $pass;
    }

    protected function checkVersion($name, $version)
    {
        $app = JFactory::getApplication();

        $major = $minor = 0;

        foreach ($this->versions[$name] as $major=>$minor) {
            if (!$major || version_compare($version, $major, '<')) continue;
            if (version_compare($version, $minor, '>=')) return true;
            break;
        }

        if (!$major) {
            $minor = reset($this->versions[$name]);
        }

        $recommended = end($this->versions[$name]);
        $app->enqueueMessage(sprintf("%s %s is not supported. Minimum required version is %s %s, but it is highly recommended to use %s %s or later.", $name, $version, $name, $minor, $name, $recommended), 'notice');

        return false;
    }


    protected function checkExtensions($extensions)
    {
        $app = JFactory::getApplication();

        $pass = 1;
        foreach ($extensions as $name) {
            if (!extension_loaded($name)) {
                $pass = 0;
                $app->enqueueMessage(sprintf("Required PHP extension '%s' is missing. Please install it into your system.", $name), 'notice');
            }
        }
        return $pass;
    }
}
