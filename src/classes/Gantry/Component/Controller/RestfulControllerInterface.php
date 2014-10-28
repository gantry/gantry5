<?php
namespace Gantry\Component\Controller;

interface RestfulControllerInterface
{
    public function index();
    public function create();
    public function store();
    public function display($id);
    public function edit($id);
    public function update($id);
    public function destroy($id);
}
