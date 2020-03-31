<?php
/**
 * Widgets class.
 *
 * @since 1.7.0
 *
 * @package Envira_Gallery
 * @author Envira Gallery Team
 */

namespace Envira\Widgets;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget Class
 */
class Widget extends \ WP_Widget {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		// Widget Name.
		$widget_name = __( 'Envira Gallery', 'envira-gallery' );
		$widget_name = apply_filters( 'envira_gallery_widget_name', $widget_name );

		// .
		$widget_ops = array(
			'classname'   => 'envira-gallery',
			'description' => __( 'Place an Envira gallery into a widgetized area.', 'envira-gallery' ),
		);
		$widget_ops = apply_filters( 'envira_gallery_widget_ops', $widget_ops );

		// Control Options.
		$control_ops = array(
			'id_base' => 'envira-gallery',
			'height'  => 350,
			'width'   => 225,
		);
		$control_ops = apply_filters( 'envira_gallery_widget_control_ops', $control_ops );

		// Init.
		parent::__construct( 'envira-gallery', $widget_name, $widget_ops, $control_ops );

		add_action( 'wp_ajax_envira_widget_get_galleries', array( $this, 'widget_get_galleries' ) );

	}

	/**
	 * Get galleries for widget.
	 *
	 * @since 1.0.0
	 */
	public function widget_get_galleries() {

		$galleries     = envira_get_galleries( false );
		$gallery_array = array();

		if ( is_array( $galleries ) ) {
			foreach ( $galleries as $gallery ) {

				if ( ! isset( $gallery['id'] ) || ! $gallery['id'] ) {
					continue;
				}

				// Instead of pulling the title from config, attempt to pull it from the gallery post first.
				$gallery_post = get_post( $gallery['id'] );

				if ( $gallery_post && ! empty( $gallery_post->post_title ) ) {
					$title = $gallery_post->post_title;
				} elseif ( ! empty( $gallery['config']['title'] ) ) {
					$title = $gallery['config']['title'];
				} elseif ( ! empty( $gallery['config']['slug'] ) ) {
					$title = $gallery['config']['slug'];
				} else {
					/* translators: %s: Gallery ID */
					$title = sprintf( __( 'Gallery ID #%s', 'envira-gallery' ), $gallery['id'] );
				}

				$gallery_array[] = array(
					'gallery_title' => $title,
					'gallery_id'    => '' . $gallery['id'] . '',
				);

			}
		}

		$string = array( 'galleries' => $gallery_array );

		echo wp_json_encode( $string );
		exit;
	}

	/**
	 * Outputs the widget within the widgetized area.
	 *
	 * @since 1.7.0
	 *
	 * @param array $args     The default widget arguments.
	 * @param array $instance The input settings for the current widget instance.
	 */
	public function widget( $args, $instance ) {

		// Extract arguments into variables.
		extract( $args );

		$gallery_id       = false;
		$title            = false;
		$number_of_images = false;
		$gallery_args     = false;

		if ( isset( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
		}
		if ( isset( $instance['envira_gallery_id'] ) ) {
			$gallery_id = $instance['envira_gallery_id'];
		}

		if ( ! $gallery_id ) {
			return;
		}

		if ( ! empty( $instance['number_of_images'] ) ) {
			$number_of_images = $instance['number_of_images'];
		}

		do_action( 'envira_gallery_widget_before_output', $args, $instance );

		echo $before_widget; // @codingStandardsIgnoreLine

		do_action( 'envira_gallery_widget_before_title', $args, $instance );

		// If a title exists, output it.
		if ( $title ) {
			echo $before_title . $title . $after_title; // @codingStandardsIgnoreLine
		}

		do_action( 'envira_gallery_widget_before_gallery', $args, $instance );

		if ( $number_of_images ) {
			if ( $number_of_images > 50 ) {
				$number_of_images = 50; // for performance reasons keep the max limit at 50, even if they manually enter a number in the widget input field.
			}
			$gallery_args = array( 'limit' => $number_of_images );
		}

		// If a gallery has been selected, output it.
		if ( $gallery_id ) {
			envira_gallery( $gallery_id, 'id', $gallery_args );
		}

		do_action( 'envira_gallery_widget_after_gallery', $args, $instance );

		echo $after_widget; // @codingStandardsIgnoreLine

		do_action( 'envira_gallery_widget_after_output', $args, $instance );

	}

	/**
	 * Sanitizes and updates the widget.
	 *
	 * @since 1.7.0
	 *
	 * @param array $new_instance The new input settings for the current widget instance.
	 * @param array $old_instance The old input settings for the current widget instance.
	 */
	public function update( $new_instance, $old_instance ) {

		// Set $instance to the old instance in case no new settings have been updated for a particular field.
		$instance = $old_instance;

		// Sanitize user inputs.
		$instance['title']             = trim( $new_instance['title'] );
		$instance['envira_gallery_id'] = absint( $new_instance['envira_gallery_id'] );
		$instance['number_of_images']  = absint( $new_instance['number_of_images'] ) === 0 ? '' : absint( $new_instance['number_of_images'] );

		return apply_filters( 'envira_gallery_widget_update_instance', $instance, $new_instance );

	}

	/**
	 * Outputs the widget form where the user can specify settings.
	 *
	 * @since 1.7.0
	 *
	 * @param array $instance The input settings for the current widget instance.
	 */
	public function form( $instance ) {

		// Get all available galleries and widget properties.
		$galleries = _envira_get_galleries( false );

		$title            = isset( $instance['title'] ) ? $instance['title'] : '';
		$gallery_id       = isset( $instance['envira_gallery_id'] ) ? $instance['envira_gallery_id'] : false;
		$number_of_images = ! empty( $instance['number_of_images'] ) ? intval( $instance['number_of_images'] ) : '';

		do_action( 'envira_gallery_widget_before_form', $instance );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'envira-gallery' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%;" />
		</p>
		<?php do_action( 'envira_gallery_widget_middle_form', $instance ); ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'envira_gallery_id' ) ); ?>"><?php esc_html_e( 'Gallery', 'envira-gallery' ); ?></label>

			<select class="form-control" id="<?php echo esc_attr( $this->get_field_id( 'envira_gallery_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'envira_gallery_id' ) ); ?>" style="width: 100%;">
			<!--<select class="form-control" name="choices-single-remote-fetch" id="choices-single-remote-fetch">-->
				<?php

				if ( is_array( $galleries ) ) {
					foreach ( $galleries as $gallery ) {
						if ( isset( $gallery['id'] ) ) {
							$title = get_the_title( $gallery['id'] );
							if ( $gallery_id && $gallery['id'] === $gallery_id ) {
								echo '<option selected="selected" value="' . absint( $gallery['id'] ) . '">' . esc_html( $title ) . '</option>';
							} else {
								echo '<option value="' . absint( $gallery['id'] ) . '">' . esc_html( $title ) . '</option>';
							}
						}
					}
				}

				?>
			</select>

		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number_of_images' ) ); ?>">
				<?php esc_html_e( 'Number Of Images To Display?', 'envira-gallery' ); ?>
			</label>
			<input class="envira_widget_number_of_images" value="<?php echo esc_attr( $number_of_images ); ?>" type="text" maxlength="3" name="<?php echo esc_attr( $this->get_field_name( 'number_of_images' ) ); ?>" id="<?php echo esc_attr( $this->get_field_name( 'number_of_images' ) ); ?>" />
			<br/><small>Leave blank to display all images in gallery.</small>
		</p>
		<?php
		do_action( 'envira_gallery_widget_after_form', $instance );

	}

}

class EnviraWidgetRandom extends \ WP_Widget { // @codingStandardsIgnoreLine

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		// Widget Name.
		$widget_name = __( 'Envira Random Images', 'envira-gallery' );
		$widget_name = apply_filters( 'envira_gallery_widget_name', $widget_name );

		// Opts.
		$widget_ops = array(
			'classname'   => 'envira-gallery-random-images',
			'description' => __( 'Display random images from your Envira galleries.', 'envira-gallery' ),
		);
		$widget_ops = apply_filters( 'envira_gallery_widget_ops', $widget_ops );

		// Control Options.
		$control_ops = array(
			'id_base' => 'envira-gallery-random-images',
			'height'  => 350,
			'width'   => 225,
		);
		$control_ops = apply_filters( 'envira_gallery_widget_control_ops', $control_ops );

		// Init.
		parent::__construct( 'envira-gallery-random-images', $widget_name, $widget_ops, $control_ops );

		add_action( 'wp_ajax_envira_widget_get_galleries', array( $this, 'widget_get_galleries' ) );

		add_filter( 'envira_gallery_custom_gallery_data_by_widget', array( $this, 'widget_custom_data' ), 5, 3 );

	}

	/**
	 * Widget Custom Data.
	 *
	 * @param array  $data Data..
	 * @param array  $atts Attribues.
	 * @param object $post Post Object.
	 * @return array
	 */
	public function widget_custom_data( $data, $atts, $post ) {

		// If this isn't a widget type, we shouldn't stick our nose in this.
		if ( empty( $atts['type'] ) || 'widget' !== $atts['type'] ) {
			return $data;
		}

		// If there is no cache, and it's a random sort, resort it.
		if ( isset( $atts['cache'] ) && 0 === intval( $atts['cache'] ) && 1 === intval( $data['config']['sort_order'] ) ) {
			$data = envira_sort_gallery( $data, $data['config']['sort_order'], $data['config']['sorting_direction'] );
		}

		return $data;

	}

	/**
	 * Get galleries for widget.
	 *
	 * @since 1.0.0
	 */
	public function widget_get_galleries() {

		$galleries     = envira_get_galleries( false );
		$gallery_array = array();

		if ( is_array( $galleries ) ) {
			foreach ( $galleries as $gallery ) {

				if ( ! isset( $gallery['id'] ) || ! $gallery['id'] ) {
					continue;
				}

				// Instead of pulling the title from config, attempt to pull it from the gallery post first.
				$gallery_post = get_post( $gallery['id'] );

				if ( $gallery_post && ! empty( $gallery_post->post_title ) ) {
					$title = $gallery_post->post_title;
				} elseif ( ! empty( $gallery['config']['title'] ) ) {
					$title = $gallery['config']['title'];
				} elseif ( ! empty( $gallery['config']['slug'] ) ) {
					$title = $gallery['config']['slug'];
				} else {
					/* translators: %s: GalleryID */
					$title = sprintf( __( 'Gallery ID #%s', 'envira-gallery' ), $gallery['id'] );
				}

				$gallery_array[] = array(
					'gallery_title' => $title,
					'gallery_id'    => '' . $gallery['id'] . '',
				);

			}
		}

		$string = array( 'galleries' => $gallery_array );

		echo wp_json_encode( $string );
		exit;
	}

	/**
	 * Outputs the widget within the widgetized area.
	 *
	 * @since 1.7.0
	 *
	 * @param array $args     The default widget arguments.
	 * @param array $instance The input settings for the current widget instance.
	 */
	public function widget( $args, $instance ) {

		// Extract arguments into variables.
		extract( $args );

		$gallery_id                     = false;
		$title                          = false;
		$envira_display_images_lightbox = false;
		$gallery_id_for_image           = false;

		if ( isset( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
		}
		if ( isset( $instance['number_of_images'] ) ) {
			$number_of_images = $instance['number_of_images'];
		} else {
			$number_of_images = 10; // default.
		}
		if ( isset( $instance['envira_display_images_lightbox'] ) && ! empty( $instance['envira_display_images_lightbox'] ) ) {
			$envira_display_images_lightbox = $instance['envira_display_images_lightbox'];
		}
		if ( isset( $instance['gallery_id'] ) ) {
			$gallery_id = $instance['gallery_id'];
		}

		// Use default (if addon is installed) if user hasn't set a gallery.
		if ( ! $gallery_id && class_exists( 'Envira_Defaults' ) ) {
			$gallery_id = get_option( 'envira_default_gallery' );

		}

		if ( ! $gallery_id ) {
			return;
		}

		do_action( 'envira_gallery_widget_before_output', $args, $instance );

		echo $before_widget; // @codingStandardsIgnoreLine

		do_action( 'envira_gallery_widget_before_title', $args, $instance );

		// If a title exists, output it.
		if ( $title ) {
			echo $before_title . $title . $after_title; // @codingStandardsIgnoreLine
		}

		do_action( 'envira_gallery_widget_before_gallery', $args, $instance );

		// If a gallery has been selected, output it.
		if ( $gallery_id ) {

			/* get random images */

			/* step one: get all images with meta_data of '_eg_has_gallery' */

			$image_ids = false; // we no longer want to rely on something like get_transient( '_eg_gallery_widget_random_image_ids' ).

			if ( false === $image_ids ) {

				$image_counter = 1;

				$image_args = array(
					'post_status'            => 'inherit',
					'post_type'              => 'attachment',
					'post_mime_type'         => 'image/jpeg,image/gif,image/jpg,image/png',
					'posts_per_page'         => -1, /* was $number_of_images */
					'meta_key'               => '_eg_has_gallery', // @codingStandardsIgnoreLine
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'fields'                 => 'ids',
				);

				$query = new \ WP_Query( $image_args );

				if ( ! empty( $query->posts ) ) {

					shuffle( $query->posts ); /* this should be faster than RND in MySQL query */

					$image_ids = array();

					foreach ( $query->posts as $image_id ) {

						$found = false;

						$galleries = get_post_meta( $image_id, '_eg_has_gallery', true );
						if ( false !== $galleries && is_array( $galleries ) ) {
							foreach ( $galleries as $the_gallery_id ) {
								// if (1) the gallery id is valid and is the right post type
								// and (2) if the gallery has the image ID.
								if ( false === $found && get_post_type( $the_gallery_id ) === 'envira' ) {
									$images_in_gallery = get_post_meta( $the_gallery_id, '_eg_in_gallery', true );
									if ( is_array( $images_in_gallery ) && in_array( $image_id, $images_in_gallery, true ) ) {
										$found                = true;
										$gallery_id_for_image = $the_gallery_id;
										continue;
									}
								}
							}
						}
						if ( $found && $gallery_id_for_image ) {
							// add image to the array of image ids.
							$image_ids[ $image_id ] = $gallery_id_for_image;
							$image_counter++;
							if ( $image_counter > $number_of_images ) {
								// stop looking through images if you found enough.
								break;
							}
						}
					}

					$expiration = envira_get_transient_expiration_time();
					set_transient( '_eg_gallery_widget_random_image_ids', $image_ids, $expiration );

				} else {

					/* no images, so don't go further */

					return;

				}
			}

			if ( ! empty( $image_ids ) ) {

				/* get $data from the $gallery_id */
				$data            = get_post_meta( $gallery_id, '_eg_gallery_data', true );
				$data['gallery'] = array();

				foreach ( $image_ids as $image_id => $gallery_id_for_image ) {
					$data = envira_prepare_gallery_data( $data, $image_id );
					if ( false === $envira_display_images_lightbox ) {
						$data['gallery'][ $image_id ]['link'] = get_permalink( $gallery_id_for_image );
					}
				}
				$data['config']['sort_order'] = 1;
				$data['config']['type']       = 'dynamic';
				$data['dynamic_id']           = $gallery_id;
				$data['config']['id']         = $gallery_id;

				if ( ! $envira_display_images_lightbox ) {
					$data['config']['lightbox_enabled'] = 0;
				}

				/* raw data to override this is in shortcode */
				$gallery_images_raw = envira_get_gallery_images( $gallery_id, true, $data );

				/* setup transients */
				$transients                       = array();
				$transients['gallery_images_raw'] = '_eg_gallery_images_raw_' . $gallery_id . '_widget_random_images';
				$transients['gallery_data']       = '_eg_gallery_data_' . $gallery_id . '_widget_random_images';
				$args                             = array();
				$args['transients']               = wp_json_encode( $transients );
				$args['dynamic']                  = 'true';
				$args['type']                     = 'widget';
				$args['cache']                    = false;

				$transient_time_duration = apply_filters( 'envira_gallery_widget_random_image_duration', DAY_IN_SECONDS );

				/* go! */
				envira_gallery( $gallery_id . '_widget_random_images', 'id', $args );

			}
		}

		do_action( 'envira_gallery_widget_after_gallery', $args, $instance );

		echo $after_widget; // @codingStandardsIgnoreLine

		do_action( 'envira_gallery_widget_after_output', $args, $instance );

	}

	/**
	 * Sanitizes and updates the widget.
	 *
	 * @since 1.7.0
	 *
	 * @param array $new_instance The new input settings for the current widget instance.
	 * @param array $old_instance The old input settings for the current widget instance.
	 */
	public function update( $new_instance, $old_instance ) {

		// Set $instance to the old instance in case no new settings have been updated for a particular field.
		$instance = $old_instance;

		// Sanitize user inputs.
		$instance['title']                          = trim( $new_instance['title'] );
		$instance['envira_display_images_lightbox'] = trim( $new_instance['envira_display_images_lightbox'] );
		$instance['gallery_id']                     = absint( $new_instance['gallery_id'] );
		$instance['number_of_images']               = absint( $new_instance['number_of_images'] );

		delete_transient( '_eg_gallery_widget_random_image_ids' );

		return apply_filters( 'envira_gallery_widget_update_instance', $instance, $new_instance );

	}

	/**
	 * Outputs the widget form where the user can specify settings.
	 *
	 * @since 1.7.0
	 *
	 * @param array $instance The input settings for the current widget instance.
	 */
	public function form( $instance ) {

		// Get all available galleries and widget properties.
		$galleries = envira_get_galleries( false );

		$title                          = isset( $instance['title'] ) ? $instance['title'] : '';
		$gallery_id                     = isset( $instance['gallery_id'] ) ? $instance['gallery_id'] : false;
		$number_of_images               = isset( $instance['number_of_images'] ) ? $instance['number_of_images'] : 10;
		$envira_display_images_lightbox = isset( $instance['envira_display_images_lightbox'] ) ? $instance['envira_display_images_lightbox'] : false;

		do_action( 'envira_gallery_widget_before_form', $instance );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'envira-gallery' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%;" />
		</p>
		<?php do_action( 'envira_gallery_widget_middle_form', $instance ); ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number_of_images' ) ); ?>"><?php esc_html_e( 'Number Of Images To Display?', 'envira-gallery' ); ?></label>
			<input class="envira_widget_random_number_of_images" min="1" max="50" value="<?php echo esc_attr( $number_of_images ); ?>" type="number" name="<?php echo esc_attr( $this->get_field_name( 'number_of_images' ) ); ?>" id="<?php echo esc_attr( $this->get_field_name( 'number_of_images' ) ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'envira_display_images_lightbox' ) ); ?>">
				When Gallery Images Are Clicked:
			</label><br/>
			<select id="<?php echo esc_attr( $this->get_field_name( 'envira_display_images_lightbox' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'envira_display_images_lightbox' ) ); ?>">
				<option value="0">Go To Gallery</option>
				<option value="1" <?php selected( $envira_display_images_lightbox, 1 ); ?>>Open Lightbox</option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'gallery_id' ) ); ?>"><?php esc_html_e( 'Use Settings From This Gallery:', 'envira-gallery' ); ?></label>

			<select class="form-control" id="<?php echo esc_attr( $this->get_field_id( 'gallery_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'gallery_id' ) ); ?>" style="width: 100%;">
				<?php

				if ( is_array( $galleries ) ) {
					foreach ( $galleries as $gallery ) {
						if ( isset( $gallery['id'] ) ) {
							$title = get_the_title( $gallery['id'] );
							if ( $gallery_id && $gallery['id'] === $gallery_id ) {
								echo '<option selected="selected" value="' . absint( $gallery['id'] ) . '">' . esc_html( $title ) . '</option>';
							} else {
								echo '<option value="' . absint( $gallery['id'] ) . '">' . esc_html( $title ) . '</option>';
							}
						}
					}
				}

				?>
			</select>
		</p>
		<?php
		do_action( 'envira_gallery_widget_after_form', $instance );

	}

}
