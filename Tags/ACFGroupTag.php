<?php

namespace UMRCP\Tags;

use ElementorPro\Modules\DynamicTags\ACF\Module;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}
//Exit if accessed directly

class ACFGroupTag extends \Elementor\Core\DynamicTags\Tag

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
        return 'acf_group_tag';
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
        return __('ACF Group Fields');
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
            Module::TEXT_CATEGORY,
            Module::POST_META_CATEGORY,
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
            'text',
            'textarea',
            'number',
            'email',
            'password',
            'wysiwyg',
            'select',
            'checkbox',
            'radio',
            'true_false',

            // Pro
            'oembed',
            'google_map',
            'date_picker',
            'time_picker',
            'date_time_picker',
            'color_picker',
        ];
    }

    /**
     * Render
     *
     * Prints out the value of the Dynamic tag
     *
     * @access public
     *
     *
     * @since 3.1.12
     *
     * @return void
     */
    public function render()
    {
        list($field, $meta_key) = Module::get_tag_value_field($this);

        /**
         * ACF is returning default value.
         * Checking if ACF returned default value so we can check to see if value actually occurs for group field.
         */
        if ($field['value'] === acf_format_value($field['default_value'], get_the_ID(), $field)) {
            $value = get_field($meta_key);
        }
        if ($value !== null) {
            $field['value'] = $value;
        }

        if ($field && !empty($field['type'])) {
            $value = $field['value'];

            switch ($field['type']) {
                case 'radio':
                    if (isset($field['choices'][$value])) {
                        $value = $field['choices'][$value];
                    }
                    break;
                case 'select':
                    // Use as array for `multiple=true` or `return_format=array`.
                    $values = (array) $value;

                    foreach ($values as $key => $item) {
                        if (isset($field['choices'][$item])) {
                            $values[$key] = $field['choices'][$item];
                        }
                    }

                    $value = implode(', ', $values);

                    break;
                case 'checkbox':
                    $value = (array) $value;
                    $values = [];
                    foreach ($value as $item) {
                        if (isset($field['choices'][$item])) {
                            $values[] = $field['choices'][$item];
                        } else {
                            $values[] = $item;
                        }
                    }

                    $value = implode(', ', $values);

                    break;
                case 'oembed':
                    // Get from db without formatting.
                    $value = $this->get_queried_object_meta($meta_key);
                    break;
                case 'google_map':
                    $meta = $this->get_queried_object_meta($meta_key);
                    $value = isset($meta['address']) ? $meta['address'] : '';
                    break;
            } // End switch().
            if ($value === '' || empty($value)) {
                $value = get_post_meta(get_the_ID(), $meta_key, true);
            }
        } else {

            // Field settings has been deleted or not available.
            $value = get_field($meta_key);
        } // End if().
        echo wp_kses_post($value);
    }

    /**
     * Get the value of the `meta_key` or `acf meta key`
     *
     * @param string $meta_key
     * @return string
     */
    private function get_queried_object_meta($meta_key)
    {
        $value = '';
        if (is_singular()) {
            $value = get_post_meta(get_the_ID(), $meta_key, true);
        } elseif (is_tax() || is_category() || is_tag()) {
            $value = get_term_meta(get_queried_object_id(), $meta_key, true);
        }

        return $value;
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
