<?php

/**
 * Clase de integración con otros plugins
 *
 * Esta clase maneja la comunicación entre Event Show y otros plugins
 * como Simple Cards Listings (establecimientos) y Advanced Role Manager
 *
 * @package Event_Show
 * @since 1.2.0
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase de integración entre plugins
 */
class Event_Show_Integrations
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Inicializar integraciones cuando todos los plugins estén cargados
        add_action('plugins_loaded', array($this, 'init_integrations'), 20);
    }

    /**
     * Inicializar integraciones con otros plugins
     */
    public function init_integrations()
    {
        // Filtros de datos de organizador y lugar para mostrar en el frontend
        add_filter('event_show_evento_data', array($this, 'add_organizer_to_evento_data'), 10, 2);
        add_filter('event_show_evento_data', array($this, 'add_lugar_to_evento_data'), 10, 2);

        // Integración con Advanced Role Manager (Auto-publicación)
        if ($this->is_arm_active()) {
            add_filter('wp_insert_post_data', array($this, 'auto_publish_by_role'), 10, 2);
        }

        // Hook para que otros plugins puedan extender las integraciones
        do_action('event_show_integrations_loaded', $this);
    }

    /**
     * Verificar si Simple Cards Listings está activo
     *
     * @return bool
     */
    public function is_scl_active()
    {
        return class_exists('Simple_Cards_Listings') && post_type_exists('establecimiento');
    }

    /**
     * Verificar si las integraciones están disponibles (método público)
     * 
     * @return bool
     */
    public static function has_establishments_integration()
    {
        return class_exists('Simple_Cards_Listings') && post_type_exists('establecimiento');
    }

    /**
     * Verificar si Advanced Role Manager está activo
     *
     * @return bool
     */
    public function is_arm_active()
    {
        return function_exists('run_advanced_role_manager');
    }

    /**
     * Agregar información del organizador a los datos del evento
     *
     * @param array $data Datos del evento
     * @param int   $event_id ID del evento
     * @return array
     */
    public function add_organizer_to_evento_data($data, $event_id)
    {
        $organizer_value = get_post_meta($event_id, '_event_organizer_id', true);

        if ($organizer_value) {
            // Parsear el formato tipo_id
            $parts = explode('_', $organizer_value, 2);
            $tipo = isset($parts[0]) ? $parts[0] : '';
            $id = isset($parts[1]) ? intval($parts[1]) : 0;

            if ($tipo === 'establecimiento' && $id) {
                $organizer = get_post($id);

                if ($organizer && $organizer->post_type === 'establecimiento') {
                    $data['organizer'] = array(
                        'id'    => $organizer->ID,
                        'type'  => 'establecimiento',
                        'title' => $organizer->post_title,
                        'url'   => get_permalink($organizer->ID),
                        'logo'  => get_the_post_thumbnail_url($organizer->ID, 'medium'),
                    );
                }
            } elseif ($tipo === 'organizador' && $id) {
                $organizador = get_term($id, 'organizador');

                if ($organizador && !is_wp_error($organizador)) {
                    $logo = get_term_meta($id, 'logo', true);
                    $contact = get_term_meta($id, 'contact_info', true);

                    $data['organizer'] = array(
                        'id'      => $organizador->term_id,
                        'type'    => 'organizador',
                        'title'   => $organizador->name,
                        'url'     => get_term_link($organizador),
                        'logo'    => $logo,
                        'contact' => $contact,
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Agregar información del lugar a los datos del evento
     *
     * @param array $data Datos del evento
     * @param int   $event_id ID del evento
     * @return array
     */
    public function add_lugar_to_evento_data($data, $event_id)
    {
        $lugar_value = get_post_meta($event_id, '_event_lugar_id', true);

        if ($lugar_value) {
            // Parsear el formato tipo_id
            $parts = explode('_', $lugar_value, 2);
            $tipo = isset($parts[0]) ? $parts[0] : '';
            $id = isset($parts[1]) ? intval($parts[1]) : 0;

            if ($tipo === 'establecimiento' && $id) {
                $lugar = get_post($id);

                if ($lugar && $lugar->post_type === 'establecimiento') {
                    $data['lugar'] = array(
                        'id'      => $lugar->ID,
                        'type'    => 'establecimiento',
                        'title'   => $lugar->post_title,
                        'url'     => get_permalink($lugar->ID),
                        'address' => get_post_meta($id, '_establecimiento_direccion', true),
                        'lat'     => get_post_meta($id, '_establecimiento_lat', true),
                        'lng'     => get_post_meta($id, '_establecimiento_lng', true),
                    );
                }
            } elseif ($tipo === 'lugar' && $id) {
                $lugar = get_term($id, 'lugar');

                if ($lugar && !is_wp_error($lugar)) {
                    $direccion = get_term_meta($id, 'direccion', true);
                    $lat = get_term_meta($id, 'latitud', true);
                    $lng = get_term_meta($id, 'longitud', true);

                    $data['lugar'] = array(
                        'id'      => $lugar->term_id,
                        'type'    => 'lugar',
                        'title'   => $lugar->name,
                        'url'     => get_term_link($lugar),
                        'address' => $direccion,
                        'lat'     => $lat,
                        'lng'     => $lng,
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Auto-publicar eventos basado en el rol del usuario
     *
     * Esta función permite que usuarios con roles específicos
     * publiquen eventos automáticamente sin necesidad de revisión
     *
     * @param array $data    Array de datos del post
     * @param array $postarr Array de datos sin filtrar
     * @return array
     */
    public function auto_publish_by_role($data, $postarr)
    {
        // Solo aplicar a eventos
        if ($data['post_type'] !== 'evento') {
            return $data;
        }

        // Solo para posts nuevos o borradores que se están guardando
        if (!in_array($data['post_status'], array('draft', 'pending', 'auto-draft'))) {
            return $data;
        }

        // Verificar si el usuario actual puede auto-publicar
        if ($this->user_can_auto_publish_events()) {
            $data['post_status'] = 'publish';

            // Hook para notificar que un evento se auto-publicó
            do_action('event_show_auto_published', $postarr['ID'], get_current_user_id());
        }

        return $data;
    }

    /**
     * Verificar si el usuario actual puede auto-publicar eventos
     *
     * @return bool
     */
    public function user_can_auto_publish_events()
    {
        // Si el usuario puede publicar posts, puede auto-publicar eventos
        if (current_user_can('publish_posts')) {
            return true;
        }

        // Obtener roles permitidos desde la configuración
        $allowed_roles = get_option('event_show_auto_publish_roles', array());

        if (empty($allowed_roles)) {
            // Roles predeterminados que pueden auto-publicar
            $allowed_roles = array('administrator', 'editor', 'event_manager');
        }

        // Verificar si el usuario tiene alguno de los roles permitidos
        $user = wp_get_current_user();

        foreach ($allowed_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return apply_filters('event_show_user_can_auto_publish', true, $user, $role);
            }
        }

        return apply_filters('event_show_user_can_auto_publish', false, $user, null);
    }

    /**
     * Obtener el organizador de un evento
     *
     * Método público para que otros plugins puedan obtener el organizador
     *
     * @param int $event_id ID del evento
     * @return WP_Post|null
     */
    public static function get_event_organizer($event_id)
    {
        $organizer_id = get_post_meta($event_id, '_event_organizer_id', true);

        if ($organizer_id) {
            return get_post($organizer_id);
        }

        return null;
    }

    /**
     * Obtener eventos de un organizador específico
     *
     * @param int   $organizer_id ID del establecimiento organizador
     * @param array $args         Argumentos adicionales para la consulta
     * @return WP_Post[]
     */
    public static function get_events_by_organizer($organizer_id, $args = array())
    {
        $defaults = array(
            'post_type'      => 'evento',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_event_organizer_ref_id',
                    'value' => $organizer_id,
                ),
                array(
                    'key'   => '_event_organizer_id',
                    'value' => 'establecimiento_' . $organizer_id,
                ),
            ),
        );

        $args = wp_parse_args($args, $defaults);

        return get_posts($args);
    }
}
