<?php
namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Request\Request;
use Gantry\Framework\Assignments as AssignmentsObject;
use Gantry\Framework\Menu;
use Gantry\Framework\Pages;

class Assignments extends HtmlController
{
    public function index()
    {
        $assignments = new AssignmentsObject(isset($this->params['configuration']) ? $this->params['configuration'] : null);

        $this->params['assignments'] = $assignments->get();
        $this->params['pages'] = new Pages();

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/assignments/assignments.html.twig', $this->params);
    }

    public function store()
    {
        /** @var Request $request */
        $request = $this->container['request'];

        $assignments = new AssignmentsObject(isset($this->params['configuration']) ? $this->params['configuration'] : null);
        $assignments->set($request->getArray());

        return '';
    }
}
