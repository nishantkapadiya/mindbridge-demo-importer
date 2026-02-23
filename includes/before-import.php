<?php
/**
 * All logic executed before the demo import starts.
 *
 * This mostly handles wiping the site's existing content to ensure
 * a clean import process.
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
 * Wipe all existing content, media, menus, and specific meta before import.
 *
 * @since 1.0.0
 */
function mindbridge_wipe_site_before_import() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;

	// 1. Delete all posts, pages & CPTs (including Trash & Private).
	// We verify IDs directly to ensure we don't miss anything.
	$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment', 'revision', 'nav_menu_item')" );

	if ( ! empty( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	// 2. Explicitly delete default "Hello World" (ID 1) and "Sample Page" if they exist.
	wp_delete_post( 1, true );
	wp_delete_post( 2, true ); // Often Sample Page.
	
	$sample_page = get_page_by_path( 'sample-page' );
	if ( $sample_page ) {
		wp_delete_post( $sample_page->ID, true );
	}

	// 3. Delete all media attachments.
	$attachment_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'" );
	if ( ! empty( $attachment_ids ) ) {
		foreach ( $attachment_ids as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}

	// 4. Delete all comments and metadata.
	$wpdb->query( "DELETE FROM {$wpdb->comments}" );
	$wpdb->query( "DELETE FROM {$wpdb->commentmeta}" );

	// 5. Delete all menus.
	$menus = wp_get_nav_menus();
	if ( ! empty( $menus ) && ! is_wp_error( $menus ) ) {
		foreach ( $menus as $menu ) {
			wp_delete_nav_menu( $menu->term_id );
		}
	}

	// 6. Reset menu locations.
	set_theme_mod( 'nav_menu_locations', array() );

	// 7. Clear widgets and sidebars.
	update_option( 'sidebars_widgets', array() );

	// 8. Reset Elementor specific data.
	delete_option( 'elementor_active_kit' );
	delete_option( 'elementor_global_css' );

	if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	// 9. Flush rewrite rules.
	flush_rewrite_rules();
}
// Use multiple hooks to ensure execution in various OCDI environments.
add_action( 'ocdi/before_import', 'mindbridge_wipe_site_before_import' );
add_action( 'ocdi/import_start', 'mindbridge_wipe_site_before_import' );

