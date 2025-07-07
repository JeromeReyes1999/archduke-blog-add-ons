<?php
$main_post = get_posts([
  'numberposts' => 1,
  'post_status' => 'publish',
])[0] ?? null;

if (!$main_post) {
  echo '<p>No posts found.</p>';
  return;
}
?>

<div <?php echo get_block_wrapper_attributes(); ?>>
	<div class="main-post">
		<!-- Row 1: Featured Image -->
		<div class="row row-1">
			<?php if ( has_post_thumbnail( $main_post->ID ) ) : ?>
			<div class="featured-img-container">
				<?php 
					$image_url = get_the_post_thumbnail_url( $main_post->ID, 'large' );

					if ( $image_url ) {
						echo '<img class="featured-img" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( get_the_title( $main_post->ID ) ) . '" />';
					} ?>
			</div>
			<?php endif; ?>
		</div>

		<!-- Row 2: Categories + Title, Date, Excerpt -->
		<div class="row row-2">
			<div class="col categories">
				<?php
					$categories = get_the_category( $main_post->ID );
					if ( ! empty( $categories ) ) {
					echo '<ul class="category-badges has-text-color has-superbfont-xxsmall-font-size">';
					foreach ( $categories as $category ) {
						echo '<li class="category-badge">' . esc_html( $category -> name ) . '</li>';
					}
					echo '</ul>';
					}
				?>
			</div>

			<div class="col content">
				<h2 class="wp-block-heading title has-text-align-left has-primary-color has-superbfont-large-font-size">
					<a href="<?php echo esc_url( get_permalink( $main_post->ID ) ); ?>">
					<?php echo esc_html( get_the_title( $main_post->ID ) ); ?>
					</a>
				</h2>
				<div class="date has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size"><?php echo esc_html( get_the_date( '', $main_post->ID ) ); ?></div>
				<?php
                    $read_time = get_post_meta($main_post->ID, 'read_time', true);
					if ($read_time !== '') {
						$rounded = round(floatval($read_time), 0);
						echo '<p class="read-time has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">' . esc_html($rounded < 1 ? '< 1' : $rounded) . ' min read</p>';
					}
				?>
				<div class="excerpt">
                    <?php  $excerpt = wp_kses_post(str_replace( '[&hellip;]', '', get_the_excerpt( $main_post->ID )));
                        echo '<p class="has-text-align-left has-mono-2-color has-text-color has-superbfont-xsmall-font-size">' . $excerpt . '...  <a  class="read-more-link" href="' . esc_url( get_permalink( $main_post->ID ) ) . '">Read more →</a></p>';
                    ?>
                </div>
			</div>
		</div>
	</div>
</div>