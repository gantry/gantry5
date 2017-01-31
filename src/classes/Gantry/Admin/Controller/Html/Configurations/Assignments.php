<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Admin\HtmlController;
use Gantry\Framework\Assignments as AssignmentsObject;
use RocketTheme\Toolbox\Event\Event;

class Assignments extends HtmlController
{
    public function index()
    {
        $outline = $this->params['outline'];

        if ($this->hasAssignments($outline)) {
            $assignments = new AssignmentsObject($outline);

            $this->params['assignments'] = $assignments->get();
            $this->params['options'] = $assignments->assignmentOptions();
            $this->params['assignment'] = $assignments->getAssignment();
        }

        return $this->render('@gantry-admin/pages/configurations/assignments/assignments.html.twig', $this->params);
    }

    public function store()
    {
        // Authorization.
        if (!$this->authorize('outline.assign')) {
            $this->forbidden();
        }

        $outline = $this->params['outline'];
        if (!$this->hasAssignments($outline)) {
            $this->undefined();
        }

        if (!$this->request->post->get('_end')) {
            throw new \OverflowException("Incomplete data received. Please increase the value of 'max_input_vars' variable (in php.ini or .htaccess)", 400);
        }

        // Save assignments.
        $assignments = new AssignmentsObject($outline);
        $assignments->save($this->request->post->getArray('assignments'));

        // Fire save event.
        $event = new Event;
        $event->gantry = $this->container;
        $event->theme = $this->container['theme'];
        $event->controller = $this;
        $event->assignments = $assignments;
        $this->container->fireEvent('admin.assignments.save', $event);

        return '';
    }

    protected function hasAssignments($outline)
    {
        // Default outline and system outlines cannot have assignments.
        return $outline !== 'default' && $outline[0] !== '_';
    }
}
