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

// URLs de calendario
$ical_url = admin_url('admin-ajax.php?action=event_show_download_ical&event_id=' . $event_id);

// Imagen del banner
$banner_id = get_post_meta($event_id, '_event_banner', true);
$banner_url = $banner_id ? wp_get_attachment_image_url($banner_id, 'full') : get_the_post_thumbnail_url($event_id, 'full');
?>

<div class="event-show-content">
    <article id="event-<?php echo esc_attr($event_id); ?>" <?php post_class('event-show-single event-single-modern'); ?>>

        <!-- Hero Banner con información superpuesta -->
        <div class="event-hero-banner" <?php if ($banner_url) : ?>style="background-image: url('<?php echo esc_url($banner_url); ?>');" <?php endif; ?>>
            <div class="event-hero-overlay"></div>
            <div class="event-hero-content">
                <div class="event-hero-info">
                    <h1 class="event-hero-title"><?php the_title(); ?></h1>
                    <div class="event-hero-meta">
                        <?php if ($event_date) : ?>
                            <span class="event-hero-date">
                                <?php echo esc_html(Event_Show_Helpers::format_date($event_date, 'M d')); ?>
                                <?php if ($event_time && $event_time_indef !== '1') : ?>
                                    - <?php echo esc_html(Event_Show_Helpers::format_time($event_time, 'H:i')); ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($lugar) : ?>
                            <span class="event-hero-location">
                                <?php echo esc_html($lugar->name); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
                            <strong class="organizer-name"><?php echo esc_html($organizador->name); ?></strong>
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
                        </div>
                    </div>
                <?php endif; ?>

                <div class="event-details-grid">
                    <div class="event-detail-card">
                        <span class="detail-icon dashicons dashicons-calendar"></span>
                        <div class="detail-info">
                            <span class="detail-label"><?php esc_html_e('Fecha y hora', 'event-show-base'); ?></span>
                            <span class="detail-value">
                                <?php echo esc_html(Event_Show_Helpers::format_date($event_date, 'M d')); ?>
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
                                            <?php echo esc_html($lugar->name); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html($lugar->name); ?>
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
                    <button class="event-read-more-btn" id="toggleDescription">
                        <?php esc_html_e('Ver más', 'event-show-base'); ?>
                    </button>
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
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $organizador_phone); ?>" target="_blank" title="WhatsApp" style="margin-left:6px;"><span class="dashicons dashicons-whatsapp"></span></a>
                                <?php endif; ?>
                                <?php if ($organizador_email) : ?>
                                    <a href="mailto:<?php echo esc_attr($organizador_email); ?>" title="Email" style="margin-left:6px;"><span class="dashicons dashicons-email"></span></a>
                                <?php endif; ?>
                                <?php if ($organizador_website) : ?>
                                    <a href="<?php echo esc_url($organizador_website); ?>" target="_blank" title="Web" style="margin-left:6px;"><span class="dashicons dashicons-admin-site"></span></a>
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