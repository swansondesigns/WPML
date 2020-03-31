<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

get_header();
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php
			//*
			echo '<pre>';
			echo 'var_dump( get_field(\'fruits\') ):';
			echo '<br>';
			$fruits = get_field('fruits');
			var_dump( $fruits );
			echo '<br>';
			echo '$fruits:';
			echo '<br>';
			echo $fruits;
			echo '<br>';
			echo '<br>';
			echo '</pre>';
			echo '<hr />';
			//*/
			/*
			echo '<pre>';
			echo 'var_dump( get_field(\'student_names\') ):';
			echo '<br>';
			$student_names = get_field('student_names');
			var_dump( $student_names );
			echo '<br>';
			echo '$student_names:';
			echo '<br>';
			echo $student_names;
			echo '<br>';
			echo '<br>';
			echo '</pre>';
			echo '<hr />';
			///


			//*
			echo '<pre>';
			echo 'var_dump( get_field(\'subtitle\') ):';
			echo '<br>';
			var_dump( get_field('subtitle') );
			echo '</pre>';
			///
			//*
			echo '<pre>';
			echo 'var_dump( get_field(\'repeater_fields\') ):';
			echo '<br>';
			$repeater_fields = get_field('repeater_fields');
			var_dump( $repeater_fields );
			echo '<br>';
			echo '<br>';
			echo '$repeater_fields:';
			echo '<br>';
			echo $repeater_fields;
			echo '</pre>';
			//*/
			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
