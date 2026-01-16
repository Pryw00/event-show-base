<?php

/**
 * Funcionalidad del área de administración
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para administración
 */
class Event_Show_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'show_admin_notices'));

        // Metafields para taxonomías
        add_action('organizador_add_form_fields', array($this, 'add_organizador_fields'));
        add_action('organizador_edit_form_fields', array($this, 'edit_organizador_fields'), 10, 2);
        add_action('created_organizador', array($this, 'save_organizador_fields'));
        add_action('edited_organizador', array($this, 'save_organizador_fields'));

        add_action('lugar_add_form_fields', array($this, 'add_lugar_fields'));
        add_action('lugar_edit_form_fields', array($this, 'edit_lugar_fields'), 10, 2);
        add_action('created_lugar', array($this, 'save_lugar_fields'));
        add_action('edited_lugar', array($this, 'save_lugar_fields'));

        // AJAX para gestión de asistentes
        add_action('wp_ajax_event_show_export_attendees', array($this, 'ajax_export_attendees'));
        add_action('wp_ajax_event_show_delete_attendee', array($this, 'ajax_delete_attendee'));

        // Campos de organizador en perfil de usuario
        add_action('show_user_profile', array($this, 'user_organizador_fields'));
        add_action('edit_user_profile', array($this, 'user_organizador_fields'));
        add_action('personal_options_update', array($this, 'save_user_organizador_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_organizador_fields'));
    }

    /**
     * Mostrar campo de organizadores en el perfil de usuario
     */
    public function user_organizador_fields($user)
    {
        if (!current_user_can('edit_users')) return;
        $user_organizadores = get_user_meta($user->ID, 'organizador_ids', true);
        if (!is_array($user_organizadores)) $user_organizadores = array();
        $terms = get_terms(array(
            'taxonomy' => 'organizador',
            'hide_empty' => false,
            'number' => 100
        ));
?>
        <h2><?php esc_html_e('Organizadores asignados', 'event-show-base'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="organizador_ids[]"><?php esc_html_e('Fichas de organizador', 'event-show-base'); ?></label></th>
                <td>
                    <select name="organizador_ids[]" id="organizador_ids" multiple style="min-width:250px;">
                        <?php foreach ($terms as $term) : ?>
                            <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected(in_array($term->term_id, $user_organizadores)); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Puedes asignar una o varias fichas de organizador a este usuario.', 'event-show-base'); ?></p>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Guardar organizadores asignados al usuario
     */
    public function save_user_organizador_fields($user_id)
    {
        if (!current_user_can('edit_users')) return;
        if (isset($_POST['organizador_ids'])) {
            $ids = array_map('intval', (array)$_POST['organizador_ids']);
            update_user_meta($user_id, 'organizador_ids', $ids);
        } else {
            delete_user_meta($user_id, 'organizador_ids');
        }
    }
    /**
     * Añadir menú de administración
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=evento',
            __('Configuración', 'event-show-base'),
            __('Configuración', 'event-show-base'),
            'manage_options',
            'event-show-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'edit.php?post_type=evento',
            __('Logs del Sistema', 'event-show-base'),
            __('Logs', 'event-show-base'),
            'manage_options',
            'event-show-logs',
            array($this, 'render_logs_page')
        );

        add_submenu_page(
            'edit.php?post_type=evento',
            __('Asistentes', 'event-show-base'),
            __('Asistentes', 'event-show-base'),
            'edit_posts',
            'event-show-attendees',
            array($this, 'render_attendees_page')
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings()
    {
        register_setting('event_show_settings', 'event_show_email_notifications_enabled');
        register_setting('event_show_settings', 'event_show_admin_email');
        register_setting('event_show_settings', 'event_show_min_days_advance');
        register_setting('event_show_settings', 'event_show_require_approval');
        register_setting('event_show_settings', 'event_show_default_organizer');
        register_setting('event_show_settings', 'event_show_events_per_page');
        register_setting('event_show_settings', 'event_show_date_format');
        register_setting('event_show_settings', 'event_show_time_format');
    }

    /**
     * Renderizar página de configuración
     */
    public function render_settings_page()
    {
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('event_show_settings');
                do_settings_sections('event_show_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="event_show_email_notifications_enabled">
                                <?php esc_html_e('Notificaciones por Email', 'event-show-base'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                id="event_show_email_notifications_enabled"
                                name="event_show_email_notifications_enabled"
                                value="1"
                                <?php checked(get_option('event_show_email_notifications_enabled', true), true); ?>>
                            <p class="description">
                                <?php esc_html_e('Habilitar envío de notificaciones automáticas por email', 'event-show-base'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_show_admin_email">
                                <?php esc_html_e('Email del Administrador', 'event-show-base'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email"
                                id="event_show_admin_email"
                                name="event_show_admin_email"
                                value="<?php echo esc_attr(get_option('event_show_admin_email', get_option('admin_email'))); ?>"
                                class="regular-text">
                            <p class="description">
                                <?php esc_html_e('Email para recibir notificaciones de nuevos registros y eventos', 'event-show-base'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_show_min_days_advance">
                                <?php esc_html_e('Días Mínimos de Anticipación', 'event-show-base'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number"
                                id="event_show_min_days_advance"
                                name="event_show_min_days_advance"
                                value="<?php echo esc_attr(get_option('event_show_min_days_advance', 5)); ?>"
                                min="0"
                                step="1"
                                class="small-text">
                            <p class="description">
                                <?php esc_html_e('Número mínimo de días de anticipación para crear eventos (usuarios no administradores)', 'event-show-base'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_show_require_approval">
                                <?php esc_html_e('Requerir Aprobación', 'event-show-base'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                id="event_show_require_approval"
                                name="event_show_require_approval"
                                value="1"
                                <?php checked(get_option('event_show_require_approval', true), true); ?>>
                            <p class="description">
                                <?php esc_html_e('Los eventos enviados por usuarios requieren aprobación del administrador', 'event-show-base'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_show_events_per_page">
                                <?php esc_html_e('Eventos por Página', 'event-show-base'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number"
                                id="event_show_events_per_page"
                                name="event_show_events_per_page"
                                value="<?php echo esc_attr(get_option('event_show_events_per_page', 12)); ?>"
                                min="1"
                                step="1"
                                class="small-text">
                            <p class="description">
                                <?php esc_html_e('Número de eventos a mostrar en las vistas públicas por defecto', 'event-show-base'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php
    }

    /**
     * Renderizar página de logs
     */
    public function render_logs_page()
    {
        $logs = Event_Show_Logger::get_logs(array('limit' => 100));
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha', 'event-show-base'); ?></th>
                        <th><?php esc_html_e('Usuario', 'event-show-base'); ?></th>
                        <th><?php esc_html_e('Acción', 'event-show-base'); ?></th>
                        <th><?php esc_html_e('Descripción', 'event-show-base'); ?></th>
                        <th><?php esc_html_e('IP', 'event-show-base'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($logs)) : ?>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log['created_at']); ?></td>
                                <td>
                                    <?php
                                    if ($log['user_id']) {
                                        $user = get_user_by('id', $log['user_id']);
                                        echo esc_html($user ? $user->display_name : '-');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($log['action']); ?></td>
                                <td><?php echo esc_html($log['description']); ?></td>
                                <td><?php echo esc_html($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No hay logs registrados', 'event-show-base'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php
    }

    /**
     * Renderizar página de asistentes
     */
    public function render_attendees_page()
    {
        // Obtener evento seleccionado
        $selected_event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;

        // Obtener todos los eventos
        $events = get_posts(array(
            'post_type' => 'evento',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        // Obtener asistentes si hay evento seleccionado
        $attendees = array();
        $event_title = '';
        if ($selected_event_id) {
            $attendees = Event_Show_Attendees::get_attendees($selected_event_id);
            $event_title = get_the_title($selected_event_id);
        }
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="tablenav top" style="margin: 20px 0;">
                <div class="alignleft actions">
                    <label for="event-select"><?php esc_html_e('Seleccionar Evento:', 'event-show-base'); ?></label>
                    <select id="event-select" name="event_id" style="min-width: 300px;">
                        <option value=""><?php esc_html_e('-- Selecciona un evento --', 'event-show-base'); ?></option>
                        <?php foreach ($events as $event) : ?>
                            <option value="<?php echo esc_attr($event->ID); ?>" <?php selected($selected_event_id, $event->ID); ?>>
                                <?php echo esc_html($event->post_title); ?>
                                (<?php echo esc_html(get_post_meta($event->ID, '_event_date', true)); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selected_event_id && !empty($attendees)) : ?>
                    <div class="alignright actions">
                        <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=event_show_export_attendees&event_id=' . $selected_event_id . '&nonce=' . wp_create_nonce('event_show_admin_nonce'))); ?>" class="button button-primary">
                            <?php esc_html_e('Descargar CSV', 'event-show-base'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($selected_event_id) : ?>
                <h2><?php echo esc_html($event_title); ?></h2>
                <p><?php printf(esc_html__('Total de asistentes: %d', 'event-show-base'), count($attendees)); ?></p>

                <?php if (!empty($attendees)) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Nombre', 'event-show-base'); ?></th>
                                <th><?php esc_html_e('Email', 'event-show-base'); ?></th>
                                <th><?php esc_html_e('Teléfono', 'event-show-base'); ?></th>
                                <th><?php esc_html_e('Cantidad', 'event-show-base'); ?></th>
                                <th><?php esc_html_e('Fecha de Registro', 'event-show-base'); ?></th>
                                <th><?php esc_html_e('Estado', 'event-show-base'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendees as $attendee) : ?>
                                <tr>
                                    <td><?php echo esc_html($attendee['name']); ?></td>
                                    <td><?php echo esc_html($attendee['email']); ?></td>
                                    <td><?php echo esc_html($attendee['phone'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($attendee['num_attendees']); ?></td>
                                    <td><?php echo esc_html($attendee['registration_date']); ?></td>
                                    <td>
                                        <span class="status-<?php echo esc_attr($attendee['status']); ?>">
                                            <?php echo esc_html(ucfirst($attendee['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php esc_html_e('No hay asistentes registrados para este evento.', 'event-show-base'); ?></p>
                <?php endif; ?>
            <?php else : ?>
                <p><?php esc_html_e('Selecciona un evento para ver sus asistentes.', 'event-show-base'); ?></p>
            <?php endif; ?>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#event-select').on('change', function() {
                    var eventId = $(this).val();
                    if (eventId) {
                        window.location.href = '<?php echo admin_url('edit.php?post_type=evento&page=event-show-attendees&event_id='); ?>' + eventId;
                    } else {
                        window.location.href = '<?php echo admin_url('edit.php?post_type=evento&page=event-show-attendees'); ?>';
                    }
                });
            });
        </script>

        <style>
            .status-confirmed {
                color: #46b450;
                font-weight: 600;
            }

            .status-pending {
                color: #f0b849;
                font-weight: 600;
            }

            .status-cancelled {
                color: #dc3232;
                font-weight: 600;
            }
        </style>
    <?php
    }

    /**
     * Añadir campos a taxonomía Organizador
     */
    public function add_organizador_fields()
    {
    ?>
        <div class="form-field">
            <label for="organizador_phone"><?php esc_html_e('Teléfono', 'event-show-base'); ?></label>
            <input type="text" name="organizador_phone" id="organizador_phone" value="">
        </div>
        <div class="form-field">
            <label for="organizador_email"><?php esc_html_e('Email', 'event-show-base'); ?></label>
            <input type="email" name="organizador_email" id="organizador_email" value="">
        </div>
        <div class="form-field">
            <label for="organizador_website"><?php esc_html_e('Sitio Web', 'event-show-base'); ?></label>
            <input type="url" name="organizador_website" id="organizador_website" value="">
        </div>
        <div class="form-field">
            <label for="organizador_image"><?php esc_html_e('Imagen/Logo', 'event-show-base'); ?></label>
            <input type="hidden" name="organizador_image" id="organizador_image" value="">
            <button type="button" class="button organizador-upload-image"><?php esc_html_e('Seleccionar Imagen', 'event-show-base'); ?></button>
            <div class="organizador-image-preview"></div>
        </div>
    <?php
    }

    /**
     * Editar campos de taxonomía Organizador
     */
    public function edit_organizador_fields($term, $taxonomy)
    {
        $phone = get_term_meta($term->term_id, 'phone', true);
        $email = get_term_meta($term->term_id, 'email', true);
        $website = get_term_meta($term->term_id, 'website', true);
        $image = get_term_meta($term->term_id, 'image', true);
        $image_url = $image ? wp_get_attachment_image_url($image, 'thumbnail') : '';
    ?>
        <tr class="form-field">
            <th scope="row">
                <label for="organizador_phone"><?php esc_html_e('Teléfono', 'event-show-base'); ?></label>
            </th>
            <td>
                <input type="text" name="organizador_phone" id="organizador_phone" value="<?php echo esc_attr($phone); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="organizador_email"><?php esc_html_e('Email', 'event-show-base'); ?></label>
            </th>
            <td>
                <input type="email" name="organizador_email" id="organizador_email" value="<?php echo esc_attr($email); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="organizador_website"><?php esc_html_e('Sitio Web', 'event-show-base'); ?></label>
            </th>
            <td>
                <input type="url" name="organizador_website" id="organizador_website" value="<?php echo esc_attr($website); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="organizador_image"><?php esc_html_e('Imagen/Logo', 'event-show-base'); ?></label>
            </th>
            <td>
                <input type="hidden" name="organizador_image" id="organizador_image" value="<?php echo esc_attr($image); ?>">
                <button type="button" class="button organizador-upload-image"><?php esc_html_e('Seleccionar Imagen', 'event-show-base'); ?></button>
                <?php if ($image_url) : ?>
                    <button type="button" class="button organizador-remove-image"><?php esc_html_e('Eliminar', 'event-show-base'); ?></button>
                <?php endif; ?>
                <div class="organizador-image-preview">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; height: auto; margin-top: 10px;">
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php
    }

    /**
     * Guardar campos de Organizador
     */
    public function save_organizador_fields($term_id)
    {
        if (isset($_POST['organizador_phone'])) {
            update_term_meta($term_id, 'phone', sanitize_text_field($_POST['organizador_phone']));
        }
        if (isset($_POST['organizador_email'])) {
            update_term_meta($term_id, 'email', sanitize_email($_POST['organizador_email']));
        }
        if (isset($_POST['organizador_website'])) {
            update_term_meta($term_id, 'website', esc_url_raw($_POST['organizador_website']));
        }
        if (isset($_POST['organizador_image'])) {
            update_term_meta($term_id, 'image', absint($_POST['organizador_image']));
        }
    }

    /**
     * Añadir campos a taxonomía Lugar
     */
    public function add_lugar_fields()
    {
    ?>
        <div class="form-field">
            <label for="lugar_address"><?php esc_html_e('Dirección', 'event-show-base'); ?></label>
            <input type="text" name="lugar_address" id="lugar_address" value="">
        </div>
        <div class="form-field">
            <label for="lugar_map_url"><?php esc_html_e('URL de Google Maps', 'event-show-base'); ?></label>
            <input type="url" name="lugar_map_url" id="lugar_map_url" value="">
            <p class="description"><?php esc_html_e('Pegar el enlace de Google Maps del lugar', 'event-show-base'); ?></p>
        </div>
        <div class="form-field">
            <label for="lugar_image"><?php esc_html_e('Imagen del Lugar', 'event-show-base'); ?></label>
            <input type="hidden" name="lugar_image" id="lugar_image" value="">
            <button type="button" class="button lugar-upload-image"><?php esc_html_e('Seleccionar Imagen', 'event-show-base'); ?></button>
            <div class="lugar-image-preview"></div>
        </div>
    <?php
    }

    /**
     * Editar campos de taxonomía Lugar
     */
    public function edit_lugar_fields($term, $taxonomy)
    {
        $address = get_term_meta($term->term_id, 'address', true);
        $map_url = get_term_meta($term->term_id, 'map_url', true);
        $image = get_term_meta($term->term_id, 'image', true);
        $image_url = $image ? wp_get_attachment_image_url($image, 'thumbnail') : '';
    ?>
        <tr class="form-field">
            <th scope="row">
                <label for="lugar_address"><?php esc_html_e('Dirección', 'event-show-base'); ?></label>
            </th>
            <td>
                <input type="text" name="lugar_address" id="lugar_address" value="<?php echo esc_attr($address); ?>" class="large-text">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="lugar_map_url"><?php esc_html_e('URL de Google Maps', 'event-show-base'); ?></label>
            </th>
            <td>
                <input type="url" name="lugar_map_url" id="lugar_map_url" value="<?php echo esc_attr($map_url); ?>" class="large-text">
                <p class="description"><?php esc_html_e('Pegar el enlace de Google Maps del lugar', 'event-show-base'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="lugar_image"><?php esc_html_e('Imagen del Lugar', 'event-show-base'); ?></label>
            </th>
            <td>
                <input type="hidden" name="lugar_image" id="lugar_image" value="<?php echo esc_attr($image); ?>">
                <button type="button" class="button lugar-upload-image"><?php esc_html_e('Seleccionar Imagen', 'event-show-base'); ?></button>
                <?php if ($image_url) : ?>
                    <button type="button" class="button lugar-remove-image"><?php esc_html_e('Eliminar', 'event-show-base'); ?></button>
                <?php endif; ?>
                <div class="lugar-image-preview">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; height: auto; margin-top: 10px;">
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Guardar campos de Lugar
     */
    public function save_lugar_fields($term_id)
    {
        if (isset($_POST['lugar_address'])) {
            update_term_meta($term_id, 'address', sanitize_text_field($_POST['lugar_address']));
        }
        if (isset($_POST['lugar_map_url'])) {
            update_term_meta($term_id, 'map_url', esc_url_raw($_POST['lugar_map_url']));
        }
        if (isset($_POST['lugar_image'])) {
            update_term_meta($term_id, 'image', absint($_POST['lugar_image']));
        }
    }

    /**
     * AJAX: Exportar asistentes
     */
    public function ajax_export_attendees()
    {
        // Verificar nonce de admin o frontend
        $nonce_verified = false;
        if (isset($_REQUEST['nonce'])) {
            if (wp_verify_nonce($_REQUEST['nonce'], 'event_show_admin_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_REQUEST['nonce'], 'event_show_nonce')) {
                $nonce_verified = true;
            }
        }

        if (!$nonce_verified) {
            wp_die(__('Verificación de seguridad fallida', 'event-show-base'));
        }

        $event_id = isset($_REQUEST['event_id']) ? absint($_REQUEST['event_id']) : 0;

        if (!$event_id) {
            wp_die(__('ID de evento inválido', 'event-show-base'));
        }

        // Si es usuario frontend, verificar que sea el autor del evento
        if (!current_user_can('edit_posts')) {
            if (!is_user_logged_in()) {
                wp_die(__('Debes iniciar sesión', 'event-show-base'));
            }

            $event = get_post($event_id);
            if (!$event || $event->post_author != get_current_user_id()) {
                wp_die(__('No tienes permisos para exportar estos asistentes', 'event-show-base'));
            }
        }

        Event_Show_Attendees::export_csv($event_id);
    }

    /**
     * AJAX: Eliminar asistente
     */
    public function ajax_delete_attendee()
    {
        check_ajax_referer('event_show_admin_nonce', 'nonce');

        if (! current_user_can('edit_posts')) {
            wp_send_json_error(__('Permisos insuficientes', 'event-show-base'));
        }

        $attendee_id = isset($_POST['attendee_id']) ? absint($_POST['attendee_id']) : 0;

        if (! $attendee_id) {
            wp_send_json_error(__('ID de asistente inválido', 'event-show-base'));
        }

        if (Event_Show_Attendees::delete($attendee_id)) {
            wp_send_json_success(__('Asistente eliminado correctamente', 'event-show-base'));
        } else {
            wp_send_json_error(__('Error al eliminar asistente', 'event-show-base'));
        }
    }

    /**
     * Mostrar notificaciones de administración
     */
    public function show_admin_notices()
    {
        global $post;

        if (! $post || 'evento' !== get_post_type($post)) {
            return;
        }

        $error = get_transient('event_show_validation_error_' . $post->ID);
        if ($error) {
        ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php echo esc_html($error); ?></p>
            </div>
<?php
            delete_transient('event_show_validation_error_' . $post->ID);
        }
    }
}
