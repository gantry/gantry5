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
		'TITLE_EXPLAIN'	=> 'Here you can set various phpBB specific options for your RocketTheme template.',
		'AVATAR_POSITION'	=> 'Avatar position',
		'AVATAR_POSITION_EXPLAIN' => 'Set avatar and user profile position.',
		'STYLE_CONFIGURATION' => 'Style Configuration',
		'ACP_CAT_RTSTYLES' => 'RocketTheme Styles',
));