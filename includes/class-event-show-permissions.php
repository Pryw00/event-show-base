<?php

/**
 * Sistema de permisos de Event Show
 *
 * @package Event_Show
 * @since 1.3.0
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar permisos del plugin
 */
class Event_Show_Permissions
{

    /**
     * Verificar si el usuario puede crear eventos
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_create_event($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_create = apply_filters('event_show_check_permission', false, 'create_eventos', $user_id);
        if ($can_create) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'create_eventos')) {
            return true;
        }

        // Los administradores siempre pueden crear
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Por defecto, cualquier usuario registrado puede crear (como solicitud)
        return is_user_logged_in();
    }

    /**
     * Verificar si el usuario puede editar un evento
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_edit_event($post_id, $user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_edit = apply_filters('event_show_check_permission', false, 'edit_others_eventos', $user_id);
        if ($can_edit) {
            return true;
        }

        // Capacidad para editar de otros
        if (user_can($user_id, 'edit_others_eventos')) {
            return true;
        }

        // Los administradores pueden editar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // El autor puede editar su propio evento si tiene la capacidad
        if ((int) $post->post_author === (int) $user_id) {
            return user_can($user_id, 'edit_eventos');
        }

        return false;
    }

    /**
     * Verificar si el usuario puede eliminar un evento
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_delete_event($post_id, $user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_delete = apply_filters('event_show_check_permission', false, 'delete_others_eventos', $user_id);
        if ($can_delete) {
            return true;
        }

        // Capacidad para eliminar de otros
        if (user_can($user_id, 'delete_others_eventos')) {
            return true;
        }

        // Los administradores pueden eliminar todo
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        $post = get_post($post_id);

        if (! $post) {
            return false;
        }

        // El autor puede eliminar su propio evento si tiene la capacidad
        if ((int) $post->post_author === (int) $user_id) {
            return user_can($user_id, 'delete_eventos');
        }

        return false;
    }

    /**
     * Verificar si el usuario puede aprobar eventos
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_approve_event($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_approve = apply_filters('event_show_check_permission', false, 'approve_eventos', $user_id);
        if ($can_approve) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'approve_eventos')) {
            return true;
        }

        // Los administradores siempre pueden aprobar
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Autores (usuarios con permiso para editar eventos de otros) pueden aprobar
        return user_can($user_id, 'edit_others_eventos');
    }

    /**
     * Verificar si el usuario puede rechazar eventos
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_reject_event($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_reject = apply_filters('event_show_check_permission', false, 'reject_eventos', $user_id);
        if ($can_reject) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'reject_eventos')) {
            return true;
        }

        // Solo administradores y usuarios con permiso de aprobar pueden rechazar
        return self::can_approve_event($user_id);
    }

    /**
     * Verificar si el usuario puede crear organizadores
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_create_organizer($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_create = apply_filters('event_show_check_permission', false, 'create_organizadores', $user_id);
        if ($can_create) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'create_organizadores')) {
            return true;
        }

        // Los administradores siempre pueden crear
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Solo usuarios con capacidad de editar eventos de otros (autores) pueden crear organizadores
        return user_can($user_id, 'edit_others_eventos');
    }

    /**
     * Verificar si el usuario puede editar organizadores
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_edit_organizer($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_edit = apply_filters('event_show_check_permission', false, 'edit_organizadores', $user_id);
        if ($can_edit) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'edit_organizadores')) {
            return true;
        }

        // Los administradores siempre pueden editar
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si el usuario puede eliminar organizadores
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_delete_organizer($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_delete = apply_filters('event_show_check_permission', false, 'delete_organizadores', $user_id);
        if ($can_delete) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'delete_organizadores')) {
            return true;
        }

        // Solo administradores pueden eliminar
        return user_can($user_id, 'manage_options');
    }

    /**
     * Verificar si el usuario puede aprobar organizadores
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_approve_organizer($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_approve = apply_filters('event_show_check_permission', false, 'approve_organizadores', $user_id);
        if ($can_approve) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'approve_organizadores')) {
            return true;
        }

        // Los administradores pueden aprobar
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Autores (usuarios con permiso para editar eventos de otros) pueden aprobar organizadores
        return user_can($user_id, 'edit_others_eventos');
    }

    /**
     * Verificar si el usuario puede ver asistentes
     *
     * @param int $event_id ID del evento (opcional).
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_view_attendees($event_id = 0, $user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_view = apply_filters('event_show_check_permission', false, 'view_event_attendees', $user_id);
        if ($can_view) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'view_event_attendees')) {
            return true;
        }

        // Los administradores siempre pueden ver
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Autores (usuarios con permiso para editar eventos de otros) pueden ver todos los asistentes
        if (user_can($user_id, 'edit_others_eventos')) {
            return true;
        }

        // Si se especifica un evento, verificar si es el autor
        if ($event_id) {
            $post = get_post($event_id);
            if ($post && (int) $post->post_author === (int) $user_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si el usuario puede gestionar asistentes
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_manage_attendees($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_manage = apply_filters('event_show_check_permission', false, 'manage_event_attendees', $user_id);
        if ($can_manage) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'manage_event_attendees')) {
            return true;
        }

        // Administradores pueden gestionar
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Autores (usuarios con permiso para editar eventos de otros) pueden gestionar asistentes
        return user_can($user_id, 'edit_others_eventos');
    }

    /**
     * Verificar si el usuario puede exportar asistentes
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_export_attendees($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_export = apply_filters('event_show_check_permission', false, 'export_event_attendees', $user_id);
        if ($can_export) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'export_event_attendees')) {
            return true;
        }

        // Los que pueden gestionar asistentes pueden exportar
        return self::can_manage_attendees($user_id);
    }

    /**
     * Verificar si el usuario puede eliminar asistentes
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_delete_attendee($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_delete = apply_filters('event_show_check_permission', false, 'delete_event_attendees', $user_id);
        if ($can_delete) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'delete_event_attendees')) {
            return true;
        }

        // Los que pueden gestionar asistentes pueden eliminar
        return self::can_manage_attendees($user_id);
    }

    /**
     * Verificar si el usuario puede ver logs
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_view_logs($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_view = apply_filters('event_show_check_permission', false, 'view_event_show_logs', $user_id);
        if ($can_view) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'view_event_show_logs')) {
            return true;
        }

        // Administradores pueden ver logs
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Autores (usuarios con permiso para editar eventos de otros) también pueden ver logs
        return user_can($user_id, 'edit_others_eventos');
    }

    /**
     * Verificar si el usuario puede gestionar configuración de Event Show
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_manage_settings($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_manage = apply_filters('event_show_check_permission', false, 'manage_event_show_settings', $user_id);
        if ($can_manage) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'manage_event_show_settings')) {
            return true;
        }

        // Solo administradores pueden gestionar configuración
        return user_can($user_id, 'manage_options');
    }

    /**
     * Verificar si el usuario puede gestionar taxonomías (categorías, lugares, organizadores)
     *
     * @param int $user_id ID del usuario (opcional).
     * @return bool
     */
    public static function can_manage_taxonomies($user_id = 0)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        // Hook para integración con Advanced Role Manager
        $can_manage = apply_filters('event_show_check_permission', false, 'manage_event_taxonomies', $user_id);
        if ($can_manage) {
            return true;
        }

        // Verificar capacidad específica
        if (user_can($user_id, 'manage_event_taxonomies')) {
            return true;
        }

        // Solo administradores pueden gestionar taxonomías directamente en el backend
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Autores pueden crear organizadores pero no acceder a gestión de taxonomías
        return false;
    }

    /**
     * Filtrar query de eventos por permisos
     *
     * @param WP_Query $query Query a filtrar.
     */
    public static function filter_query_by_permissions($query)
    {
        // Solo filtrar en admin y en la consulta principal
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        if ('evento' !== $query->get('post_type')) {
            return;
        }

        $user_id = get_current_user_id();

        // Administradores ven todo
        if (user_can($user_id, 'manage_options')) {
            return;
        }

        // Verificar permiso usando el sistema de integración con ARM
        $can_edit_others = apply_filters('event_show_check_permission', false, 'edit_others_eventos', $user_id);

        // Si no usa ARM, verificar capacidad nativa
        if (!$can_edit_others && user_can($user_id, 'edit_others_eventos')) {
            $can_edit_others = true;
        }

        // Si tiene permiso para ver de otros, ver todo
        if ($can_edit_others) {
            return;
        }

        // Verificar si al menos puede editar propios
        $can_edit = apply_filters('event_show_check_permission', false, 'edit_eventos', $user_id);
        if (!$can_edit && !user_can($user_id, 'edit_eventos')) {
            // No tiene permisos, no mostrar nada
            $query->set('author', 0);
            return;
        }

        // Solo ver propios
        $query->set('author', $user_id);
    }
}

// Filtrar query en admin
add_action('pre_get_posts', array('Event_Show_Permissions', 'filter_query_by_permissions'));
