<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress;

use Timber\Pagination;

/**
 * Class PostQuery
 * @package Gantry\WordPress
 */
class PostQuery extends \Timber\PostQuery
{
    /**
     * For backwards compatibility.
     *
     * @return mixed
     */
    public function post_count()
    {
        return $this->count();
    }

    /**
     * For backwards compatibility.
     *
     * @param array $prefs
     * @return Pagination
     */
    public function get_pagination($prefs)
    {
        return $this->pagination((array)$prefs);
    }
}
