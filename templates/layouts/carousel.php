<?php

/**
 * Layout Carrusel para eventos
 *
 * @package Event_Show
 */

// Variables disponibles: $events (WP_Query), $atts (array)
$show_countdown = isset($atts['show_countdown']) && 'yes' === $atts['show_countdown'];
?>

<div class="event-show-carousel" data-show-countdown="<?php echo esc_attr($show_countdown ? '1' : '0'); ?>">
    <?php if ($events->have_posts()) : ?>
        <div class="carousel-container">
            <div class="carousel-track">
                <?php
                while ($events->have_posts()) :
                    $events->the_post();
                    $event_id = get_the_ID();
                    $event_date = get_post_meta($event_id, '_event_date', true);
                    $event_time = get_post_meta($event_id, '_event_time', true);
                    $thumbnail_id = get_post_meta($event_id, '_event_thumbnail', true);
                    $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : get_the_post_thumbnail_url($event_id, 'large');

                    $lugares = wp_get_post_terms($event_id, 'lugar');
                    $organizadores = wp_get_post_terms($event_id, 'organizador');

                    // Countdown
                    $countdown = $show_countdown ? Event_Show_Helpers::get_countdown($event_id) : null;
                ?>
                    <div class="carousel-slide">
                        <?php if ($thumbnail_url) : ?>
                            <div class="carousel-slide-image">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
                                <div class="carousel-slide-overlay"></div>
                            </div>
                        <?php endif; ?>

                        <div class="carousel-slide-content">
                            <div class="slide-meta">
                                <span class="slide-date"><?php echo esc_html(Event_Show_Helpers::format_date($event_date)); ?></span>
                                <?php if ($event_time) : ?>
                                    <span class="slide-time"><?php echo esc_html(Event_Show_Helpers::format_time($event_time)); ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 class="slide-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>

                            <?php if (! empty($organizadores)) : ?>
                                <p class="slide-organizer"><?php echo esc_html($organizadores[0]->name); ?></p>
                            <?php endif; ?>

                            <?php if (! empty($lugares)) : ?>
                                <p class="slide-location">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($lugares[0]->name); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($countdown) : ?>
                                <div class="slide-countdown" data-countdown="<?php echo esc_attr($countdown['total_seconds']); ?>">
                                    <div class="countdown-item">
                                        <span class="countdown-value"><?php echo esc_html($countdown['days']); ?></span>
                                        <span class="countdown-label"><?php esc_html_e('días', 'event-show-base'); ?></span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-value"><?php echo esc_html($countdown['hours']); ?></span>
                                        <span class="countdown-label"><?php esc_html_e('hrs', 'event-show-base'); ?></span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-value"><?php echo esc_html($countdown['minutes']); ?></span>
                                        <span class="countdown-label"><?php esc_html_e('min', 'event-show-base'); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <a href="<?php the_permalink(); ?>" class="slide-btn">
                                <?php esc_html_e('Ver Evento', 'event-show-base'); ?>
                            </a>
                        </div>
                    </div>
                <?php
                endwhile;
                ?>
            </div>

            <!-- Controles del carrusel -->
            <button class="carousel-control carousel-prev" aria-label="<?php esc_attr_e('Anterior', 'event-show-base'); ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </button>
            <button class="carousel-control carousel-next" aria-label="<?php esc_attr_e('Siguiente', 'event-show-base'); ?>">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>

            <!-- Indicadores -->
            <div class="carousel-indicators">
                <?php
                $count = $events->post_count;
                for ($i = 0; $i < $count; $i++) :
                ?>
                    <button class="carousel-indicator<?php echo 0 === $i ? ' active' : ''; ?>" data-slide="<?php echo esc_attr($i); ?>"></button>
                <?php
                endfor;
                ?>
            </div>
        </div>
    <?php else : ?>
        <p class="no-events-message"><?php esc_html_e('No se encontraron eventos', 'event-show-base'); ?></p>
    <?php endif; ?>
</div>