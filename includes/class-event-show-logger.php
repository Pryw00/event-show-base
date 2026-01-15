<?php

/**
 * Sistema de Logs/Auditoría
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para registrar logs de acciones
 */
class Event_Show_Logger
{

    /**
     * Registrar un log
     *
     * @param string $action Acción realizada
     * @param string $object_type Tipo de objeto
     * @param int    $object_id ID del objeto
     * @param string $description Descripción
     */
    public static function log($action, $object_type = null, $object_id = null, $description = null)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_show_logs';

        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();

        $data = array(
            'user_id' => $user_id ? $user_id : null,
            'action' => sanitize_text_field($action),
            'object_type' => $object_type ? sanitize_text_field($object_type) : null,
            'object_id' => $object_id ? absint($object_id) : null,
            'description' => $description ? sanitize_text_field($description) : null,
            'ip_address' => $ip_address,
        );

        $wpdb->insert($table, $data);
    }

    /**
     * Obtener logs
     *
     * @param array $args Argumentos de búsqueda
     * @return array
     */
    public static function get_logs($args = array())
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_show_logs';

        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'action' => null,
            'object_type' => null,
            'object_id' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');

        if ($args['action']) {
            $where[] = $wpdb->prepare('action = %s', $args['action']);
        }

        if ($args['object_type']) {
            $where[] = $wpdb->prepare('object_type = %s', $args['object_type']);
        }

        if ($args['object_id']) {
            $where[] = $wpdb->prepare('object_id = %d', $args['object_id']);
        }

        if ($args['user_id']) {
            $where[] = $wpdb->prepare('user_id = %d', $args['user_id']);
        }

        if ($args['date_from']) {
            $where[] = $wpdb->prepare('created_at >= %s', $args['date_from']);
        }

        if ($args['date_to']) {
            $where[] = $wpdb->prepare('created_at <= %s', $args['date_to']);
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";

        $logs = $wpdb->get_results(
            $wpdb->prepare($sql, $args['limit'], $args['offset']),
            ARRAY_A
        );

        return $logs ? $logs : array();
    }

    /**
     * Limpiar logs antiguos
     *
     * @param int $days Días de antigüedad
     */
    public static function clean_old_logs($days = 90)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'event_show_logs';
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_at < %s",
            $date
        ));
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
}
