<?php
function avatar_position($value, $key = '', $avatar_position_options='')
{
	$avatar_position_options .= '<option value="right"' . (($value == "right") ? ' selected="selected"' : '') . '>Right</option>
	<option value="left"' . (($value == "left") ? ' selected="selected"' : '') . '>Left</option>
	';
	return $avatar_position_options;
}
$display_vars = array(
	'vars'	=> array(
		'legend1'				=> 'OTHER_CONFIGURATION',
		'avatar_position'			=> array('lang' => 'AVATAR_POSITION',		'validate' => 'string',	'type' => 'select', 'function' => 'avatar_position', 'params' => array('{CONFIG_VALUE}'), 'explain' => true),
		)
	);			
	?>
