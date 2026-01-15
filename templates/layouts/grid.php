<?php

/**
 * Layout Grid para eventos
 *
 * @package Event_Show
 */

// Variables disponibles: $events (WP_Query), $atts (array)
?>

<div class="event-show-grid">
    <?php if ($events->have_posts()) : ?>
        <div class="events-grid-container">
            <?php
            while ($events->have_posts()) :
                $events->the_post();
                include EVENT_SHOW_PLUGIN_DIR . 'templates/partials/event-card.php';
            endwhile;
            ?>
        </div>
    <?php else : ?>
        <p class="no-events-message"><?php esc_html_e('No se encontraron eventos', 'event-show-base'); ?></p>
    <?php endif; ?>
</div>