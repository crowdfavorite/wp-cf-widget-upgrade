<?php
/*
Plugin Name: CF Widget Upgrade
Plugin URI: http://crowdfavorite.com
Description: Widget Upgrading Script
Version: 0.1
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/



/**
 * This will update widget settings from pre 2.8 widget implementations to 2.8+ implementations.
 *
 * @param string $class - Name of the Class object of the new widget
 * @param string $options_key - WP Options table key of where to find the old widget data
 * @param string $old_base - ID Base of the old widget
 * @param string $new_base - ID Base of the new widget
 */
function cf_widget_update($class, $options_key, $old_base, $new_base) {
	// Create a new instance of the widget type
	$widget = new $class();
	// Get all of the settings for the particular widget type
	$settings = $widget->get_settings();
	// Get the settings of the old widget type
	$options = get_option($options_key);
	// Have the plugins run their own processing of widget data
	$settings = apply_filters('cf_widget_update_settings', $settings, $class, $options);
	// Save the widget settings back to the new widget type
	$widget->save_settings($settings);
	// Grab the all of the sidebars and the widgets
	$sidebars_widgets = wp_get_sidebars_widgets();
	// Run through all of the sidebars and string replace the old widget keys with the new widget keys.  Luckily we can keep IDs in place
	if (is_array($sidebars_widgets) && !empty($sidebars_widgets)) {
		foreach ($sidebars_widgets as $sidebar => $widgets) {
			foreach ($widgets as $spot => $widget_key) {
				$sidebars_widgets[$sidebar][$spot] = str_replace($old_base, $new_base, $widget_key);
			}
		}
	}
	// Update the sidebars widgets so the new widget implementation will show up
	wp_set_sidebars_widgets($sidebars_widgets);
}

// How to Run the fix
// This will run at init, but if the front end is where it is hit, the page will need to be refreshed for the new widgets to be displayed
function cflk_widget_update() {
	cf_widget_update('cflk_Widget', 'cf_links_widget', 'cf-links', 'cflk-widget');
}
// add_action('init', 'cflk_widget_update', 99);

function cflk_widget_update_settings($settings, $class, $options) {
	if ($class == 'cflk_Widget') {
		// Run through the old widget settings and push them to the new settings array
		if (is_array($options) && !empty($options)) {
			foreach ($options as $key => $values) {
				$settings[$key] = array(
					'title' => $values['title'],
					'list_key' => $values['select']
				);
			}
		}
	}
	return $settings;
}
add_filter('cf_widget_update_settings', 'cflk_widget_update_settings', 10, 3);
*/
?>