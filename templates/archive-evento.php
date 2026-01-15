<?php

/**
 * Template para archivo/lista de eventos
 *
 * @package Event_Show
 */

get_header();
?>

<div class="event-show-archive">
    <header class="archive-header">
        <?php
        if (is_post_type_archive('evento')) {
        ?>
            <h1><?php esc_html_e('Todos los Eventos', 'event-show-base'); ?></h1>
        <?php
        } else {
            the_archive_title('<h1>', '</h1>');
            the_archive_description('<div class="archive-description">', '</div>');
        }
        ?>
    </header>

    <?php if (have_posts()) : ?>
        <div class="events-grid">
            <?php
            while (have_posts()) :
                the_post();
                include EVENT_SHOW_PLUGIN_DIR . 'templates/partials/event-card.php';
            endwhile;
            ?>
        </div>

        <?php
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => __('&laquo; Anterior', 'event-show-base'),
            'next_text' => __('Siguiente &raquo;', 'event-show-base'),
        ));
        ?>
    <?php else : ?>
        <p><?php esc_html_e('No se encontraron eventos', 'event-show-base'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
