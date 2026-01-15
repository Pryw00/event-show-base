<?php

/**
 * Registro de Taxonomías
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para registrar taxonomías
 */
class Event_Show_Taxonomies
{

    /**
     * Registrar todas las taxonomías
     */
    public static function register()
    {
        self::register_categoria_evento();
        self::register_clasificacion_edad();
        self::register_organizador();
        self::register_lugar();
    }

    /**
     * Registrar taxonomía Categoría de Evento
     */
    private static function register_categoria_evento()
    {
        $labels = array(
            'name'                       => _x('Categorías de Evento', 'Taxonomy General Name', 'event-show-base'),
            'singular_name'              => _x('Categoría de Evento', 'Taxonomy Singular Name', 'event-show-base'),
            'menu_name'                  => __('Categorías', 'event-show-base'),
            'all_items'                  => __('Todas las Categorías', 'event-show-base'),
            'parent_item'                => __('Categoría Padre', 'event-show-base'),
            'parent_item_colon'          => __('Categoría Padre:', 'event-show-base'),
            'new_item_name'              => __('Nueva Categoría', 'event-show-base'),
            'add_new_item'               => __('Añadir Nueva Categoría', 'event-show-base'),
            'edit_item'                  => __('Editar Categoría', 'event-show-base'),
            'update_item'                => __('Actualizar Categoría', 'event-show-base'),
            'view_item'                  => __('Ver Categoría', 'event-show-base'),
            'separate_items_with_commas' => __('Separar categorías con comas', 'event-show-base'),
            'add_or_remove_items'        => __('Añadir o eliminar categorías', 'event-show-base'),
            'choose_from_most_used'      => __('Elegir de las más usadas', 'event-show-base'),
            'popular_items'              => __('Categorías Populares', 'event-show-base'),
            'search_items'               => __('Buscar Categorías', 'event-show-base'),
            'not_found'                  => __('No encontrado', 'event-show-base'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'categoria-evento'),
        );

        register_taxonomy('categoria_evento', array('evento'), $args);
    }

    /**
     * Registrar taxonomía Clasificación de Edad
     */
    private static function register_clasificacion_edad()
    {
        $labels = array(
            'name'                       => _x('Clasificaciones de Edad', 'Taxonomy General Name', 'event-show-base'),
            'singular_name'              => _x('Clasificación de Edad', 'Taxonomy Singular Name', 'event-show-base'),
            'menu_name'                  => __('Clasificación Edad', 'event-show-base'),
            'all_items'                  => __('Todas las Clasificaciones', 'event-show-base'),
            'parent_item'                => __('Clasificación Padre', 'event-show-base'),
            'parent_item_colon'          => __('Clasificación Padre:', 'event-show-base'),
            'new_item_name'              => __('Nueva Clasificación', 'event-show-base'),
            'add_new_item'               => __('Añadir Nueva Clasificación', 'event-show-base'),
            'edit_item'                  => __('Editar Clasificación', 'event-show-base'),
            'update_item'                => __('Actualizar Clasificación', 'event-show-base'),
            'view_item'                  => __('Ver Clasificación', 'event-show-base'),
            'separate_items_with_commas' => __('Separar clasificaciones con comas', 'event-show-base'),
            'add_or_remove_items'        => __('Añadir o eliminar clasificaciones', 'event-show-base'),
            'choose_from_most_used'      => __('Elegir de las más usadas', 'event-show-base'),
            'popular_items'              => __('Clasificaciones Populares', 'event-show-base'),
            'search_items'               => __('Buscar Clasificaciones', 'event-show-base'),
            'not_found'                  => __('No encontrado', 'event-show-base'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'clasificacion-edad'),
        );

        register_taxonomy('clasificacion_edad', array('evento'), $args);
    }

    /**
     * Registrar taxonomía Organizador
     */
    private static function register_organizador()
    {
        $labels = array(
            'name'                       => _x('Organizadores', 'Taxonomy General Name', 'event-show-base'),
            'singular_name'              => _x('Organizador', 'Taxonomy Singular Name', 'event-show-base'),
            'menu_name'                  => __('Organizadores', 'event-show-base'),
            'all_items'                  => __('Todos los Organizadores', 'event-show-base'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'new_item_name'              => __('Nuevo Organizador', 'event-show-base'),
            'add_new_item'               => __('Añadir Nuevo Organizador', 'event-show-base'),
            'edit_item'                  => __('Editar Organizador', 'event-show-base'),
            'update_item'                => __('Actualizar Organizador', 'event-show-base'),
            'view_item'                  => __('Ver Organizador', 'event-show-base'),
            'separate_items_with_commas' => __('Separar organizadores con comas', 'event-show-base'),
            'add_or_remove_items'        => __('Añadir o eliminar organizadores', 'event-show-base'),
            'choose_from_most_used'      => __('Elegir de los más usados', 'event-show-base'),
            'popular_items'              => __('Organizadores Populares', 'event-show-base'),
            'search_items'               => __('Buscar Organizadores', 'event-show-base'),
            'not_found'                  => __('No encontrado', 'event-show-base'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'organizador'),
        );

        register_taxonomy('organizador', array('evento'), $args);
    }

    /**
     * Registrar taxonomía Lugar
     */
    private static function register_lugar()
    {
        $labels = array(
            'name'                       => _x('Lugares', 'Taxonomy General Name', 'event-show-base'),
            'singular_name'              => _x('Lugar', 'Taxonomy Singular Name', 'event-show-base'),
            'menu_name'                  => __('Lugares', 'event-show-base'),
            'all_items'                  => __('Todos los Lugares', 'event-show-base'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'new_item_name'              => __('Nuevo Lugar', 'event-show-base'),
            'add_new_item'               => __('Añadir Nuevo Lugar', 'event-show-base'),
            'edit_item'                  => __('Editar Lugar', 'event-show-base'),
            'update_item'                => __('Actualizar Lugar', 'event-show-base'),
            'view_item'                  => __('Ver Lugar', 'event-show-base'),
            'separate_items_with_commas' => __('Separar lugares con comas', 'event-show-base'),
            'add_or_remove_items'        => __('Añadir o eliminar lugares', 'event-show-base'),
            'choose_from_most_used'      => __('Elegir de los más usados', 'event-show-base'),
            'popular_items'              => __('Lugares Populares', 'event-show-base'),
            'search_items'               => __('Buscar Lugares', 'event-show-base'),
            'not_found'                  => __('No encontrado', 'event-show-base'),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'lugar'),
        );

        register_taxonomy('lugar', array('evento'), $args);
    }
}
