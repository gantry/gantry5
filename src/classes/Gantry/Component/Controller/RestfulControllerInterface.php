<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Controller;

/**
 * Interface RestfulControllerInterface
 * @package Gantry\Component\Controller
 */
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
