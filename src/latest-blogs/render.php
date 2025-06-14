<?php
$posts = get_posts( [
  'numberposts' => 1,
  'post_status' => 'publish',
] );

if ( empty( $posts ) ) {
  echo '<p>No posts found.</p>';
  return;
}

$post = $posts[0];
?>

<div <?php echo get_block_wrapper_attributes(); ?> class="custom-post-layout">
  <!-- Row 1: Featured Image -->
  <div class="row row-1">
    <?php if ( has_post_thumbnail( $post->ID ) ) : ?>
      <div class="featured-img-container">
        <?php 
			$image_url = get_the_post_thumbnail_url( $post->ID, 'large' );

			if ( $image_url ) {
				echo '<img class="featured-img" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( get_the_title( $post->ID ) ) . '" />';
			} ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Row 2: Categories + Title, Date, Excerpt -->
  <div class="row row-2">
    <div class="col categories">
      <?php
        $categories = get_the_category( $post->ID );
        if ( ! empty( $categories ) ) {
          echo '<ul>';
          foreach ( $categories as $category ) {
            echo '<li>' . esc_html( $category -> name ) . '</li>';
          }
          echo '</ul>';
        }
      ?>
    </div>

    <div class="col content">
      <h2 class="title">
        <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
          <?php echo esc_html( get_the_title( $post->ID ) ); ?>
        </a>
      </h2>
      <div class="date"><?php echo esc_html( get_the_date( '', $post->ID ) ); ?></div>
      <div class="excerpt"><?php echo wp_kses_post( get_the_excerpt( $post->ID ) ); ?></div>
    </div>
  </div>
</div>