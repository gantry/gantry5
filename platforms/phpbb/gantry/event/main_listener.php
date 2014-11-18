<?php

/**
*
* @package RT Gantry Extension
* @copyright (c) 2013 rockettheme
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace rockettheme\gantry\event;

/**
* @ignore
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Event listener
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'		=> 'load_language_on_setup',
			'core.adm_page_header' => 'add_admin_header_vars',
			'core.page_header' => 'add_header_vars',
			);
	}

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var string phpEx */
	protected $php_ext;

	/**
	* Constructor
	*
	* @param \phpbb\controller\helper	$helper		Controller helper object
	* @param \phpbb\template			$template	Template object
	* @param \phpbb\user				$user		User object
	* @param string						$php_ext	phpEx
	*/
	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, $php_ext)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->php_ext = $php_ext;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'rockettheme/gantry',
			'lang_set' => 'gantry',
			);
		$event['lang_set_ext'] = $lang_set_ext;

		/* Set up xml options file */
		function load_xml_file($style_name) {
			global $phpbb_root_path;
			if (file_exists($phpbb_root_path. 'styles/'.$style_name.'/phpbb-options.xml')) {
				global $xml;
				$xml = simplexml_load_file($phpbb_root_path. 'styles/'.$style_name.'/phpbb-options.xml');
				return $xml;
			} else {
				exit('Failed to open phpbb-options.xml. Please make sure that you installed style correctly.');
			}
		}
	}
	
	public function add_admin_header_vars($event)
	{
		global $phpbb_root_path, $mode;
		$this->template->assign_vars(array(
			'MODE'		=> $mode,
			)
		);
	}
	public function add_header_vars($event)
	{
		global $phpbb_root_path, $user, $template, $config;

		$xml = load_xml_file($user->style['style_path']);
		$style_prefix = $user->style['style_path'].'_';

		// Assign template vars
		foreach ($xml->xpath('//form/fieldset') as $group) {
			foreach ($group->xpath('field') as $item) {
				$item_list[] = $item['name'];
			}
		}
		$items = array();
		foreach($item_list as $item) {
			$items[] = array(
				strtoupper((string)$item) => $config[(string)$style_prefix.$item],
				);
		}

		$template_vars = call_user_func_array('array_merge', $items);
		$template->assign_vars($template_vars);	
	}
}
