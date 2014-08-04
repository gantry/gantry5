<?php
namespace Gantry;

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
        $this->theme = $theme;
        $this->site =  new Site\Site;
    }
}
