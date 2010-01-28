<?php
/*
Plugin Name: CF Widget Upgrade
Plugin URI: http://crowdfavorite.com
Description: Widget Upgrading Script
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

// Constants
	define('CFWU_VERSION', '1.0');
	define('CFWU_DIR',trailingslashit(realpath(dirname(__FILE__))));

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR','wp-content/plugins');
}

load_plugin_textdomain('cfwu');

// Admin Functionality

function cfwu_request_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cfwu-admin-js':
				cfwu_admin_js();
				break;
			case 'cfwu-admin-css':
				cfwu_admin_css();
				die();
				break;
		}
	}
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cfwu-process':
				if (!empty($_POST['cfwu-action']) && $_POST['cfwu-action'] == 'process') {
					$result = false;
					$result = cfwu_widget_update(false);
					if ($result) {
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/themes.php?page='.basename(__FILE__).'&message=updated');
						die();
					}
					else {
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/themes.php?page='.basename(__FILE__).'&message=failure');
						die();
					}
				}
				else {
					global $cfwu_content;
					ob_start();
					cfwu_widget_update(true);
					$cfwu_content = ob_get_contents();
					ob_end_clean();
					$cfwu_content = '<h3>Widget Update Preprocess</h3>'.$cfwu_content;
				}
				break;
		}
	}
}
add_action('init', 'cfwu_request_handler');

if (!empty($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
	wp_enqueue_script('jquery');
	wp_enqueue_script('cfwu-admin-js', trailingslashit(get_bloginfo('url')).'?cf_action=cfwu-admin-js', array('jquery'), CFWU_VERSION);
	wp_enqueue_style('cfwu-admin-css', trailingslashit(get_bloginfo('url')).'?cf_action=cfwu-admin-css', array(), CFWU_VERSION, 'screen');
}

function cfwu_admin_js() {
	header('Content-type: text/javascript');
	?>
	;(function($) {
		$(function() {
			$(".cfwu-more-info").click(function() {
				var id = $(this).attr("href").replace("#","");
				$("."+id).toggle();
				return false;
			});
			$(".cfwu-update-show").click(function() {
				var id = $(this).attr("href").replace("#","").replace("-show","");
				$("#"+id).toggle();
				$("#"+id+"-hide").show();
				$("#"+id+"-show").hide();
				return false;
			});
			$(".cfwu-update-hide").click(function() {
				var id = $(this).attr("href").replace("#","").replace("-hide","");
				$("#"+id).toggle();
				$("#"+id+"-show").show();
				$("#"+id+"-hide").hide();
				return false;
			});
		});
	})(jQuery);
	<?php
	do_action('cfwu-admin-js');
	die();
}

function cfwu_admin_css() {
	header('Content-type: text/css');
	do_action('cfwu-admin-css');
	?>
	.cfwu-plugins {
		width:40%;
	}
	.cfwu-details {
		margin-bottom:20px;
	}
	.cfwu-current-sidebars-widgets,
	.cfwu-updated-sidebars-widgets {
		margin:10px 10px 10px 0;
	}
	.cfwu-sidebar-list {
		border:1px solid #DFDFDF;
		display:block;
		height:200px;
		overflow:auto;
		padding:10px;
		margin:10px 10px 10px 0;
	}
	.cfwu-widget-list,
	.cfwu-no-widgets {
		margin:10px 0 0 10px;
	}
	.cfwu-widget-data {
		display:none;
		border:1px solid #DFDFDF;
		padding:0 0 0 5px;
		background-color:#FFFEEB;
		color:#555555;
	}
	
	.cfwu-top-level-debug {
		padding:10px;
	}
	.cfwu-second-level-debug,
	.cfwu-third-level-debug,
	.cfwu-fourth-level-debug {
		padding-left:10px;
	}
	
	.cfwu-processing-cell {
		width:42%;
	}
	.cfwu-more-info-cell {
		width:15%;
		text-align:center;
	}
	<?php
	die();
}

function cfwu_admin_menu() {
	add_submenu_page(
		'themes.php',
		__('CF Widget Upgrade', 'cfwu'),
		__('CF Widget Upgrade', 'cfwu'),
		'edit_themes',
		basename(__FILE__),
		'cfwu_admin_page'
	);
}
add_action('admin_menu', 'cfwu_admin_menu');

function cfwu_admin_message($message) {
	$html = '';
	switch ($message) {
		case 'updated':
			$html .= '<div id="message" class="updated fade">
				<p>'.__('Widget processing complete', 'cfwu').'</p>
			</div>';			
			break;
		case 'failure':
			$html .= '<div id="message" class="updated fade">
				<p>'.__('Widget processing failure, please check the checkbox next to the process button', 'cfwu').'</p>
			</div>';			
			break;
	}
	return $html;
}

function cfwu_admin_page() {
	global $wp_registered_sidebars,$cfwu_content;
	?>
	<div class="wrap">
		<?php 
		echo screen_icon().'<h2>'.__('CF Widget Upgrade', 'cfwu').'</h2>';
		if (empty($cfwu_content)) {
			$plugins = apply_filters('cfwu-widget-upgrade', array());
			$sidebars_widgets = wp_get_sidebars_widgets();

			$display_content = array();
			if (is_array($sidebars_widgets) && !empty($sidebars_widgets)) {
				foreach ($sidebars_widgets as $key => $widgets) {
					$sidebar_title = '';
					if ($key == 'wp_inactive_widgets') {
						$sidebar_title = __('Inactive Widgets', 'cfwu');
					}
					else {
						$sidebar_title = $wp_registered_sidebars[$key]['name'];
					}
					
					if (is_array($widgets) && !empty($widgets)) {
						$content = '';
						foreach ($widgets as $widget) {
							$old_content = '';
							$process_content = '';
							foreach ($plugins as $plugin_key => $plugin) {
								if (strstr($widget, $plugin['old_base']) !== false) {
									$old_content .= '<li>'.$widget;
									if (function_exists($plugin['display_callback'])) {
										$type = '';
										if (strstr($widget, $plugin['old_base']) !== false) {
											$type = __('Old', 'cfwu');
										}
										else if (strstr($widget, $plugin['new_base']) !== false) {
											$type = __('New', 'cfwu');
										}
										$old_content .= '<div class="'.$widget.'-data cfwu-widget-data">'.__('Type: ', 'cfwu').$type.'<br />'.call_user_func_array($plugin['display_callback'], array($widget)).'</div>';
									}
									$old_content .= '</li>';
									break;
								}
							}
							foreach ($plugins as $plugin_key => $plugin) {
								if (function_exists($plugin['display_debug_callback']) && strstr($widget, $plugin['old_base']) !== false) {
									$debug_content = call_user_func_array($plugin['display_debug_callback'], array($widget));
									$process_content .= '<li>'.$debug_content['new_key'];
									$process_content .= $debug_content['content'];
									$process_content .= '</li>';
									break;
								}
							}
							if (!empty($old_content) && !empty($process_content)) {
								$content .= '
									<tr>
										<td>'.$old_content.'</td>
										<td>'.$process_content.'</td>
										<td class="cfwu-more-info-cell"><a href="#'.$widget.'-data" class="cfwu-more-info">'.__('More Details', 'cfwu').'</a></td>
									</tr>
								';
							}
						}
					}

					if (!empty($content)) {
						$display_content['full'][$key] = array(
							'sidebar_title' => $sidebar_title,
							'content' => $content
						);
					}
					else {
						$display_content['empty'][$key] = array(
							'sidebar_title' => $sidebar_title
						);
					}
				}
			}
			?>
				<?php if (!empty($_GET['message'])) { echo cfwu_admin_message($_GET['message']); } ?>
				<div class="cfwu-plugins">
					<h3><?php _e('Updatable Plugins', 'cfwu'); ?></h3>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php _e('Plugin Name', 'cfwu'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							if (is_array($plugins) && !empty($plugins)) {
								foreach ($plugins as $key => $plugin) {
									if (empty($plugin['name'])) { continue; }
									?>
									<tr>
										<td><?php echo $plugin['name']; ?></td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
				</div>
				<div class="cfwu-sidebars-widgets">
					<div class="cfwu-current-sidebars-widgets">
						<h3><?php _e('Sidebars', 'cfwu'); ?></h3>
						<?php 
						if (!empty($display_content['full'])) {
							foreach ($display_content['full'] as $key => $content) { 
						?>
						<table class="widefat cfwu-details">
							<thead>
								<tr>
									<th colspan="3">
										<?php echo $content['sidebar_title'].__(' Sidebar', 'cfwu'); ?>
									</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th class="cfwu-processing-cell"><?php _e('Before Processing', 'cfwu'); ?></th>
									<th class="cfwu-processing-cell"><?php _e('After Processing', 'cfwu'); ?></th>
									<th class="cfwu-more-info-cell" style="text-align:center;"><?php _e('More Information', 'cfwu'); ?></th>
								</tr>
								<?php echo $content['content']; ?>
							</tbody>
						</table>
						<?php
							}
							if (!empty($display_content['empty'])) {
								?>
								<table class="widefat cfwu-details">
									<thead>
										<tr>
											<th><?php _e('Sidebars with no widgets to update'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ($display_content['empty'] as $sidebar) {
											?>
											<tr>
												<td><?php echo $sidebar['sidebar_title']; ?></td>
											</tr>
											<?php
										}
										?>
									</tbody>
								</table>
								<?php
							}
							?>
							<form action="" method="post">
								<p class="submit" style="border-top: none;">
									<input type="hidden" name="cf_action" value="cfwu-process" />
									<input type="submit" name="submit" value="<?php _e('Preprocess Widgets', 'cfwu'); ?>" class="button-primary button" />
								</p>
							</form>
							<?php
						}
						else {
							echo '<h4>'.__('No updatable widgets', 'cfwu').'</h4>';
						}
						?>
					</div>
					<div class="clear"></div>
				</div>
			<?php
		}
		else {
			?>
			<div id="message" class="updated fade"><?php echo $cfwu_content; ?></div>
			<form action="" method="post">
				<p class="submit" style="border-top: none;">
					<input type="hidden" name="cf_action" value="cfwu-process" />
					<input type="hidden" name="cfwu-action" value="process" />
					<input type="submit" name="submit" value="<?php _e('Process Widgets', 'cfwu'); ?>" class="button-primary button" />
				</p>
			</form>
			<?php
		}
		?>
	</div>
	<?php
}

function cfwu_showhide($key) {
	return '<a href="#'.$key.'-show" id="'.$key.'-show" class="cfwu-update-show">Show</a><a href="#'.$key.'-hide" id="'.$key.'-hide" class="cfwu-update-hide" style="display:none;">Hide</a>';
}

// Upgrade Functionality

/**
 * This will update widget settings from pre 2.8 widget implementations to 2.8+ implementations.
 *
 * @param string $class - Name of the Class object of the new widget
 * @param string $options_key - WP Options table key of where to find the old widget data
 * @param string $old_base - ID Base of the old widget
 * @param string $new_base - ID Base of the new widget
 */
function cfwu_widget_update($debug = true) {
	$plugins = apply_filters('cfwu-widget-upgrade', array());
	
	if (is_array($plugins) && !empty($plugins)) {
		// Grab the all of the sidebars and the widgets
		$sidebars_widgets = wp_get_sidebars_widgets();
		
		foreach ($plugins as $key => $plugin) {
			if (!class_exists($plugin['class'])) { continue; }
			$updated_keys = array();
			$found_widgets_debug = '';
			if (!empty($plugin['old_base']) && !empty($plugin['new_base'])) {
				// Run through all of the sidebars and string replace the old widget keys with the new widget keys.  Luckily we can keep IDs in place
				if (is_array($sidebars_widgets) && !empty($sidebars_widgets)) {
					foreach ($sidebars_widgets as $sidebar => $widgets) {
						foreach ($widgets as $spot => $widget_key) {
							$new_key = str_replace($plugin['old_base'], $plugin['new_base'], $widget_key);
							if ($new_key != $widget_key) {
								if ($debug) {
									$found_widgets_debug .= 'Found widget with key: <strong>'.$widget_key.'</strong>.  New key: <strong>'.$new_key.'</strong><br />';
								}
								$sidebars_widgets[$sidebar][$spot] = $new_key;
								$updated_keys[] = str_replace($plugin['old_base'].'-', '', $widget_key);
							}
						}
					}
				}
			}

			// Create a new instance of the widget type
			$widget = new $plugin['class']();
			// Get all of the settings for the particular widget type
			$settings = $widget->get_settings();
			// Have the plugins run their own processing of widget data
			if (function_exists($plugin['update_callback']) && !empty($updated_keys)) {
				if ($debug) {
					?>
				<div id="<?php echo $plugin['class']; ?>" class="cfwu-top-level-debug">
					Updating Plugin Class: <strong><?php echo $plugin['class'].' | '.cfwu_showhide($plugin['class'].'-updating'); ?></strong>
					<div id="<?php echo $plugin['class']; ?>-updating" style="display:none;" class="cfwu-second-level-debug">
						Searching for Widgets: | <?php echo cfwu_showhide($plugin['class'].'-widgets-search'); ?>
						<small>(A list of widgets that the process will update)</small>
						<div id="<?php echo $plugin['class']; ?>-widgets-search" style="display:none;" class="cfwu-third-level-debug">
							<?php echo $found_widgets_debug; ?>
						</div><!--<?php echo $plugin['class']; ?>-widgets-search-->
						<br />
						Processing Settings | <?php echo cfwu_showhide($plugin['class'].'-settings'); ?>
						<div id="<?php echo $plugin['class']; ?>-settings" style="display:none;" class="cfwu-third-level-debug">
							Initial Settings: | <?php echo cfwu_showhide($plugin['class'].'-settings-pre'); ?>
							<small>(New Widget style settings before being processed)</small>
							<pre id="<?php echo $plugin['class']; ?>-settings-pre" style="display:none;" class="cfwu-fourth-level-debug"><?php print_r($settings); ?></pre><!--<?php echo $plugin['class']; ?>-settings-pre-->
					<?php
				}
				// Process the settings for the widget using the provided callback function
				$settings = call_user_func_array($plugin['update_callback'], array($settings, $updated_keys, $debug, $plugin['class']));
				if ($debug) {
					?>
							<br />
							Processed Settings: | <?php echo cfwu_showhide($plugin['class'].'-settings-post'); ?>
							<small>(New Widget style settings after being processed)</small>
							<pre id="<?php echo $plugin['class']; ?>-settings-post" style="display:none;" class="cfwu-fourth-level-debug"><?php print_r($settings); ?></pre><!--<?php echo $plugin['class']; ?>-settings-post-->
						</div><!--<?php echo $plugin['class']; ?>-settings-->
					</div><!--<?php echo $plugin['class']; ?>-updating-->
				</div><!--<?php echo $plugin['class']; ?>-->
					<?php
				}

				// Save the widget settings back to the new widget type
				if (!$debug) {
					$widget->save_settings($settings);
				}
			}
		}
		
		if ($debug) {
			?>
			<div id="sidebars-widgets" class="cfwu-top-level-debug">
				Sidebars Updates: | <?php echo cfwu_showhide('sidebars-widgets-content'); ?>
				<div id="sidebars-widgets-content" style="display:none" class="cfwu-second-level-debug">
					Original Sidebars Widgets: | <?php echo cfwu_showhide('sidebars-widgets-pre'); ?>
					<small>(This will show the Widget IDs for each of the sidebars before the update happens)</small>
					<pre id="sidebars-widgets-pre" style="display:none;" class="cfwu-third-level-debug"><?php print_r(wp_get_sidebars_widgets()); ?></pre>
					<br />
					Updated Sidebars Widgets: | <?php echo cfwu_showhide('sidebars-widgets-post'); ?>
					<small>(This will show the Widget IDs for each of the sidebars after the update happens)</small>
					<pre id="sidebars-widgets-post" style="display:none;" class="cfwu-third-level-debug"><?php print_r($sidebars_widgets); ?></pre>
				</div><!--sidebars-widgets-content-->
			</div><!--sidebars-widgets-->
			<?php
		}
		// Update the sidebars widgets so the new widget implementation will show up
		if (!$debug) {
			wp_set_sidebars_widgets($sidebars_widgets);
		}
	}
	if ($debug) {
		return false;
	}
	return true;
}

// External Plugin Functionality

// CF Links Functionality

function cflk_widget_upgrade($plugins) {
	$plugins['cflk-widget'] = array(
		'name' => 'CF Links',
		'class' => 'cflk_Widget',
		'update_callback' => 'cflk_widget_update_settings',
		'display_callback' => 'cflk_widget_display_settings',
		'display_debug_callback' => 'cflk_widget_display_settings_debug',
		'old_base' => 'cf-links',
		'new_base' => 'cflk-widget'
	);
	return $plugins;
}
add_filter('cfwu-widget-upgrade', 'cflk_widget_upgrade');

function cflk_widget_update_settings($settings, $updated_keys = array(), $debug = true, $class) {
	// Get the settings of the old widget type
	$options = get_option('cf_links_widget');
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

function cflk_widget_display_settings($key) {
	$html = '';
	if (strstr($key, 'cf-links')) {
		$options = get_option('cf_links_widget');
		$data = $options[str_replace('cf-links-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected List', 'cfwu').': '.$data['select'].'<br />';
	}
	else {
		$widget = new cflk_Widget();
		// Get all of the settings for the particular widget type
		$settings = $widget->get_settings();
		$data = $settings[str_replace('cflk-widget-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected List', 'cfwu').': '.$data['list_key'].'<br />';
	}
	return $html;
}

function cflk_widget_display_settings_debug($key) {
	$options = get_option('cf_links_widget');
	$data = $options[str_replace('cf-links-', '', $key)];
	$new_key = str_replace('cf-links-', 'cflk-widget-', $key);
	$html = '<div class="'.$key.'-data cfwu-widget-data">'.__('Type: New-Updated', 'cfwu').'<br />';
	$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected List', 'cfwu').': '.$data['select'].'<br />';
	$html .= '</div>';
	return array('content' => $html, 'new_key' => $new_key);
}


// CF Snippets Functionality

function cfsnip_widget_upgrade($plugins) {
	$plugins['cfsnip-widget'] = array(
		'name' => 'CF Snippets',
		'class' => 'cfsnip_Widget',
		'update_callback' => 'cfsnip_widget_update_settings',
		'display_callback' => 'cfsnip_widget_display_settings',
		'display_debug_callback' => 'cfsnip_widget_display_settings_debug',
		'old_base' => 'cfsnip-widgets',
		'new_base' => 'cfsnip-widget'
	);
	return $plugins;
}
add_filter('cfwu-widget-upgrade', 'cfsnip_widget_upgrade');

function cfsnip_widget_update_settings($settings, $updated_keys = array(), $debug = true) {
	// Get the settings of the old widget type
	$options = get_option('cfsnip_widgets');
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
			if (in_array($key, $updated_keys)) {
				$settings[$key] = array(
					'title' => $values['title'],
					'list_key' => $values['snippet-name']
				);
				unset($options[$key]);
			}
		}
	}
	// Put this in so we get rid of the old widget data so it doesn't get processed twice
	if (!$debug) {
		update_option('cfsnip_widgets', $options);
	}
	return $settings;
}

function cfsnip_widget_display_settings($key) {
	$html = '';
	if (strstr($key, 'cfsnip-widgets')) {
		$options = get_option('cfsnip_widgets');
		$data = $options[str_replace('cfsnip-widgets-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected Snippet', 'cfwu').': '.$data['snippet-name'].'<br />';
	}
	else {
		$widget = new cfsnip_Widget();
		// Get all of the settings for the particular widget type
		$settings = $widget->get_settings();
		$data = $settings[str_replace('cfsnip-widget-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected Snippet', 'cfwu').': '.$data['list_key'].'<br />';
	}
	return $html;
}

function cfsnip_widget_display_settings_debug($key) {
	$options = get_option('cfsnip_widgets');
	$data = $options[str_replace('cfsnip-widgets-', '', $key)];
	$new_key = str_replace('cfsnip-widgets-', 'cfsnip-widget-', $key);
	$html = '<div class="'.$key.'-data cfwu-widget-data">'.__('Type: New-Updated', 'cfwu').'<br />';
	$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Selected Snippet', 'cfwu').': '.$data['snippet-name'].'<br />';
	$html .= '</div>';
	return array('content' => $html, 'new_key' => $new_key);
}


// CF OpenX Handler Functionality
// CF OpenX Handler Functionality has been commented out due to the problems searching for widgets with the same
// base ID for the old style and new style
/*
function cfox_widget_upgrade($plugins) {
	$plugins['cfox'] = array(
		'name' => 'CF OpenX',
		'class' => 'cfox_widget',
		'update_callback' => 'cfox_widget_update_settings',
		'display_callback' => 'cfox_widget_display_settings',
		'display_debug_callback' => 'cfox_widget_display_settings_debug',
		'old_base' => 'cfox',
		'new_base' => 'cfox'
	);
	$plugins['cfox_preload'] = array(
		'name' => 'CF OpenX Preload',
		'class' => 'cfox_preload_widget',
		'update_callback' => 'cfox_preload_widget_update_settings',
		'display_callback' => 'cfox_preload_widget_display_settings',
		'display_debug_callback' => 'cfox_preload_widget_display_settings_debug',
		'old_base' => 'cfox-preload',
		'new_base' => 'cfox_preload'
	);
	return $plugins;
}
add_filter('cfwu-widget-upgrade', 'cfox_widget_upgrade');

function cfox_widget_update_settings($settings, $updated_keys = array(), $debug = true) {
	// Get the settings of the old widget type
	$options = get_option('cfox_widget');
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
			if (in_array($key, $updated_keys)) {
				$settings[$key] = array(
					'title' => $values['title'],
					'zone' => $values['zoneID']
				);
				unset($options[$key]);
			}
		}
	}
	// Put this in so we get rid of the old widget data so it doesn't get processed twice
	if (!$debug) {
		update_option('cfox_widget', $options);
	}
	return $settings;
}

function cfox_widget_display_settings($key) {
	$html = '';
	if (strstr($key, 'cfox')) {
		$options = get_option('cfox_widget');
		$data = $options[str_replace('cfox-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Zone ID', 'cfwu').': '.$data['zoneID'].'<br />';
	}
	else {
		$widget = new cfox_widget();
		// Get all of the settings for the particular widget type
		$settings = $widget->get_settings();
		$data = $settings[str_replace('cfox-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Zone ID', 'cfwu').': '.$data['zone'].'<br />';
	}
	return $html;
}

function cfox_widget_display_settings_debug($key) {
	$options = get_option('cfox_widget');
	$data = $options[str_replace('cfox-', '', $key)];
	$new_key = str_replace('cfox-', 'cfox-', $key);
	$html = '<div class="'.$key.'-data cfwu-widget-data">'.__('Type: New-Updated', 'cfwu').'<br />';
	$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Zone ID', 'cfwu').': '.$data['zoneID'].'<br />';
	$html .= '</div>';
	return array('content' => $html, 'new_key' => $new_key);
}

function cfox_preload_widget_update_settings($settings, $updated_keys = array(), $debug = true) {
	// Get the settings of the old widget type
	$options = get_option('cfox_preload_widget');
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
			if (in_array($key, $updated_keys)) {
				$settings[$key] = array(
					'title' => $values['title'],
					'zone' => $values['zoneID']
				);
				unset($options[$key]);
			}
		}
	}
	// Put this in so we get rid of the old widget data so it doesn't get processed twice
	if (!$debug) {
		update_option('cfox_preload_widget', $options);
	}
	return $settings;
}

function cfox_preload_widget_display_settings($key) {
	$html = '';
	if (strstr($key, 'cfox-preload')) {
		$options = get_option('cfox_preload_widget');
		$data = $options[str_replace('cfox-preload-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Zone ID', 'cfwu').': '.$data['zoneID'].'<br />';
	}
	else {
		$widget = new cfox_preload_widget();
		// Get all of the settings for the particular widget type
		$settings = $widget->get_settings();
		$data = $settings[str_replace('cfox_preload-', '', $key)];
		$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Zone ID', 'cfwu').': '.$data['zone'].'<br />';
	}
	return $html;
}

function cfox_preload_widget_display_settings_debug($key) {
	$options = get_option('cfox_preload_widget');
	$data = $options[str_replace('cfox-preload-', '', $key)];
	$new_key = str_replace('cfox-preload-', 'cfox_preload-', $key);
	$html = '<div class="'.$key.'-data cfwu-widget-data">'.__('Type: New-Updated', 'cfwu').'<br />';
	$html .= __('Title', 'cfwu').': '.$data['title'].'<br />'.__('Zone ID', 'cfwu').': '.$data['zoneID'].'<br />';
	$html .= '</div>';
	return array('content' => $html, 'new_key' => $new_key);
}
*/

## README Handling

/**
 * Enqueue the readme function
 */
function cfwu_add_readme() {
	if(function_exists('cfreadme_enqueue')) {
		cfreadme_enqueue('cfwu','cfwu_readme');
	}
}
add_action('admin_init','cfwu_add_readme');

/**
 * return the contents of the CF Widget Upgrade README file
 *
 * @return string
 */
function cfwu_readme() {
	$file = realpath(dirname(__FILE__)).'/README.txt';
	if(is_file($file) && is_readable($file)) {
		$markdown = file_get_contents($file);
		$markdown = preg_replace('|!\[(.*?)\]\((.*?)\)|','![$1]('.trailingslashit(ABSPATH).'wp-content/mu-plugins/cf-widget-upgrade/$2)',$markdown);
		return $markdown;
	}
	return null;
}

?>