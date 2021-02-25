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

namespace Gantry\Component\Content\Block;

/**
 * @since 5.4.3
 */
interface ContentBlockInterface extends \Serializable
{
    /**
     * @param string $id
     * @return static
     * @since 5.4.3
     */
    public static function create($id = null);

    /**
     * @param array $serialized
     * @return ContentBlockInterface
     * @since 5.4.3
     */
    public static function fromArray(array $serialized);

    /**
     * Block constructor.
     *
     * @param string $id
     * @since 5.4.3
     */
    public function __construct($id = null);

    /**
     * @return string
     * @since 5.4.3
     */
    public function getId();

    /**
     * @return string
     * @since 5.4.3
     */
    public function getToken();

    /**
     * @return array
     * @since 5.4.3
     */
    public function toArray();

    /**
     * @param array $serialized
     * @since 5.4.3
     */
    public function build(array $serialized);

    /**
     * @param string $content
     * @return $this
     * @since 5.4.3
     */
    public function setContent($content);

    /**
     * @param ContentBlockInterface $block
     * @return $this
     * @since 5.4.3
     */
    public function addBlock(ContentBlockInterface $block);

    /**
     * @return string
     */
    public function toString();
}
