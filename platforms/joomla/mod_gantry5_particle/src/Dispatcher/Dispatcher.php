<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Module\Gantry5Particle\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Helper\ModuleHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_gantry_particle
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   5.2.0
     */
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];

        $cacheParams               = new \stdClass();
        $cacheParams->cachemode    = 'id';
        $cacheParams->class        = $this->getHelperFactory()->getHelper('Gantry5ParticleHelper');
        $cacheParams->method       = 'getParticle';
        $cacheParams->methodparams = [$this->module, $params, $data['app']];
        $cacheParams->modeparams   = md5(serialize([$this->module->module, $this->module->id]));

        $data['particle'] = ModuleHelper::moduleCache($this->module, $params, $cacheParams);

        return $data;
    }
}
