<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Request\Request;
use Gantry\Framework\Assignments as AssignmentsObject;
use Gantry\Framework\Menu;
use RocketTheme\Toolbox\Event\Event;

class Assignments extends HtmlController
{
    public function index()
    {
        $configuration = isset($this->params['configuration']) ? $this->params['configuration'] : null;
        if ($configuration !== 'default') {
            $assignments = new AssignmentsObject($configuration);

            $this->params['assignments'] = $assignments->get();
            $this->params['options'] = $assignments->assignmentOptions();
            $this->params['assignment'] = $assignments->getAssignment();
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/assignments/assignments.html.twig', $this->params);
    }

    public function store()
    {
        $configuration = isset($this->params['configuration']) ? $this->params['configuration'] : null;
        if ($configuration === 'default') {
            $this->undefined();
        }

        $assignments = new AssignmentsObject($configuration);
        $assignments->set($this->request->post->getArray());

        // Fire save event.
        $event = new Event;
        $event->gantry = $this->container;
        $event->theme = $this->container['theme'];
        $event->controller = $this;
        $event->assignments = $assignments;
        $this->container->fireEvent('admin.assignments.save', $event);

        return '';
    }
}
