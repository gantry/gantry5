<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Component\System;

/**
 * Class Messages
 * @package Gantry\Component\System
 */
class Messages
{
    protected $messages = [];

    /**
     * @param string $message
     * @param string $type
     * @return $this
     */
    public function add($message, $type = 'warning')
    {
        $this->messages[] = ['type' => $type, 'message' => $message];

        return $this;
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->messages;
    }

    /**
     * @return $this
     */
    public function clean()
    {
        $this->messages = [];

        return $this;
    }
}
