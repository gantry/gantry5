<?php
namespace Gantry\Component\Controller;

use RocketTheme\Toolbox\DI\Container;
use RuntimeException;

abstract class BaseController implements RestfulControllerInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(array $params)
    {
        throw new RuntimeException('Not Found', 404);
    }

    public function create(array $params)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function store(array $params)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function display(array $params)
    {
        throw new RuntimeException('Not Found', 404);
    }

    public function edit(array $params)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function update(array $params)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function destroy(array $params)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }
}
