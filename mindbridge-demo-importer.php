<?php
/**
 * Plugin Name:       MindBridge Demo Importer
 * Plugin URI:        https://zealousweb.com
 * Description:       One Click Demo Import configuration and logic for the MindBridge theme.
 * Version:           1.0.0
 * Author:            ZealousWeb
 * Author URI:        https://zealousweb.com
 * Text Domain:       mindbridge-demo-importer
 *
 * @package MindBridge_Demo_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin version and path constants.
 */
if ( ! defined( 'MINDBRIDGE_DEMO_IMPORTER_VERSION' ) ) {
	define( 'MINDBRIDGE_DEMO_IMPORTER_VERSION', '1.0.0' );
}

if ( ! defined( 'MINDBRIDGE_DEMO_IMPORTER_PATH' ) ) {
	define( 'MINDBRIDGE_DEMO_IMPORTER_PATH', plugin_dir_path( __FILE__ ) );
}

// Include required logic modules.
require_once MINDBRIDGE_DEMO_IMPORTER_PATH . 'includes/demo-config.php';
require_once MINDBRIDGE_DEMO_IMPORTER_PATH . 'includes/before-import.php';
require_once MINDBRIDGE_DEMO_IMPORTER_PATH . 'includes/after-import.php';

/**
 * Display admin notice if Required OCDI plugin is missing.
 *
 * @since 1.0.0
 */
function mindbridge_ocdi_missing_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! class_exists( 'OCDI_Plugin' ) ) {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'MindBridge Demo Importer requires the One Click Demo Import plugin to be installed and activated.', 'mindbridge-demo-importer' )
		);
	}
}
add_action( 'admin_notices', 'mindbridge_ocdi_missing_notice' );

/**
 * Register demo import files for One Click Demo Import.
 *
 * @return array Bundled import file configurations.
 * @since  1.0.0
 */
function mindbridge_ocdi_import_files() {
	if ( ! class_exists( 'OCDI_Plugin' ) ) {
		return array();
	}

	$config = mindbridge_get_demo_config();

	$import = array(
		'import_file_name'         => esc_html__( 'MindBridge Main Demo', 'mindbridge-demo-importer' ),
		'import_file_url'          => $config['urls']['content'],
		'import_preview_image_url' => $config['files']['preview'],
		'preview_url'              => home_url( '/' ),
		'import_notice'            => esc_html__( 'Please wait while the demo is imported.', 'mindbridge-demo-importer' ),
	);

	// Check if widgets file exists and is valid before adding it to import list.
	if ( file_exists( $config['files']['widgets'] ) && filesize( $config['files']['widgets'] ) > 50 ) {
		$import['import_widget_file_url'] = $config['urls']['widgets'];
	}

	return array( $import );
}
add_filter( 'ocdi/import_files', 'mindbridge_ocdi_import_files' );

/**
 * Only show the OCDI import page if the MindBridge theme is currently active.
 *
 * @param  bool $show Whether to show the import page.
 * @return bool
 * @since  1.0.0
 */
function mindbridge_ocdi_show_page( $show ) {
	return ( wp_get_theme()->get( 'TextDomain' ) === 'mindbridge' );
}
add_filter( 'ocdi/show_import_page', 'mindbridge_ocdi_show_page' );
