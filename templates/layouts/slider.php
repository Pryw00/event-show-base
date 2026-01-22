<?php

/**
 * Layout Slider para eventos
 * 
 * Muestra un evento destacado a la izquierda con información
 * y un slider de imágenes/posters de eventos a la derecha
 *
 * @package Event_Show
 */

// Variables disponibles: $events (WP_Query), $atts (array)
$autoplay = isset($atts['autoplay']) && 'true' === $atts['autoplay'];
$autoplay_speed = isset($atts['autoplay_speed']) ? intval($atts['autoplay_speed']) : 5000;
?>

<div class="event-show-slider" data-autoplay="<?php echo esc_attr($autoplay ? '1' : '0'); ?>" data-autoplay-speed="<?php echo esc_attr($autoplay_speed); ?>">
    <?php if ($events->have_posts()) : ?>
        <div class="slider-wrapper">
            <!-- Panel izquierdo con información del evento activo -->
            <div class="slider-info-panel">
                <?php
                $events->rewind_posts();
                $first = true;
                while ($events->have_posts()) :
                    $events->the_post();
                    $event_id = get_the_ID();
                    $event_date = get_post_meta($event_id, '_event_date', true);
                    $event_time = get_post_meta($event_id, '_event_time', true);
                    $event_time_indef = get_post_meta($event_id, '_event_time_indef', true);
                    $lugares = wp_get_post_terms($event_id, 'lugar');
                    $categorias = wp_get_post_terms($event_id, 'categoria_evento');
                ?>
                    <div class="slider-info-item<?php echo $first ? ' active' : ''; ?>" data-slide-info="<?php echo esc_attr($event_id); ?>">
                        <?php if (! empty($categorias)) : ?>
                            <span class="slider-category"><?php echo esc_html($categorias[0]->name); ?></span>
                        <?php endif; ?>

                        <h2 class="slider-title"><?php the_title(); ?></h2>

                        <div class="slider-meta">
                            <?php if ($event_date) : ?>
                                <span class="slider-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo esc_html(Event_Show_Helpers::format_date($event_date, 'd M Y')); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($event_time && $event_time_indef !== '1') : ?>
                                <span class="slider-time">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html(Event_Show_Helpers::format_time($event_time, 'H:i')); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (! empty($lugares)) : ?>
                            <p class="slider-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($lugares[0]->name); ?>
                            </p>
                        <?php endif; ?>

                        <a href="<?php the_permalink(); ?>" class="slider-btn">
                            <?php esc_html_e('Ver Detalles', 'event-show-base'); ?>
                        </a>
                    </div>
                <?php
                    $first = false;
                endwhile;
                ?>
            </div>

            <!-- Panel derecho con slider de imágenes -->
            <div class="slider-images-panel">
                <div class="slider-images-track">
                    <?php
                    $events->rewind_posts();
                    $index = 0;
                    while ($events->have_posts()) :
                        $events->the_post();
                        $event_id = get_the_ID();
                        $thumbnail_id = get_post_meta($event_id, '_event_thumbnail', true);
                        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : get_the_post_thumbnail_url($event_id, 'large');

                        if (! $thumbnail_url) {
                            $thumbnail_url = EVENT_SHOW_PLUGIN_URL . 'assets/images/default-event.jpg';
                        }

                        // Determinar clase según posición
                        $position_class = '';
                        if ($index === 0) {
                            $position_class = 'active';
                        } elseif ($index === 1) {
                            $position_class = 'next';
                        } elseif ($index === 2) {
                            $position_class = 'far-next';
                        }
                    ?>
                        <div class="slider-image-item <?php echo esc_attr($position_class); ?>"
                            data-slide-index="<?php echo esc_attr($index); ?>"
                            data-event-id="<?php echo esc_attr($event_id); ?>">
                            <a href="<?php the_permalink(); ?>" class="slider-image-link">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
                            </a>
                        </div>
                    <?php
                        $index++;
                    endwhile;
                    ?>
                </div>

                <!-- Controles del slider -->
                <div class="slider-controls">
                    <button class="slider-control slider-prev" aria-label="<?php esc_attr_e('Anterior', 'event-show-base'); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                    <button class="slider-control slider-next" aria-label="<?php esc_attr_e('Siguiente', 'event-show-base'); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>
        </div>
    <?php else : ?>
        <p class="no-events-message"><?php esc_html_e('No se encontraron eventos', 'event-show-base'); ?></p>
    <?php endif; ?>
</div>