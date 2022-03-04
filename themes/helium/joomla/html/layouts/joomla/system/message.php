<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @copyright Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

if (version_compare(JVERSION, 4.0, '>')) {
    include JPATH_ROOT . '/layouts/joomla/system/message.php';
    return;
}

/**
 * Joomla 3 version of the system messages.
 */

$msgList = $displayData['msgList'];

?>
<div id="system-message-container">
    <?php if (is_array($msgList) && !empty($msgList)) : ?>
    <div id="system-message">
        <?php foreach ($msgList as $type => $msgs) : ?>
            <div class="alert alert-<?php echo $type; ?>">
                <?php // This requires JS so we should add it trough JS. Progressive enhancement and stuff. ?>
                <a class="close" data-dismiss="alert">×</a>

                <?php if (!empty($msgs)) : ?>
                    <h4 class="alert-heading"><?php echo Text::_($type); ?></h4>
                    <div>
                        <?php foreach ($msgs as $msg) : ?>
                            <p><?php echo $msg; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
