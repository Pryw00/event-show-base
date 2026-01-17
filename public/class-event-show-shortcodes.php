<?php

/**
 * Shortcodes del plugin
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar shortcodes
 */
class Event_Show_Shortcodes
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_shortcode('event_show_list', array($this, 'event_list_shortcode'));
        add_shortcode('event_show_grid', array($this, 'event_grid_shortcode'));
        add_shortcode('event_show_calendar', array($this, 'event_calendar_shortcode'));
        add_shortcode('event_show_carousel', array($this, 'event_carousel_shortcode'));
        add_shortcode('event_show_slider', array($this, 'event_slider_shortcode'));
        add_shortcode('event_show_registration', array($this, 'registration_form_shortcode'));
        add_shortcode('event_show_submit_form', array($this, 'submit_form_shortcode'));
        add_shortcode('event_show_dashboard', array($this, 'user_dashboard_shortcode'));
    }

    /**
     * Shortcode para lista de eventos
     *
     * [event_show_list layout="list" category="" age_rating="" limit="12"]
     */
    public function event_list_shortcode($atts)
    {
        $atts = Event_Show_Helpers::sanitize_shortcode_atts($atts, array(
            'layout' => 'list',
            'category' => '',
            'age_rating' => '',
            'limit' => get_option('event_show_events_per_page', 12),
            'show' => 'upcoming', // upcoming|past|all
        ));

        return $this->render_events($atts);
    }

    /**
     * Shortcode para grid de eventos
     *
     * [event_show_grid category="" age_rating="" limit="12"]
     */
    public function event_grid_shortcode($atts)
    {
        $atts = Event_Show_Helpers::sanitize_shortcode_atts($atts, array(
            'layout' => 'grid',
            'category' => '',
            'age_rating' => '',
            'limit' => get_option('event_show_events_per_page', 12),
            'show' => 'upcoming',
        ));

        return $this->render_events($atts);
    }

    /**
     * Shortcode para calendario de eventos
     *
     * [event_show_calendar]
     */
    public function event_calendar_shortcode($atts)
    {
        $atts = Event_Show_Helpers::sanitize_shortcode_atts($atts, array(
            'layout' => 'calendar',
            'category' => '',
            'age_rating' => '',
            'limit' => -1,
            'show' => 'upcoming',
        ));

        return $this->render_events($atts);
    }

    /**
     * Shortcode para carrusel de eventos
     *
     * [event_show_carousel limit="6"]
     */
    public function event_carousel_shortcode($atts)
    {
        $atts = Event_Show_Helpers::sanitize_shortcode_atts($atts, array(
            'layout' => 'carousel',
            'category' => '',
            'age_rating' => '',
            'limit' => 6,
            'show_countdown' => 'yes',
            'show' => 'upcoming',
        ));

        return $this->render_events($atts);
    }

    /**
     * Shortcode para slider de eventos
     *
     * [event_show_slider limit="5" autoplay="true" autoplay_speed="5000"]
     */
    public function event_slider_shortcode($atts)
    {
        $atts = Event_Show_Helpers::sanitize_shortcode_atts($atts, array(
            'layout' => 'slider',
            'category' => '',
            'age_rating' => '',
            'limit' => 5,
            'autoplay' => 'true',
            'autoplay_speed' => 5000,
            'show' => 'upcoming',
        ));

        return $this->render_events($atts);
    }

    /**
     * Renderizar eventos según layout
     */
    private function render_events($atts)
    {
        // Obtener TODOS los eventos publicados primero, luego filtrar manualmente
        $args = array(
            'post_type' => 'evento',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        // Filtrar por categoría
        if (! empty($atts['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'categoria_evento',
                'field' => 'slug',
                'terms' => explode(',', $atts['category']),
            );
        }

        // Filtrar por clasificación de edad
        if (! empty($atts['age_rating'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'clasificacion_edad',
                'field' => 'slug',
                'terms' => explode(',', $atts['age_rating']),
            );
        }

        if (isset($args['tax_query']) && count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        $events = new WP_Query($args);

        // Timestamp actual para comparar
        $now = time();

        // Función para normalizar fechas dd/mm/yyyy a Y-m-d
        $normalize_date = function ($date) {
            if (!$date) return '';
            $parts = explode('/', $date);
            if (count($parts) === 3) {
                $d = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $m = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $y = $parts[2];
                return "$y-$m-$d";
            }
            return $date;
        };

        // Ordenar y filtrar eventos por fecha/hora
        $filtered_ids = array();
        if ($events->have_posts()) {
            $posts_data = array();

            while ($events->have_posts()) {
                $events->the_post();
                $post_id = get_the_ID();
                $start_date = get_post_meta($post_id, '_event_date', true);
                $start_time = get_post_meta($post_id, '_event_time', true);
                $end_date = get_post_meta($post_id, '_event_end_date', true);
                $end_time = get_post_meta($post_id, '_event_end_time', true);

                // Fecha para ordenar (inicio)
                $sort_datetime = $normalize_date($start_date) . ' ' . ($start_time ? $start_time : '00:00');
                $sort_timestamp = strtotime($sort_datetime);

                // Fecha para filtrar (fin si existe, si no inicio)
                $filter_date = $end_date ? $end_date : $start_date;
                $filter_time = $end_time ? $end_time : ($end_date ? '23:59' : ($start_time ? $start_time : '00:00'));
                $filter_datetime = $normalize_date($filter_date) . ' ' . $filter_time;
                $filter_timestamp = strtotime($filter_datetime);

                $posts_data[] = array(
                    'id' => $post_id,
                    'sort_timestamp' => $sort_timestamp,
                    'filter_timestamp' => $filter_timestamp,
                );
            }
            wp_reset_postdata();

            // Ordenar por fecha de inicio
            usort($posts_data, function ($a, $b) {
                return $a['sort_timestamp'] - $b['sort_timestamp'];
            });

            // Filtrar según show
            foreach ($posts_data as $data) {
                $include = true;
                if (!isset($atts['show']) || $atts['show'] === 'upcoming') {
                    $include = ($data['filter_timestamp'] >= $now);
                } elseif ($atts['show'] === 'past') {
                    $include = ($data['filter_timestamp'] < $now);
                }
                if ($include) {
                    $filtered_ids[] = $data['id'];
                }
            }

            // Aplicar límite
            if ($atts['limit'] > 0) {
                $filtered_ids = array_slice($filtered_ids, 0, $atts['limit']);
            }
        }

        // Crear nuevo WP_Query solo con los IDs filtrados
        if (!empty($filtered_ids)) {
            $events = new WP_Query(array(
                'post_type' => 'evento',
                'post__in' => $filtered_ids,
                'orderby' => 'post__in',
                'posts_per_page' => -1,
            ));
        } else {
            // Sin eventos que mostrar
            $events = new WP_Query(array('post__in' => array(0)));
        }

        ob_start();

        $template = 'templates/layouts/' . $atts['layout'] . '.php';
        $template_path = EVENT_SHOW_PLUGIN_DIR . $template;

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>' . esc_html__('Layout no encontrado', 'event-show-base') . '</p>';
        }

        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Shortcode para formulario de registro
     *
     * [event_show_registration event_id="123"]
     */
    public function registration_form_shortcode($atts)
    {
        $atts = Event_Show_Helpers::sanitize_shortcode_atts($atts, array(
            'event_id' => get_the_ID(),
        ));

        $event_id = $atts['event_id'];

        if (! $event_id || 'evento' !== get_post_type($event_id)) {
            return '<p>' . esc_html__('Evento no válido', 'event-show-base') . '</p>';
        }

        // Verificar si el registro está habilitado
        $enable_registration = get_post_meta($event_id, '_enable_registration', true);
        if ('0' === $enable_registration) {
            return '<p>' . esc_html__('El registro para este evento está deshabilitado', 'event-show-base') . '</p>';
        }

        // Verificar si el evento ya pasó
        if (Event_Show_Helpers::is_past_event($event_id)) {
            return '<p>' . esc_html__('Este evento ya ha finalizado', 'event-show-base') . '</p>';
        }

        // Verificar fecha límite
        $deadline = get_post_meta($event_id, '_registration_deadline', true);
        if ($deadline && strtotime(str_replace('/', '-', $deadline)) < time()) {
            return '<p>' . esc_html__('El plazo de registro para este evento ha finalizado', 'event-show-base') . '</p>';
        }

        // Verificar aforo
        $max_attendees = get_post_meta($event_id, '_max_attendees', true);
        if ($max_attendees) {
            $current_attendees = Event_Show_Attendees::get_total_attendees($event_id);
            if ($current_attendees >= $max_attendees) {
                return '<p>' . esc_html__('Este evento ha alcanzado su capacidad máxima', 'event-show-base') . '</p>';
            }
        }

        ob_start();
        include EVENT_SHOW_PLUGIN_DIR . 'templates/registration-form.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para formulario de envío de eventos
     *
     * [event_show_submit_form]
     */
    public function submit_form_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesión para enviar un evento', 'event-show-base') . ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Iniciar sesión', 'event-show-base') . '</a></p>';
        }

        ob_start();
        include EVENT_SHOW_PLUGIN_DIR . 'templates/submit-event-form.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para dashboard de usuario
     *
     * [event_show_dashboard]
     */
    public function user_dashboard_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('Debes iniciar sesión para acceder al dashboard', 'event-show-base') . ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Iniciar sesión', 'event-show-base') . '</a></p>';
        }

        ob_start();
        include EVENT_SHOW_PLUGIN_DIR . 'templates/user-dashboard.php';
        return ob_get_clean();
    }
}
