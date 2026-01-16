<?php

/**
 * Template para evento individual (single)
 *
 * @package Event_Show
 */

get_header();


$event_id = get_the_ID();
if (! $event_id || 'evento' !== get_post_type($event_id)) {
    echo '<p style="color:red">' . esc_html__('Evento no válido o no encontrado', 'event-show-base') . '</p>';
    get_footer();
    return;
}

$event_date = get_post_meta($event_id, '_event_date', true);
$event_time = get_post_meta($event_id, '_event_time', true);
$event_end_date = get_post_meta($event_id, '_event_end_date', true);
$event_end_time = get_post_meta($event_id, '_event_end_time', true);

// Obtener taxonomías (máximo 5 términos por tipo)
$organizadores = wp_get_post_terms($event_id, 'organizador', array('number' => 5));
$lugares = wp_get_post_terms($event_id, 'lugar', array('number' => 5));
$categorias = wp_get_post_terms($event_id, 'categoria_evento', array('number' => 5));
$clasificaciones = wp_get_post_terms($event_id, 'clasificacion_edad', array('number' => 5));

// Datos del organizador
$organizador = ! empty($organizadores) ? $organizadores[0] : null;
$organizador_image = $organizador ? get_term_meta($organizador->term_id, 'image', true) : '';
$organizador_phone = $organizador ? get_term_meta($organizador->term_id, 'phone', true) : '';
$organizador_email = $organizador ? get_term_meta($organizador->term_id, 'email', true) : '';
$organizador_website = $organizador ? get_term_meta($organizador->term_id, 'website', true) : '';

// Datos del lugar
$lugar = ! empty($lugares) ? $lugares[0] : null;
$lugar_address = $lugar ? get_term_meta($lugar->term_id, 'address', true) : '';
$lugar_map_url = $lugar ? get_term_meta($lugar->term_id, 'map_url', true) : '';

// URLs de calendario (generación simplificada para evitar recursión)
$ical_url = admin_url('admin-ajax.php?action=event_show_download_ical&event_id=' . $event_id);
$google_calendar_url = '#'; // Se generará con JavaScript si es necesario
?>

<article id="event-<?php echo esc_attr($event_id); ?>" <?php post_class('event-show-single'); ?>>

    <!-- Banner/Imagen destacada -->
    <?php if (has_post_thumbnail()) : ?>
        <div class="event-banner">
            <?php the_post_thumbnail('full'); ?>
        </div>
    <?php endif; ?>

    <div class="event-content-wrapper">
        <!-- Título y botones de acción -->
        <header class="event-header">
            <h1 class="event-title"><?php the_title(); ?></h1>

            <div class="event-actions">
                <!-- Botón compartir -->
                <button class="event-share-btn" data-event-id="<?php echo esc_attr($event_id); ?>">
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e('Compartir', 'event-show-base'); ?>
                </button>

                <!-- Menú de calendarios -->
                <div class="event-calendar-dropdown">
                    <button class="event-calendar-btn">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Agregar al Calendario', 'event-show-base'); ?>
                    </button>
                    <div class="calendar-menu">
                        <a href="<?php echo esc_url($google_calendar_url); ?>" target="_blank">
                            <?php esc_html_e('Google Calendar', 'event-show-base'); ?>
                        </a>
                        <a href="<?php echo esc_url($ical_url); ?>">
                            <?php esc_html_e('iCal / Outlook', 'event-show-base'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Información del organizador -->
        <?php if ($organizador) : ?>
            <div class="event-organizer-card">
                <strong><?php esc_html_e('Organizado por:', 'event-show-base'); ?></strong>
                <div class="organizer-info">
                    <?php if ($organizador_image) : ?>
                        <div class="organizer-logo">
                            <?php echo wp_get_attachment_image($organizador_image, 'thumbnail'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="organizer-details">
                        <h3><?php echo esc_html($organizador->name); ?></h3>
                        <?php if ($organizador_phone) : ?>
                            <p><span class="dashicons dashicons-phone"></span> <?php echo esc_html($organizador_phone); ?></p>
                        <?php endif; ?>
                        <?php if ($organizador_email) : ?>
                            <p><span class="dashicons dashicons-email"></span> <a href="mailto:<?php echo esc_attr($organizador_email); ?>"><?php echo esc_html($organizador_email); ?></a></p>
                        <?php endif; ?>
                        <?php if ($organizador_website) : ?>
                            <p><span class="dashicons dashicons-admin-links"></span> <a href="<?php echo esc_url($organizador_website); ?>" target="_blank"><?php esc_html_e('Sitio Web', 'event-show-base'); ?></a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Detalles principales del evento -->
        <div class="event-details-box">
            <div class="event-detail-item">
                <span class="dashicons dashicons-calendar"></span>
                <div>
                    <strong><?php esc_html_e('Fecha y hora', 'event-show-base'); ?></strong>
                    <p><?php echo esc_html(Event_Show_Helpers::format_datetime($event_date, $event_time)); ?></p>
                    <?php if ($event_end_date) : ?>
                        <p><?php echo esc_html__('Hasta:', 'event-show-base') . ' ' . esc_html(Event_Show_Helpers::format_datetime($event_end_date, $event_end_time)); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($lugar) : ?>
                <div class="event-detail-item">
                    <span class="dashicons dashicons-location"></span>
                    <div>
                        <strong><?php esc_html_e('Lugar', 'event-show-base'); ?></strong>
                        <p><?php echo esc_html($lugar->name); ?></p>
                        <?php if ($lugar_address) : ?>
                            <p><?php echo esc_html($lugar_address); ?></p>
                        <?php endif; ?>
                        <?php if ($lugar_map_url) : ?>
                            <p><a href="<?php echo esc_url($lugar_map_url); ?>" target="_blank"><?php esc_html_e('Ver en Google Maps', 'event-show-base'); ?></a></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! empty($categorias)) : ?>
                <div class="event-detail-item">
                    <span class="dashicons dashicons-category"></span>
                    <div>
                        <strong><?php esc_html_e('Categoría', 'event-show-base'); ?></strong>
                        <p><?php echo esc_html($categorias[0]->name); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! empty($clasificaciones)) : ?>
                <div class="event-detail-item">
                    <span class="dashicons dashicons-groups"></span>
                    <div>
                        <strong><?php esc_html_e('Clasificación de edad', 'event-show-base'); ?></strong>
                        <p><?php echo esc_html($clasificaciones[0]->name); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulario de registro -->
        <?php
        $event_content = get_post_field('post_content', $event_id);
        if (false === strpos($event_content, '[event_show_registration')) : ?>
            <div class="event-registration-section">
                <h2><?php esc_html_e('Registrar Asistencia', 'event-show-base'); ?></h2>
                <?php echo do_shortcode('[event_show_registration event_id="' . $event_id . '"]'); ?>
            </div>
        <?php endif; ?>

        <!-- Descripción del evento -->
        <div class="event-description">
            <h2><?php esc_html_e('Descripción', 'event-show-base'); ?></h2>
            <?php
            // Remover temporalmente TODOS los filtros de the_content para evitar recursión
            global $wp_filter;
            $content_filters_backup = isset($wp_filter['the_content']) ? $wp_filter['the_content'] : null;
            remove_all_filters('the_content');

            $content = get_the_content();
            // Eliminar el shortcode de registro si está presente
            $content = preg_replace('/\[event_show_registration[^\]]*\]/i', '', $content);

            // Aplicar filtros básicos de WordPress (wpautop, wptexturize, etc)
            $content = wpautop($content);
            $content = do_shortcode($content);

            // Restaurar los filtros originales
            if ($content_filters_backup) {
                $wp_filter['the_content'] = $content_filters_backup;
            }

            echo $content;
            ?>
        </div>

    </div>
</article>

<?php
get_footer();
