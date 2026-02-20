<?php

/**
 * Gestión de Asistentes
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar asistentes
 */
class Event_Show_Attendees
{

    /**
     * Registrar un asistente
     *
     * @param int   $event_id ID del evento
     * @param array $data Datos del asistente
     * @return int|bool ID del registro o false si falla
     */
    public static function register($event_id, $data)
    {
        global $wpdb;

        // Validaciones
        if (! self::can_register($event_id, $data)) {
            return false;
        }

        $table = $wpdb->prefix . 'event_show_attendees';

        $insert_data = array(
            'event_id' => $event_id,
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'num_attendees' => isset($data['num_attendees']) ? absint($data['num_attendees']) : 1,
            'accepted_terms' => isset($data['accepted_terms']) ? 1 : 0,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
            'status' => 'confirmed',
        );

        $result = $wpdb->insert($table, $insert_data);

        if ($result) {
            $attendee_id = $wpdb->insert_id;

            // Enviar notificación al asistente
            Event_Show_Notifications::send_attendee_confirmation($attendee_id, $event_id);

            // Enviar notificación al admin
            Event_Show_Notifications::send_admin_new_registration($attendee_id, $event_id);

            // Log
            Event_Show_Logger::log(
                'attendee_registered',
                'evento',
                $event_id,
                sprintf(__('Nuevo registro: %s (%s)', 'event-show-base'), $data['name'], $data['email'])
            );

            return $attendee_id;
        }

        return false;
    }

    /**
     * Verificar si se puede registrar
     *
     * @param int   $event_id ID del evento
     * @param array $data Datos del asistente
     * @return bool
     */
    public static function can_register($event_id, $data)
    {
        // Verificar que el evento existe
        if ('evento' !== get_post_type($event_id)) {
            return false;
        }

        // Verificar que el registro está habilitado
        $enable_registration = get_post_meta($event_id, '_enable_registration', true);
        if ('0' === $enable_registration) {
            return false;
        }

        // Verificar fecha límite de registro
        $registration_deadline = get_post_meta($event_id, '_registration_deadline', true);
        if ($registration_deadline) {
            $deadline_timestamp = Event_Show_Helpers::date_to_timestamp($registration_deadline);
            if ($deadline_timestamp !== false && time() > $deadline_timestamp) {
                return false;
            }
        }

        // Verificar aforo máximo
        $max_attendees = get_post_meta($event_id, '_max_attendees', true);
        if ($max_attendees) {
            $current_count = self::get_total_attendees($event_id);
            $num_attendees = isset($data['num_attendees']) ? absint($data['num_attendees']) : 1;

            if (($current_count + $num_attendees) > $max_attendees) {
                return false;
            }
        }

        // Verificar email duplicado
        if (self::is_email_registered($event_id, $data['email'])) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si un email ya está registrado
     *
     * @param int    $event_id ID del evento
     * @param string $email Email a verificar
     * @return bool
     */
    public static function is_email_registered($event_id, $email)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_show_attendees';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE event_id = %d AND email = %s",
            $event_id,
            $email
        ));

        return $count > 0;
    }

    /**
     * Obtener total de asistentes de un evento
     *
     * @param int $event_id ID del evento
     * @return int
     */
    public static function get_total_attendees($event_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_show_attendees';
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(num_attendees) FROM $table WHERE event_id = %d AND status = 'confirmed'",
            $event_id
        ));

        return $total ? intval($total) : 0;
    }

    /**
     * Obtener lista de asistentes de un evento
     *
     * @param int $event_id ID del evento
     * @return array
     */
    public static function get_attendees($event_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_show_attendees';
        $attendees = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE event_id = %d ORDER BY registration_date DESC",
            $event_id
        ), ARRAY_A);

        return $attendees ? $attendees : array();
    }

    /**
     * Exportar asistentes a CSV
     *
     * @param int $event_id ID del evento
     */
    public static function export_csv($event_id)
    {
        $attendees = self::get_attendees($event_id);
        $event_title = get_the_title($event_id);

        // Headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="asistentes-' . sanitize_file_name($event_title) . '-' . date('Y-m-d') . '.csv"');

        // Abrir output
        $output = fopen('php://output', 'w');

        // BOM para UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers del CSV
        fputcsv($output, array(
            __('ID', 'event-show-base'),
            __('Nombre', 'event-show-base'),
            __('Email', 'event-show-base'),
            __('Teléfono', 'event-show-base'),
            __('Cantidad', 'event-show-base'),
            __('Fecha Registro', 'event-show-base'),
            __('Estado', 'event-show-base'),
        ));

        // Datos
        foreach ($attendees as $attendee) {
            fputcsv($output, array(
                $attendee['id'],
                $attendee['name'],
                $attendee['email'],
                $attendee['phone'],
                $attendee['num_attendees'],
                $attendee['registration_date'],
                $attendee['status'],
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private static function get_client_ip()
    {
        $ip = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }

    /**
     * Eliminar asistente
     *
     * @param int $attendee_id ID del asistente
     * @return bool
     */
    public static function delete($attendee_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_show_attendees';
        $result = $wpdb->delete($table, array('id' => $attendee_id), array('%d'));

        if ($result) {
            Event_Show_Logger::log(
                'attendee_deleted',
                'attendee',
                $attendee_id,
                __('Asistente eliminado', 'event-show-base')
            );
        }

        return $result !== false;
    }
}
