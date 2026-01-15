<?php

/**
 * Plugin Name: Event Show
 * Plugin URI: https://example.com/event-show
 * Description: Plugin de gestión avanzada de eventos para WordPress, desarrollado conforme a los requerimientos IEEE 830-1998 definidos en el documento SRS.
 * Version: 1.0.0
 * Author: Pryw00
 * Author URI: https://example.com
 * Text Domain: event-show-base
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('EVENT_SHOW_VERSION', '1.0.0');
define('EVENT_SHOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVENT_SHOW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EVENT_SHOW_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin Event Show
 */
class Event_Show
{

    /**
     * Instancia única de la clase
     *
     * @var Event_Show
     */
    private static $instance = null;

    /**
     * Obtener la instancia única de la clase
     *
     * @return Event_Show
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Cargar dependencias del plugin
     */
    private function load_dependencies()
    {
        // Core
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-post-types.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-taxonomies.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-metaboxes.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-database.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-attendees.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-notifications.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-logger.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'includes/class-event-show-helpers.php';

        // Admin
        require_once EVENT_SHOW_PLUGIN_DIR . 'admin/class-event-show-admin.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'admin/class-event-show-admin-columns.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'admin/class-event-show-settings.php';

        // Public
        require_once EVENT_SHOW_PLUGIN_DIR . 'public/class-event-show-public.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'public/class-event-show-shortcodes.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'public/class-event-show-templates.php';
        require_once EVENT_SHOW_PLUGIN_DIR . 'public/class-event-show-ajax.php';
    }

    /**
     * Definir hooks de WordPress
     */
    private function define_hooks()
    {
        // Activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Cargar textdomain para internacionalización
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Inicializar componentes
        add_action('init', array($this, 'init_components'));

        // Enqueue scripts y styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Activar plugin
     */
    public function activate()
    {
        // Crear tablas de base de datos
        Event_Show_Database::create_tables();

        // Registrar post types y taxonomías para flush rewrite rules
        Event_Show_Post_Types::register();
        Event_Show_Taxonomies::register();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Crear página de configuración por defecto
        $this->create_default_settings();

        // Log de activación
        Event_Show_Logger::log('Plugin activado', 'system');
    }

    /**
     * Desactivar plugin
     */
    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Log de desactivación
        Event_Show_Logger::log('Plugin desactivado', 'system');
    }

    /**
     * Crear configuración por defecto
     */
    private function create_default_settings()
    {
        $default_settings = array(
            'email_notifications_enabled' => true,
            'admin_email' => get_option('admin_email'),
            'min_days_advance' => 5,
            'require_approval' => true,
            'default_organizer' => 0,
            'events_per_page' => 12,
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
        );

        foreach ($default_settings as $key => $value) {
            if (false === get_option('event_show_' . $key)) {
                add_option('event_show_' . $key, $value);
            }
        }
    }

    /**
     * Cargar textdomain para traducciones
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'event-show-base',
            false,
            dirname(EVENT_SHOW_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Inicializar componentes del plugin
     */
    public function init_components()
    {
        // Registrar post types
        Event_Show_Post_Types::register();

        // Registrar taxonomías
        Event_Show_Taxonomies::register();

        // Inicializar metaboxes
        new Event_Show_Metaboxes();

        // Inicializar admin
        if (is_admin()) {
            new Event_Show_Admin();
            new Event_Show_Admin_Columns();
            new Event_Show_Settings();
        }

        // Inicializar público
        new Event_Show_Public();
        new Event_Show_Shortcodes();
        new Event_Show_Templates();
        new Event_Show_Ajax();
    }

    /**
     * Encolar assets públicos
     */
    public function enqueue_public_assets()
    {
        // CSS
        wp_enqueue_style(
            'event-show-public',
            EVENT_SHOW_PLUGIN_URL . 'assets/css/public.css',
            array(),
            EVENT_SHOW_VERSION
        );

        // JS
        wp_enqueue_script(
            'event-show-public',
            EVENT_SHOW_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            EVENT_SHOW_VERSION,
            true
        );

        // Localizar script
        wp_localize_script(
            'event-show-public',
            'eventShowData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('event_show_nonce'),
                'i18n' => array(
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'event-show-base'),
                    'success' => __('¡Registro exitoso!', 'event-show-base'),
                    'loading' => __('Cargando...', 'event-show-base'),
                ),
            )
        );
    }

    /**
     * Encolar assets de admin
     */
    public function enqueue_admin_assets($hook)
    {
        // Solo cargar en páginas del plugin
        $screen = get_current_screen();

        // Cargar en: post type evento, taxonomías del plugin, y páginas de configuración
        $allowed_post_types = array('evento');
        $allowed_taxonomies = array('organizador', 'lugar', 'categoria_evento', 'clasificacion_edad');

        $should_load = false;

        if ($screen) {
            // En páginas de edición de eventos
            if (in_array($screen->post_type, $allowed_post_types)) {
                $should_load = true;
            }
            // En páginas de taxonomías
            if (in_array($screen->taxonomy, $allowed_taxonomies)) {
                $should_load = true;
            }
            // En páginas de configuración del plugin
            if (strpos($hook, 'event-show') !== false) {
                $should_load = true;
            }
        }

        if (!$should_load) {
            return;
        }

        // WordPress Media Uploader
        wp_enqueue_media();

        // CSS
        wp_enqueue_style(
            'event-show-admin',
            EVENT_SHOW_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EVENT_SHOW_VERSION
        );

        // jQuery UI Datepicker
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style(
            'jquery-ui-style',
            '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
            array(),
            '1.13.2'
        );

        // JS
        wp_enqueue_script(
            'event-show-admin',
            EVENT_SHOW_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            EVENT_SHOW_VERSION,
            true
        );

        // Localizar script
        wp_localize_script(
            'event-show-admin',
            'eventShowAdminData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('event_show_admin_nonce'),
            )
        );
    }
}

/**
 * Inicializar el plugin
 */
function event_show_init()
{
    return Event_Show::get_instance();
}

// Iniciar el plugin
event_show_init();
