<?php
/**
 * Centralized Demo Configuration Manager.
 *
 * This file defines all paths and URLs for demo assets, ensuring
 * consistency across the plugin.
 *
 * @package    MindBridge_Demo_Importer
 * @author     ZealousWeb
 * @copyright  2026 ZealousWeb
 * @license    GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global configuration settings for the demo importer.
 *
 * @return array Demo configuration data.
 * @since  1.0.0
 */
function mindbridge_get_demo_config() {
	$base_path = plugin_dir_path( dirname( __FILE__ ) ) . 'demo/';
	$base_url  = plugin_dir_url( dirname( __FILE__ ) ) . 'demo/';

	return array(
		'old_url' => 'https://siteproofs.com/projects/mind-bridge/mindbridge-new/',
		'files'   => array(
			'content'       => $base_path . 'demo-content.xml',
			'customizer'    => $base_path . 'customizer.dat',
			'widgets'       => $base_path . 'widgets.wie',
			'site_settings' => $base_path . 'site-settings.json',
			'fluent_forms'  => $base_path . 'fluent-forms.json',
			'preview'       => $base_url . 'preview.png',
		),
		'urls'    => array(
			'content' => $base_url . 'demo-content.xml',
			'widgets' => $base_url . 'widgets.wie',
		),
	);
}
