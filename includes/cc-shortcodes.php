<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcode: [comic_calendar]
 * Attributes:
 *   view="month" (default) — original month calendar grid
 *   view="year"            — compact iCal-style year overview
 */
function cc_shortcode_handler( $atts ) {
    $atts = shortcode_atts( [ 'view' => 'month' ], $atts, 'comic_calendar' );
    $view = sanitize_key( $atts['view'] );

    $current_id = cc_resolve_current_comic_id();
    if ( ! $current_id ) return '<p>No comics found.</p>';

    $all_ids = get_transient( 'cc_all_ids' );
    if ( false === $all_ids ) {
        $q = new WP_Query([
            'post_type'      => 'cc_comic',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ]);
        $all_ids = $q->posts;
        set_transient( 'cc_all_ids', $all_ids, 12 * HOUR_IN_SECONDS );
    }

    $title      = get_the_title( $current_id );
    $img        = get_the_post_thumbnail_url( $current_id, 'full' );
    $base_url   = cc_get_page_url();
    $comic_link = cc_get_comic_pretty_url( $current_id, $base_url );

    // Individual comic permalinks enlarge the image; the main calendar links to the permalink.
    $request_path = trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    $base_path = trim( (string) wp_parse_url( $base_url, PHP_URL_PATH ), '/' );
    $is_detail = (int) get_query_var( 'cc_comic_id' ) === (int) $current_id
        || $request_path === trim( $base_path . '/' . $current_id, '/' )
        || ( isset( $_GET['cc_comic_id'] ) && (int) $_GET['cc_comic_id'] === (int) $current_id )
        || ( isset( $_GET['comic'] ) && (int) $_GET['comic'] === (int) $current_id );

    ob_start();
    ?>
    <div class="cc-wrapper">
        <?php if ( $img ) : ?>
            <div class="cc-comic-display">
                <?php if ( $is_detail ) : ?>
                    <a href="<?php echo esc_url( $img ); ?>" class="cc-lightbox-trigger" data-cc-lightbox aria-label="<?php echo esc_attr( sprintf( 'Enlarge comic: %s', $title ) ); ?>">
                        <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url( $comic_link ); ?>">
                        <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    if ( $view === 'year' ) {
        echo cc_render_year_controls( $all_ids, $current_id, $base_url );
    } else {
        echo cc_render_controls( $all_ids, $current_id, $base_url );
    }
    return ob_get_clean();
}
add_shortcode( 'comic_calendar', 'cc_shortcode_handler' );


/* =========================================================
   YEAR VIEW
   ========================================================= */

function cc_render_year_controls( $ids, $current_id, $base_url ) {
    $current_date = get_the_date( 'Y-m-d', $current_id );

    // Which year to display — from ?cc_year= or from the current comic
    $display_year = isset( $_GET['cc_year'] ) && is_numeric( $_GET['cc_year'] )
        ? intval( $_GET['cc_year'] )
        : intval( date( 'Y', strtotime( $current_date ) ) );

    // Year bounds from archive
    $years = array_unique( array_map( function( $pid ) {
        return intval( get_the_date( 'Y', $pid ) );
    }, $ids ) );
    sort( $years );
    $first_year = $years[0];
    $last_year  = end( $years );

    $prev_year = $display_year - 1;
    $next_year = $display_year + 1;
    $prev_year_url = ( $prev_year >= $first_year ) ? add_query_arg( 'cc_year', $prev_year, $base_url ) : '#';
    $next_year_url = ( $next_year <= $last_year  ) ? add_query_arg( 'cc_year', $next_year, $base_url ) : '#';

    // Strip nav (First / Prev / Random / Next / Latest)
    $index    = array_search( $current_id, $ids );
    $first_id = end( $ids );
    $last_id  = $ids[0];
    $prev_id  = ( $index !== false && isset( $ids[ $index + 1 ] ) ) ? $ids[ $index + 1 ] : null;
    $next_id  = ( $index !== false && isset( $ids[ $index - 1 ] ) ) ? $ids[ $index - 1 ] : null;
    $rand_id  = $ids[ array_rand( $ids ) ];

    $first_url = cc_get_comic_pretty_url( $first_id, $base_url );
    $prev_url  = $prev_id ? cc_get_comic_pretty_url( $prev_id, $base_url ) : '#';
    $rand_url  = cc_get_comic_pretty_url( $rand_id, $base_url );
    $next_url  = $next_id ? cc_get_comic_pretty_url( $next_id, $base_url ) : '#';
    $last_url  = cc_get_comic_pretty_url( $last_id, $base_url );

    // Build a lookup: 'YYYY-MM-DD' => post_id for this year
    $date_map = [];
    foreach ( $ids as $pid ) {
        $d = get_the_date( 'Y-m-d', $pid );
        if ( intval( substr( $d, 0, 4 ) ) === $display_year ) {
            $date_map[ $d ] = $pid;
        }
    }

    $calendar_title = cc_get_calendar_title();

    ob_start();
    ?>
    <div class="cc-controls-container cc-year-view">

        <div class="cc-calendar-eyebrow"><?php echo esc_html( $calendar_title ); ?></div>

        <!-- Year pager -->
        <div class="cc-month-pager">
            <h3 class="cc-month-title">
                <a href="<?php echo esc_url( $prev_year_url ); ?>"
                   class="cc-month-nav cc-prev-month <?php echo ( $prev_year < $first_year ) ? 'disabled' : ''; ?>">
                    &laquo;
                </a>
                <?php echo esc_html( $display_year ); ?>
                <a href="<?php echo esc_url( $next_year_url ); ?>"
                   class="cc-month-nav cc-next-month <?php echo ( $next_year > $last_year ) ? 'disabled' : ''; ?>">
                    &raquo;
                </a>
            </h3>
        </div>

        <!-- Strip nav buttons -->
        <div class="cc-nav-buttons">
            <a href="<?php echo esc_url( $first_url ); ?>" class="cc-btn <?php echo ( $current_id == $first_id ) ? 'disabled' : ''; ?>">&laquo; First</a>
            <a href="<?php echo esc_url( $prev_url );  ?>" class="cc-btn <?php echo ! $prev_id ? 'disabled' : ''; ?>">Prev</a>
            <a href="<?php echo esc_url( $rand_url );  ?>" class="cc-btn">Random</a>
            <a href="<?php echo esc_url( $next_url );  ?>" class="cc-btn <?php echo ! $next_id ? 'disabled' : ''; ?>">Next</a>
            <a href="<?php echo esc_url( $last_url );  ?>" class="cc-btn <?php echo ( $current_id == $last_id ) ? 'disabled' : ''; ?>">Today &raquo;</a>
        </div>

        <!-- 12-month compact grid -->
        <div class="cc-year-grid">
            <?php
            for ( $m = 1; $m <= 12; $m++ ) {
                echo cc_render_mini_month( $display_year, $m, $date_map, $current_date, $base_url );
            }
            ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}


/**
 * Render one compact month block for the year view.
 */
function cc_render_mini_month( $year, $month, $date_map, $current_date, $base_url ) {
    $month_ts      = mktime( 0, 0, 0, $month, 1, $year );
    $days_in_month = intval( date( 't', $month_ts ) );
    $start_dow     = intval( date( 'w', $month_ts ) ); // 0=Sun

    $month_name = date( 'F', $month_ts ); 
    $today = current_time( 'Y-m-d' );

    ob_start();
    ?>
    <div class="cc-mini-month">
        <div class="cc-mini-month-name"><?php echo esc_html( $month_name ); ?></div>
        <table class="cc-mini-cal">
            <thead>
                <tr>
                    <th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $cell = 0;
            echo '<tr>';

            // Leading empty cells
            for ( $i = 0; $i < $start_dow; $i++ ) {
                echo '<td></td>';
                $cell++;
            }

            for ( $d = 1; $d <= $days_in_month; $d++ ) {
                $date_str   = sprintf( '%04d-%02d-%02d', $year, $month, $d );
                $has_comic  = isset( $date_map[ $date_str ] );
                $is_current = ( $date_str === $current_date );

                $classes = [];
                if ( $has_comic  ) $classes[] = 'cc-has-comic';
                if ( $is_current ) $classes[] = 'cc-current-day';
                $is_missed = ! $has_comic && $date_str < $today;
                if ( $is_missed ) $classes[] = 'cc-missed';
                $class_str = $classes ? implode( ' ', $classes ) : '';

                echo '<td' . ( $class_str ? ' class="' . esc_attr( $class_str ) . '"' : '' ) . '>';

                if ( $has_comic ) {
                    $link = cc_get_comic_pretty_url( $date_map[ $date_str ], $base_url );
                    echo '<a href="' . esc_url( $link ) . '" title="' . esc_attr( get_the_title( $date_map[ $date_str ] ) ) . '">' . $d . '</a>';
                } elseif ( $is_missed ) {
                    echo '<span class="cc-missed-date" title="No comic published on ' . esc_attr( $date_str ) . '"><i class="cc-missed-face" aria-hidden="true"></i></span>';
                } else {
                    echo '<span>' . $d . '</span>';
                }

                echo '</td>';
                $cell++;

                if ( $cell % 7 === 0 && $d < $days_in_month ) {
                    echo '</tr><tr>';
                }
            }

            // Trailing empty cells to complete the row
            $remainder = $cell % 7;
            if ( $remainder !== 0 ) {
                for ( $i = $remainder; $i < 7; $i++ ) {
                    echo '<td></td>';
                }
            }
            ?>
            </tr>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}


/* =========================================================
   ORIGINAL MONTH VIEW (unchanged)
   ========================================================= */

function cc_render_controls( $ids, $current_id, $base_url ) {
    $current_date   = get_the_date( 'Y-m-d', $current_id );
    $calendar_title = cc_get_calendar_title();

    $index    = array_search( $current_id, $ids );
    $first_id = end( $ids );
    $last_id  = $ids[0];

    $prev_id = ( $index !== false && isset( $ids[ $index + 1 ] ) ) ? $ids[ $index + 1 ] : null;
    $next_id = ( $index !== false && isset( $ids[ $index - 1 ] ) ) ? $ids[ $index - 1 ] : null;
    $rand_id = $ids[ array_rand( $ids ) ];

    $target_month   = date( 'Y-m', strtotime( $current_date ) );
    $start_of_month = strtotime( $target_month . '-01' );

    $prev_month_ts  = strtotime( 'first day of last month', $start_of_month );
    $prev_month_str = date( 'Y-m', $prev_month_ts );
    $has_prev_month = cc_month_has_comics( $prev_month_str, $ids );
    $prev_month_url = $has_prev_month ? add_query_arg( 'cc_month', $prev_month_str, $base_url ) : '#';

    $next_month_ts  = strtotime( 'first day of next month', $start_of_month );
    $next_month_str = date( 'Y-m', $next_month_ts );
    $has_next_month = cc_month_has_comics( $next_month_str, $ids );
    $next_month_url = $has_next_month ? add_query_arg( 'cc_month', $next_month_str, $base_url ) : '#';

    $first_url = cc_get_comic_pretty_url( $first_id, $base_url );
    $prev_url  = $prev_id ? cc_get_comic_pretty_url( $prev_id,  $base_url ) : '#';
    $rand_url  = cc_get_comic_pretty_url( $rand_id,  $base_url );
    $next_url  = $next_id ? cc_get_comic_pretty_url( $next_id,  $base_url ) : '#';
    $last_url  = cc_get_comic_pretty_url( $last_id,  $base_url );

    ob_start();
    ?>
    <div class="cc-controls-container">

        <div class="cc-calendar-eyebrow"><?php echo esc_html( $calendar_title ); ?></div>

        <div class="cc-month-pager">
            <h3 class="cc-month-title">
                <a href="<?php echo esc_url( $prev_month_url ); ?>"
                   class="cc-month-nav cc-prev-month <?php echo ! $has_prev_month ? 'disabled' : ''; ?>">
                    &laquo;
                </a>
                <?php echo esc_html( date( 'F Y', $start_of_month ) ); ?>
                <a href="<?php echo esc_url( $next_month_url ); ?>"
                   class="cc-month-nav cc-next-month <?php echo ! $has_next_month ? 'disabled' : ''; ?>">
                    &raquo;
                </a>
            </h3>
        </div>

        <div class="cc-nav-buttons">
            <a href="<?php echo esc_url( $first_url ); ?>" class="cc-btn <?php echo ( $current_id == $first_id ) ? 'disabled' : ''; ?>">&laquo; First</a>
            <a href="<?php echo esc_url( $prev_url );  ?>" class="cc-btn <?php echo ! $prev_id ? 'disabled' : ''; ?>">Prev</a>
            <a href="<?php echo esc_url( $rand_url );  ?>" class="cc-btn">Random</a>
            <a href="<?php echo esc_url( $next_url );  ?>" class="cc-btn <?php echo ! $next_id ? 'disabled' : ''; ?>">Next</a>
            <a href="<?php echo esc_url( $last_url );  ?>" class="cc-btn <?php echo ( $current_id == $last_id ) ? 'disabled' : ''; ?>">Today &raquo;</a>
        </div>

        <?php echo cc_render_month_grid( $ids, $current_date, $base_url ); ?>
    </div>
    <?php
    return ob_get_clean();
}


function cc_month_has_comics( $month_ym, $all_ids ) {
    if ( empty( $all_ids ) ) return false;
    static $cached_months = [];
    $cache_key = md5( implode( '-', $all_ids ) );
    if ( ! isset( $cached_months[ $cache_key ] ) ) {
        $months = [];
        foreach ( $all_ids as $pid ) {
            $months[ get_the_date( 'Y-m', $pid ) ] = true;
        }
        $cached_months[ $cache_key ] = $months;
    }
    return ! empty( $cached_months[ $cache_key ][ $month_ym ] );
}


function cc_render_month_grid( $all_ids, $current_date, $base_url ) {
    $target_month = date( 'Y-m', strtotime( $current_date ) );

    $month_ids = [];
    foreach ( $all_ids as $pid ) {
        if ( get_the_date( 'Y-m', $pid ) === $target_month ) {
            $month_ids[ get_the_date( 'j', $pid ) ] = $pid;
        }
    }

    $days_in_month = date( 't', strtotime( $target_month ) );
    $start_dow     = date( 'w', strtotime( $target_month . '-01' ) ); 
    $today = current_time( 'Y-m-d' );

    $html  = '<div class="cc-calendar-grid">';
    $html .= '<div class="cc-cal-table"><table><thead><tr>';
    $html .= '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>';
    $html .= '</tr></thead><tbody><tr>';

    $count = 0;
    for ( $i = 0; $i < $start_dow; $i++ ) { $html .= '<td></td>'; $count++; }

    for ( $d = 1; $d <= $days_in_month; $d++ ) {
        $is_active = isset( $month_ids[ $d ] );
        $is_curr   = ( $d == date( 'j', strtotime( $current_date ) ) );
        $date_str = sprintf( '%s-%02d', $target_month, $d );
        $is_missed = ! $is_active && $date_str < $today;
        $class = $is_curr ? 'cc-today' : '';
        if ( $is_missed ) $class .= ' cc-missed';

        $html .= '<td class="' . esc_attr( trim( $class ) ) . '">';
        if ( $is_active ) {
            $link  = cc_get_comic_pretty_url( $month_ids[ $d ], $base_url );
            $html .= '<a href="' . esc_url( $link ) . '">' . $d . '</a>';
        } elseif ( $is_missed ) {
            $html .= '<span class="cc-missed-date" title="' . esc_attr( 'No comic published on ' . $date_str ) . '"><i class="cc-missed-face" aria-hidden="true"></i></span>';
        } else {
            $html .= '<span class="muted">' . $d . '</span>';
        }
        $html .= '</td>';

        $count++;
        if ( $count % 7 == 0 ) $html .= '</tr><tr>';
    }

    $html .= '</tr></tbody></table></div></div>';
    return $html;
}
