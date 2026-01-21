<?php

/**
 * Tarjeta parcial de evento para reutilizar
 *
 * @package Event_Show
 */

$event_id = get_the_ID();
$event_date = get_post_meta($event_id, '_event_date', true);
$event_time = get_post_meta($event_id, '_event_time', true);
$thumbnail_id = get_post_meta($event_id, '_event_thumbnail', true);
$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : get_the_post_thumbnail_url($event_id, 'medium');

$lugares = wp_get_post_terms($event_id, 'lugar');
$organizadores = wp_get_post_terms($event_id, 'organizador');
?>

<div class="event-card">
    <?php if ($thumbnail_url) : ?>
        <div class="event-card-image">
            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">
            <div class="event-card-overlay"></div>
        </div>
    <?php endif; ?>

    <div class="event-card-content">
        <div class="event-card-date">
            <?php echo esc_html(Event_Show_Helpers::format_date($event_date, 'd M Y')); ?>
            <?php if ($event_time) : ?>
                <span class="event-card-time"><?php echo esc_html(Event_Show_Helpers::format_time($event_time, 'H:i')); ?></span>
            <?php endif; ?>
        </div>

        <h3 class="event-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php if (! empty($organizadores)) : ?>
            <p class="event-card-organizer">
                <?php echo esc_html($organizadores[0]->name); ?>
            </p>
        <?php endif; ?>

        <?php if (! empty($lugares)) : ?>
            <p class="event-card-location">
                <span class="dashicons dashicons-location"></span>
                <?php echo esc_html($lugares[0]->name); ?>
            </p>
        <?php endif; ?>

        <a href="<?php the_permalink(); ?>" class="event-card-btn">
            <?php esc_html_e('Ver Detalles', 'event-show-base'); ?>
        </a>
    </div>
</div>