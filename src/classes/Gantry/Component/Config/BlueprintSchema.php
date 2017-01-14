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

namespace Gantry\Component\Config;

use RocketTheme\Toolbox\Blueprints\BlueprintSchema as BaseBlueprintSchema;


/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class BlueprintSchema extends BaseBlueprintSchema
{
    protected $configuration;

    /**
     * Constructor.
     *
     * @param array $serialized  Serialized content if available.
     */
    public function __construct($serialized = null)
    {
        parent::__construct($serialized);

        $this->configuration = new Config(isset($serialized['configuration']) ? $serialized['configuration'] : []);
    }

    /**
     * Convert object into an array.
     *
     * @return array
     */
    public function getState()
    {
        return parent::getState() + ['configuration' => $this->configuration->toArray()];
    }

    /**
     * Get nested structure containing default values defined in the blueprints.
     *
     * Fields without default value are ignored in the list.
     *
     * @return array
     */
    public function getDefaults()
    {
        return array_merge_recursive($this->configuration->toArray(), $this->buildDefaults($this->nested));
    }

    /**
     * Embed an array to the blueprint.
     *
     * @param $name
     * @param array $value
     * @param string $separator
     * @param bool $merge   Merge fields instead replacing them.
     * @return $this
     */
    public function embed($name, array $value, $separator = '.', $merge = false)
    {
        parent::embed($name, $value, $separator, $merge);

        if (isset($value['configuration'])) {
            $this->configuration->set($name, $value['configuration'], $separator);
        }

        return $this;
    }
}
