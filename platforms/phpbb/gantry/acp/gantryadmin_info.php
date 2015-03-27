<?php

/**
*
* @package RT Gantry Extension
* @copyright (c) 2013 rockettheme
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace rockettheme\gantry\acp;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class gantryadmin_info
{
function module()
	{
		global $phpbb_root_path;
		
		$output = array(
			'filename'	=> '\rockettheme\gantry\acp\gantryadmin_module',
			'modes'        => array(
				'gantryadmin'        => array(
					'title' => 'Gantry Templates',
					'auth'  => 'acl_a_group',
					'cat'   => array('ACP_STYLE_MANAGEMENT')
					)
				),
			);
		return $output;
	}
}
