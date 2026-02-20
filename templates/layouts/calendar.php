<?php

/**
 * Layout Calendario para eventos
 *
 * @package Event_Show
 */

// Variables disponibles: $events (WP_Query), $atts (array)
?>

<div class="event-show-calendar">
    <?php if ($events->have_posts()) : ?>
        <div class="calendar-view">
            <?php
            // Agrupar eventos por mes
            $events_by_month = array();

            while ($events->have_posts()) :
                $events->the_post();
                $event_id = get_the_ID();
                $event_date = get_post_meta($event_id, '_event_date', true);

                if ($event_date) {
                    $timestamp = Event_Show_Helpers::date_to_timestamp($event_date);
                    if ($timestamp !== false) {
                        $month_key = date('Y-m', $timestamp);
                        $month_name = date_i18n('F Y', $timestamp);

                        if (! isset($events_by_month[$month_key])) {
                            $events_by_month[$month_key] = array(
                                'name' => $month_name,
                                'events' => array(),
                            );
                        }

                        $events_by_month[$month_key]['events'][] = $event_id;
                    }
                }
            endwhile;

            // Mostrar eventos agrupados
            foreach ($events_by_month as $month_data) :
            ?>
                <div class="calendar-month">
                    <h3 class="month-title"><?php echo esc_html($month_data['name']); ?></h3>
                    <div class="month-events">
                        <?php
                        foreach ($month_data['events'] as $event_id) :
                            $event = get_post($event_id);
                            $event_date = get_post_meta($event_id, '_event_date', true);
                            $event_time = get_post_meta($event_id, '_event_time', true);
                            $event_time_indef = get_post_meta($event_id, '_event_time_indef', true);
                            $lugar = Event_Show_Helpers::get_event_location($event_id);

                            $timestamp = Event_Show_Helpers::date_to_timestamp($event_date);
                            $day = $timestamp !== false ? date_i18n('d', $timestamp) : '';
                            $day_name = $timestamp !== false ? date_i18n('D', $timestamp) : '';
                        ?>
                            <div class="calendar-event-item">
                                <div class="calendar-event-date">
                                    <span class="day-number"><?php echo esc_html($day); ?></span>
                                    <span class="day-name"><?php echo esc_html($day_name); ?></span>
                                </div>
                                <div class="calendar-event-info">
                                    <h4><a href="<?php echo esc_url(get_permalink($event_id)); ?>"><?php echo esc_html($event->post_title); ?></a></h4>
                                    <?php if ($event_time && $event_time_indef !== '1') : ?>
                                        <p class="event-time"><?php echo esc_html(Event_Show_Helpers::format_time($event_time)); ?></p>
                                    <?php endif; ?>
                                    <?php if ($lugar) : ?>
                                        <p class="event-location"><span class="dashicons dashicons-location"></span> <?php echo esc_html($lugar['name']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php
                        endforeach;
                        ?>
                    </div>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    <?php else : ?>
        <p class="no-events-message"><?php esc_html_e('No se encontraron eventos', 'event-show-base'); ?></p>
    <?php endif; ?>
</div>