<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcode: [comic_calendar]
 */
function cc_shortcode_handler( $atts ) {
    $current_id = cc_resolve_current_comic_id();
    if ( ! $current_id ) return '<p>No comics found.</p>';

    // --- Cache Strategy --- (Retrieved from cache/DB)
    $all_ids = get_transient( 'cc_all_ids' );
    if ( false === $all_ids ) {
        $q = new WP_Query([
            'post_type'      => 'cc_comic',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids'
        ]);
        $all_ids = $q->posts;
        set_transient( 'cc_all_ids', $all_ids, 12 * HOUR_IN_SECONDS );
    }

    // Prep Data
    $title = get_the_title( $current_id );
    $img   = get_the_post_thumbnail_url( $current_id, 'full' );
    $base_url   = cc_get_page_url();
    $comic_link = cc_get_comic_pretty_url( $current_id, $base_url );
    
    ob_start();
    ?>
    <div class="cc-wrapper">
        <?php if ($img): ?>
            <div class="cc-comic-display">
                <a href="<?php echo esc_url($comic_link); ?>">
                    <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" />
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php 
    // Load Controls
    echo cc_render_controls( $all_ids, $current_id, $base_url );
    return ob_get_clean();
}
add_shortcode( 'comic_calendar', 'cc_shortcode_handler' );


/**
 * Renders the calendar and navigation buttons with the new Month Pager.
 */
function cc_render_controls( $ids, $current_id, $base_url ) {
    $current_date = get_the_date( 'Y-m-d', $current_id );
    $calendar_title = cc_get_calendar_title();
    
    // --- Nav Button Logic ---
    $index    = array_search( $current_id, $ids );
    $first_id = end($ids);
    $last_id  = $ids[0];
    
    $prev_id  = ($index !== false && isset($ids[$index+1])) ? $ids[$index+1] : null;
    $next_id  = ($index !== false && isset($ids[$index-1])) ? $ids[$index-1] : null;
    $rand_id  = $ids[ array_rand($ids) ];

    // --- Month Pager Logic ---
    $target_month      = date('Y-m', strtotime($current_date));
    $start_of_month    = strtotime($target_month . '-01');

    // Calculate Previous Month URL (uses helper to check if a comic exists in that month)
    $prev_month_ts  = strtotime("first day of last month", $start_of_month);
    $prev_month_str = date('Y-m', $prev_month_ts);
    $has_prev_month = cc_month_has_comics($prev_month_str, $ids);
    $prev_month_url = $has_prev_month ? add_query_arg( 'cc_month', $prev_month_str, $base_url ) : '#';
    $is_first_month = !$has_prev_month;

    // Calculate Next Month URL
    $next_month_ts  = strtotime("first day of next month", $start_of_month);
    $next_month_str = date('Y-m', $next_month_ts);
    $has_next_month = cc_month_has_comics($next_month_str, $ids);
    $next_month_url = $has_next_month ? add_query_arg( 'cc_month', $next_month_str, $base_url ) : '#';
    $is_last_month = !$has_next_month;

    // Links (using pretty permalinks)
    $first_url = cc_get_comic_pretty_url( $first_id, $base_url );
    $prev_url  = $prev_id ? cc_get_comic_pretty_url( $prev_id, $base_url ) : '#';
    $rand_url  = cc_get_comic_pretty_url( $rand_id, $base_url );
    $next_url  = $next_id ? cc_get_comic_pretty_url( $next_id, $base_url ) : '#';
    $last_url  = cc_get_comic_pretty_url( $last_id, $base_url );
    
    // Render
    ob_start();
    ?>
    <div class="cc-controls-container">

        <div class="cc-calendar-eyebrow"><?php echo esc_html( $calendar_title ); ?></div>

        <div class="cc-month-pager">
           
            <h3 class="cc-month-title">
             <a href="<?php echo esc_url($prev_month_url); ?>" class="cc-month-nav cc-prev-month <?php if ($is_first_month) echo 'disabled'; ?>">
                &laquo;
            </a>
                <?php echo esc_html( date('F Y', $start_of_month) ); ?>
            <a href="<?php echo esc_url($next_month_url); ?>" class="cc-month-nav cc-next-month <?php if ($is_last_month) echo 'disabled'; ?>">
                &raquo;
            </a>
            </h3>
        </div>

        <div class="cc-nav-buttons">
            <a href="<?php echo esc_url($first_url); ?>" class="cc-btn <?php echo ($current_id == $first_id) ? 'disabled' : ''; ?>">&laquo; First</a>
            <a href="<?php echo esc_url($prev_url); ?>" class="cc-btn <?php echo !$prev_id ? 'disabled' : ''; ?>">Prev</a>
            <a href="<?php echo esc_url($rand_url); ?>" class="cc-btn">Random</a>
            <a href="<?php echo esc_url($next_url); ?>" class="cc-btn <?php echo !$next_id ? 'disabled' : ''; ?>">Next</a>
            <a href="<?php echo esc_url($last_url); ?>" class="cc-btn <?php echo ($current_id == $last_id) ? 'disabled' : ''; ?>">Today &raquo;</a>
        </div>
        
        <?php echo cc_render_month_grid($ids, $current_date, $base_url); ?>
    </div>
    <?php
    return ob_get_clean();
}


/**
 * Helper function to check if any comic exists in a given YYYY-MM month.
 */
function cc_month_has_comics($month_ym, $all_ids) {
    if (empty($all_ids)) return false;

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


function cc_render_month_grid($all_ids, $current_date, $base_url) {
    // Determine month to show (based on current comic)
    $target_month = date('Y-m', strtotime($current_date));
    
    // Filter IDs to only this month for the grid
    $month_ids = [];
    foreach($all_ids as $pid) {
        if( get_the_date('Y-m', $pid) === $target_month ) {
            $month_ids[ get_the_date('j', $pid) ] = $pid; // Key by day number (1-31)
        }
    }
    
    $days_in_month = date('t', strtotime($target_month));
    $start_dow     = date('w', strtotime($target_month . '-01'));
    
    $html = '<div class="cc-calendar-grid">';
    
    // REDUNDANCY FIX: Removed the redundant month header here.
    
    $html .= '<div class="cc-cal-table"><table><thead><tr>';
    $html .= '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>';
    $html .= '</tr></thead><tbody><tr>';
    
    // ... rest of grid logic ...
    $count = 0;
    for($i=0; $i<$start_dow; $i++) { $html .= '<td></td>'; $count++; }
    
    for($d=1; $d<=$days_in_month; $d++) {
        $is_active = isset($month_ids[$d]);
        $is_curr   = ($d == date('j', strtotime($current_date)));
        $class     = $is_curr ? 'cc-today' : '';
        
        $html .= '<td class="'.$class.'">';
        if($is_active) {
            // Use pretty permalinks for comic detail links
            $link = cc_get_comic_pretty_url( $month_ids[$d], $base_url );
            $html .= '<a href="'.esc_url($link).'">'.$d.'</a>';
        } else {
            $html .= '<span class="muted">'.$d.'</span>';
        }
        $html .= '</td>';
        
        $count++;
        if($count % 7 == 0) $html .= '</tr><tr>';
    }
    $html .= '</tr></tbody></table></div></div>';
    return $html;
}