<?php
namespace Gantry\Component\Controller;

interface RestfulControllerInterface
{
    /**
     * @example GET /resources
     *
     * @param array $params
     * @return mixed
     */
    public function index(array $params);

    /**
     * @example GET /resources/:id
     *
     * @param array $params
     * @return mixed
     */
    public function display(array $params);

    /**
     * Special sub-resource to create a new resource (returns a form).
     *
     * @example GET /resources/create
     *
     * @param array $params
     * @return mixed
     */
    public function create(array $params);

    /**
     * Special sub-resource to edit existing resource (returns a form).
     *
     * @example GET /resources/:id/edit
     *
     * @param array $params
     * @return mixed
     */
    public function edit(array $params);

    /**
     * @example POST /resources
     *
     * @param array $params
     * @return mixed
     */
    public function store(array $params);

    /**
     * @example PUT /resources/:id
     *
     * @param array $params
     * @return mixed
     */
    public function replace(array $params);

    /**
     * @example PATCH /resources/:id
     *
     * @param array $params
     * @return mixed
     */
    public function update(array $params);

    /**
     * @example DELETE /resources/:id
     *
     * @param array $params
     * @return mixed
     */
    public function destroy(array $params);
}
