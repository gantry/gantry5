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

class main_info
{
function module()
	{
		global $phpbb_root_path;
		
		$output = array(
			'filename'	=> '\rockettheme\gantry\acp\main_module',
			'title'		=> 'Gantry',
			'version'      => '1.0.0',
			'modes'        => array(
				'global_conf'        => array(
					'title' => 'Global configuration',
					'auth'  => 'acl_a_group',
					'cat'   => array('ACP_CAT_RTSTYLES')
				)
			),
		);

		$info_files  = array();
		$styles_path = $phpbb_root_path . DIRECTORY_SEPARATOR . 'styles';
		$dh          = opendir($styles_path);
		while (false !== ($filename = readdir($dh))) {
			$style_path = $styles_path . DIRECTORY_SEPARATOR . $filename;
			$info_path  = $style_path . DIRECTORY_SEPARATOR . 'admin_info.json';
			if ($filename !== '.' && $filename !== '..' && is_dir($style_path) && is_file($info_path)) {
				$info_files[] = $info_path;
			}
		}
		foreach ($info_files as $info_file) {
			$template_infos     = json_decode(file_get_contents($info_file), true);
			foreach($template_infos as $template_key => $template_info){
				$output['modes'][$template_key] = $template_info;
			}
		}
		return $output;
	}
}
