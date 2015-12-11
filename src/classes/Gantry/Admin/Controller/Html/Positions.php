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
            '/*/**'        => 'forward',
        ],
        'PUT'    => [
            '/'   => 'undefined',
            '/**' => 'forward'
        ],
        'PATCH'  => [
            '/'   => 'undefined',
            '/**' => 'forward'
        ],
        'DELETE' => [
            '/'     => 'undefined',
            '/*'    => 'delete',
            '/*/**' => 'forward'
        ]
    ];

    public function index()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $finder = new \Gantry\Component\Config\ConfigFileFinder();
        $files = $finder->getFiles($locator->findResources('gantry-config://positions'));

        $positions = array_keys($files);
        sort($positions);


        $this->params['positions'] = $positions;

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

        return new JsonResponse(['html' => 'Position created.', 'id' => "position-{$id}", 'position' => $html]);
    }

    public function rename($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];
        $list = $positions->all();
        $position = $position . '.yaml';

        if (!isset($list[$position])) {
            $this->forbidden();
        }

        $title = $this->request->post['title'];
        $id = $positions->rename($position, $title);

        $html = $this->container['admin.theme']->render(
            '@gantry-admin/layouts/position.html.twig',
            ['name' => $id, 'title' => $title]
        );

        return new JsonResponse(['html' => 'Position renamed.', 'id' => "position-{$position}", 'position' => $html]);
    }

    public function duplicate($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];
        $list = $positions->all();;

        if (!isset($list[$position . '.yaml'])) {
            $this->forbidden();
        }

        $positions->duplicate($position);

        return new JsonResponse(['html' => 'Position duplicated.']);
    }

    public function delete($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];
        $list = $positions->all();
        $position = $position . '.yaml';

        if (!isset($list[$position])) {
            $this->forbidden();
        }

        $positions->delete($position);

        return new JsonResponse(['html' => 'Position deleted.', 'position' => $position]);
    }

    public function forward()
    {
        $path = func_get_args();

        $positions = $this->container['positions']->toArray();

        $configuration = isset($positions[$path[0]]) ? array_shift($path) : 'default';

        $this->container['configuration'] = $configuration;

        $method = $this->params['method'];
        $page = (array_shift($path) ?: 'styles');
        $resource = $this->params['location'] . '/'. $page;

        $this->params['configuration'] = $configuration;
        $this->params['location'] = $resource;
        $this->params['configuration_page'] = $page;
        $this->params['navbar'] = !empty($this->request->get['navbar']);

        return $this->executeForward($resource, $method, $path, $this->params);
    }

    protected function executeForward($resource, $method = 'GET', $path, $params = [])
    {
        $class = '\\Gantry\\Admin\\Controller\\Html\\' . strtr(ucwords(strtr($resource, '/', ' ')), ' ', '\\');
        if (!class_exists($class)) {
            throw new \RuntimeException('Position not found', 404);
        }

        /** @var HtmlController $controller */
        $controller = new $class($this->container);

        // Execute action.
        $response = $controller->execute($method, $path, $params);

        if (!$response instanceof Response) {
            $response = new HtmlResponse($response);
        }

        return $response;
    }
}
