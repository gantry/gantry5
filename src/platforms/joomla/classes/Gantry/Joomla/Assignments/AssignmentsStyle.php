<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class AssignmentsStyle implements AssignmentsInterface
{
    public $type = 'style';
    public $priority = 2;

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        static $rules;

        if (!isset($rules)) {
            $rules = [];

            $template = \JFactory::getApplication()->getTemplate(true);

            $theme = $template->template;
            $outline = $template->params->get('configuration', !empty($template->id) ? $template->id : $template->params->get('preset', null));

            if (JDEBUG) {
                GANTRY_DEBUGGER && \Gantry\Debugger::addMessage('Template Style:', 'debug') && \Gantry\Debugger::addMessage($template, 'debug');

                if (!$outline) {
                    \JFactory::getApplication()->enqueueMessage('JApplicationSite::getTemplate() was overridden with no specified Gantry 5 outline.', 'debug');
                }
            }

            /** @var UniformResourceLocator $locator */
            $locator = Gantry::instance()['locator'];

            if ($outline && is_dir($locator("gantry-themes://{$theme}/custom/config/{$outline}"))) {
                $rules = ['id' => [$outline => $this->priority]];
            }
        }

        return $rules;
    }

    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function listRules($configuration)
    {
        return [];
    }
}
