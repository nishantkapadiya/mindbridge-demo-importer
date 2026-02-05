<?php
/**
 * Plugin Name:       MindBridge Demo Importer
 * Plugin URI:        https://zealousweb.com
 * Description:       One Click Demo Import integration for the MindBridge WordPress theme.
 * Version:           1.0.0
 * Author:            ZealousWeb
 * Author URI:        https://zealousweb.com
 * Text Domain:       mindbridge-demo-importer
 *
 * @package MindBridge_Demo_Importer
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * ------------------------------------------------------------------
 * Admin notice if OCDI plugin missing
 * ------------------------------------------------------------------
 */
function mindbridge_ocdi_missing_notice()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	if (!class_exists('OCDI_Plugin')) {
		echo '<div class="notice notice-warning is-dismissible"><p>';
		esc_html_e(
			'MindBridge Demo Importer requires the One Click Demo Import plugin to be installed and activated.',
			'mindbridge-demo-importer'
		);
		echo '</p></div>';
	}
}
add_action('admin_notices', 'mindbridge_ocdi_missing_notice');

/**
 * ------------------------------------------------------------------
 * Register demo import files
 * ------------------------------------------------------------------
 */
function mindbridge_ocdi_import_files()
{

	if (!class_exists('OCDI_Plugin')) {
		return array();
	}

	$base_path = plugin_dir_path(__FILE__) . 'demo/';
	$base_url = plugin_dir_url(__FILE__) . 'demo/';

	$import = array(
		'import_file_name' => esc_html__('MindBridge Main Demo', 'mindbridge-demo-importer'),
		'import_file_url' => $base_url . 'demo-content.xml',
		'import_preview_image_url' => $base_url . 'preview.png',
		'preview_url' => home_url('/'),
		'import_notice' => esc_html__('Please wait while the demo is imported.', 'mindbridge-demo-importer'),
	);

	$widget_file = $base_path . 'widgets.wie';
	if (file_exists($widget_file) && filesize($widget_file) > 50) {
		$import['import_widget_file_url'] = $base_url . 'widgets.wie';
	}

	return array($import);
}
add_filter('ocdi/import_files', 'mindbridge_ocdi_import_files');

/**
 * ------------------------------------------------------------------
 * WIPE EXISTING SITE DATA BEFORE DEMO IMPORT
 * ------------------------------------------------------------------
 */
function mindbridge_wipe_site_before_import()
{

	if (!current_user_can('manage_options')) {
		return;
	}

	// 1. Delete all posts, pages & CPTs
	$all_posts = get_posts(array(
		'post_type' => 'any',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
	));

	foreach ($all_posts as $post_id) {
		wp_delete_post($post_id, true);
	}

	// 2. Delete all media attachments
	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
	));

	foreach ($attachments as $attachment_id) {
		wp_delete_attachment($attachment_id, true);
	}

	// 3. Delete all menus
	$menus = wp_get_nav_menus();
	foreach ($menus as $menu) {
		wp_delete_nav_menu($menu->term_id);
	}

	// 4. Reset menu locations
	set_theme_mod('nav_menu_locations', array());

	// 5. Clear widgets
	update_option('sidebars_widgets', array());

	// 6. Reset Elementor data
	delete_option('elementor_active_kit');
	delete_option('elementor_global_css');

	// Clear Elementor files
	if (did_action('elementor/loaded') && class_exists('\Elementor\Plugin')) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	// 7. Flush rewrite rules
	flush_rewrite_rules();
}
add_action('ocdi/before_import', 'mindbridge_wipe_site_before_import');


/**
 * ------------------------------------------------------------------
 * After import setup (menus & front page)
 * ------------------------------------------------------------------
 */
function mindbridge_ocdi_after_import_setup()
{

	$menu = get_term_by('name', 'Primary Menu', 'nav_menu');
	if ($menu) {
		set_theme_mod(
			'nav_menu_locations',
			array(
				'primary' => (int) $menu->term_id,
			)
		);
	}

	$home = get_page_by_title('Home');
	if ($home) {
		update_option('show_on_front', 'page');
		update_option('page_on_front', (int) $home->ID);
	}
}
add_action('ocdi/after_import', 'mindbridge_ocdi_after_import_setup', 10);

/**
 * ------------------------------------------------------------------
 * Import Customizer Data
 * ------------------------------------------------------------------
 */
function mindbridge_import_customizer_data()
{

	if (wp_get_theme()->get('TextDomain') !== 'mindbridge') {
		return;
	}

	$file = plugin_dir_path(__FILE__) . 'demo/customizer.dat';
	if (!file_exists($file)) {
		return;
	}

	$data = maybe_unserialize(file_get_contents($file));
	if (!is_array($data)) {
		return;
	}

	if (!empty($data['mods'])) {
		foreach ($data['mods'] as $key => $value) {
			set_theme_mod(sanitize_key($key), $value);
		}
	}
}
add_action('ocdi/after_import', 'mindbridge_import_customizer_data', 15);

/**
 * ------------------------------------------------------------------
 * Import Elementor Site Settings (Kit)
 * ------------------------------------------------------------------
 */
function mindbridge_import_elementor_site_settings()
{

	if (!did_action('elementor/loaded')) {
		return;
	}

	$file = plugin_dir_path(__FILE__) . 'demo/site-settings.json';
	if (!file_exists($file)) {
		return;
	}

	$data = json_decode(file_get_contents($file), true);
	if (empty($data['settings'])) {
		return;
	}

	$kit_id = get_option('elementor_active_kit');
	if (!$kit_id) {
		return;
	}

	update_post_meta($kit_id, '_elementor_page_settings', $data['settings']);

	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
add_action('ocdi/after_import', 'mindbridge_import_elementor_site_settings', 25);

/**
 * ------------------------------------------------------------------
 * FORCE ELEMENTOR GLOBAL STYLES RE-SYNC
 * ------------------------------------------------------------------
 */
function mindbridge_force_elementor_globals_resync()
{

	if (!did_action('elementor/loaded')) {
		return;
	}

	$args = array(
		'post_type' => 'any',
		'post_status' => array('publish', 'draft'),
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => '_elementor_data',
				'compare' => 'EXISTS',
			),
		),
		'fields' => 'ids',
	);

	$posts = get_posts($args);

	foreach ($posts as $post_id) {

		$data = get_post_meta($post_id, '_elementor_data', true);
		if (empty($data)) {
			continue;
		}

		$decoded = json_decode($data, true);
		if (!is_array($decoded)) {
			continue;
		}

		$decoded = mindbridge_remove_local_styles_recursive($decoded);

		update_post_meta($post_id, '_elementor_data', wp_json_encode($decoded));
		update_post_meta($post_id, '_elementor_edit_mode', 'builder');
		update_post_meta($post_id, '_elementor_version', ELEMENTOR_VERSION);
	}

	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
add_action('ocdi/after_import', 'mindbridge_force_elementor_globals_resync', 30);

/**
 * ------------------------------------------------------------------
 * Remove local Elementor widget styles recursively
 * ------------------------------------------------------------------
 */
function mindbridge_remove_local_styles_recursive($elements)
{

	foreach ($elements as &$element) {

		if (isset($element['settings'])) {

			$remove_keys = array(
				'color',
				'text_color',
				'title_color',
				'heading_color',
				'background_color',
				'button_text_color',
				'button_background_color',
				'typography',
				'font_family',
				'font_size',
				'font_weight',
				'line_height',
				'letter_spacing',
			);

			foreach ($remove_keys as $key) {
				unset($element['settings'][$key]);
			}
		}

		if (isset($element['elements']) && is_array($element['elements'])) {
			$element['elements'] = mindbridge_remove_local_styles_recursive($element['elements']);
		}
	}

	return $elements;
}

/**
 * ------------------------------------------------------------------
 * Show OCDI page only for MindBridge theme
 * ------------------------------------------------------------------
 */
function mindbridge_ocdi_show_page()
{
	return wp_get_theme()->get('TextDomain') === 'mindbridge';
}
add_filter('ocdi/show_import_page', 'mindbridge_ocdi_show_page');


/**
 * ------------------------------------------------------------------
 * Import Fluent Forms
 * ------------------------------------------------------------------
 */
function mindbridge_import_fluent_forms()
{

	if (!function_exists('wpFluent')) {
		return;
	}

	$file = plugin_dir_path(__FILE__) . 'demo/fluent-forms.json';
	if (!file_exists($file)) {
		return;
	}

	$forms = json_decode(file_get_contents($file), true);
	if (empty($forms) || !is_array($forms)) {
		return;
	}

	foreach ($forms as $form) {

		if (empty($form['title']) || empty($form['form_fields'])) {
			continue;
		}

		$exists = wpFluent()
			->table('fluentform_forms')
			->where('title', sanitize_text_field($form['title']))
			->first();

		if ($exists) {
			continue;
		}

		$insert_data = array(
			'title' => sanitize_text_field($form['title']),
			'form_fields' => wp_json_encode($form['form_fields']),
			'status' => isset($form['status']) ? sanitize_text_field($form['status']) : 'published',
			'has_payment' => isset($form['has_payment']) ? intval($form['has_payment']) : 0,
			'type' => isset($form['type']) ? sanitize_text_field($form['type']) : 'form',
			'appearance_settings' => isset($form['appearance_settings']) ? wp_json_encode($form['appearance_settings']) : '',
			'created_at' => current_time('mysql'),
			'updated_at' => current_time('mysql'),
		);

		// Check if 'settings' column exists in the table to avoid SQL error.
		global $wpdb;
		$table_name = $wpdb->prefix . 'fluentform_forms';
		$column_check = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `$table_name` LIKE %s", 'settings'));

		if (!empty($column_check)) {
			// In some versions of Fluent Forms, settings are stored in the main table
			$insert_data['settings'] = isset($form['settings']) ? wp_json_encode($form['settings']) : '';
		}

		$form_id = wpFluent()->table('fluentform_forms')->insertGetId($insert_data);

		// Handle Form Meta (Check both 'form_meta' and 'metas' keys from the JSON)
		$meta_data = array();
		if (!empty($form['form_meta']) && is_array($form['form_meta'])) {
			$meta_data = $form['form_meta'];
		} elseif (!empty($form['metas']) && is_array($form['metas'])) {
			$meta_data = $form['metas'];
		}

		if ($form_id && !empty($meta_data)) {
			foreach ($meta_data as $meta) {
				if (empty($meta['meta_key'])) {
					continue;
				}

				wpFluent()->table('fluentform_form_meta')->insert([
					'form_id' => $form_id,
					'meta_key' => sanitize_text_field($meta['meta_key']),
					'value' => is_array($meta['value']) ? wp_json_encode($meta['value']) : $meta['value'],
				]);
			}
		}
	}
}
add_action('ocdi/after_import', 'mindbridge_import_fluent_forms', 20);

add_action( 'pt-ocdi/after_import', 'mytheme_set_elementor_global_settings' );
function mytheme_set_elementor_global_settings() {

    // Disable Default Colors
    update_option( 'elementor_disable_color_schemes', 'yes' );

    // Disable Default Fonts
    update_option( 'elementor_disable_typography_schemes', 'yes' );

}