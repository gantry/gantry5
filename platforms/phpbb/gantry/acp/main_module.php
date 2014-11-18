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

class main_module
{
	var $u_action;
	
	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		$template->assign_vars(array(
			'MODE'	=>  $mode,
			));	
		$submit	= request_var('submit', '');
		$form_key = 'gantry';
		add_form_key($form_key);

		$this->new_config = $config;

		$this->request = $request;
		$cfg_array = (isset($_REQUEST['config'])) ? request_var('config', array('' => '')) : $this->new_config;
		$error = array();
		$this->tpl_name = 'acp_gantry';
		
		$xml = \rockettheme\gantry\event\load_xml_file($user->style['style_path']);
		$style_prefix = $user->style['style_path'].'_';

		// Assign template vars
		foreach ($xml->xpath('//form/fieldset') as $group) {
			$template->assign_block_vars('gantry', array(
				'GROUP_NAME' => $group['label'],
				));
			foreach ($group->xpath('field') as $item) {
				$item_list[] = $item['name'];
				$template->assign_block_vars('gantry.fields', array(
					'NAME' => $item['name'],
					'LABEL' => $item['label'],
					'TYPE' => $item['type'],
					'DESCRIPTION' => $item['description'],
					));
				foreach ($item->xpath('option') as $option) {
					$template->assign_block_vars('gantry.fields.options', array(
						'VALUE' => $option['value'],
						'LABEL' => $option,
						));
				}
			}
		}

		// Save values
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			foreach($item_list as $item) {
				$config->set((string)$style_prefix.$item, $request->variable((string)$item, ''));
			}

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		// Assign saved values into template
		$template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			));

		$items = array();
		foreach($item_list as $item) {
			$items[] = array(
				strtoupper((string)$item) => $config[(string)$style_prefix.$item],
				);
		}

		$template_vars = call_user_func_array('array_merge', $items);
		$template->assign_block_vars('gantry_template', $template_vars);

	}
}