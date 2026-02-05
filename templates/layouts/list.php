<?php

/**
 * Layout Lista para eventos
 *
 * @package Event_Show
 */

// Variables disponibles: $events (WP_Query), $atts (array), $pagination_data (array)
?>

<div class="event-show-list" data-layout="list" data-pagination-type="<?php echo esc_attr($pagination_data['pagination_type']); ?>" data-paged="<?php echo esc_attr($pagination_data['paged']); ?>" data-max-pages="<?php echo esc_attr($pagination_data['max_pages']); ?>" data-atts='<?php echo esc_attr(json_encode($pagination_data['atts'])); ?>'>
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

                $organizador = Event_Show_Helpers::get_event_organizer($event_id);
                $lugar = Event_Show_Helpers::get_event_location($event_id);
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
                            <?php $event_time_indef = get_post_meta($event_id, '_event_time_indef', true); ?>
                            <span class="event-date">
                                <?php echo esc_html(Event_Show_Helpers::format_date($event_date)); ?>
                                <?php if ($event_time && $event_time_indef !== '1') : ?>
                                    - <?php echo esc_html(Event_Show_Helpers::format_time($event_time)); ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($organizador) : ?>
                                <span class="event-organizer"><?php echo esc_html($organizador['name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <h3 class="event-list-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <div class="event-list-excerpt">
                            <?php the_excerpt(); ?>
                        </div>

                        <?php if ($lugar) : ?>
                            <p class="event-list-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($lugar['name']); ?>
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

        <?php if ($pagination_data['has_pagination'] && $pagination_data['max_pages'] > 1) : ?>
            <div class="event-show-pagination-wrapper">
                <?php if ($pagination_data['pagination_type'] === 'default') : ?>
                    <!-- Paginación tradicional con números -->
                    <div class="event-show-pagination-numbers">
                        <?php
                        $current_page = $pagination_data['paged'];
                        $max_pages = $pagination_data['max_pages'];

                        if ($current_page > 1) {
                            echo '<a href="?paged=' . ($current_page - 1) . '" class="pagination-btn pagination-prev">&laquo; ' . esc_html__('Anterior', 'event-show-base') . '</a>';
                        }

                        for ($i = 1; $i <= $max_pages; $i++) {
                            $class = ($i === $current_page) ? 'pagination-number active' : 'pagination-number';
                            echo '<a href="?paged=' . $i . '" class="' . esc_attr($class) . '">' . $i . '</a>';
                        }

                        if ($current_page < $max_pages) {
                            echo '<a href="?paged=' . ($current_page + 1) . '" class="pagination-btn pagination-next">' . esc_html__('Siguiente', 'event-show-base') . ' &raquo;</a>';
                        }
                        ?>
                    </div>
                <?php elseif ($pagination_data['pagination_type'] === 'load_more') : ?>
                    <!-- Botón "Cargar más" -->
                    <?php if ($pagination_data['paged'] < $pagination_data['max_pages']) : ?>
                        <div class="event-show-load-more">
                            <button class="event-load-more-btn" data-next-page="<?php echo esc_attr($pagination_data['paged'] + 1); ?>">
                                <span class="btn-text"><?php esc_html_e('Cargar más eventos', 'event-show-base'); ?></span>
                                <span class="btn-loading" style="display:none;"><?php esc_html_e('Cargando...', 'event-show-base'); ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php elseif ($pagination_data['pagination_type'] === 'infinite') : ?>
                    <!-- Infinite scroll trigger -->
                    <?php if ($pagination_data['paged'] < $pagination_data['max_pages']) : ?>
                        <div class="event-show-infinite-trigger" data-next-page="<?php echo esc_attr($pagination_data['paged'] + 1); ?>" style="height:1px;"></div>
                        <div class="event-show-infinite-loading" style="display:none;text-align:center;padding:20px;">
                            <?php esc_html_e('Cargando más eventos...', 'event-show-base'); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <p class="no-events-message"><?php esc_html_e('No se encontraron eventos', 'event-show-base'); ?></p>
    <?php endif; ?>
</div>