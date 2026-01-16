<?php

/**
 * Funcionalidad pública del plugin
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para funcionalidad pública
 */
class Event_Show_Public
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_filter('the_content', array($this, 'filter_content'));
        add_action('wp_head', array($this, 'add_structured_data'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_media_uploader'));
    }

    /**
     * Enqueue WP media uploader for frontend modals
     */
    public function enqueue_media_uploader()
    {
        if (is_user_logged_in()) {
            wp_enqueue_media();
        }
    }

    /**
     * Filtrar contenido del evento
     */
    public function filter_content($content)
    {
        if (! is_singular('evento')) {
            return $content;
        }

        $use_default_template = get_post_meta(get_the_ID(), '_use_default_template', true);

        // Si no usa plantilla por defecto, retornar contenido normal
        if ('0' === $use_default_template) {
            return $content;
        }

        // Usar plantilla personalizada
        ob_start();
        include EVENT_SHOW_PLUGIN_DIR . 'templates/single-event.php';
        $template_content = ob_get_clean();

        return $template_content;
    }

    /**
     * Añadir datos estructurados (Schema.org)
     */
    public function add_structured_data()
    {
        if (! is_singular('evento')) {
            return;
        }

        $event_id = get_the_ID();
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_end_date = get_post_meta($event_id, '_event_end_date', true);
        $event_end_time = get_post_meta($event_id, '_event_end_time', true);

        if (! $event_date) {
            return;
        }

        // Construir fecha/hora ISO 8601
        $start_datetime = date('c', strtotime(str_replace('/', '-', $event_date) . ' ' . ($event_time ? $event_time : '00:00')));
        $end_datetime = date('c', strtotime(str_replace('/', '-', ($event_end_date ? $event_end_date : $event_date)) . ' ' . ($event_end_time ? $event_end_time : $event_time)));

        // Obtener lugar
        $lugares = wp_get_post_terms($event_id, 'lugar');
        $lugar_nombre = ! empty($lugares) ? $lugares[0]->name : '';
        $lugar_address = ! empty($lugares) ? get_term_meta($lugares[0]->term_id, 'address', true) : '';

        // Obtener organizador
        $organizadores = wp_get_post_terms($event_id, 'organizador');
        $organizador_nombre = ! empty($organizadores) ? $organizadores[0]->name : '';

        // Imagen
        $image_url = get_the_post_thumbnail_url($event_id, 'large');

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => get_the_title($event_id),
            'description' => wp_strip_all_tags(get_the_excerpt($event_id)),
            'startDate' => $start_datetime,
            'endDate' => $end_datetime,
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
        );

        if ($image_url) {
            $schema['image'] = $image_url;
        }

        if ($lugar_nombre) {
            $schema['location'] = array(
                '@type' => 'Place',
                'name' => $lugar_nombre,
            );

            if ($lugar_address) {
                $schema['location']['address'] = array(
                    '@type' => 'PostalAddress',
                    'streetAddress' => $lugar_address,
                );
            }
        }

        if ($organizador_nombre) {
            $schema['organizer'] = array(
                '@type' => 'Organization',
                'name' => $organizador_nombre,
            );
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}
