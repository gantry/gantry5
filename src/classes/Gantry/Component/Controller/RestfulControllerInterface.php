<?php
namespace Gantry\Component\Controller;

interface RestfulControllerInterface
{
    /**
     * @example GET /resources
     *
     * @return mixed
     */
    public function index();

    /**
     * @example GET /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function display($id);

    /**
     * Special sub-resource to create a new resource (returns a form).
     *
     * @example GET /resources/create
     *
     * @return mixed
     */
    public function create();

    /**
     * Special sub-resource to edit existing resource (returns a form).
     *
     * @example GET /resources/:id/edit
     *
     * @param string $id
     * @return mixed
     */
    public function edit($id);

    /**
     * @example POST /resources
     *
     * @return mixed
     */
    public function store();

    /**
     * @example PUT /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function replace($id);

    /**
     * @example PATCH /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function update($id);

    /**
     * @example DELETE /resources/:id
     *
     * @param string $id
     * @return mixed
     */
    public function destroy($id);
}
