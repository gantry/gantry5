<?php
/**
 * @package     Joomla.Site
 * @subpackage  Template.system
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Gantry Specific Module Chrome
function modChrome_gantry($module, &$params, &$attribs)
{
	$moduleTag      = $params->get('module_tag', 'div');
	$headerTag      = htmlspecialchars($params->get('header_tag', 'h3'));
	$bootstrapSize  = (int) $params->get('bootstrap_size', 0);
	$moduleClass    = $bootstrapSize != 0 ? ' span' . $bootstrapSize : '';

	// Temporarily store header class in variable
	$headerClass    = $params->get('header_class');
	$headerClass    = ($headerClass) ? ' class="' . htmlspecialchars($headerClass) . '"' : '';

	if (!empty ($module->content)) : ?>
		<<?php echo $moduleTag; ?> class="moduletable<?php echo htmlspecialchars($params->get('moduleclass_sfx')) . $moduleClass; ?>">
			<?php if ((bool) $module->showtitle) : ?>
				<<?php echo $headerTag . $headerClass . '>' . $module->title; ?></<?php echo $headerTag; ?>>
			<?php endif; ?>
			<?php echo $module->content; ?>
		</<?php echo $moduleTag; ?>>
	<?php endif;
}

?>
