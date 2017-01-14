<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
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
    public static function create($id = null);
    public static function fromArray(array $serialized);

    public function __construct($id = null);

    public function getId();
    public function getToken();

    public function toArray();
    public function build(array $serialized);

    public function setContent($content);
    public function addBlock(ContentBlockInterface $block);
}