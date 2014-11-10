<?php
namespace Gantry\Component\Controller;

interface RestfulControllerInterface
{
    public function index(array $params);
    public function create(array $params);
    public function store(array $params);
    public function display(array $params);
    public function edit(array $params);
    public function update(array $params);
    public function destroy(array $params);
}
