<?php

/**
 * Registro de Custom Post Types
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para registrar Custom Post Types
 */
class Event_Show_Post_Types
{

    /**
     * Registrar todos los post types
     */
    public static function register()
    {
        self::register_evento();
    }

    /**
     * Registrar CPT Evento
     */
    private static function register_evento()
    {
        $labels = array(
            'name'                  => _x('Eventos', 'Post Type General Name', 'event-show-base'),
            'singular_name'         => _x('Evento', 'Post Type Singular Name', 'event-show-base'),
            'menu_name'             => __('Eventos', 'event-show-base'),
            'name_admin_bar'        => __('Evento', 'event-show-base'),
            'archives'              => __('Archivo de Eventos', 'event-show-base'),
            'attributes'            => __('Atributos del Evento', 'event-show-base'),
            'parent_item_colon'     => __('Evento Padre:', 'event-show-base'),
            'all_items'             => __('Todos los Eventos', 'event-show-base'),
            'add_new_item'          => __('Añadir Nuevo Evento', 'event-show-base'),
            'add_new'               => __('Añadir Nuevo', 'event-show-base'),
            'new_item'              => __('Nuevo Evento', 'event-show-base'),
            'edit_item'             => __('Editar Evento', 'event-show-base'),
            'update_item'           => __('Actualizar Evento', 'event-show-base'),
            'view_item'             => __('Ver Evento', 'event-show-base'),
            'view_items'            => __('Ver Eventos', 'event-show-base'),
            'search_items'          => __('Buscar Evento', 'event-show-base'),
            'not_found'             => __('No encontrado', 'event-show-base'),
            'not_found_in_trash'    => __('No encontrado en papelera', 'event-show-base'),
            'featured_image'        => __('Imagen Destacada (Banner)', 'event-show-base'),
            'set_featured_image'    => __('Establecer imagen destacada', 'event-show-base'),
            'remove_featured_image' => __('Eliminar imagen destacada', 'event-show-base'),
            'use_featured_image'    => __('Usar como imagen destacada', 'event-show-base'),
            'insert_into_item'      => __('Insertar en evento', 'event-show-base'),
            'uploaded_to_this_item' => __('Subido a este evento', 'event-show-base'),
            'items_list'            => __('Lista de eventos', 'event-show-base'),
            'items_list_navigation' => __('Navegación de lista de eventos', 'event-show-base'),
            'filter_items_list'     => __('Filtrar lista de eventos', 'event-show-base'),
        );

        $args = array(
            'label'               => __('Evento', 'event-show-base'),
            'description'         => __('Eventos del sitio', 'event-show-base'),
            'labels'              => $labels,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields'),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar-alt',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'rewrite'             => array(
                'slug'       => 'eventos',
                'with_front' => false,
            ),
        );

        register_post_type('evento', $args);
    }
}
