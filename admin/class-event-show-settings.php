<?php

/**
 * Página de configuración adicional
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para configuración
 */
class Event_Show_Settings
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
    }

    public function register_settings()
    {
        register_setting('event_show_settings', 'event_show_priority_roles');
        add_settings_section(
            'event_show_priority_roles_section',
            __('Roles prioritarios para eventos', 'event-show-base'),
            '__return_false',
            'event_show_settings'
        );
        add_settings_field(
            'event_show_priority_roles',
            __('Roles prioritarios (separados por coma)', 'event-show-base'),
            array($this, 'priority_roles_field'),
            'event_show_settings',
            'event_show_priority_roles_section'
        );
    }

    public function priority_roles_field()
    {
        $value = esc_attr(get_option('event_show_priority_roles', 'gold,platinum,vip'));
        echo '<input type="text" name="event_show_priority_roles" value="' . $value . '" style="width:300px;" />';
        echo '<p class="description">' . __('Ejemplo: gold,platinum,vip', 'event-show-base') . '</p>';
    }

    public function add_settings_page()
    {
        add_options_page(
            __('Event Show - Configuración', 'event-show-base'),
            __('Event Show', 'event-show-base'),
            'manage_options',
            'event_show_settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page()
    {
        echo '<div class="wrap">';
        echo '<h1>' . __('Configuración Event Show', 'event-show-base') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('event_show_settings');
        do_settings_sections('event_show_settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
