<?php

/**
 * Layout Lista para eventos
 *
 * @package Event_Show
 */

// Variables disponibles: $events (WP_Query), $atts (array)
?>

<div class="event-show-list">
    <?php if ($events->have_posts()) : ?>
        <div class="events-list-container">
            <?php
            while ($events->have_posts()) :
                $events->the_post();
                $event_id = get_the_ID();
                $event_date = get_post_meta($event_id, '_event_date', true);
                $event_time = get_post_meta($event_id, '_event_time', true);
                $thumbnail_id = get_post_meta($event_id, '_event_thumbnail', true);
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : get_the_post_thumbnail_url($event_id, 'medium');

                $lugares = wp_get_post_terms($event_id, 'lugar');
                $organizadores = wp_get_post_terms($event_id, 'organizador');
            ?>
                <div class="event-list-item">
                    <?php if ($thumbnail_url) : ?>
                        <div class="event-list-image">
                            <a href="<?php the_permalink(); ?>">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="event-list-content">
                        <div class="event-list-meta">
                            <span class="event-date"><?php echo esc_html(Event_Show_Helpers::format_datetime($event_date, $event_time)); ?></span>
                            <?php if (! empty($organizadores)) : ?>
                                <span class="event-organizer"><?php echo esc_html($organizadores[0]->name); ?></span>
                            <?php endif; ?>
                        </div>

                        <h3 class="event-list-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <div class="event-list-excerpt">
                            <?php the_excerpt(); ?>
                        </div>

                        <?php if (! empty($lugares)) : ?>
                            <p class="event-list-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($lugares[0]->name); ?>
                            </p>
                        <?php endif; ?>

                        <a href="<?php the_permalink(); ?>" class="event-list-btn">
                            <?php esc_html_e('Ver más', 'event-show-base'); ?> →
                        </a>
                    </div>
                </div>
            <?php
            endwhile;
            ?>
        </div>
    <?php else : ?>
        <p class="no-events-message"><?php esc_html_e('No se encontraron eventos', 'event-show-base'); ?></p>
    <?php endif; ?>
</div>