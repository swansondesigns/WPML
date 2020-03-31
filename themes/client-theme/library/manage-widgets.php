<?php

class sd_Divider_Widget extends WP_Widget {
  /**
  * To create the example widget all four methods will be
  * nested inside this single instance of the WP_Widget class.
  **/
  public function __construct() {
    $widget_options = array(
      'classname' => 'divider-widget',
      'description' => 'This is an Divider Widget',
    );
    parent::__construct( 'divider-widget', 'Divider Widget', $widget_options );
  }

  public function widget( $args, $instance ) {}

}
function sd_register_widgets() {
  register_widget( 'sd_Divider_Widget' );
}
// add_action( 'widgets_init', 'sd_register_widgets' );
