<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Response\Json;

class Layouts extends JsonController
{
    public function index()
    {
        echo new Json(['foo' => 1]);
    }

    public function display($id)
    {
    }
}
