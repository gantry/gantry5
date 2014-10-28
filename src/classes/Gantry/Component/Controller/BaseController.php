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

    public function index()
    {
        throw new RuntimeException('Not Found', 404);
    }

    public function create()
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function store()
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function display($id)
    {
        throw new RuntimeException('Not Found', 404);
    }

    public function edit($id)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function update($id)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }

    public function destroy($id)
    {
        throw new RuntimeException('Method Not Allowed', 405);
    }
}
