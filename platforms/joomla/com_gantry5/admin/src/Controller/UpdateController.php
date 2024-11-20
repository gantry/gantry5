<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Component\Gantry5\Administrator\Controller;

use Gantry\Component\Filesystem\Streams;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Gantry5 update controller for the Update view
 */
class UpdateController extends BaseController
{
    /**
     * Fetch and report updates in \JSON format, for AJAX requests
     *
     * @return  void
     */
    public function ajax()
    {
        if (!Session::checkToken('get')) {
            $this->app->setHeader('status', 403, true);
            $this->app->sendHeaders();
            echo Text::_('JINVALID_TOKEN_NOTICE');
            $this->app->close();
        }

        $gantry = Gantry::instance();

        /** @var Streams $streams */
        $streams = $gantry['streams'];
        $streams->register();

        /** @var Platform $platform */
        $platform   = $gantry['platform'];
        $updateInfo = $platform->updates();

        $update   = [];
        $update[] = ['version' => $updateInfo['latest']];

        echo json_encode($update);

        $this->app->close();
    }
}
