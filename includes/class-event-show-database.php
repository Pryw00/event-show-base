<?php

/**
 * Gestión de la base de datos del plugin
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar tablas de la base de datos
 */
class Event_Show_Database
{

    /**
     * Crear todas las tablas necesarias
     */
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de asistentes
        $table_attendees = $wpdb->prefix . 'event_show_attendees';
        $sql_attendees = "CREATE TABLE IF NOT EXISTS $table_attendees (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id bigint(20) UNSIGNED NOT NULL,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			phone varchar(50) DEFAULT NULL,
			num_attendees int(11) NOT NULL DEFAULT 1,
			accepted_terms tinyint(1) NOT NULL DEFAULT 0,
			registration_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			status varchar(20) NOT NULL DEFAULT 'confirmed',
			ip_address varchar(100) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY event_id (event_id),
			KEY email (email)
		) $charset_collate;";

        // Tabla de logs
        $table_logs = $wpdb->prefix . 'event_show_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			action varchar(100) NOT NULL,
			object_type varchar(50) DEFAULT NULL,
			object_id bigint(20) UNSIGNED DEFAULT NULL,
			description text DEFAULT NULL,
			ip_address varchar(100) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY object_type (object_type),
			KEY created_at (created_at)
		) $charset_collate;";

        // Tabla de meta datos para taxonomías (organizadores y lugares)
        $table_termmeta = $wpdb->prefix . 'event_show_termmeta';
        $sql_termmeta = "CREATE TABLE IF NOT EXISTS $table_termmeta (
			meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			term_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			PRIMARY KEY  (meta_id),
			KEY term_id (term_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_attendees);
        dbDelta($sql_logs);
        dbDelta($sql_termmeta);
    }

    /**
     * Eliminar todas las tablas
     */
    public static function drop_tables()
    {
        global $wpdb;

        $table_attendees = $wpdb->prefix . 'event_show_attendees';
        $table_logs = $wpdb->prefix . 'event_show_logs';
        $table_termmeta = $wpdb->prefix . 'event_show_termmeta';

        $wpdb->query("DROP TABLE IF EXISTS $table_attendees");
        $wpdb->query("DROP TABLE IF EXISTS $table_logs");
        $wpdb->query("DROP TABLE IF EXISTS $table_termmeta");
    }
}
