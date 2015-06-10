<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\WordPress\Assignments\AssignmentsWalker;
use Gantry\WordPress\Assignments\AssignmentsContext;
use Gantry\WordPress\Assignments\AssignmentsMenu;
use Gantry\WordPress\Assignments\AssignmentsPost;
use Gantry\WordPress\Assignments\AssignmentsArchive;

class Assignments
{
    use GantryTrait;

    protected $style_id;

    public function __construct($style_id)
    {
        $this->style_id = $style_id;
    }

    public function get()
    {
        return $this->getTypes();
    }

    public function set($data)
    {
//        if (isset($data['menu'])) {
//            $this->setMenu($data['menu']);
//        }
    }

    // FIXME Is this necessary ?
    public function types()
    {
        $types = array(
            'context',
            'menu',
            'post',
//            'taxonomy',
            'archive'
        );

        return apply_filters('g5_assignments_types', $types);
    }

    public function getTypes()
    {
        $list = [];

        foreach($this->types() as $type) {
            switch( $type ) {
                // Context
                case 'context' :

                    $instance = new AssignmentsContext;

                    // Get label and items for each menu
                    $list[$type]['label'] = 'Page Context';
                    $list[$type]['items'] = $instance->getItems();

                    unset($instance);

                    break;

                // Menu
                case 'menu' :

                    $instance = new AssignmentsMenu;

                    // Get all defined menus
                    $menus = $instance->getMenus();

                    // Break if there are no menus
                    if(!$menus) break;

                    // Get label and items for each menu
                    foreach ($menus as $menu) {
                        $list[$type . '-' . $menu->slug]['label'] = 'Menu - ' . $menu->name;
                        $list[$type . '-' . $menu->slug]['items'] = $instance->getItems($menu);
                    }

                    unset($instance);

                    break;

                // Posts & Pages (including their types and taxonomies)
                case 'post' :

                    $instance = new AssignmentsPost;

                    // Get all defined post types
                    $post_types = $instance->getPostTypes();

                    // Break if there are no post types defined
                    if (!$post_types) break;

                    // Get label and items for each post types
                    foreach($post_types as $post_type) {
                        $post_type = apply_filters('g5_assignments_' . $post_type->name . '_object', $post_type);

                        if($post_type) {
                            $list[$post_type->name]['label'] = $post_type->labels->name;
                            $list[$post_type->name]['items'] = $instance->getItems($post_type);

                            // Get current post type taxonomies and its terms
                            $taxonomies = get_object_taxonomies($post_type->name);
                            if(!empty($taxonomies)) {
                                $list[$post_type->name . '-terms']['label'] = $post_type->labels->name . ': Terms';
                                $list[$post_type->name . '-terms']['items'] = $instance->getTerms($taxonomies, $post_type);
                            }
                        }
                    }

                    unset($instance);

                    break;

                // Taxonomy and Taxonomy Archives
                case 'taxonomy' :
                case 'archive' :

                    $instance = new AssignmentsArchive;

                    $taxonomies = $instance->getTaxonomies();
                    if(empty($taxonomies)) break;

                    foreach($taxonomies as $tax) {
                        $tax = apply_filters('g5_assignments_' . $type . '_' . $tax->name . '_taxonomy_object', $tax);

                        $type == 'taxonomy' ? $type_label = 'Taxonomies: ' : $type_label = 'Archives: ';

                        $list[$type . '-' . $tax->name]['label'] = $type_label . $tax->labels->name;
                        $list[$type . '-' . $tax->name]['items'] = $instance->getItems($tax);
                    }

                    unset($instance);

                    break;
            }
        }

        do_action('g5_assignments_list', $list);

        return $list;
    }

    public function setMenu($data)
    {
        return true;
    }
}
