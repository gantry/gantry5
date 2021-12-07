<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Serializable;

/**
 * Serializable trait
 *
 * Adds backwards compatibility to PHP 5/7 Serializable interface.
 *
 * Note: Remember to add: `implements \Serializable` to the classes which use this trait.
 */
trait Serializable
{
    /**
     * @return string
     */
    #[\ReturnTypeWillChange]
    final public function serialize()
    {
        return serialize($this->__serialize());
    }

    /**
     * @param string $serialized
     * @return void
     */
    #[\ReturnTypeWillChange]
    final public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized, ['allowed_classes' => $this->getUnserializeAllowedClasses()]));
    }

    /**
     * @return array|bool
     */
    protected function getUnserializeAllowedClasses()
    {
        return false;
    }
}
