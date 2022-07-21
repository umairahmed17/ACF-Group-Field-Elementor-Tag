<?php

namespace UMRCP\Tags;

use ElementorPro\Modules\DynamicTags\ACF\Module;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}
//Exit if accessed directly

class ACFGroupImageTag extends \Elementor\Core\DynamicTags\Data_Tag

{

    /**
     * Get Name
     *
     * Returns the Name of the tag
     *
     * @access public
     *
     *
     * @since 2.0.0
     *
     * @return string
     */
    public function get_name()
    {
        return 'acf_group_image_tag';
    }

    /**
     * Get Title
     *
     * Returns the title of the Tag
     *
     * @access public
     *
     *
     * @since 2.0.0
     *
     * @return string
     */
    public function get_title()
    {
        return __('ACF Group Image Fields');
    }

    /**
     * Get Group
     *
     * Returns the Group of the tag
     *
     * @access public
     *
     *
     * @since 2.0.0
     *
     * @return string
     */
    public function get_group()
    {
        return Module::ACF_GROUP;
    }

    /**
     * Get Categories
     *
     * Returns an array of tag categories
     *
     * @access public
     *
     *
     * @since 2.0.0
     *
     * @return array
     */
    public function get_categories()
    {
        return [
            Module::IMAGE_CATEGORY,
        ];
    }

    /**
     * Register Controls
     *
     * Registers the Dynamic tag controls
     *
     * @access protected
     *
     *
     * @since 2.0.0
     *
     * @return void
     */
    protected function _register_controls()
    {

        $this->add_control(
            'key',
            [
                'label' => __('Key'),
                'type' => Controls_Manager::SELECT,
                'groups' => self::modified_get_control_options($this->get_supported_fields()),
            ]
        );
    }

    /**
     * @param array $types
     *
     * @return array
     */
    public static function modified_get_control_options($types)
    {
        // ACF >= 5.0.0
        if (function_exists('acf_get_field_groups')) {
            $acf_groups = \acf_get_field_groups();
        } else {
            $acf_groups = \apply_filters('acf/get_field_groups', []);
        }

        $groups = [];

        $options_page_groups_ids = [];

        if (function_exists('acf_options_page')) {
            $pages = \acf_options_page()->get_pages();
            foreach ($pages as $slug => $page) {
                $options_page_groups = acf_get_field_groups([
                    'options_page' => $slug,
                ]);

                foreach ($options_page_groups as $options_page_group) {
                    $options_page_groups_ids[] = $options_page_group['ID'];
                }
            }
        }

        foreach ($acf_groups as $acf_group) {
            /* Dummy group variable so that our added groups show in after title */
            $groups_dummy = [];
            // ACF >= 5.0.0
            if (function_exists('acf_get_fields')) {
                if (isset($acf_group['ID']) && !empty($acf_group['ID'])) {
                    $fields = \acf_get_fields($acf_group['ID']);
                } else {
                    $fields = \acf_get_fields($acf_group);
                }
            } else {
                $fields = \apply_filters('acf/field_group/get_fields', [], $acf_group['id']);
            }

            $options = [];

            if (!is_array($fields)) {
                continue;
            }

            $has_option_page_location = in_array($acf_group['ID'], $options_page_groups_ids, true);
            $is_only_options_page = $has_option_page_location && 1 === count($acf_group['location']);

            $options = [];
            foreach ($fields as $field) {

                //Modified for groups
                if ($field['type'] === 'group') {
                    $groups_dummy[] = self::generating_group_options($field, $types, [$field['name']], $groups_dummy);
                }

                if (!in_array($field['type'], $types, true)) {
                    continue;
                }

                $key = $field['key'] . ':' . $field['name'];
                $options[$key] = $field['label'];
            }

            /**
             * Putting it before options check as options can be empty here.
             */
            if (empty($groups_dummy) && empty($options)) {
                continue;
            }

            if (1 === count($options)) {
                $options = [-1 => ' -- '] + $options;
            }

            $groups[] = [
                'label' => $acf_group['title'],
                'options' => $options,
            ];

            if (!empty($groups_dummy)) {
                $groups = array_merge($groups, $groups_dummy);
            }
        } // End foreach().

        return $groups;
    }

    /**
     * Generating options for elementor select.
     *
     * @param array $fields
     * @param array $types
     * @param array $group_names
     * @param array &$groups passing groups by reference for nested groups.
     * 
     * @return array $group [
     *  'label' => string,
     *   'options' => array,
     * ]
     */
    private static function generating_group_options(array $group_field, array $types, array $group_names, array &$groups)
    {
        $options = [];
        foreach ($group_field['sub_fields'] as $field) {

            //check if field is group for nested groups.
            if ($field['type'] === 'group') {
                $group_names[] = $field['name'];
                $groups[] = self::generating_group_options($field, $types, $group_names, $groups);
            }

            if (!in_array($field['type'], $types, true)) {
                continue;
            }

            $key = $field['key'] . ':' . join("_", $group_names) . "_" . $field['name'];
            $options[$key] = $field['label'];
        }
        return array(
            'label' => $group_field['label'],
            'options' => $options,
        );
    }

    public function get_supported_fields()
    {
        return [
            //Image,
            'image',

        ];
    }

    /**
     * Return Value
     *
     * returns the value of the Dynamic tag
     *
     * @access public
     *
     *
     * @since 3.1.12
     *
     * @return array
     */
    public function get_value(array $options = [])
    {
        $image_data = [
            'id' => null,
            'url' => '',
        ];

        list($field, $meta_key) = Module::get_tag_value_field($this);

        if ($field && is_array($field)) {
            $field['return_format'] = isset($field['save_format']) ? $field['save_format'] : $field['return_format'];
            switch ($field['return_format']) {
                case 'object':
                case 'array':
                    $value = $field['value'];
                    break;
                case 'url':
                    $value = [
                        'id' => 0,
                        'url' => $field['value'],
                    ];
                    break;
                case 'id':
                    $src = wp_get_attachment_image_src($field['value'], $field['preview_size']);
                    $value = [
                        'id' => $field['value'],
                        'url' => $src[0],
                    ];
                    break;
            }
        }

        if (!isset($value)) {
            // Field settings has been deleted or not available.
            $value = get_field($meta_key);
        }
        if ($value === false) {
            $id = get_post_meta(get_the_ID(), $meta_key, true);
            $src = wp_get_attachment_image_src($id, $field['preview_size']);
            $value = [
                'id' => $field['value'],
                'url' => $src[0],
            ];
        }

        if (empty($value) && $this->get_settings('fallback')) {
            $value = $this->get_settings('fallback');
        }

        if (!empty($value) && is_array($value)) {
            $image_data['id'] = $value['id'];
            $image_data['url'] = $value['url'];
        }

        return $image_data;
    }


    protected function get_group_field(string $group, string $field, $post_id = 0)
    {
        $group_data = get_field($group, $post_id);
        if (is_array($group_data) && array_key_exists($field, $group_data)) {
            return $group_data[$field];
        }
        return null;
    }
}
