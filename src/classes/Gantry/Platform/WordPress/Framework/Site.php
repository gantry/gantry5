<?php
namespace Gantry\Framework;

class Site extends \TimberSite
{
    /**
     * @param string $widget_id
     * @return \TimberFunctionWrapper
     */
    public function sidebar( $widget_id = '' ) {
        return \TimberHelper::function_wrapper( 'dynamic_sidebar', array( $widget_id ), true );
    }
}
