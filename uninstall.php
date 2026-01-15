<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package Event_Show
 */

// Si no se llama desde WordPress, salir
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Eliminar todas las tablas personalizadas
 */
function event_show_delete_tables()
{
    global $wpdb;

    $tables = array(
        $wpdb->prefix . 'event_show_attendees',
        $wpdb->prefix . 'event_show_logs',
        $wpdb->prefix . 'event_show_termmeta',
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}

/**
 * Eliminar todos los eventos y sus metadatos
 */
function event_show_delete_events()
{
    global $wpdb;

    // Obtener todos los eventos
    $events = get_posts(
        array(
            'post_type'      => 'evento',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        )
    );

    // Eliminar cada evento y sus metadatos
    foreach ($events as $event_id) {
        wp_delete_post($event_id, true);
    }

    // Limpiar metadatos huérfanos
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
		WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
    );
}

/**
 * Eliminar todos los términos de las taxonomías
 */
function event_show_delete_terms()
{
    $taxonomies = array(
        'categoria_evento',
        'clasificacion_edad',
        'organizador',
        'lugar',
    );

    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'fields'     => 'ids',
            )
        );

        if (! is_wp_error($terms)) {
            foreach ($terms as $term_id) {
                wp_delete_term($term_id, $taxonomy);
            }
        }
    }
}

/**
 * Eliminar opciones del plugin
 */
function event_show_delete_options()
{
    $options = array(
        'event_show_version',
        'event_show_db_version',
        'event_show_settings',
        'event_show_email_from',
        'event_show_email_name',
        'event_show_enable_notifications',
        'event_show_reminder_days',
        'event_show_logs_retention',
        'event_show_default_template',
        'event_show_default_capacity',
        'event_show_require_approval',
        'event_show_google_maps_api',
    );

    foreach ($options as $option) {
        delete_option($option);
    }
}

/**
 * Eliminar capacidades personalizadas
 */
function event_show_delete_capabilities()
{
    $roles = array('administrator', 'editor', 'author');
    $caps  = array(
        'edit_evento',
        'read_evento',
        'delete_evento',
        'edit_eventos',
        'edit_others_eventos',
        'publish_eventos',
        'read_private_eventos',
        'delete_eventos',
        'delete_private_eventos',
        'delete_published_eventos',
        'delete_others_eventos',
        'edit_private_eventos',
        'edit_published_eventos',
        'manage_event_terms',
        'edit_event_terms',
        'delete_event_terms',
        'assign_event_terms',
    );

    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($caps as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
}

/**
 * Limpiar caché
 */
function event_show_clean_cache()
{
    wp_cache_flush();
}

/**
 * Ejecutar desinstalación solo si el usuario lo confirmó
 */
if (get_option('event_show_delete_on_uninstall', false)) {
    // Eliminar tablas
    event_show_delete_tables();

    // Eliminar eventos
    event_show_delete_events();

    // Eliminar términos
    event_show_delete_terms();

    // Eliminar opciones
    event_show_delete_options();

    // Eliminar capacidades
    event_show_delete_capabilities();

    // Limpiar caché
    event_show_clean_cache();
}
