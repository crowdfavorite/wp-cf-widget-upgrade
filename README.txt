## CF Widget Upgrade

The CF Widget Upgrade plugin has been designed to upgrade old version (pre WP 2.7) of WordPress widgets to new class based WordPress widgets.

### Upgrade Page

The Upgrade page link is located under the Appearance navigation section of the WordPress admin.  This page will display all of the plugins that are available for upgrade as well as all of the widgets that are available for upgrading.  After checking the widgets that will be upgraded, the PreProcess button will take the admin user to the confirmation screen.  This screen will display more detailed information about the widget upgrade process and also provide an upgrade button.  Once the upgrade is complete the main screen is displayed again with a list of widgets that did not get processed.

#### Updatable Plugins

The Updatable Plugins section displays which plugins have provided information to the Widget Upgrade plugin for processing.  Plugins must provide upgrade instructions to the Widget Upgrade plugin so their widgets may be upgraded.

#### Sidebars

The Sidebars section displays a list of the current sidebars and the widgets in them that need to be upgraded.  If a sidebar does not have any widgets to be upgraded it will be placed in the Sidebars with no widgets to update section.  If no widgets are available for upgrade, no sidebars will be shown

##### Widgets

The widget keys displayed in the Sidebars section show the Before Processing and After Processing states of the widgets.  The More Information link next to the widget keys will give all of the information about the widget for Before and After Processing.

#### Widget Update Preprocess

After clicking the Preprocess button, the screen will display the Widget Update Preprocess screen.  This screen displays all of the information about the widgets being updated.  Also displayed is the Sidebars Updates information.  All of this will display the before and after processing state of the widgets and sidebars.

After clicking the Process Widgets button, the process will commence and the browser will be directed back to the Main Widget Upgrade screen.

### Plugin Filtering

For Widgets to be filtered, the Plugin containing them must tell the Widget Upgrade script how to process the widgets.  This is done via Filters and callback functions.

#### Integration Filter

Below is an example of the Integration Filter for a plugin.  The CF Links plugin is used as the example.

	// $plugins: This is the array of other plugins that have filtered in settings
	function cflk_widget_upgrade($plugins) {
		$plugins['cflk-widget'] = array(
			'name' => 'CF Links',													// Name of the Plugin
			'class' => 'cflk_Widget',												// Class of the new version of the widget
			'update_callback' => 'cflk_widget_update_settings',						// Function used for processing the widgets
			'display_callback' => 'cflk_widget_display_settings',					// Function used to display the information about the old/new widgets
			'display_debug_callback' => 'cflk_widget_display_settings_debug',		// Function used to display the Preprocess information for the widgets
			'old_base' => 'cf-links',												// ID Base of the old widget style
			'new_base' => 'cflk-widget'												// ID Base of the new widget style
		);
		return $plugins;
	}
	add_filter('cfwu-widget-upgrade', 'cflk_widget_upgrade');						// Filter to add content to is cfwu-widget-upgrade

#### Update Settings Function

Below is an example of the Update Settings Function.  The CF Links plugin is used as the example.

	// $settings: These are the current settings for the new widget class type
	// $updated_keys: This is a list of keys using the old ID base for the function to find and update
	// $debug: This is to tell the function to display the debug information and not fully process widget data
	// $class: This is the class object name of the new widget class
	function cflk_widget_update_settings($settings, $updated_keys = array(), $debug = true, $class) {
		// Get the settings of the old widget type
		$options = get_option('cf_links_widget');
		// If we are debugging, output the debug information to the screen
		// The cfwu_showhide function will display the Show/Hide links for the section.  The parameter passed in is the ID of the element to Show/Hide.  The JavaScript is provided automatically.
		if ($debug) {
			?>
			<br />
			Old Widget Options: | <?php echo cfwu_showhide($class.'-old-widget-options'); ?>
			<small>(Old Widget style settings)</small>
			<pre id="<?php echo $class; ?>-old-widget-options" style="display:none;" class="cfwu-fourth-level-debug"><?php print_r($options); ?></pre><!--<?php echo $class; ?>-old-widget-options-->
			<br />
			Updated Keys: | <?php echo cfwu_showhide($class.'-updated-keys'); ?>
			<small>(List of IDs that the process will search for to pass to the new widget style settings)</small>
			<pre id="<?php echo $class; ?>-updated-keys" style="display:none;" class="cfwu-fourth-level-debug"><?php print_r($updated_keys); ?></pre><!--<?php echo $class; ?>-updated-keys-->
			<?php
		}
		// Run through the old widget settings and push them to the new settings array
		if (is_array($options) && !empty($options)) {
			foreach ($options as $key => $values) {
				// If the widget key is in the updated keys list, insert the information into the new widget class settings array
				if (in_array($key, $updated_keys)) {
					$settings[$key] = array(
						'title' => $values['title'],
						'list_key' => $values['select']
					);
					unset($options[$key]);
				}
			}
		}
		// Put this in so we get rid of the old widget data so it doesn't get processed twice
		if (!$debug) {
			update_option('cf_links_widget', $options);
		}
		return $settings;
	}

#### Display Settings Function

Below is an example of the Display Settings Function. The CF Links plugin is used as an example.

	// This function takes a widget key, and will display the data for the selected key.  
	// $key: The widget key to display
	function cflk_widget_display_settings($key) {
		$html = '';
		if (strstr($key, 'cf-links')) {
			// Using the old widget style, get the data and display the Title and Selected list for the widget
			$options = get_option('cf_links_widget');
			$data = $options[str_replace('cf-links-', '', $key)];
			$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected List', 'cfwu').': '.$data['select'].'<br />';
		}
		else {
			// Using the new widget style, get the data and display the Title and Selected list for the widget
			$widget = new cflk_Widget();
			$settings = $widget->get_settings();
			$data = $settings[str_replace('cflk-widget-', '', $key)];
			$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected List', 'cfwu').': '.$data['list_key'].'<br />';
		}
		return $html;
	}

#### Display Debug Settings Function

Below is an example of the Display Debug Settings Function.  The CF Links plugin is used as an example.
	
	// This function will display the post processing widget settings for the inserted key
	// $key: The key of the old style widget to show updated data for
	function cflk_widget_display_settings_debug($key) {
		// Get the old widget data, and show what the new data will look like
		$options = get_option('cf_links_widget');
		$data = $options[str_replace('cf-links-', '', $key)];
		$new_key = str_replace('cf-links-', 'cflk-widget-', $key);
		$html = '<div class="'.$key.'-data cfwu-widget-data">'.__('Type: New-Updated', 'cfwu').'<br />';
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected List', 'cfwu').': '.$data['select'].'<br />';
		$html .= '</div>';
		return array('content' => $html, 'new_key' => $new_key);
	}
