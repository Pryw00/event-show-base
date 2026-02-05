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
            'pagination_type' => 'default', // default|infinite|load_more
            'per_page' => get_option('event_show_events_per_page', 12),
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
            'columns' => 3, // 2, 3 o 4 columnas
            'pagination_type' => 'default', // default|infinite|load_more
            'per_page' => get_option('event_show_events_per_page', 12),
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
        // Paginación
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $has_pagination = in_array($atts['layout'], ['grid', 'list']) && !empty($atts['pagination_type']);

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

            // Aplicar límite (limit=-1 significa sin límite)
            $limit = intval($atts['limit']);
            if ($limit > 0) {
                $filtered_ids = array_slice($filtered_ids, 0, $limit);
            }
        }

        // Total de eventos antes de paginación
        $total_events = count($filtered_ids);
        $max_pages = 1;

        // Aplicar paginación si corresponde
        if ($has_pagination && !empty($filtered_ids)) {
            $per_page = intval($atts['per_page']);
            if ($per_page > 0) {
                $max_pages = ceil($total_events / $per_page);
                $offset = ($paged - 1) * $per_page;
                $filtered_ids = array_slice($filtered_ids, $offset, $per_page);
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

        // Pasar variables de paginación a los templates
        $pagination_data = array(
            'has_pagination' => $has_pagination,
            'pagination_type' => isset($atts['pagination_type']) ? $atts['pagination_type'] : 'default',
            'paged' => $paged,
            'max_pages' => $max_pages,
            'total_events' => $total_events,
            'per_page' => isset($atts['per_page']) ? intval($atts['per_page']) : 12,
            'atts' => $atts,
        );

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
     * [event_show_registration event_id="123" button_text="Registrarse"]
     */
    public function registration_form_shortcode($atts)
    {
        $atts = Event_Show_Helpers::sanitize_shortcode_atts($atts, array(
            'event_id' => get_the_ID(),
            'button_text' => __('Registrarse', 'event-show-base'),
        ));

        $event_id = $atts['event_id'];
        $button_text = $atts['button_text'];

        if (! $event_id || 'evento' !== get_post_type($event_id)) {
            return '<p>' . esc_html__('Evento no válido', 'event-show-base') . '</p>';
        }

        // Verificar si el registro está habilitado
        $enable_registration = get_post_meta($event_id, '_enable_registration', true);
        if ('0' === $enable_registration) {
            // Obtener organizador - Priorizar metadatos sobre taxonomía (igual que single-evento.php)
            $organizador = null;
            $organizador_name = '';
            $organizador_email = '';
            $organizador_phone = '';
            $organizador_image = '';
            $organizador_website = '';
            $organizador_facebook = '';
            $organizador_instagram = '';
            $organizador_twitter = '';

            $organizer_value = get_post_meta($event_id, '_event_organizer_id', true);
            if ($organizer_value) {
                // Parsear el formato tipo_id
                $parts = explode('_', $organizer_value, 2);
                $tipo = isset($parts[0]) ? $parts[0] : '';
                $id = isset($parts[1]) ? intval($parts[1]) : 0;

                if ($tipo === 'establecimiento' && $id && post_type_exists('establecimiento')) {
                    $establecimiento = get_post($id);
                    if ($establecimiento && $establecimiento->post_type === 'establecimiento') {
                        $organizador_name = $establecimiento->post_title;
                        $organizador_image = get_post_thumbnail_id($establecimiento->ID);
                        $organizador_phone = get_post_meta($establecimiento->ID, '_scl_telefono', true);
                        $organizador_email = get_post_meta($establecimiento->ID, '_scl_email', true);
                        $organizador_website = get_post_meta($establecimiento->ID, '_scl_website', true);
                        $organizador_facebook = get_post_meta($establecimiento->ID, '_scl_facebook', true);
                        $organizador_instagram = get_post_meta($establecimiento->ID, '_scl_instagram', true);
                        $organizador_twitter = get_post_meta($establecimiento->ID, '_scl_twitter', true);
                        // Crear objeto similar a término para compatibilidad
                        $organizador = (object) array(
                            'term_id' => $establecimiento->ID,
                            'name' => $organizador_name,
                            'type' => 'establecimiento'
                        );
                    }
                } elseif ($tipo === 'organizador' && $id) {
                    $organizador_term = get_term($id, 'organizador');
                    if ($organizador_term && !is_wp_error($organizador_term)) {
                        $organizador = $organizador_term;
                        $organizador_name = $organizador_term->name;
                        $organizador_image = get_term_meta($organizador_term->term_id, 'image', true);
                        $organizador_phone = get_term_meta($organizador_term->term_id, 'phone', true);
                        $organizador_email = get_term_meta($organizador_term->term_id, 'email', true);
                        $organizador_website = get_term_meta($organizador_term->term_id, 'website', true);
                        $organizador_facebook = get_term_meta($organizador_term->term_id, 'facebook', true);
                        $organizador_instagram = get_term_meta($organizador_term->term_id, 'instagram', true);
                        $organizador_twitter = get_term_meta($organizador_term->term_id, 'twitter', true);
                    }
                }
            }

            // Fallback: usar taxonomía si no hay metadatos
            if (!$organizador) {
                $organizadores = wp_get_post_terms($event_id, 'organizador', array('number' => 1));
                $organizador = !empty($organizadores) ? $organizadores[0] : null;
                if ($organizador) {
                    $organizador_name = $organizador->name;
                    $organizador_email = get_term_meta($organizador->term_id, 'email', true);
                    $organizador_phone = get_term_meta($organizador->term_id, 'phone', true);
                    $organizador_image = get_term_meta($organizador->term_id, 'image', true);
                    $organizador_website = get_term_meta($organizador->term_id, 'website', true);
                    $organizador_facebook = get_term_meta($organizador->term_id, 'facebook', true);
                    $organizador_instagram = get_term_meta($organizador->term_id, 'instagram', true);
                    $organizador_twitter = get_term_meta($organizador->term_id, 'twitter', true);
                }
            }

            // Avatar
            if ($organizador_image) {
                $organizer_avatar = wp_get_attachment_image($organizador_image, 'thumbnail', false, array('class' => 'organizer-avatar', 'style' => 'border-radius:50%;width:64px;height:64px;object-fit:cover;'));
            } else {
                $organizer_avatar = '<span class="dashicons dashicons-businessperson" style="font-size:48px;color:#888;"></span>';
            }

            // Si hay al menos nombre, email o teléfono, mostrar la tarjeta
            if ($organizador_name || $organizador_email || $organizador_phone) {
                $contact_buttons = '';
                if ($organizador_email) {
                    $contact_buttons .= '<a href="mailto:' . esc_attr($organizador_email) . '" class="event-contact-btn" style="display:inline-block;margin:0 6px 6px 0;padding:8px 16px;color:#fff;background:#0073aa;border-radius:4px;text-decoration:none;font-weight:500;"><span class="dashicons dashicons-email" style="vertical-align:middle;"></span> Email</a>';
                }
                if ($organizador_phone) {
                    $contact_buttons .= '<a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $organizador_phone) . '" target="_blank" class="event-contact-btn" style="display:inline-block;margin:0 6px 6px 0;padding:8px 16px;color:#fff;background:#25D366;border-radius:4px;text-decoration:none;font-weight:500;"><span class="dashicons dashicons-whatsapp" style="vertical-align:middle;"></span> WhatsApp</a>';
                }
                if ($organizador_website) {
                    $contact_buttons .= '<a href="' . esc_url($organizador_website) . '" target="_blank" class="event-contact-btn" style="display:inline-block;margin:0 6px 6px 0;padding:8px 16px;color:#fff;background:#666;border-radius:4px;text-decoration:none;font-weight:500;"><span class="dashicons dashicons-admin-site" style="vertical-align:middle;"></span> Web</a>';
                }
                if ($organizador_facebook) {
                    $contact_buttons .= '<a href="' . esc_url($organizador_facebook) . '" target="_blank" class="event-contact-btn" style="display:inline-block;margin:0 6px 6px 0;padding:8px 16px;color:#fff;background:#1877F2;border-radius:4px;text-decoration:none;font-weight:500;"><span class="dashicons dashicons-facebook" style="vertical-align:middle;"></span> Facebook</a>';
                }
                if ($organizador_instagram) {
                    $contact_buttons .= '<a href="' . esc_url($organizador_instagram) . '" target="_blank" class="event-contact-btn" style="display:inline-block;margin:0 6px 6px 0;padding:8px 16px;color:#fff;background:#E4405F;border-radius:4px;text-decoration:none;font-weight:500;"><span class="dashicons dashicons-instagram" style="vertical-align:middle;"></span> Instagram</a>';
                }
                if ($organizador_twitter) {
                    $contact_buttons .= '<a href="' . esc_url($organizador_twitter) . '" target="_blank" class="event-contact-btn" style="display:inline-block;margin:0 6px 6px 0;padding:8px 16px;color:#fff;background:#1DA1F2;border-radius:4px;text-decoration:none;font-weight:500;"><span class="dashicons dashicons-twitter" style="vertical-align:middle;"></span> Twitter</a>';
                }

                return '<div class="event-organizer-contact-card" style="box-shadow:0 2px 8px rgba(0,0,0,0.07);padding:24px 20px 18px 20px;border-radius:12px;max-width:400px;margin:0 auto 24px auto;text-align:center;">'
                    . '<div style="margin-bottom:12px;">' . $organizer_avatar . '</div>'
                    . ($organizador_name ? '<div style="font-size:18px;font-weight:600;margin-bottom:8px;">' . esc_html($organizador_name) . '</div>' : '')
                    . '<div style="font-size:14px;margin-bottom:12px;color:#666;">' . esc_html__('Para registrarte en este evento, contacta directamente con el organizador.', 'event-show-base') . '</div>'
                    . ($contact_buttons ? '<div style="margin-top:10px;display:flex;flex-wrap:wrap;justify-content:center;gap:6px;">' . $contact_buttons . '</div>' : '')
                    . '</div>';
            } else {
                // Si no hay datos del organizador, solo mostrar el mensaje genérico
                return '<div style="text-align:center;font-size:15px;color:#444;margin:24px auto;">' . esc_html__('Para registrarte en este evento, contacta directamente con el organizador.', 'event-show-base') . '</div>';
            }
        }

        // Verificar si el evento ya pasó
        if (Event_Show_Helpers::is_past_event($event_id)) {
            return '<p class="event-registration-closed">' . esc_html__('Este evento ya ha finalizado', 'event-show-base') . '</p>';
        }

        // Verificar fecha límite
        $deadline = get_post_meta($event_id, '_registration_deadline', true);
        if ($deadline && strtotime(str_replace('/', '-', $deadline)) < time()) {
            return '<p class="event-registration-closed">' . esc_html__('El plazo de registro para este evento ha finalizado', 'event-show-base') . '</p>';
        }

        // Verificar aforo
        $max_attendees = get_post_meta($event_id, '_max_attendees', true);
        if ($max_attendees) {
            $current_attendees = Event_Show_Attendees::get_total_attendees($event_id);
            if ($current_attendees >= $max_attendees) {
                return '<p class="event-registration-full">' . esc_html__('Este evento ha alcanzado su capacidad máxima', 'event-show-base') . '</p>';
            }
        }

        // Generar ID único para el modal
        $modal_id = 'registration-modal-' . $event_id;
        $event_title = get_the_title($event_id);

        ob_start();
?>
        <div class="event-registration-shortcode">
            <button type="button" class="event-registration-trigger-btn" data-modal-id="<?php echo esc_attr($modal_id); ?>">
                <span class="btn-icon dashicons dashicons-groups"></span>
                <span class="btn-text"><?php echo esc_html($button_text); ?></span>
            </button>

            <!-- Modal de registro -->
            <div id="<?php echo esc_attr($modal_id); ?>" class="event-registration-modal-overlay" style="display: none;">
                <div class="event-registration-modal">
                    <button type="button" class="event-registration-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'event-show-base'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <div class="event-registration-modal-header">
                        <h3 class="event-registration-modal-title"><?php esc_html_e('Registro', 'event-show-base'); ?></h3>
                        <p class="event-registration-modal-event"><?php echo esc_html($event_title); ?></p>
                    </div>
                    <div class="event-registration-modal-body">
                        <?php
                        include EVENT_SHOW_PLUGIN_DIR . 'templates/registration-form.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>
<?php
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
