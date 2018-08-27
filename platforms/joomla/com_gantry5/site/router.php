<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die ();

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

/**
 * Class Gantry5Router
 *
 * TODO:
 */
class Gantry5Router extends RouterView
{
    /**
     * Search Component router constructor
     *
     * @param   CMSApplication  $app   The application object
     * @param   AbstractMenu    $menu  The menu object to work with
     */
    public function __construct($app = null, $menu = null)
    {
        $custom = new RouterViewConfiguration('custom');
        $this->registerView($custom);

        $error = new RouterViewConfiguration('error');
        $this->registerView($error);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }
}
