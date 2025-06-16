<?php
/**
 * Plugin Name:       Archduke Blog Add Ons
 * Description:       A Plugin to customize archduke blog website.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       archduke-blog-add-ons
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Registers the block using a `blocks-manifest.php` file, which improves the performance of block type registration.
 * Behind the scenes, it also registers all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function create_block_archduke_blog_add_ons_block_init() {
	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
	 * based on the registered block metadata.
	 * Added in WordPress 6.8 to simplify the block metadata registration process added in WordPress 6.7.
	 *
	 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
	 */
	if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
		wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
		return;
	}

	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` file.
	 * Added to WordPress 6.7 to improve the performance of block type registration.
	 *
	 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
	 */
	if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
		wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
	}
	/**
	 * Registers the block type(s) in the `blocks-manifest.php` file.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 */
	$manifest_data = require __DIR__ . '/build/blocks-manifest.php';
	foreach ( array_keys( $manifest_data ) as $block_type ) {
		register_block_type( __DIR__ . "/build/{$block_type}" );
	}
}

add_action( 'init', 'create_block_archduke_blog_add_ons_block_init' );

function myplugin_register_read_time_meta() {
	register_post_meta( 'post', 'read_time', [
		'single'       => true,
		'type'         => 'string',
		'show_in_rest' => true,
		'auth_callback' => '__return_true',
	] );
}
add_action( 'init', 'myplugin_register_read_time_meta' );

function archduke_blog_add_ons_add_read_time_meta( $post_id ) {
    remove_action( 'save_post', 'archduke_blog_add_ons_add_read_time_meta' );

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'post' ) return;

    $word_count = str_word_count( strip_tags( $post->post_content ) );
    $words_per_minute = 200;
    $read_time = round( $word_count / $words_per_minute, 2 ); // decimal with 2 digits

    update_post_meta( $post_id, 'read_time', $read_time );

    add_action( 'save_post', 'archduke_blog_add_ons_add_read_time_meta' );
}
add_action( 'save_post', 'archduke_blog_add_ons_add_read_time_meta' );

function archduke_blog_add_ons_backfill_read_time() {
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    $posts = get_posts( $args );

    foreach ( $posts as $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) continue;

        $word_count = str_word_count( strip_tags( $post->post_content ) );
        $words_per_minute = 200;
        $read_time = round( $word_count / $words_per_minute, 2 ); // same here

        update_post_meta( $post_id, 'read_time', $read_time );
    }
}
register_activation_hook( __FILE__, 'archduke_blog_add_ons_backfill_read_time' );

function manage_block_templates( $query_result, $query, $template_type ) {
    $theme         = wp_get_theme();
    $template_dir  = plugin_dir_path( __FILE__ ) . 'templates';
    $template_files = glob( $template_dir . '*.html' );

    foreach ( $template_files as $file_path ) {
        $filename = basename( $file_path, '.html' );
        $contents = file_get_contents( $file_path );
        $contents = str_replace( '~theme~', $theme->stylesheet, $contents );

        $template = new WP_Block_Template();
        $template->type           =  'wp_template';
        $template->theme          = $theme->stylesheet;
        $template->slug           = $filename;
        $template->id             = "{$theme->stylesheet}//{$template_type}/{$filename}";
        $template->title          = ucwords( str_replace( '-', ' ', $filename ) );
        $template->description    = '';
        $template->status         = 'publish';
        $template->source         = 'custom';
        $template->has_theme_file = true;
        $template->is_custom      = true;
        $template->content        = $contents;

        $query_result[] = $template;
    }

    return $query_result;
}
add_filter( 'get_block_templates', 'manage_block_templates', 10, 3 );
