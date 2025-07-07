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
    $current_theme = wp_get_theme()->get_stylesheet();

    $theme_dir = plugin_dir_path( __FILE__ ) . 'themes/' . $current_theme . '/templates/';

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

function archduke_apply_custom_theme_style( $theme_json ) {
	$theme_slug = wp_get_theme()->get_stylesheet();
	$variation_path = plugin_dir_path( __FILE__ ) . 'themes/' . $theme_slug . '/theme.json';

	if ( ! file_exists( $variation_path ) ) {
		return $theme_json;
	}

	$variation_json = file_get_contents( $variation_path );
	$variation_data = json_decode( $variation_json, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		error_log( 'Invalid JSON in style variation.' );
		return $theme_json;
	}

	// Resolve plugin_file: paths
	$plugin_url = plugin_dir_url( __FILE__ );
	if ( isset( $variation_data['settings']['typography']['fontFamilies'] ) ) {
		foreach ( $variation_data['settings']['typography']['fontFamilies'] as &$font ) {
			if ( isset( $font['fontFace'] ) ) {
				foreach ( $font['fontFace'] as &$face ) {
					if ( isset( $face['src'] ) && is_array( $face['src'] ) ) {
						foreach ( $face['src'] as &$src ) {
							if ( str_starts_with( $src, 'plugin_file:./' ) ) {
								$relative_path = substr( $src, strlen( 'plugin_file:./' ) );
								$src = $plugin_url . ltrim( $relative_path, '/' );
							}
						}
					}
				}
			}
		}
	}

	$theme_json->update_with( $variation_data );

	return $theme_json;
}

add_action( 'after_setup_theme', function () {
	add_filter( 'wp_theme_json_data_theme', 'archduke_apply_custom_theme_style' );
} );

function my_custom_styles() {
    $theme_slug = wp_get_theme()->get_stylesheet();
    wp_enqueue_style(
        'my-custom-style',
        plugin_dir_url(__FILE__) . 'themes/' . $theme_slug . '/custom-style.css'
    );
}
add_action('wp_enqueue_scripts', 'my_custom_styles');
add_action('enqueue_block_editor_assets', 'my_custom_styles');

function parse_pattern_metadata($file_path) {
    $contents = file_get_contents($file_path);
    $metadata = [];

    if (preg_match('#/\*\*(.*?)\*/#s', $contents, $match)) {
        $raw_header = trim($match[1]);
        $lines = explode("\n", $raw_header);

        foreach ($lines as $line) {
            $line = trim($line, " *\t\n\r\0\x0B");
            if (strpos($line, ':') !== false) {
                [$key, $value] = array_map('trim', explode(':', $line, 2));
                $metadata[strtolower($key)] = $value;
            }
        }
    }

    return $metadata;
}

add_action('init', function () {
    $theme_slug   = wp_get_theme()->get_stylesheet();
    $patterns_dir = plugin_dir_path(__FILE__) . 'themes/' . $theme_slug . '/patterns/';

    if (!is_dir($patterns_dir)) return;

    foreach (new DirectoryIterator($patterns_dir) as $file) {
        if ($file->isDot() || !$file->isFile() || $file->getExtension() !== 'php') continue;

        $file_path = $file->getPathname();

        $headers = get_file_data($file_path, [
            'title'         => 'Title',
            'slug'          => 'Slug',
            'categories'    => 'Categories',
        ]);

        if (empty($headers['slug']) || empty($headers['title'])) continue;

        unregister_block_pattern($headers['slug']);

        ob_start();
        include $file_path;
        $pattern_content = ob_get_clean();

        register_block_pattern($headers['slug'], [
            'title'         => $headers['title'],
            'slug'          => $headers['slug'],
            'categories'    => array_map('trim', explode(',', $headers['categories'] ?? 'uncategorized')),
            'content'       => $pattern_content
        ]);
    }
});
// Register the rewrite rule on init
add_action('init', 'myplugin_register_image_rewrite');
function myplugin_register_image_rewrite() {
    add_rewrite_rule(
        '^([^/]+\.(jpg|jpeg|png|gif|webp))$',
        'index.php?imported_file=$matches[1]',
        'top'
    );
}

// Register the custom query variable
add_filter('query_vars', function ($vars) {
    $vars[] = 'imported_file';
    return $vars;
});

// Serve the file from the /imported folder
add_action('template_redirect', function () {
    $filename = get_query_var('imported_file');
    if (!$filename) return;

    $upload_dir = wp_upload_dir();
    $filepath = trailingslashit($upload_dir['basedir']) . 'imported/' . basename($filename);

    if (!file_exists($filepath)) {
        status_header(404);
        echo 'File not found.';
        exit;
    }

    $mime = mime_content_type($filepath);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: public, max-age=604800');
    readfile($filepath);
    exit;
});

// Flush rewrite rules on plugin activation
register_activation_hook(__FILE__, 'myplugin_activate');
function myplugin_activate() {
    myplugin_register_image_rewrite(); // Ensure rule exists
    flush_rewrite_rules();
}

// Flush again on deactivation to clean up
register_deactivation_hook(__FILE__, 'myplugin_deactivate');
function myplugin_deactivate() {
    flush_rewrite_rules();
}

add_action('admin_init', function () {
    $upload_dir = wp_upload_dir();
    $ftp_dir = trailingslashit($upload_dir['basedir']) . 'ftp/';
    $imported_dir = trailingslashit($upload_dir['basedir']) . 'imported/';

    if (!is_dir($ftp_dir)) {
        return;
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'];

    $ftp_files = array_filter(scandir($ftp_dir), function ($file) use ($ftp_dir, $allowed_extensions) {
        if ($file === '.' || $file === '..' || !is_file($ftp_dir . $file)) return false;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, $allowed_extensions);
    });

    if (empty($ftp_files)) {
        return;
    }

    if (!is_dir($imported_dir)) {
        wp_mkdir_p($imported_dir);
    }

    global $wpdb;
    $existing = $wpdb->get_col("
        SELECT meta_value FROM {$wpdb->postmeta}
        WHERE meta_key = '_wp_attached_file' AND meta_value LIKE 'imported/%'
    ");
    $existing_flipped = array_flip($existing);

    foreach ($ftp_files as $file) {
        $ftp_path = $ftp_dir . $file;

        $base = pathinfo($file, PATHINFO_FILENAME);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $new_file = $file;
        $counter = 1;

        while (isset($existing_flipped['imported/' . $new_file])) {
            $new_file = $base . '-' . $counter . '.' . $ext;
            $counter++;
        }

        $target_path = $imported_dir . $new_file;

        if (!rename($ftp_path, $target_path)) {
            error_log('Failed to move file: ' . $file);
            continue;
        }

        $attachment = [
            'post_mime_type' => wp_check_filetype($new_file)['type'],
            'post_title' => sanitize_file_name($new_file),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $target_path);

        //--the code below creates more photo sizes and other things but it's very slow--
        // require_once(ABSPATH . 'wp-admin/includes/image.php');

        // $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
        // wp_update_attachment_metadata($attach_id, $attach_data);

        // update_post_meta($attach_id, '_wp_attached_file', 'imported/' . $new_file);

        error_log('Imported: ' . $new_file);
    }

    error_log('FTP import process completed.');
});
