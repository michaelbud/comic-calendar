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
            array( 'description' => esc_html__( 'Displays a random or latest comic from the Comic Calendar post type.', 'comic-calendar' ) ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     */
    public function widget( $args, $instance ) {
        // Get widget title
        $raw_title = isset( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $raw_title );

        $comic_count  = isset( $instance['comic_count'] ) ? absint( $instance['comic_count'] ) : 1;
        $comic_count  = max( 1, min( $comic_count, 10 ) );
        $comic_spacing = isset( $instance['comic_spacing'] ) ? absint( $instance['comic_spacing'] ) : 0;

        // Start HTML output (Standard WordPress widget wrappers)
        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Determine selection mode
        $selection = isset( $instance['comic_selection'] ) ? $instance['comic_selection'] : 'random';
        $selection = in_array( $selection, array( 'random', 'latest', 'featured' ), true ) ? $selection : 'random';

        // Fetch comics based on selection
        $query_args = array(
            'post_type'      => 'cc_comic',
            'posts_per_page' => $comic_count,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        );

        if ( 'featured' === $selection ) {
            $query_args['meta_query'] = array(
                array(
                    'key'   => 'cc_featured',
                    'value' => '1'
                )
            );
            $query_args['orderby'] = 'rand';
        } elseif ( 'random' === $selection ) {
            $query_args['orderby'] = 'rand';
        } else {
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'DESC';
        }

        $comic_query = new WP_Query( $query_args );

        if ( $comic_query->have_posts() ) {
            $wrapper_style = 'display:flex;flex-direction:column;';
            if ( $comic_spacing > 0 ) {
                $wrapper_style .= 'gap:' . $comic_spacing . 'px;';
            }

            ?>
            <div class="cc-random-comic-widget" style="<?php echo esc_attr( $wrapper_style ); ?>">
                <?php foreach ( $comic_query->posts as $comic_id ) {
                    $comic_title = get_the_title( $comic_id );

                    // Fetch the thumbnail using the 'medium' size for sidebar use
                    $comic_thumbnail = get_the_post_thumbnail( $comic_id, 'medium', array('alt' => $comic_title, 'title' => $comic_title) );

                    // Get the base URL of the calendar page using our helper
                    $base_url = cc_get_page_url();

                    // Create the link using the reliable query parameter method (as we established)
                    $link = add_query_arg( 'cc_comic_id', $comic_id, $base_url );
                    ?>
                    <div class="cc-random-comic-item">
                        <a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $comic_title ); ?>">
                            <?php echo $comic_thumbnail; ?>
                        </a>
                        <p class="cc-random-comic-title">
                            <a href="<?php echo esc_url( $link ); ?>">
                                <?php //echo esc_html( $comic_title ); ?>
                            </a>
                        </p>
                    </div>
                <?php } ?>
            </div>
            <?php
        } else {
            if ( 'featured' === $selection ) {
                echo '<p>' . esc_html__( 'No featured comics yet!', 'comic-calendar' ) . '</p>';
            } else {
                echo '<p>' . esc_html__( 'No comics published yet!', 'comic-calendar' ) . '</p>';
            }
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
        $selection = isset( $instance['comic_selection'] ) ? $instance['comic_selection'] : 'random';
        $comic_count = isset( $instance['comic_count'] ) ? absint( $instance['comic_count'] ) : 1;
        $comic_spacing = isset( $instance['comic_spacing'] ) ? absint( $instance['comic_spacing'] ) : 0;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'comic-calendar' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'comic_selection' ) ); ?>"><?php esc_attr_e( 'Comic to display:', 'comic-calendar' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'comic_selection' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'comic_selection' ) ); ?>">
                <option value="random" <?php selected( $selection, 'random' ); ?>><?php esc_html_e( 'Random comic', 'comic-calendar' ); ?></option>
                <option value="latest" <?php selected( $selection, 'latest' ); ?>><?php esc_html_e( 'Latest comic', 'comic-calendar' ); ?></option>
                <option value="featured" <?php selected( $selection, 'featured' ); ?>><?php esc_html_e( 'Featured comic', 'comic-calendar' ); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'comic_count' ) ); ?>"><?php esc_attr_e( 'Number of comics to display:', 'comic-calendar' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'comic_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'comic_count' ) ); ?>" type="number" step="1" min="1" max="10" value="<?php echo esc_attr( $comic_count ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'comic_spacing' ) ); ?>"><?php esc_attr_e( 'Spacing between comics (px):', 'comic-calendar' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'comic_spacing' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'comic_spacing' ) ); ?>" type="number" step="1" min="0" value="<?php echo esc_attr( $comic_spacing ); ?>">
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';

        $selection = ( ! empty( $new_instance['comic_selection'] ) ) ? sanitize_text_field( $new_instance['comic_selection'] ) : 'random';
        $instance['comic_selection'] = in_array( $selection, array( 'random', 'latest', 'featured' ), true ) ? $selection : 'random';
        $instance['comic_count'] = isset( $new_instance['comic_count'] ) ? max( 1, min( absint( $new_instance['comic_count'] ), 10 ) ) : 1;
        $instance['comic_spacing'] = isset( $new_instance['comic_spacing'] ) ? max( 0, absint( $new_instance['comic_spacing'] ) ) : 0;
        return $instance;
    }
}

// Register the widget action
function cc_register_random_comic_widget() {
    register_widget( 'CC_Random_Comic_Widget' );
}
add_action( 'widgets_init', 'cc_register_random_comic_widget' );