<?php

/**
 * Template alternativo para single evento (usado en shortcode de contenido)
 * Este template se usa cuando se filtra el contenido via the_content
 * NO debe incluir get_header/get_footer porque ya están en el tema
 *
 * @package Event_Show
 */

$event_id = get_the_ID();
if (! $event_id || 'evento' !== get_post_type($event_id)) {
    echo '<p style="color:red">' . esc_html__('Evento no válido o no encontrado', 'event-show-base') . '</p>';
    return;
}

$event_date = get_post_meta($event_id, '_event_date', true);
$event_time = get_post_meta($event_id, '_event_time', true);
$event_time_indef = get_post_meta($event_id, '_event_time_indef', true);
$event_end_date = get_post_meta($event_id, '_event_end_date', true);
$event_end_time = get_post_meta($event_id, '_event_end_time', true);

// Obtener taxonomías (máximo 5 términos por tipo)
$categorias = wp_get_post_terms($event_id, 'categoria_evento', array('number' => 5));
$clasificaciones = wp_get_post_terms($event_id, 'clasificacion_edad', array('number' => 5));

// Datos del organizador usando helper
$organizador_data = Event_Show_Helpers::get_event_organizer($event_id);
$organizador = null;
$organizador_name = '';
$organizador_image = '';
$organizador_phone = '';
$organizador_email = '';
$organizador_website = '';
$organizador_facebook = '';
$organizador_instagram = '';
$organizador_twitter = '';

if ($organizador_data) {
    $organizador_name = $organizador_data['name'];

    if ($organizador_data['type'] === 'establecimiento') {
        $establecimiento = get_post($organizador_data['id']);
        if ($establecimiento) {
            $organizador_image = get_post_thumbnail_id($establecimiento->ID);
            $organizador_phone = get_post_meta($establecimiento->ID, '_scl_telefono', true);
            $organizador_email = get_post_meta($establecimiento->ID, '_scl_email', true);
            $organizador_website = get_post_meta($establecimiento->ID, '_scl_website', true);
            $organizador_facebook = get_post_meta($establecimiento->ID, '_scl_facebook', true);
            $organizador_instagram = get_post_meta($establecimiento->ID, '_scl_instagram', true);
            $organizador_twitter = get_post_meta($establecimiento->ID, '_scl_twitter', true);
        }
        // Crear objeto similar a término para compatibilidad
        $organizador = (object) array(
            'term_id' => $organizador_data['id'],
            'name' => $organizador_name,
            'type' => 'establecimiento'
        );
    } elseif ($organizador_data['type'] === 'organizador') {
        $organizador = get_term($organizador_data['id'], 'organizador');
        if ($organizador && !is_wp_error($organizador)) {
            $organizador_image = get_term_meta($organizador->term_id, 'image', true);
            $organizador_phone = get_term_meta($organizador->term_id, 'phone', true);
            $organizador_email = get_term_meta($organizador->term_id, 'email', true);
            $organizador_website = get_term_meta($organizador->term_id, 'website', true);
            $organizador_facebook = get_term_meta($organizador->term_id, 'facebook', true);
            $organizador_instagram = get_term_meta($organizador->term_id, 'instagram', true);
            $organizador_twitter = get_term_meta($organizador->term_id, 'twitter', true);
        }
    }
}

// Datos del lugar usando helper
$lugar_data = Event_Show_Helpers::get_event_location($event_id);
$lugar = null;
$lugar_name = '';
$lugar_address = '';
$lugar_map_url = '';

if ($lugar_data) {
    $lugar_name = $lugar_data['name'];

    if ($lugar_data['type'] === 'establecimiento') {
        $establecimiento = get_post($lugar_data['id']);
        if ($establecimiento) {
            $lugar_address = get_post_meta($establecimiento->ID, '_scl_direccion', true);
            $lugar_map_url = get_post_meta($establecimiento->ID, '_scl_map_url', true);
        }
        // Crear objeto similar a término para compatibilidad
        $lugar = (object) array(
            'term_id' => $lugar_data['id'],
            'name' => $lugar_name,
            'type' => 'establecimiento'
        );
    } elseif ($lugar_data['type'] === 'lugar') {
        $lugar = get_term($lugar_data['id'], 'lugar');
        if ($lugar && !is_wp_error($lugar)) {
            $lugar_address = get_term_meta($lugar->term_id, 'address', true);
            $lugar_map_url = get_term_meta($lugar->term_id, 'map_url', true);
        }
    }
}

if ($lugar_data) {
    if ($lugar_data['type'] === 'establecimiento') {
        $establecimiento = get_post($lugar_data['id']);
        if ($establecimiento) {
            $lugar_address = get_post_meta($establecimiento->ID, '_scl_direccion', true);
            $lugar_map_url = get_post_meta($establecimiento->ID, '_scl_map_url', true);
        }
    } elseif ($lugar_data['type'] === 'lugar') {
        $lugar = get_term($lugar_data['id'], 'lugar');
        if ($lugar && !is_wp_error($lugar)) {
            $lugar_address = get_term_meta($lugar->term_id, 'address', true);
            $lugar_map_url = get_term_meta($lugar->term_id, 'map_url', true);
        }
    }
}

// URLs de calendario
$ical_url = admin_url('admin-ajax.php?action=event_show_download_ical&event_id=' . $event_id);

// Imagen del banner
$banner_id = get_post_meta($event_id, '_event_banner', true);
$has_custom_banner = $banner_id && is_numeric($banner_id) && $banner_id > 0;

// Debug JS para consola
add_action('wp_footer', function () use ($banner_id, $has_custom_banner, $event_id) {
?>
    <script>
        console.log('[EventShow] Banner personalizado:', <?php echo json_encode($banner_id); ?>);
        console.log('[EventShow] ¿Tiene banner personalizado?', <?php echo $has_custom_banner ? 'true' : 'false'; ?>);
        <?php
        $default_banner_id = get_option('event_show_default_banner', '');
        ?>
        console.log('[EventShow] Banner por defecto:', <?php echo json_encode($default_banner_id); ?>);
        <?php
        $banner_url = '';
        if ($has_custom_banner) {
            $banner_url = wp_get_attachment_image_url($banner_id, 'full');
        } else if ($default_banner_id && is_numeric($default_banner_id) && $default_banner_id > 0) {
            $banner_url = wp_get_attachment_image_url($default_banner_id, 'full');
        } else {
            $banner_url = get_the_post_thumbnail_url($event_id, 'full');
        }
        ?>
        console.log('[EventShow] URL final del banner:', <?php echo json_encode($banner_url); ?>);
    </script>
<?php
});

// Determinar qué banner usar
if ($has_custom_banner) {
    // Usar banner personalizado del evento
    $banner_url = wp_get_attachment_image_url($banner_id, 'full');
} else {
    // Intentar usar banner por defecto
    $default_banner_id = get_option('event_show_default_banner', '');
    if ($default_banner_id && is_numeric($default_banner_id) && $default_banner_id > 0) {
        $banner_url = wp_get_attachment_image_url($default_banner_id, 'full');
    } else {
        // Si no hay banner por defecto, usar imagen destacada del post
        $banner_url = get_the_post_thumbnail_url($event_id, 'full');
    }
}
?>

<div class="event-show-content">
    <article id="event-<?php echo esc_attr($event_id); ?>" <?php post_class('event-show-single event-single-modern'); ?>>

        <!-- Hero Banner con información superpuesta -->
        <div class="event-hero-banner" <?php if ($banner_url) : ?>style="background-image: url('<?php echo esc_url($banner_url); ?>');" <?php endif; ?>>
            <?php if (!$has_custom_banner) : ?>
                <div class="event-hero-overlay"></div>
                <div class="event-hero-content">
                    <div class="event-hero-info">
                        <h1 class="event-hero-title"><?php the_title(); ?></h1>
                        <div class="event-hero-meta">
                            <?php if ($event_date) : ?>
                                <span class="event-hero-date">
                                    <?php echo esc_html(Event_Show_Helpers::format_date($event_date, 'l, d M Y')); ?>
                                    <?php if ($event_time && $event_time_indef !== '1') : ?>
                                        - <?php echo esc_html(Event_Show_Helpers::format_time($event_time, 'H:i')); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($lugar_data) : ?>
                                <span class="event-hero-location">
                                    <?php echo esc_html($lugar_data['name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="event-content-wrapper event-two-columns">
            <!-- Columna principal (izquierda) -->
            <div class="event-main-column">
                <header class="event-header-actions">
                    <h2 class="event-title-section"><?php the_title(); ?></h2>
                    <div class="event-actions">
                        <button class="event-share-btn" data-event-id="<?php echo esc_attr($event_id); ?>" title="<?php esc_attr_e('Compartir evento', 'event-show-base'); ?>">
                            <span class="dashicons dashicons-share"></span>
                        </button>
                        <a href="<?php echo esc_url($ical_url); ?>" class="event-calendar-btn" title="<?php esc_attr_e('Agregar al calendario', 'event-show-base'); ?>" download>
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </a>
                    </div>
                </header>

                <?php if ($organizador) : ?>
                    <div class="event-organizer-inline">
                        <?php if ($organizador_image) : ?>
                            <div class="organizer-avatar">
                                <?php echo wp_get_attachment_image($organizador_image, 'thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="organizer-text">
                            <span class="organizer-label"><?php esc_html_e('Organizado por', 'event-show-base'); ?></span>
                            <strong class="organizer-name"><?php echo esc_html($organizador_name); ?></strong>
                        </div>
                        <div class="organizer-text-info">
                            <?php if ($organizador_phone) : ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $organizador_phone); ?>" target="_blank" title="WhatsApp" style="margin-left:6px;"><span class="dashicons dashicons-whatsapp"></span></a>
                            <?php endif; ?>
                            <?php if ($organizador_email) : ?>
                                <a href="mailto:<?php echo esc_attr($organizador_email); ?>" title="Email" style="margin-left:6px;"><span class="dashicons dashicons-email"></span></a>
                            <?php endif; ?>
                            <?php if ($organizador_website) : ?>
                                <a href="<?php echo esc_url($organizador_website); ?>" target="_blank" title="Web" style="margin-left:6px;"><span class="dashicons dashicons-admin-site"></span></a>
                            <?php endif; ?>
                            <?php if ($organizador_facebook) : ?>
                                <a href="<?php echo esc_url($organizador_facebook); ?>" target="_blank" title="Facebook" style="margin-left:6px;"><span class="dashicons dashicons-facebook"></span></a>
                            <?php endif; ?>
                            <?php if ($organizador_instagram) : ?>
                                <a href="<?php echo esc_url($organizador_instagram); ?>" target="_blank" title="Instagram" style="margin-left:6px;"><span class="dashicons dashicons-instagram"></span></a>
                            <?php endif; ?>
                            <?php if ($organizador_twitter) : ?>
                                <a href="<?php echo esc_url($organizador_twitter); ?>" target="_blank" title="Twitter" style="margin-left:6px;"><span class="dashicons dashicons-twitter"></span></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="event-details-grid">
                    <div class="event-detail-card">
                        <span class="detail-icon dashicons dashicons-calendar"></span>
                        <div class="detail-info">
                            <span class="detail-label"><?php esc_html_e('Fecha y hora', 'event-show-base'); ?></span>
                            <span class="detail-value">
                                <?php echo esc_html(Event_Show_Helpers::format_date($event_date, 'l, d M Y')); ?>
                                <?php if ($event_time && $event_time_indef !== '1') : ?>
                                    - <?php echo esc_html(Event_Show_Helpers::format_time($event_time, 'H:i')); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($lugar) : ?>
                        <div class="event-detail-card">
                            <span class="detail-icon dashicons dashicons-location"></span>
                            <div class="detail-info">
                                <span class="detail-label"><?php esc_html_e('Lugar', 'event-show-base'); ?></span>
                                <span class="detail-value">
                                    <?php if (!empty($lugar_map_url)) : ?>
                                        <a href="<?php echo esc_url($lugar_map_url); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e('Ver ubicación', 'event-show-base'); ?>">
                                            <?php echo esc_html($lugar_name); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html($lugar_name); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($categorias)) : ?>
                        <div class="event-detail-card">
                            <span class="detail-icon dashicons dashicons-category"></span>
                            <div class="detail-info">
                                <span class="detail-label"><?php esc_html_e('Categoría del evento', 'event-show-base'); ?></span>
                                <span class="detail-value"><?php echo esc_html($categorias[0]->name); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($clasificaciones)) : ?>
                        <div class="event-detail-card">
                            <span class="detail-icon dashicons dashicons-groups"></span>
                            <div class="detail-info">
                                <span class="detail-label"><?php esc_html_e('Clasificación de edad', 'event-show-base'); ?></span>
                                <span class="detail-value"><?php echo esc_html($clasificaciones[0]->name); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="event-description-section">
                    <h3 class="section-title"><?php esc_html_e('Descripción', 'event-show-base'); ?></h3>
                    <div class="event-description-content">
                        <?php
                        $content = get_the_content();
                        $content = preg_replace('/\[event_show_registration[^\]]*\]/i', '', $content);
                        $content = wpautop($content);
                        echo $content;
                        ?>
                    </div>
                </div>

                <?php if ($organizador) : ?>
                    <div class="event-organizer-detail">
                        <div class="organizer-header">
                            <?php if ($organizador_image) : ?>
                                <div class="organizer-logo-large">
                                    <?php echo wp_get_attachment_image($organizador_image, 'thumbnail'); ?>
                                </div>
                            <?php endif; ?>
                            <div class="organizer-meta">
                                <span class="organizer-published-by"><?php esc_html_e('Publicado por', 'event-show-base'); ?></span>
                                <strong class="organizer-name-large"><?php echo esc_html($organizador->name); ?></strong>
                                <?php if ($organizador_phone) : ?>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $organizador_phone); ?>" target="_blank" title="WhatsApp"><span class="dashicons dashicons-whatsapp"></span></a>
                                <?php endif; ?>
                                <?php if ($organizador_email) : ?>
                                    <a href="mailto:<?php echo esc_attr($organizador_email); ?>" title="Email" ><span class="dashicons dashicons-email"></span></a>
                                <?php endif; ?>
                                <?php if ($organizador_website) : ?>
                                    <a href="<?php echo esc_url($organizador_website); ?>" target="_blank" title="Web"><span class="dashicons dashicons-admin-site"></span></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Columna lateral (derecha) - Panel de registro -->
            <aside class="event-sidebar-column">
                <div class="event-registration-panel">
                    <h3 class="registration-title"><?php esc_html_e('Registro', 'event-show-base'); ?></h3>
                    <?php
                    $event_content = get_post_field('post_content', $event_id);
                    if (false === strpos($event_content, '[event_show_registration')) :
                        echo do_shortcode('[event_show_registration event_id="' . $event_id . '"]');
                    endif;
                    ?>
                </div>
            </aside>
        </div>
    </article>
</div>