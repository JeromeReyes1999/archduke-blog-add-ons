<?php
$paged = max(1, get_query_var('paged'));
$per_page = 3;
$offset = ($paged - 1) * $per_page + 1;

$more_posts = get_posts([
  'numberposts' => $per_page,
  'offset'      => $offset,
  'post_status' => 'publish',
]);

update_meta_cache('post', wp_list_pluck($more_posts, 'ID'));

$total_posts = wp_count_posts()->publish - 1;
$total_pages = ceil($total_posts / $per_page);
?>

<div <?php echo get_block_wrapper_attributes(); ?>>
	<div class="more-posts">
		<h2 class='grid-title wp-block-heading'>Discover More</h2>
		<div class="post-grid">
		<?php foreach ($more_posts as $post): setup_postdata($post); ?>
			<article class="post-card">
			<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
				<div class="post-thumb">
					<?php 
						$image_url = get_the_post_thumbnail_url( $post->ID, 'large' );

						if ( $image_url ) {
							echo '<img class="featured-img" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( get_the_title( $post->ID ) ) . '" />';
						} ?>
				</div>

				<div class="post-body">
					<div class="categories has-text-color has-superbfont-xxsmall-font-size">
						<?php
							$categories = get_the_category( $post->ID );
							if ( ! empty( $categories ) ) {
							echo '<ul class="category-badges">';
							foreach ( $categories as $category ) {
								echo '<li class="category-badge">' . esc_html( $category -> name ) . '</li>';
							}
							echo '</ul>';
							}
						?>
					</div>
					<h2 class="wp-block-heading title has-primary-color has-superbfont-medium-font-size"><?php echo get_the_title( $post->ID ) ?></h2>
					<p class="date has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">
						<?php echo esc_html( get_the_date( '', $post->ID ) ); ?>
					</p>
					<?php
						$read_time = get_post_meta($post->ID, 'read_time', true);
						if ($read_time !== '') {
							$rounded = round(floatval($read_time), 0);
							echo '<p class="read-time has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">' . esc_html($rounded < 1 ? '< 1' : $rounded) . ' min read</p>';
						}
					?>
					<p class="excerpt has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">
						<?php echo wp_kses_post( wp_trim_words( get_the_excerpt( $post->ID ), 20) ) ?>
					</p>
				</div>
			</a>
			</article>
		<?php endforeach; wp_reset_postdata(); ?>
		</div>

		<?php
			echo paginate_links([
				'total'   => $total_pages,
				'current' => $paged,
				'type'    => 'list',
				'prev_text' => '&lsaquo;',
				'next_text' => '&rsaquo;',
			]);
		?>
	</div>
</div>
