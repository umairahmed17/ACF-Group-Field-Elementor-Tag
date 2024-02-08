<?php

namespace UMRCP\Tags;

//including the base tag file
require_once __DIR__ . '/base/Base.php';

use ElementorPro\Modules\DynamicTags\ACF\Module;
use Elementor\Controls_Manager;
use GroupBase;

if (!defined('ABSPATH')) {
	exit;
}
//Exit if accessed directly

class ACFURLTag extends \Elementor\Core\DynamicTags\Data_Tag
{
	use GroupBase;
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
		return 'acf_group_url_tag';
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
		return __('ACF Group URL Fields');
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
			Module::URL_CATEGORY,
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

		$this->add_control(
			'fallback',
			[
				'label' => esc_html__('Fallback', 'elementor-pro'),
			]
		);
	}


	public function get_supported_fields()
	{
		return [
			'text',
			'email',
			'image',
			'file',
			'page_link',
			'post_object',
			'relationship',
			'taxonomy',
			'url',
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
		list($field, $meta_key) = Module::get_tag_value_field($this);

		/**
		 * ACF is returning default field value in $field['value']
		 * If a value actually has been set for this group field, use it instead of the default value.
		 */
		$value = get_field($meta_key);
		if ($value !== null) {
			$field['value'] = $value;
		}

		if ($field) {
			$value = $field['value'];

			if (is_array($value) && isset($value[0])) {
				$value = $value[0];
			}

			if ($value) {
				if (!isset($field['return_format'])) {
					$field['return_format'] = isset($field['save_format']) ? $field['save_format'] : '';
				}

				switch ($field['type']) {
					case 'email':
						if ($value) {
							$value = 'mailto:' . $value;
						}
						break;
					case 'image':
					case 'file':
						switch ($field['return_format']) {
							case 'array':
							case 'object':
								$value = $value['url'];
								break;
							case 'id':
								if ('image' === $field['type']) {
									$src = wp_get_attachment_image_src($value, 'full');
									$value = $src[0];
								} else {
									$value = wp_get_attachment_url($value);
								}
								break;
						}
						break;
					case 'post_object':
					case 'relationship':
						$value = get_permalink($value);
						break;
					case 'taxonomy':
						$value = get_term_link($value, $field['taxonomy']);
						break;
				} // End switch().
			}
		} else {
			// Field settings has been deleted or not available.
			$value = get_field($meta_key);
		} // End if().

		if (empty($value)) {
			$value = get_post_meta(get_the_ID(), $meta_key, true);
		}

		// if value is still empty then use default
		if (empty($value) && $this->get_settings('fallback')) {
			$value = $this->get_settings('fallback');
		}

		return wp_kses_post($value);
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
