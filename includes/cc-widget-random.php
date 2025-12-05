<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Random Comic Widget Class
 */
class CC_Random_Comic_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'cc_random_comic_widget', // Base ID
            esc_html__( 'Random Comic', 'comic-calendar' ), // Name
            array( 'description' => esc_html__( 'Displays a random comic from the Comic Calendar post type.', 'comic-calendar' ) ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     */
    public function widget( $args, $instance ) {
        // Get widget title
        $title = apply_filters( 'widget_title', $instance['title'] );

        // Start HTML output (Standard WordPress widget wrappers)
        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // 1. Get a random comic ID
        $random_comic = new WP_Query( array(
            'post_type'      => 'cc_comic',
            'posts_per_page' => 1,
            'orderby'        => 'rand',
            'fields'         => 'ids'
        ) );

        if ( $random_comic->have_posts() ) {
            $comic_id = $random_comic->posts[0];
            $comic_title = get_the_title( $comic_id );
            
            // Fetch the thumbnail using the 'medium' size for sidebar use
            $comic_thumbnail = get_the_post_thumbnail( $comic_id, 'medium', array('alt' => $comic_title, 'title' => $comic_title) );
            
            // Get the base URL of the calendar page using our helper
            $base_url = cc_get_page_url();

            // Create the link using the reliable query parameter method (as we established)
            $link = add_query_arg( 'cc_comic_id', $comic_id, $base_url );

            ?>
            <div class="cc-random-comic-widget">
                <a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $comic_title ); ?>">
                    <?php echo $comic_thumbnail; ?>
                </a>
                <p class="cc-random-comic-title">
                    <a href="<?php echo esc_url( $link ); ?>">
                        <?php //echo esc_html( $comic_title ); ?>
                    </a>
                </p>
            </div>
            <?php
        } else {
            echo '<p>' . esc_html__( 'No comics published yet!', 'comic-calendar' ) . '</p>';
        }
        wp_reset_postdata();

        // End HTML output
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form (Title field).
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Random Comic', 'comic-calendar' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'comic-calendar' ); ?></label> 
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php 
    }

    /**
     * Sanitize widget form values as they are saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        return $instance;
    }
}

// Register the widget action
function cc_register_random_comic_widget() {
    register_widget( 'CC_Random_Comic_Widget' );
}
add_action( 'widgets_init', 'cc_register_random_comic_widget' );