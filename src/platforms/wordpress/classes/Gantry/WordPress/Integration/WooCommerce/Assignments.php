<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 * Contains WordPress core code.
 */

namespace Gantry\WordPress\Assignments;

use Gantry\Component\Assignments\AssignmentsInterface;

/**
 * Class Assignments
 * @package Gantry\WordPress\Integration\WooCommerce
 */
class AssignmentsWoocommerce implements AssignmentsInterface
{
    /** @var string */
    public $type = 'woocommerce';
    /** @var int */
    public $priority = 4;

    /** @var array */
    protected $context = [
        'is_shop'             => 'Shop Page',
        'is_product'          => 'Product Page',
        'is_product_category' => 'Product Category',
        'is_product_tag'      => 'Product Tag',
        'is_cart'             => 'Cart Page',
        'is_checkout'         => 'Checkout Page',
        'is_account_page'     => 'Customer Account Page'
    ];

    /**
     * Returns list of rules which apply to the current page.
     *
     * @return array
     */
    public function getRules()
    {
        $rules = [];

        foreach ($this->context as $var => $label) {
            if ($var() === true) {
                $rules[$var] = $this->priority;
            }
        }

        return [$rules];
    }

    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function listRules($configuration)
    {
        // Get label and items for the context.
        $list = [
            'label' => 'WooCommerce',
            'items' => $this->getItems()
        ];

        return [$list];
    }

    /**
     * @return array
     */
    protected function getItems()
    {
        $items = [];
        $context = $this->context;

        foreach ($context as $conditional => $label) {
            $items[] = [
                'name'  => $conditional,
                'label' => $label
            ];
        }

        return $items;
    }

    /**
     * Add WooCommerce to the Page Context list
     *
     * @param $context
     * @return array
     */
    public static function addPageContextItem($context)
    {
        if (is_array($context)) {
            $context['is_woocommerce'] = 'WooCommerce Page';
        }

        return $context;
    }

    /**
     * Add WooCommerce conditional tag check to the rules
     *
     * @param $rules
     * @param $priority
     * @return array
     */
    public static function addPageContextConditionals($rules, $priority = 1)
    {
        if (!isset($rules)) {
            $rules = [];
        }

        if (\is_woocommerce() === true) {
            $rules['is_woocommerce'] = $priority;
        }

        return $rules;
    }
}
