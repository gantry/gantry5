<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

class modGantry5Particle
{
    public function __construct($params)
    {
        $this->parameters = $params;
    }

    function display()
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];

        $params = [
            'particle' => $gantry['config']->get('particles.copyright')
        ];

        return $theme->render('@particles/copyright.html.twig', $params);
    }
}
