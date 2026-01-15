<?php

/**
 * Gestión de templates
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar templates
 */
class Event_Show_Templates
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_filter('single_template', array($this, 'load_single_template'));
        add_filter('archive_template', array($this, 'load_archive_template'));
    }

    /**
     * Cargar template para single evento
     */
    public function load_single_template($template)
    {
        if (is_singular('evento')) {
            $use_default_template = get_post_meta(get_the_ID(), '_use_default_template', true);

            // Si usa plantilla por defecto
            if ('1' === $use_default_template || '' === $use_default_template) {
                $plugin_template = EVENT_SHOW_PLUGIN_DIR . 'templates/single-evento.php';

                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }

        return $template;
    }

    /**
     * Cargar template para archive de eventos
     */
    public function load_archive_template($template)
    {
        if (is_post_type_archive('evento') || is_tax(array('categoria_evento', 'clasificacion_edad', 'organizador', 'lugar'))) {
            $plugin_template = EVENT_SHOW_PLUGIN_DIR . 'templates/archive-evento.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
}
