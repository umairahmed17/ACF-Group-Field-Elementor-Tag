<?php


trait GroupBase
{
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
				$group_names[] = ' ' . $field['name'];
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
}
