<?php

/**
 * Layout Grid para eventos
 *
 * @package Event_Show
 */

// Variables disponibles: $events (WP_Query), $atts (array), $pagination_data (array)
?>

<?php
// Determinar clase de columnas
$columns = isset($atts['columns']) ? intval($atts['columns']) : 3;
$columns = max(2, min(4, $columns)); // Entre 2 y 4
$columns_class = 'grid-columns-' . $columns;
?>
<div class="event-show-grid <?php echo esc_attr($columns_class); ?>" data-layout="grid" data-pagination-type="<?php echo esc_attr($pagination_data['pagination_type']); ?>" data-paged="<?php echo esc_attr($pagination_data['paged']); ?>" data-max-pages="<?php echo esc_attr($pagination_data['max_pages']); ?>" data-atts='<?php echo esc_attr(json_encode($pagination_data['atts'])); ?>'>
    <?php if ($events->have_posts()) : ?>
        <div class="events-grid-container">
            <?php
            while ($events->have_posts()) :
                $events->the_post();
                include EVENT_SHOW_PLUGIN_DIR . 'templates/partials/event-card.php';
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