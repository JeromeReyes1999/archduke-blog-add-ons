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
    $read_time = round( $word_count / $words_per_minute, 2 );

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
        $read_time = round( $word_count / $words_per_minute, 2 );

        update_post_meta( $post_id, 'read_time', $read_time );
    }
}
register_activation_hook( __FILE__, 'archduke_blog_add_ons_backfill_read_time' );

function register_custom_templates() {
    $base_dir = plugin_dir_path(__FILE__) . 'templates/';
    $current_theme = wp_get_theme()->get_stylesheet();

    $theme_dir = trailingslashit($base_dir . $current_theme);

    if (!is_dir($theme_dir)) return;

    $iterator = new DirectoryIterator($theme_dir);

    foreach ($iterator as $file) {
        if ($file->isDot() || !$file->isFile() || $file->getExtension() !== 'html') continue;

        $template_slug = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $theme_slug = $current_theme;

        $exists = get_posts([
            'post_type'      => 'wp_template',
            'post_status'    => 'publish',
            'name'           => $template_slug,
            'tax_query'      => [[
                'taxonomy' => 'wp_theme',
                'field'    => 'name',
                'terms'    => $theme_slug,
            ]],
            'posts_per_page' => 1,
        ]);

        if (!empty($exists)) continue;

        wp_insert_post([
            'post_title'   => ucwords(str_replace(['-', '_'], ' ', $template_slug)),
            'post_name'    => $template_slug,
            'post_type'    => 'wp_template',
            'post_status'  => 'publish',
            'post_content' => file_get_contents($file->getPathname()),
            'tax_input'    => [ 'wp_theme' => [ $theme_slug ] ],
        ]);
    }
}
add_action('init', 'register_custom_templates');