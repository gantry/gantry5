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

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Framework\Positions as PositionsObject;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Positions extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'   => 'index',
            '/**' => 'forward',
        ],
        'POST' => [
            '/'            => 'undefined',
            '/*'           => 'undefined',
            '/create'      => 'create',
            '/*/rename'    => 'rename',
            '/*/duplicate' => 'duplicate',
            '/*/delete'    => 'delete',
            '/*/**'        => 'forward',
        ],
        'PUT'    => [
            '/'   => 'undefined',
            '/**' => 'forward'
        ],
        'PATCH'  => [
            '/'   => 'undefined',
            '/**' => 'forward'
        ]
    ];

    public function index()
    {
        $this->params['positions'] = $this->container['positions'];

        return $this->container['admin.theme']->render('@gantry-admin/pages/positions/positions.html.twig', $this->params);
    }

    public function create()
    {
        /** @var PositionsObject $position */
        $position = $this->container['positions'];

        $title = $this->request->post->get('title', 'Untitled');

        $id = $position->create($title);

        $html = $this->container['admin.theme']->render(
            '@gantry-admin/layouts/position.html.twig',
            ['name' => $id, 'title' => $title]
        );

        return new JsonResponse(['html' => sprintf("Position '%s' created.", $id), 'id' => "position-{$id}", 'position' => $html]);
    }

    public function rename($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $title = $this->request->post['title'];
        $id = $positions->rename($position, $title);

        $html = $this->container['admin.theme']->render(
            '@gantry-admin/layouts/position.html.twig',
            ['name' => $id, 'title' => $title]
        );

        return new JsonResponse(['html' => sprintf("Position renamed to '%s'.", $id), 'id' => "position-{$position}", 'position' => $html]);
    }

    public function duplicate($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $id = $positions->duplicate($position);

        return new JsonResponse(['html' => sprintf("Position duplicated as '%s'.", $id)]);
    }

    public function delete($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $positions->delete($position);

        return new JsonResponse(['html' => sprintf("Position '%s' deleted.", $position), 'position' => $position]);
    }
}
