<?php

/**
 * Manejador de peticiones AJAX
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar AJAX
 */
class Event_Show_Ajax
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Registro de asistentes
        add_action('wp_ajax_event_show_register_attendee', array($this, 'register_attendee'));
        add_action('wp_ajax_nopriv_event_show_register_attendee', array($this, 'register_attendee'));

        // Envío de eventos
        add_action('wp_ajax_event_show_submit_event', array($this, 'submit_event'));

        // Gestión de lugares y organizadores por usuarios
        add_action('wp_ajax_event_show_create_organizer', array($this, 'create_organizer'));
        add_action('wp_ajax_event_show_create_location', array($this, 'create_location'));

        // Descargar iCal
        add_action('wp_ajax_event_show_download_ical', array($this, 'download_ical'));
        add_action('wp_ajax_nopriv_event_show_download_ical', array($this, 'download_ical'));
    }

    /**
     * AJAX: Registrar asistente
     */
    public function register_attendee()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $num_attendees = isset($_POST['num_attendees']) ? absint($_POST['num_attendees']) : 1;
        $accepted_terms = isset($_POST['accepted_terms']) ? true : false;

        // Validaciones
        if (! $event_id || ! $name || ! $email) {
            wp_send_json_error(array(
                'message' => __('Por favor, completa todos los campos obligatorios', 'event-show-base'),
            ));
        }

        if (! is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Email inválido', 'event-show-base'),
            ));
        }

        if (! $accepted_terms) {
            wp_send_json_error(array(
                'message' => __('Debes aceptar los términos y condiciones', 'event-show-base'),
            ));
        }

        $data = array(
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'num_attendees' => $num_attendees,
            'accepted_terms' => $accepted_terms,
        );

        $result = Event_Show_Attendees::register($event_id, $data);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('¡Registro exitoso! Recibirás un correo de confirmación.', 'event-show-base'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No se pudo completar el registro. Verifica que no estés ya registrado o que haya cupos disponibles.', 'event-show-base'),
            ));
        }
    }

    /**
     * AJAX: Enviar evento
     */
    public function submit_event()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Debes iniciar sesión para enviar un evento', 'event-show-base'),
            ));
        }

        // Recoger datos del formulario
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $event_date = isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : '';
        $event_time = isset($_POST['event_time']) ? sanitize_text_field($_POST['event_time']) : '';
        $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
        $age_rating = isset($_POST['age_rating']) ? absint($_POST['age_rating']) : 0;
        $organizer = isset($_POST['organizer']) ? absint($_POST['organizer']) : 0;
        $location = isset($_POST['location']) ? absint($_POST['location']) : 0;

        // Validaciones
        if (! $title || ! $description || ! $event_date || ! $event_time) {
            wp_send_json_error(array(
                'message' => __('Por favor, completa todos los campos obligatorios', 'event-show-base'),
            ));
        }

        // Validar que sea al menos 5 días en el futuro
        $min_days = get_option('event_show_min_days_advance', 5);
        $event_timestamp = strtotime(str_replace('/', '-', $event_date));
        $min_timestamp = strtotime("+{$min_days} days");

        if ($event_timestamp < $min_timestamp) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('El evento debe programarse con al menos %d días de anticipación', 'event-show-base'),
                    $min_days
                ),
            ));
        }

        // Crear el evento como borrador
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => 'evento',
            'post_status' => get_option('event_show_require_approval', true) ? 'pending' : 'publish',
            'post_author' => get_current_user_id(),
        );

        $event_id = wp_insert_post($post_data);

        if (is_wp_error($event_id)) {
            wp_send_json_error(array(
                'message' => __('Error al crear el evento', 'event-show-base'),
            ));
        }

        // Guardar metadatos
        update_post_meta($event_id, '_event_date', $event_date);
        update_post_meta($event_id, '_event_time', $event_time);
        update_post_meta($event_id, '_use_default_template', '1');
        update_post_meta($event_id, '_enable_registration', '1');

        // Asignar taxonomías
        if ($category) {
            wp_set_post_terms($event_id, array($category), 'categoria_evento');
        }
        if ($age_rating) {
            wp_set_post_terms($event_id, array($age_rating), 'clasificacion_edad');
        }
        if ($organizer) {
            wp_set_post_terms($event_id, array($organizer), 'organizador');
        }
        if ($location) {
            wp_set_post_terms($event_id, array($location), 'lugar');
        }

        // Enviar notificación al admin
        Event_Show_Notifications::send_admin_new_event_submission($event_id);

        // Log
        Event_Show_Logger::log(
            'event_submitted',
            'evento',
            $event_id,
            sprintf(__('Evento "%s" enviado por usuario', 'event-show-base'), $title)
        );

        wp_send_json_success(array(
            'message' => __('¡Evento enviado correctamente! Será revisado por un administrador antes de publicarse.', 'event-show-base'),
        ));
    }

    /**
     * AJAX: Crear organizador
     */
    public function create_organizer()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Debes iniciar sesión', 'event-show-base'),
            ));
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $website = isset($_POST['website']) ? esc_url_raw($_POST['website']) : '';

        if (! $name) {
            wp_send_json_error(array(
                'message' => __('El nombre es obligatorio', 'event-show-base'),
            ));
        }

        $term = wp_insert_term($name, 'organizador');

        if (is_wp_error($term)) {
            wp_send_json_error(array(
                'message' => $term->get_error_message(),
            ));
        }

        $term_id = $term['term_id'];

        // Guardar meta
        if ($email) {
            update_term_meta($term_id, 'email', $email);
        }
        if ($phone) {
            update_term_meta($term_id, 'phone', $phone);
        }
        if ($website) {
            update_term_meta($term_id, 'website', $website);
        }

        wp_send_json_success(array(
            'message' => __('Organizador creado correctamente', 'event-show-base'),
            'term_id' => $term_id,
            'term_name' => $name,
        ));
    }

    /**
     * AJAX: Crear lugar
     */
    public function create_location()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (! is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Debes iniciar sesión', 'event-show-base'),
            ));
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        $map_url = isset($_POST['map_url']) ? esc_url_raw($_POST['map_url']) : '';

        if (! $name) {
            wp_send_json_error(array(
                'message' => __('El nombre es obligatorio', 'event-show-base'),
            ));
        }

        $term = wp_insert_term($name, 'lugar');

        if (is_wp_error($term)) {
            wp_send_json_error(array(
                'message' => $term->get_error_message(),
            ));
        }

        $term_id = $term['term_id'];

        // Guardar meta
        if ($address) {
            update_term_meta($term_id, 'address', $address);
        }
        if ($map_url) {
            update_term_meta($term_id, 'map_url', $map_url);
        }

        wp_send_json_success(array(
            'message' => __('Lugar creado correctamente', 'event-show-base'),
            'term_id' => $term_id,
            'term_name' => $name,
        ));
    }

    /**
     * AJAX: Descargar archivo iCal
     */
    public function download_ical()
    {
        $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;

        if (! $event_id || 'evento' !== get_post_type($event_id)) {
            wp_die(__('Evento no válido', 'event-show-base'));
        }

        $ical = Event_Show_Helpers::generate_ical($event_id);
        $filename = sanitize_file_name(get_the_title($event_id)) . '.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $ical;
        exit;
    }
}
