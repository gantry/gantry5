<?php
/**
*
* @package migration
* @copyright (c) 2012 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2
*
*/

namespace rockettheme\gantry\migrations\v10x;

class release_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['gantry_mod_version']) && version_compare($this->config['gantry_mod_version'], '1.0.0', '>=');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}


	public function update_data()
	{
		global $phpbb_root_path;

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

		return array(
			array('if', array(
				array('module.exists', array('acp', 'ACP_CAT_CUSTOMISE', 'ACP_CAT_RTSTYLES')),
				array('module.remove', array('acp', 'ACP_CAT_CUSTOMISE', 'ACP_CAT_RTSTYLES')),
				)),

			array('module.add', array(
				'acp',
				'ACP_CAT_CUSTOMISE',
				'ACP_CAT_RTSTYLES'
				)),
			
			array('module.add', array(
				'acp',
				'ACP_CAT_RTSTYLES',
				array(
					'module_basename'	=> '\rockettheme\gantry\acp\main_module',
					'modes'				=> array_keys($output['modes']),
					),
				),
			),

			array('config.add', array('gantry_mod_version', '1.0.0')),
			array('config.add', array('gantry_is_installed', '1')),


			);

	}
}
