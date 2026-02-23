<?php
/**
 * All logic executed after the demo data is successfully imported.
 *
 * This file handles setting up the home/blog pages, syncing menus,
 * importing customizer data, and configuring Elementor & Fluent Forms.
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
 * Basic setup after import (front page & blog page assignment).
 *
 * @since 1.0.0
 */
function mindbridge_ocdi_after_import_setup() {
	$home = get_posts(
		array(
			'title'          => 'Home',
			'post_type'      => 'page',
			'posts_per_page' => 1,
		)
	);
	$blog = get_posts(
		array(
			'title'          => 'Blog',
			'post_type'      => 'page',
			'posts_per_page' => 1,
		)
	);

	if ( ! empty( $home ) && ! empty( $blog ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $home[0]->ID );
		update_option( 'page_for_posts', (int) $blog[0]->ID );
	}
}
add_action( 'ocdi/after_import', 'mindbridge_ocdi_after_import_setup', 10 );

/**
 * Synchronize Customizer menu settings and blog category selectors with correct demo IDs.
 *
 * @since 1.0.0
 */
function mindbridge_sync_header_menu_after_customizer() {
	// Only run if the theme is active.
	if ( wp_get_theme()->get( 'TextDomain' ) !== 'mindbridge' ) {
		return;
	}

	// Remap Header Menu.
	$main_menu = get_term_by( 'slug', 'main-menu', 'nav_menu' );
	if ( $main_menu && isset( $main_menu->term_id ) ) {
		set_theme_mod( 'header_menu', (int) $main_menu->term_id );
	}

	// Remap Footer Menus.
	$footer_menu_1 = get_term_by( 'slug', 'footer-menu-1', 'nav_menu' );
	if ( $footer_menu_1 && isset( $footer_menu_1->term_id ) ) {
		set_theme_mod( 'footer_menu_quick_links', (int) $footer_menu_1->term_id );
	}

	$footer_menu_2 = get_term_by( 'slug', 'footer-menu-2', 'nav_menu' );
	if ( $footer_menu_2 && isset( $footer_menu_2->term_id ) ) {
		set_theme_mod( 'footer_menu_patient_links', (int) $footer_menu_2->term_id );
	}

	$utility_menu = get_term_by( 'slug', 'utility-menu', 'nav_menu' );
	if ( $utility_menu && isset( $utility_menu->term_id ) ) {
		set_theme_mod( 'footer_menu_utility', (int) $utility_menu->term_id );
	}

	// Remap Blog Category Selector if empty or invalid.
	$current_cat = get_theme_mod( 'post_category_selector' );
	$needs_fix   = false;

	if ( empty( $current_cat ) ) {
		$needs_fix = true;
	} else {
		$term = get_term( (int) $current_cat, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			$needs_fix = true;
		}
	}

	if ( $needs_fix ) {
		$demo_cat = get_term_by( 'slug', 'mental-health-tips', 'category' );
		if ( $demo_cat && ! is_wp_error( $demo_cat ) ) {
			set_theme_mod( 'post_category_selector', (int) $demo_cat->term_id );
		}
	}
}
add_action( 'ocdi/after_import', 'mindbridge_sync_header_menu_after_customizer', 40 );

/**
 * Prevent duplicate menu items that can occasionally occur during XML imports.
 *
 * @since 1.0.0
 */
function mindbridge_dedupe_menu_items_after_import() {
	if ( wp_get_theme()->get( 'TextDomain' ) !== 'mindbridge' ) {
		return;
	}

	$menus = wp_get_nav_menus();
	if ( empty( $menus ) ) {
		return;
	}

	$demo_menu_slugs = array(
		'main-menu',
		'footer-menu-1',
		'footer-menu-2',
		'utility-menu',
	);

	foreach ( $menus as $menu ) {
		if ( empty( $menu->slug ) || ! in_array( $menu->slug, $demo_menu_slugs, true ) ) {
			continue;
		}

		$items = wp_get_nav_menu_items(
			$menu->term_id,
			array(
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			)
		);

		if ( empty( $items ) ) {
			continue;
		}

		$seen = array();
		foreach ( $items as $item ) {
			$title = isset( $item->title ) ? trim( wp_strip_all_tags( $item->title ) ) : '';
			$key   = strtolower( $title );

			if ( isset( $seen[ $key ] ) ) {
				wp_delete_post( $item->ID, true );
			} else {
				$seen[ $key ] = true;
			}
		}
	}
}
add_action( 'ocdi/after_import', 'mindbridge_dedupe_menu_items_after_import', 45 );

/**
 * Import Customizer settings from the bundled .dat file.
 *
 * @since 1.0.0
 */
function mindbridge_import_customizer_data() {
	if ( wp_get_theme()->get( 'TextDomain' ) !== 'mindbridge' ) {
		return;
	}

	$config = mindbridge_get_demo_config();
	$file   = $config['files']['customizer'];
	if ( ! file_exists( $file ) ) {
		return;
	}

	// Fetch file content.
	$raw_data = file_get_contents( $file );
	if ( ! $raw_data ) {
		return;
	}

	$data = maybe_unserialize( $raw_data );
	if ( ! is_array( $data ) ) {
		return;
	}

	if ( ! empty( $data['mods'] ) ) {
		$old_url = untrailingslashit( $config['old_url'] );
		$new_url = untrailingslashit( home_url() );

		foreach ( $data['mods'] as $key => $value ) {
			if ( is_string( $value ) ) {
				$value = str_replace( $old_url . '/', $new_url . '/', $value );
				$value = str_replace( $old_url, $new_url, $value );
			}
			// sanitize_key handles name safety; set_theme_mod handles DB safety.
			set_theme_mod( sanitize_key( $key ), $value );
		}
	}
}
add_action( 'ocdi/after_import', 'mindbridge_import_customizer_data', 15 );

/**
 * Import Elementor Kit (Site Settings) meta data.
 *
 * @since 1.0.0
 */
function mindbridge_import_elementor_site_settings() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}

	$config = mindbridge_get_demo_config();
	$file   = $config['files']['site_settings'];
	if ( ! file_exists( $file ) ) {
		return;
	}

	$content = file_get_contents( $file );
	if ( ! $content ) {
		return;
	}

	$data = json_decode( $content, true );
	if ( empty( $data['settings'] ) ) {
		return;
	}

	$kit_id = get_option( 'elementor_active_kit' );
	if ( ! $kit_id ) {
		return;
	}

	update_post_meta( $kit_id, '_elementor_page_settings', wp_slash( $data['settings'] ) );

	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}
add_action( 'ocdi/after_import', 'mindbridge_import_elementor_site_settings', 25 );

/**
 * Force specific Elementor meta updates and clear caches to ensure styles apply correctly.
 *
 * @since 1.0.0
 */
function mindbridge_force_elementor_globals_resync() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}

	$args = array(
		'post_type'      => 'any',
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_elementor_data',
				'compare' => 'EXISTS',
			),
		),
		'fields'         => 'ids',
	);

	$posts = get_posts( $args );

	foreach ( $posts as $post_id ) {
		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! empty( $data ) ) {
			// Helper to fix potential encoding issues with apostrophes in Elementor JSON.
			$data = preg_replace( '/(?<!\\\\)u([0-9a-fA-F]{4})/', '\\\\u$1', $data );
			update_post_meta( $post_id, '_elementor_data', wp_slash( $data ) );
		}

		$settings = get_post_meta( $post_id, '_elementor_page_settings', true );
		if ( ! empty( $settings ) ) {
			update_post_meta( $post_id, '_elementor_page_settings', wp_slash( $settings ) );
		}

		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
		}
	}

	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}
add_action( 'ocdi/after_import', 'mindbridge_force_elementor_globals_resync', 30 );

/**
 * Import bundled Fluent Forms.
 *
 * @since 1.0.0
 */
function mindbridge_import_fluent_forms() {
	if ( ! function_exists( 'wpFluent' ) ) {
		return;
	}

	$config = mindbridge_get_demo_config();
	$file   = $config['files']['fluent_forms'];
	if ( ! file_exists( $file ) ) {
		return;
	}

	$forms = json_decode( file_get_contents( $file ), true );
	if ( empty( $forms ) || ! is_array( $forms ) ) {
		return;
	}

	global $wpdb;

	foreach ( $forms as $form ) {
		if ( empty( $form['title'] ) || empty( $form['form_fields'] ) ) {
			continue;
		}

		// Check if form already exists by title.
		$exists = wpFluent()
			->table( 'fluentform_forms' )
			->where( 'title', sanitize_text_field( $form['title'] ) )
			->first();

		if ( $exists ) {
			continue;
		}

		$insert_data = array(
			'title'               => sanitize_text_field( $form['title'] ),
			'form_fields'         => wp_json_encode( $form['form_fields'] ),
			'status'              => isset( $form['status'] ) ? sanitize_text_field( $form['status'] ) : 'published',
			'has_payment'         => isset( $form['has_payment'] ) ? intval( $form['has_payment'] ) : 0,
			'type'                => isset( $form['type'] ) ? sanitize_text_field( $form['type'] ) : 'form',
			'appearance_settings' => isset( $form['appearance_settings'] ) ? wp_json_encode( $form['appearance_settings'] ) : '',
			'created_at'          => current_time( 'mysql' ),
			'updated_at'          => current_time( 'mysql' ),
		);

		$table_name   = $wpdb->prefix . 'fluentform_forms';
		$column_check = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `$table_name` LIKE %s", 'settings' ) );

		if ( ! empty( $column_check ) ) {
			$insert_data['settings'] = isset( $form['settings'] ) ? wp_json_encode( $form['settings'] ) : '';
		}

		$form_id = wpFluent()->table( 'fluentform_forms' )->insertGetId( $insert_data );

		if ( $form_id ) {
			$meta_data = array();
			if ( ! empty( $form['form_meta'] ) && is_array( $form['form_meta'] ) ) {
				$meta_data = $form['form_meta'];
			} elseif ( ! empty( $form['metas'] ) && is_array( $form['metas'] ) ) {
				$meta_data = $form['metas'];
			}

			foreach ( $meta_data as $meta ) {
				if ( empty( $meta['meta_key'] ) ) {
					continue;
				}

				wpFluent()->table( 'fluentform_form_meta' )->insert(
					array(
						'form_id'  => $form_id,
						'meta_key' => sanitize_text_field( $meta['meta_key'] ),
						'value'    => is_array( $meta['value'] ) ? wp_json_encode( $meta['value'] ) : $meta['value'],
					)
				);
			}
		}
	}
}
add_action( 'ocdi/after_import', 'mindbridge_import_fluent_forms', 20 );

/**
 * Configure Elementor to use global styles by disabling default schemes.
 *
 * @since 1.0.0
 */
function mindbridge_set_elementor_global_settings() {
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
}
add_action( 'ocdi/after_import', 'mindbridge_set_elementor_global_settings' );

/**
 * Safety Net: Explicitly remove default WordPress content if it still exists.
 *
 * @since 1.0.0
 */
function mindbridge_cleanup_default_content() {
	global $wpdb;

	// 1. Delete default comments & meta (removes "A WordPress Commenter").
	$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_author = 'A WordPress Commenter' OR comment_content LIKE '%Hi, this is a comment.%'" );
	$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})" );

	// 2. Delete default posts/pages by slug.
	$defaults = array(
		'hello-world'    => 'post',
		'sample-page'    => 'page',
		'privacy-policy' => 'page',
	);

	foreach ( $defaults as $slug => $type ) {
		$page = get_page_by_path( $slug, OBJECT, $type );
		if ( $page ) {
			wp_delete_post( $page->ID, true );
		}
	}
}
add_action( 'ocdi/after_import', 'mindbridge_cleanup_default_content', 5 );

/**
 * Comprehensive URL replacement targeting posts, meta (including Elementor JSON), and options.
 *
 * @since 1.0.0
 */
function mindbridge_ocdi_replace_urls() {
	global $wpdb;

	$config  = mindbridge_get_demo_config();
	$old_url = untrailingslashit( $config['old_url'] );
	$new_url = untrailingslashit( home_url() );

	if ( $old_url === $new_url ) {
		return;
	}

	// Prepare Escaped versions for JSON (Elementor).
	$old_url_escaped = str_replace( '/', '\/', $old_url );
	$new_url_escaped = str_replace( '/', '\/', $new_url );

	$replacements = array(
		$old_url . '/'         => $new_url . '/',
		$old_url               => $new_url,
		$old_url_escaped . '\/' => $new_url_escaped . '\/',
		$old_url_escaped       => $new_url_escaped,
	);

	foreach ( $replacements as $search => $replace ) {
		// 1. Posts Content.
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $search, $replace ) );

		// 2. Post Meta (Elementor and general).
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_value NOT LIKE 's:%%' OR meta_key = '_elementor_data'", $search, $replace ) );

		// 3. Options.
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, %s, %s) WHERE option_value NOT LIKE 's:%%'", $search, $replace ) );
	}
}
add_action( 'ocdi/after_import', 'mindbridge_ocdi_replace_urls', 70 );



/**
 * Update the specific "Subscription Form" Fluent Form ID to match demo configuration.
 *
 * @since 1.0.0
 */
function mindbridge_update_default_fluent_form() {
	if ( ! function_exists( 'wpFluent' ) ) {
		return;
	}

	$config = mindbridge_get_demo_config();
	$file   = $config['files']['fluent_forms'];
	if ( ! file_exists( $file ) ) {
		return;
	}

	$forms = json_decode( file_get_contents( $file ), true );
	if ( empty( $forms ) || ! is_array( $forms ) ) {
		return;
	}

	$form_id   = 2;
	$form_name = 'Subscription Form';

	$existing_form = wpFluent()->table( 'fluentform_forms' )->where( 'id', $form_id )->first();
	if ( ! $existing_form || $existing_form->title !== $form_name ) {
		return;
	}

	foreach ( $forms as $form ) {
		if ( empty( $form['title'] ) || $form['title'] !== $form_name ) {
			continue;
		}

		$update_data = array(
			'title'       => sanitize_text_field( $form['title'] ),
			'form_fields' => wp_json_encode( $form['form_fields'] ),
			'updated_at'  => current_time( 'mysql' ),
		);

		if ( isset( $form['appearance_settings'] ) ) {
			$update_data['appearance_settings'] = wp_json_encode( $form['appearance_settings'] );
		}

		if ( isset( $form['settings'] ) ) {
			$update_data['settings'] = wp_json_encode( $form['settings'] );
		}

		wpFluent()->table( 'fluentform_forms' )->where( 'id', $form_id )->update( $update_data );

		if ( ! empty( $form['form_meta'] ) && is_array( $form['form_meta'] ) ) {
			wpFluent()->table( 'fluentform_form_meta' )->where( 'form_id', $form_id )->delete();

			foreach ( $form['form_meta'] as $meta ) {
				if ( empty( $meta['meta_key'] ) ) {
					continue;
				}

				wpFluent()->table( 'fluentform_form_meta' )->insert(
					array(
						'form_id'  => $form_id,
						'meta_key' => sanitize_text_field( $meta['meta_key'] ),
						'value'    => is_array( $meta['value'] ) ? wp_json_encode( $meta['value'] ) : $meta['value'],
					)
				);
			}
		}
		break;
	}
}
add_action( 'ocdi/after_import', 'mindbridge_update_default_fluent_form', 35 );

/**
 * Assign branding assets (logos, favicons, and fallback images) from imported media.
 *
 * @since 1.0.0
 */
function mindbridge_set_demo_branding_and_fallbacks() {
	if ( wp_get_theme()->get( 'TextDomain' ) !== 'mindbridge' ) {
		return;
	}

	// Dynamic branding mapping (Theme Mods).
	$branding_urls = array(
		'header_logo'                => 'MindBridge-Logo-1',
		'appointment_button_icon'    => 'header-button-icon',
		'footer_logo'                => 'MindBridge-Logo-1',
		'mobile_logo'                => 'mobile-icon-mb',
		'news_fallback_thumbnail'    => 'news-thumbnail',
		'press_fallback_thumbnail'   => 'press-thumbnail',
		'event_fallback_thumbnail'   => 'event-thumbnail',
		'webinar_fallback_thumbnail' => 'event-thumbnail',
		'category_filter_icon'       => 'Category',
		'audience_filter_icon'       => 'Audience',
		'therapy_filter_icon'        => 'Therapy-Type',
		'error_404_image'            => '404',
	);

	foreach ( $branding_urls as $theme_mod => $image_title ) {
		$image = get_posts(
			array(
				'title'          => $image_title,
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $image ) ) {
			set_theme_mod( $theme_mod, wp_get_attachment_url( $image[0] ) );
		}
	}

	// Favicon (Site Icon).
	$favicon = get_posts(
		array(
			'title'          => 'favicon',
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $favicon ) ) {
		update_option( 'site_icon', $favicon[0] );
	}
}
add_action( 'ocdi/after_import', 'mindbridge_set_demo_branding_and_fallbacks', 60 );
