<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
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
        $outline = isset($this->params['configuration']) ? $this->params['configuration'] : null;
        if ($outline && $outline !== 'default' && $outline[0] !== '_') {
            $assignments = new AssignmentsObject($outline);

            $this->params['assignments'] = $assignments->get();
            $this->params['options'] = $assignments->assignmentOptions();
            $this->params['assignment'] = $assignments->getAssignment();
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/assignments/assignments.html.twig', $this->params);
    }

    public function store()
    {
        if (!$this->container->authorize('outline.assign')) {
            $this->forbidden();
        }

        $outline = isset($this->params['configuration']) ? $this->params['configuration'] : null;
        if ($outline && ($outline === 'default' || $outline[0] === '_')) {
            $this->undefined();
        }

        if (!$this->request->post->get('_end')) {
            throw new \OverflowException("Incomplete data received. Please increase the value of 'max_input_vars' variable (in php.ini or .htaccess)", 400);
        }
        $assignments = new AssignmentsObject($outline);
        $assignments->set($this->request->post->getArray('assignments'));

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
