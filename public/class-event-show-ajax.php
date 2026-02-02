<?php

// ...existing code...


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
     * AJAX: Editar organizador
     */
    public function edit_organizer()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Debes iniciar sesión', 'event-show-base'),
            ));
        }

        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $website = isset($_POST['website']) ? esc_url_raw($_POST['website']) : '';
        $image_id = '';
        // Procesar archivo subido si existe
        if (!empty($_FILES['image_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $file = $_FILES['image_file'];
            $upload = wp_handle_upload($file, array('test_form' => false));
            if (!isset($upload['error']) && isset($upload['file'])) {
                $filetype = wp_check_filetype($upload['file'], null);
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => sanitize_file_name($file['name']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $image_id = wp_insert_attachment($attachment, $upload['file']);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($image_id, $upload['file']);
                wp_update_attachment_metadata($image_id, $attach_data);
            }
        } else {
            $image_id = isset($_POST['image']) ? intval($_POST['image']) : '';
        }

        if (!$term_id || !$name) {
            wp_send_json_error(array(
                'message' => __('Faltan datos obligatorios', 'event-show-base'),
            ));
        }

        // Solo permitir editar si el usuario tiene asignado este organizador
        $user_organizadores = get_user_meta(get_current_user_id(), 'organizador_ids', true);
        if (!is_array($user_organizadores) || !in_array($term_id, $user_organizadores)) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para editar este organizador', 'event-show-base'),
            ));
        }

        // Actualizar nombre
        wp_update_term($term_id, 'organizador', array('name' => $name));
        // Actualizar meta
        update_term_meta($term_id, 'email', $email);
        update_term_meta($term_id, 'phone', $phone);
        update_term_meta($term_id, 'website', $website);
        update_term_meta($term_id, 'image', $image_id);

        wp_send_json_success(array(
            'message' => __('Organizador actualizado correctamente', 'event-show-base'),
            'term_id' => $term_id,
            'term_name' => $name,
        ));
    }

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
        add_action('wp_ajax_event_show_edit_organizer', array($this, 'edit_organizer'));

        // Nuevos AJAX para organizadores (dashboard)
        add_action('wp_ajax_get_organizer_data', array($this, 'get_organizer_data'));
        add_action('wp_ajax_save_organizer', array($this, 'save_organizer'));

        // Edición de eventos
        add_action('wp_ajax_event_show_get_event_data', array($this, 'get_event_data'));
        add_action('wp_ajax_event_show_edit_event', array($this, 'edit_event'));

        // Obtener asistentes
        add_action('wp_ajax_event_show_get_attendees', array($this, 'get_attendees'));

        // Descargar iCal
        add_action('wp_ajax_event_show_download_ical', array($this, 'download_ical'));
        add_action('wp_ajax_nopriv_event_show_download_ical', array($this, 'download_ical'));

        // Paginación AJAX
        add_action('wp_ajax_event_show_load_more', array($this, 'load_more_events'));
        add_action('wp_ajax_nopriv_event_show_load_more', array($this, 'load_more_events'));
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
        $event_date_end = isset($_POST['event_date_end']) ? sanitize_text_field($_POST['event_date_end']) : '';
        $event_time_end = isset($_POST['event_time_end']) ? sanitize_text_field($_POST['event_time_end']) : '';
        $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
        $age_rating = isset($_POST['age_rating']) ? absint($_POST['age_rating']) : 0;
        $organizer_id = isset($_POST['organizer_id']) ? absint($_POST['organizer_id']) : 0;
        $location = isset($_POST['location']) ? absint($_POST['location']) : 0;

        // Procesar imágenes obligatorias
        $banner_id = '';
        $thumb_id = '';
        if (!empty($_FILES['event_banner']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $file = $_FILES['event_banner'];
            $upload = wp_handle_upload($file, array('test_form' => false));
            if (!isset($upload['error']) && isset($upload['file'])) {
                $filetype = wp_check_filetype($upload['file'], null);
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => sanitize_file_name($file['name']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $banner_id = wp_insert_attachment($attachment, $upload['file']);
                $attach_data = wp_generate_attachment_metadata($banner_id, $upload['file']);
                wp_update_attachment_metadata($banner_id, $attach_data);
            }
        }
        if (!empty($_FILES['event_thumbnail']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $file = $_FILES['event_thumbnail'];
            $upload = wp_handle_upload($file, array('test_form' => false));
            if (!isset($upload['error']) && isset($upload['file'])) {
                $filetype = wp_check_filetype($upload['file'], null);
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => sanitize_file_name($file['name']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $thumb_id = wp_insert_attachment($attachment, $upload['file']);
                $attach_data = wp_generate_attachment_metadata($thumb_id, $upload['file']);
                wp_update_attachment_metadata($thumb_id, $attach_data);
            }
        }

        // Validaciones
        if (!$title || !$description || !$event_date || !$event_time || !$banner_id || !$thumb_id) {
            wp_send_json_error(array(
                'message' => __('Por favor, completa todos los campos obligatorios y sube las imágenes requeridas', 'event-show-base'),
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
        update_post_meta($event_id, '_event_date_end', $event_date_end);
        update_post_meta($event_id, '_event_time_end', $event_time_end);
        update_post_meta($event_id, '_event_banner', $banner_id);
        update_post_meta($event_id, '_event_thumbnail', $thumb_id);
        update_post_meta($event_id, '_use_default_template', '1');
        update_post_meta($event_id, '_enable_registration', '1');

        // Asignar taxonomías
        if ($category) {
            wp_set_post_terms($event_id, array($category), 'categoria_evento');
        }
        if ($age_rating) {
            wp_set_post_terms($event_id, array($age_rating), 'clasificacion_edad');
        }

        // Asignar establecimiento organizador y validar propiedad
        if ($organizer_id) {
            $establecimiento = get_post($organizer_id);

            // Verificar que sea un establecimiento válido
            if ($establecimiento && $establecimiento->post_type === 'establecimiento') {
                $current_user_id = get_current_user_id();

                // Verificar que el usuario sea propietario (excepto administradores)
                if (current_user_can('manage_options') || (int)$establecimiento->post_author === $current_user_id) {
                    update_post_meta($event_id, '_event_organizer_id', $organizer_id);
                } else {
                    // Usuario intentó asignar un establecimiento que no le pertenece
                    wp_delete_post($event_id, true);
                    wp_send_json_error(array(
                        'message' => __('No tienes permisos para usar ese establecimiento como organizador.', 'event-show-base'),
                    ));
                }
            }
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
        $image_id = '';
        // Procesar archivo subido si existe
        if (!empty($_FILES['image_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $file = $_FILES['image_file'];
            $upload = wp_handle_upload($file, array('test_form' => false));
            if (!isset($upload['error']) && isset($upload['file'])) {
                $filetype = wp_check_filetype($upload['file'], null);
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => sanitize_file_name($file['name']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $image_id = wp_insert_attachment($attachment, $upload['file']);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($image_id, $upload['file']);
                wp_update_attachment_metadata($image_id, $attach_data);
            }
        }

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
        if ($image_id) {
            update_term_meta($term_id, 'image', $image_id);
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

    /**
     * AJAX: Obtener datos de evento para edición
     */
    public function get_event_data()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión', 'event-show-base')));
        }

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('ID de evento inválido', 'event-show-base')));
        }

        $event = get_post($event_id);

        if (!$event || $event->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('No tienes permiso para editar este evento', 'event-show-base')));
        }

        // Verificar que quedan al menos 7 días
        $event_date = get_post_meta($event_id, '_event_date', true);
        if ($event_date) {
            $event_timestamp = strtotime(str_replace('/', '-', $event_date));
            $days_until_event = floor(($event_timestamp - time()) / (60 * 60 * 24));
            if ($days_until_event < 7) {
                wp_send_json_error(array('message' => __('Solo puedes editar eventos con al menos 7 días de anticipación', 'event-show-base')));
            }
        }

        wp_send_json_success(array(
            'title' => $event->post_title,
            'description' => $event->post_content,
            'event_date' => get_post_meta($event_id, '_event_date', true),
            'event_time' => get_post_meta($event_id, '_event_time', true),
            'event_end_date' => get_post_meta($event_id, '_event_end_date', true),
            'event_end_time' => get_post_meta($event_id, '_event_end_time', true),
            'max_attendees' => get_post_meta($event_id, '_max_attendees', true),
            'category' => $this->get_first_term_id($event_id, 'categoria_evento'),
            'age_classification' => $this->get_first_term_id($event_id, 'clasificacion_edad'),
            'location' => $this->get_first_term_id($event_id, 'lugar'),
            'banner_url' => get_the_post_thumbnail_url($event_id, 'large'),
            'grid_image_url' => $this->get_grid_image_url($event_id),
        ));
    }

    /**
     * Helper: Obtener primer ID de término de una taxonomía
     */
    private function get_first_term_id($post_id, $taxonomy)
    {
        $terms = wp_get_post_terms($post_id, $taxonomy);
        return (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->term_id : '';
    }

    /**
     * Helper: Obtener URL de imagen grid
     */
    private function get_grid_image_url($post_id)
    {
        $thumbnail_id = get_post_meta($post_id, '_event_thumbnail', true);
        return $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
    }

    /**
     * AJAX: Editar evento
     */
    public function edit_event()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión', 'event-show-base')));
        }

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $event_date = isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : '';
        $event_time = isset($_POST['event_time']) ? sanitize_text_field($_POST['event_time']) : '';
        $event_end_date = isset($_POST['event_end_date']) ? sanitize_text_field($_POST['event_end_date']) : '';
        $event_end_time = isset($_POST['event_end_time']) ? sanitize_text_field($_POST['event_end_time']) : '';
        $max_attendees = isset($_POST['max_attendees']) ? intval($_POST['max_attendees']) : '';
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $age_classification = isset($_POST['age_classification']) ? intval($_POST['age_classification']) : 0;
        $location = isset($_POST['location']) ? intval($_POST['location']) : 0;

        if (!$event_id || !$description || !$event_date || !$event_time) {
            wp_send_json_error(array('message' => __('Faltan datos obligatorios', 'event-show-base')));
        }

        $event = get_post($event_id);

        if (!$event || $event->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('No tienes permiso para editar este evento', 'event-show-base')));
        }

        // Verificar que quedan al menos 7 días
        $event_timestamp = strtotime(str_replace('/', '-', $event_date));
        $days_until_event = floor(($event_timestamp - time()) / (60 * 60 * 24));
        if ($days_until_event < 7) {
            wp_send_json_error(array('message' => __('Solo puedes editar eventos con al menos 7 días de anticipación', 'event-show-base')));
        }

        // Actualizar evento y cambiar estado a pending
        $updated = wp_update_post(array(
            'ID' => $event_id,
            'post_content' => $description,
            'post_status' => 'pending', // Volver a revisión
        ));

        if (is_wp_error($updated)) {
            wp_send_json_error(array('message' => __('Error al actualizar el evento', 'event-show-base')));
        }

        // Actualizar metadatos
        update_post_meta($event_id, '_event_date', $event_date);
        update_post_meta($event_id, '_event_time', $event_time);
        update_post_meta($event_id, '_event_end_date', $event_end_date);
        update_post_meta($event_id, '_event_end_time', $event_end_time);
        update_post_meta($event_id, '_max_attendees', $max_attendees);

        // Actualizar taxonomías
        if ($category) {
            wp_set_post_terms($event_id, array($category), 'categoria_evento');
        }
        if ($age_classification) {
            wp_set_post_terms($event_id, array($age_classification), 'clasificacion_edad');
        }
        if ($location) {
            wp_set_post_terms($event_id, array($location), 'lugar');
        }

        // Procesar banner (imagen destacada)
        if (!empty($_FILES['banner']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('banner', $event_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($event_id, $attachment_id);
            }
        }

        // Procesar imagen grid
        if (!empty($_FILES['grid_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('grid_image', $event_id);
            if (!is_wp_error($attachment_id)) {
                update_post_meta($event_id, '_event_thumbnail', $attachment_id);
            }
        }

        // Log
        Event_Show_Logger::log(
            'event_edited',
            'evento',
            $event_id,
            sprintf(__('Evento "%s" editado y enviado a revisión', 'event-show-base'), $event->post_title)
        );

        wp_send_json_success(array(
            'message' => __('Evento actualizado correctamente. Pasará a revisión nuevamente.', 'event-show-base'),
        ));
    }

    /**
     * AJAX: Obtener asistentes de un evento
     */
    public function get_attendees()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Debes iniciar sesión', 'event-show-base')));
        }

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error(array('message' => __('ID de evento inválido', 'event-show-base')));
        }

        // Verificar que el usuario es el autor del evento
        $event = get_post($event_id);
        if (!$event || $event->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('No tienes permisos para ver estos asistentes', 'event-show-base')));
        }

        // Obtener asistentes
        $attendees = Event_Show_Attendees::get_attendees($event_id);

        wp_send_json_success(array(
            'attendees' => $attendees,
            'total' => count($attendees),
        ));
    }

    /**
     * AJAX: Cargar más eventos (paginación)
     */
    public function load_more_events()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $layout = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'grid';
        $atts = isset($_POST['atts']) ? $_POST['atts'] : array();

        // Si atts viene como string JSON, decodificar
        if (is_string($atts)) {
            $atts = json_decode(stripslashes($atts), true);
        }

        // Asegurarse de que atts es un array
        if (!is_array($atts)) {
            $atts = array();
        }

        // Sanitizar atts
        $atts['layout'] = $layout;

        // Simular paginación
        set_query_var('paged', $page);

        // Usar Event_Show_Shortcodes para renderizar
        $shortcodes = new Event_Show_Shortcodes();
        $reflection = new ReflectionClass($shortcodes);
        $method = $reflection->getMethod('render_events');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($shortcodes, $atts);
        $full_html = ob_get_clean();

        // Extraer solo el contenido de eventos (sin wrapper ni paginación)
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $full_html);

        $container_class = ($layout === 'grid') ? 'events-grid-container' : 'events-list-container';
        $xpath = new DOMXPath($dom);
        $container = $xpath->query("//*[contains(@class, '{$container_class}')]")->item(0);

        if ($container) {
            $html = '';
            foreach ($container->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }

            wp_send_json_success(array(
                'html' => $html,
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No se pudieron cargar más eventos', 'event-show-base'),
            ));
        }
    }

    /**
     * AJAX: Obtener datos de un organizador
     */
    public function get_organizer_data()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Debes iniciar sesión', 'event-show-base'),
            ));
        }

        $organizer_id = isset($_POST['organizer_id']) ? intval($_POST['organizer_id']) : 0;

        if (!$organizer_id) {
            wp_send_json_error(array(
                'message' => __('ID de organizador inválido', 'event-show-base'),
            ));
        }

        $term = get_term($organizer_id, 'organizador');

        if (is_wp_error($term) || !$term) {
            wp_send_json_error(array(
                'message' => __('Organizador no encontrado', 'event-show-base'),
            ));
        }

        wp_send_json_success(array(
            'id' => $term->term_id,
            'name' => $term->name,
            'description' => $term->description,
            'contact_info' => get_term_meta($term->term_id, 'contact_info', true),
            'logo' => get_term_meta($term->term_id, 'logo', true),
        ));
    }

    /**
     * AJAX: Guardar organizador (crear o actualizar)
     */
    public function save_organizer()
    {
        check_ajax_referer('event_show_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Debes iniciar sesión', 'event-show-base'),
            ));
        }

        $current_user_id = get_current_user_id();
        $organizer_id = isset($_POST['organizer_id']) ? intval($_POST['organizer_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $contact_info = isset($_POST['contact_info']) ? sanitize_text_field($_POST['contact_info']) : '';
        $logo = isset($_POST['logo']) ? esc_url_raw($_POST['logo']) : '';

        if (empty($name)) {
            wp_send_json_error(array(
                'message' => __('El nombre es obligatorio', 'event-show-base'),
            ));
        }

        if ($organizer_id) {
            // Actualizar organizador existente
            // Verificar que el usuario sea el propietario o admin
            $owner_id = get_term_meta($organizer_id, 'owner_id', true);

            if (!current_user_can('manage_options') && $owner_id != $current_user_id) {
                wp_send_json_error(array(
                    'message' => __('No tienes permisos para editar este organizador', 'event-show-base'),
                ));
            }

            $result = wp_update_term($organizer_id, 'organizador', array(
                'name' => $name,
                'description' => $description,
            ));

            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                ));
            }

            $term_id = $organizer_id;

            // Log de edición
            Event_Show_Logger::log_activity(
                'organizer_updated',
                $current_user_id,
                'Organizador actualizado: ' . $name,
                array('term_id' => $term_id)
            );
        } else {
            // Crear nuevo organizador
            $result = wp_insert_term($name, 'organizador', array(
                'description' => $description,
            ));

            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                ));
            }

            $term_id = $result['term_id'];

            // Establecer propietario
            update_term_meta($term_id, 'owner_id', $current_user_id);

            // Establecer nombre del propietario para referencia
            $current_user = wp_get_current_user();
            update_term_meta($term_id, 'owner_name', $current_user->display_name);

            // Estado: pendiente hasta que un admin lo apruebe
            // Solo admins pueden crear organizadores ya aprobados
            $status = current_user_can('manage_options') ? 'approved' : 'pending';
            update_term_meta($term_id, 'status', $status);

            // Log de creación
            Event_Show_Logger::log_activity(
                'organizer_created',
                $current_user_id,
                'Organizador creado: ' . $name . ' (Estado: ' . $status . ')',
                array('term_id' => $term_id, 'status' => $status)
            );
        }

        // Guardar otros metadatos
        update_term_meta($term_id, 'contact_info', $contact_info);
        update_term_meta($term_id, 'logo', $logo);

        // Mensaje de respuesta según si necesita aprobación
        $message = '';
        if ($organizer_id) {
            $message = __('Organizador actualizado correctamente', 'event-show-base');
        } else {
            if (current_user_can('manage_options')) {
                $message = __('Organizador creado y aprobado correctamente', 'event-show-base');
            } else {
                $message = __('Organizador creado. Está pendiente de aprobación por un administrador antes de poder usarse.', 'event-show-base');
            }
        }

        wp_send_json_success(array(
            'message' => $message,
            'term_id' => $term_id,
            'needs_approval' => !$organizer_id && !current_user_can('manage_options'),
        ));
    }
}
