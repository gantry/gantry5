<?php

/**
*
* rokbb [British English]
*
* @package language
* @version $Id$
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
		'TITLE'	=> 'Global Configuration',
		'TITLE_EXPLAIN'	=> 'Here you can set various options for your RocketTheme Phpbb3 style.',
		'ALLOW_JMENU'	=> 'Show Joomla menu',
		'ALLOW_JMENU_EXPLAIN'	=> 'Show Joomla menu instead of phpbb3 menu at the top of the page.',
		'JOOMLA_MENU_PATH' => 'Joomla menu path',
		'JOOMLA_MENU_PATH_EXPLAIN' => 'Relative path to directory of your Joomla installation.Remember about ending slash.',
		'AVATAR_POSITION'	=> 'Avatar position',
		'AVATAR_POSITION_EXPLAIN' => 'Set avatar and user profile position.',
		'STYLE_CONFIGURATION' => 'Style Configuration',
		'ACP_CAT_RTSTYLES' => 'RocketTheme Styles',
));