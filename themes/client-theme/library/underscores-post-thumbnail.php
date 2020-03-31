<?php

/**
 * Displays an optional post thumbnail.
 *
 * Wraps the post thumbnail in an anchor element on index views, or a div
 * element when on single views.
 *
 * This function is pluggable in the underscores theme so this is the override.
 *
 * The idea is that the thumbnail will be set to a value that comes from 'manage-images.php'.
 *
 * I still don't understand why this is better than the filter method by Barbara Ford in the code reference
 * @see https://developer.wordpress.org/reference/functions/the_post_thumbnail/#user-contributed-notes
 * Search for "Post Thumbnail Linking to the Post Permalink"
 *
 */
function underscores_post_thumbnail() {
	if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
		return;
	}

	if ( is_singular() ) :
		?>

		<div class="post-thumbnail">
			<?php the_post_thumbnail( 'rectangle-wide' ); ?>
		</div><!-- .post-thumbnail -->

	<?php else : ?>

	<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php
		the_post_thumbnail( 'post-thumbnail', array(
			'alt' => the_title_attribute( array(
				'echo' => false,
			) ),
		) );
		?>
	</a>

	<?php
	endif; // End is_singular().
}
