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
use Gantry\WordPress\AssignmentsWalker;

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
            'page-context',
            'menu',
            'post',
//            'taxonomy',
            'archive'
        );

        return apply_filters('g5_assignments_defined_types', $types);
    }

    public function getTypes()
    {
        $list = [];

        foreach($this->types() as $type) {
            switch( $type ) {
                // Page Context
                case 'page-context' :
                    $list[$type]['label'] = 'Page Context';

                    $page_context = array(
                        'is_404'            => '404 Not Found Page',
                        'is_search'         => 'Search Page',
                        'is_tax'            => 'Taxonomy Archive',
                        'is_front_page'     => 'Front Page',
                        'is_home'           => 'Home Page',
                        'is_attachment'     => 'Attachment Page',
                        'is_single'         => 'Single Post',
                        'is_page'           => 'Single Page',
                        'is_category'       => 'Category Archive Page',
                        'is_tag'            => 'Tag Archive Page',
                        'is_author'         => 'Author Page',
                        'is_date'           => 'Date Archive Page',
                        'is_preview'        => 'Preview Page',
                        'is_comments_popup' => 'Comments Popup Page'
                    );

                    $page_context = apply_filters('g5_assignments_page_context_items', $page_context, $type);
                    ksort($page_context);

                    $items = [];

                    foreach($page_context as $conditional => $label) {
                        $items[] = [
                            'name'  => $conditional,
                            'label' => $label
                        ];
                    }

                    $list[$type]['items'] = $items;
                    unset($items);

                    break;

                // Menus
                case 'menu' :
                    // Get all registered Menus and order them by name
                    $menus = wp_get_nav_menus(['orderby' => 'name']);
                    if (!$menus) break;

                    foreach ($menus as $menu) {
                        $menu_key = $type . '-' . $menu->slug;
                        $list[$menu_key]['label'] = 'Menu - ' . $menu->name;

                        // Get all items for the current menu
                        if ($menu && !is_wp_error($menu)) {
                            $menu_items = wp_get_nav_menu_items($menu->term_id);
                        }
                        $items = [];

                        // Check if the menu is not empty
                        if(!$menu_items) {
                            $items[] = [
                                'name'     => '',
                                'label'    => 'No items',
                                'disabled' => true
                            ];
                        } else {
                            $walker = new AssignmentsWalker;

                            $new_menu_items = [];
                            foreach($menu_items as $new_menu_item) {
                                $new_menu_item->id           = $new_menu_item->ID;
                                $new_menu_item->parent_id    = empty($new_menu_item->menu_item_parent) ? get_post_meta($new_menu_item->ID, '_menu_item_menu_item_parent', true) : $new_menu_item->menu_item_parent;
                                $new_menu_items[] = $new_menu_item;
                            }

                            $menu_items = $walker->walk($new_menu_items, 0);

                            foreach($menu_items as $menu_item) {
                                $items[] = [
                                    'name'     => '',
                                    'id'       => $menu_item->ID,
                                    'label'    => $menu_item->level > 0 ? str_repeat('—', $menu_item->level) . ' ' . $menu_item->title : $menu_item->title,
                                    'disabled' => false
                                ];
                            }
                        }

                        $list[$menu_key]['items'] = apply_filters('g5_assignments_' . $menu_key . '_menu_items', $items, $menu_key);
                        unset($items);
                    }

                    break;

                    // Posts & Pages (including their types and taxonomies)
                    case 'post' :
                        // Get all post types
                        $post_types = get_post_types(['show_ui' => true], 'object');
                        if (!$post_types) break;

                        foreach($post_types as $post_type) {
                            $post_type = apply_filters('g5_assignments_post_object', $post_type);

                            if($post_type) {
                                $list[$post_type->name]['label'] = $post_type->labels->name;

                                $query_args = [
                                    'order'                  => 'ASC',
                                    'orderby'                => 'title',
                                    'post_type'              => $post_type->name,
                                    'suppress_filters'       => true,
                                    'update_post_term_cache' => false,
                                    'update_post_meta_cache' => false,
                                    'posts_per_page'         => -1
                                ];

                                $get_posts = new \WP_Query;
                                $posts = $get_posts->query($query_args);

                                $items = [];

                                // Check if there are any posts
                                if(!$get_posts->post_count) {
                                    $items[] = [
                                        'name'     => '',
                                        'label'    => 'No items',
                                        'disabled' => true
                                    ];
                                } else {
                                    $walker = new AssignmentsWalker;

                                    $new_posts = [];
                                    foreach($posts as $new_post) {
                                        $new_post->id           = $new_post->ID;
                                        $new_post->parent_id    = $new_post->post_parent;
                                        $new_posts[] = $new_post;
                                    }

                                    $posts = $walker->walk($new_posts, 0);

                                    foreach($posts as $post) {
                                        $post->post_title != '' ? $post_title = $post->post_title : $post_title = $post_type->labels->singular_name . ' #' . $post->ID;

                                        $items[] = [
                                            'name'     => $post->post_name,
                                            'id'       => $post->ID,
                                            'label'    => $post->level > 0 ? str_repeat('—', $post->level) . ' ' . $post_title : $post_title,
                                            'disabled' => false
                                        ];
                                    }
                                }

                                $list[$post_type->name]['items'] = apply_filters('g5_assignments_' . $post_type->name . '_items', $items, $type, $post_type);
                                unset($items);

                                /*
                                 * Current post type taxonomies
                                 */

                                // Get current post type taxonomies
                                $taxonomies = get_object_taxonomies($post_type->name);
                                if(empty($taxonomies)) continue;

                                $list[$post_type->name . '-terms']['label'] = $post_type->labels->name . ': Terms';

                                $terms_args = [
                                    'child_of'                 => 0,
                                    'exclude'                  => '',
                                    'hide_empty'               => false,
                                    'hierarchical'             => 1,
                                    'include'                  => '',
                                    'include_last_update_time' => false,
                                    'order'                    => 'ASC',
                                    'orderby'                  => 'name',
                                    'pad_counts'               => false,
                                ];

                                $items = [];

                                foreach($taxonomies as $tax) {
                                    $tax = apply_filters('g5_assignments_' . $post_type->name . '_taxonomy_object', $tax);

                                    $taxonomy = get_taxonomy($tax);
                                    $terms = get_terms($tax, $terms_args);

                                    $items[] = [
                                        'name'     => $taxonomy->name,
                                        'label'    => $taxonomy->label,
                                        'parent'   => true,
                                        'disabled' => true
                                    ];

                                    if(empty($terms)) {
                                        $items[] = [
                                            'name'     => '',
                                            'label'    => 'No items',
                                            'disabled' => true
                                        ];
                                    } else {
                                        $walker = new AssignmentsWalker;

                                        $new_terms = [];
                                        foreach($terms as $new_term) {
                                            $new_term->id           = $new_term->term_id;
                                            $new_term->parent_id    = $new_term->parent;
                                            $new_terms[] = $new_term;
                                        }

                                        $terms = $walker->walk($new_terms, 0);

                                        foreach($terms as $term) {
                                            $items[] = [
                                                'name'     => $term->slug,
                                                'id'       => $term->term_id,
                                                'label'    => $term->level > 0 ? str_repeat('—', $term->level+1) . ' ' . $term->name : '— ' . $term->name,
                                                'disabled' => false
                                            ];
                                        }
                                    }
                                }

                                $list[$post_type->name . '-terms']['items'] = apply_filters('g5_assignments_' . $post_type->name . '_taxonomy_items', $items, $type, $post_type);
                                unset($items, $taxonomies, $tax, $taxonomy, $terms, $new_terms);
                            }
                        }

                    break;

                    // Taxonomy and Taxonomy Archives
                    case 'taxonomy' :
                    case 'archive' :
                        $taxonomies = get_taxonomies(array('show_ui' => true), 'object');
                        if(empty($taxonomies)) break;

                        foreach($taxonomies as $tax) {
                            $tax = apply_filters('g5_assignments_' . $type . '_' . $tax->name . '_taxonomy_object', $tax);

                            $type == 'taxonomy' ? $type_label = 'Taxonomies: ' : $type_label = 'Archives: ';

                            $list[$type . '-' . $tax->name]['label'] = $type_label . $tax->labels->name;

                            $terms_args = [
                                'child_of'                 => 0,
                                'exclude'                  => '',
                                'hide_empty'               => false,
                                'hierarchical'             => 1,
                                'include'                  => '',
                                'include_last_update_time' => false,
                                'order'                    => 'ASC',
                                'orderby'                  => 'name',
                                'pad_counts'               => false,
                            ];

                            $terms = get_terms($tax->name, $terms_args);

                            $items = [];

                            if(empty($terms) || is_wp_error($terms)) {
                                $items[] = [
                                    'name'     => '',
                                    'label'    => 'No items',
                                    'disabled' => true
                                ];
                            } else {
                                $walker = new AssignmentsWalker;

                                $new_terms = [];
                                foreach($terms as $new_term) {
                                    $new_term->id           = $new_term->term_id;
                                    $new_term->parent_id    = $new_term->parent;
                                    $new_terms[] = $new_term;
                                }

                                $terms = $walker->walk($new_terms, 0);

                                foreach($terms as $term) {
                                    $items[] = [
                                        'name'     => $term->slug,
                                        'id'       => $term->term_id,
                                        'label'    => $term->level > 0 ? str_repeat('—', $term->level) . ' ' . $term->name : $term->name,
                                        'disabled' => false
                                    ];
                                }
                            }

                            $list[$type . '-' . $tax->name]['items'] = apply_filters('g5_assignments_' . $type . '_' . $tax->name . '_taxonomy_items', $items, $type, $tax);
                            unset($items, $tax, $terms, $new_terms);
                        }

                    break;

                do_action('g5_assignments_types', $list, $types);
            }
        }

        return $list;
    }

    public function setMenu($data)
    {
        return true;
    }
}
