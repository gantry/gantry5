<?php
namespace Gantry;

use Gantry\Base\Config;

class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected function __construct()
    {
        // Make sure Timber plugin has been loaded.
        if ( !class_exists( 'Timber' ) ) {
            throw new \LogicException( 'Timber not activated. Make sure you activate the plugin in <a href="' . admin_url( 'plugins.php#timber' ) . '">' . admin_url( 'plugins.php' ) . '</a>' );
        }
    }

    public function initialize( Theme\Theme $theme ) {
        $path = $theme->path;
        $this->theme = $theme;
        $this->config = Config::instance( $path . '/cache/config.php', $path );
        $this->site =  new Site\Site;
    }
}
