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
        add_action('wp_ajax_event_show_send_test_email', array($this, 'ajax_send_test_email'));
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
     * Enviar correo de prueba al administrador con los datos de ejemplo
     */
    public function ajax_send_test_email()
    {
        check_ajax_referer('event_show_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => 'No autorizado']);
        }
        $type = isset($_POST['template_type']) ? sanitize_text_field($_POST['template_type']) : '';
        $template = isset($_POST['template']) ? wp_kses_post($_POST['template']) : '';
        $subject = get_option('event_show_email_subject_' . $type, '');
        if (empty($subject)) {
            // Asunto por defecto si no está personalizado
            if ($type === 'registration') {
                $subject = '¡Confirmación de registro para {{Titulo evento}}!';
            } elseif ($type === 'reminder') {
                $subject = 'Recordatorio: {{Titulo evento}}';
            } elseif ($type === 'update') {
                $subject = 'Actualización importante en el evento: {{Titulo evento}}';
            } else {
                $subject = 'Mensaje de prueba';
            }
        }
        $admin_email = get_option('event_show_admin_email', get_option('admin_email'));
        $from_name = get_option('event_show_email_from_name', 'Event Show');
        $from_email = get_option('event_show_email_from_address', get_option('admin_email'));
        $headers = array('Content-Type: text/html; charset=UTF-8');
        add_filter('wp_mail_from', function () use ($from_email) {
            return $from_email;
        });
        add_filter('wp_mail_from_name', function () use ($from_name) {
            return $from_name;
        });
        $data = [
            '{{Titulo evento}}' => 'Concierto de Rock',
            '{{Fecha}}' => '25/01/2026',
            '{{Hora}}' => '20:00',
            '{{Nombre usuario}}' => 'Juan Pérez',
            '{{Email usuario}}' => 'juan.perez@email.com',
            '{{Enlace evento}}' => 'https://tusitio.com/evento/concierto-rock',
            '{{Organizador}}' => 'Producciones XYZ',
            '{{Lugar}}' => 'Teatro Principal',
        ];
        foreach ($data as $k => $v) {
            $template = str_replace($k, $v, $template);
            $subject = str_replace($k, $v, $subject);
        }
        $result = wp_mail($admin_email, $subject, $template, $headers);
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
        wp_send_json(['success' => $result]);
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

        // Página de aprobación de organizadores
        // Contar organizadores pendientes para mostrar badge
        $pending_count = $this->get_pending_organizers_count();
        $menu_title = __('Organizadores Pendientes', 'event-show-base');
        if ($pending_count > 0) {
            $menu_title .= ' <span class="awaiting-mod update-plugins count-' . $pending_count . '"><span class="pending-count">' . number_format_i18n($pending_count) . '</span></span>';
        }

        add_submenu_page(
            'edit.php?post_type=evento',
            __('Aprobar Organizadores', 'event-show-base'),
            $menu_title,
            'manage_options',
            'event-show-approve-organizers',
            array($this, 'render_approve_organizers_page')
        );
    }

    /**
     * Obtener cantidad de organizadores pendientes
     */
    private function get_pending_organizers_count()
    {
        $pending = get_terms(array(
            'taxonomy' => 'organizador',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'status',
                    'value' => 'pending',
                    'compare' => '='
                )
            ),
            'fields' => 'count'
        ));

        return is_numeric($pending) ? $pending : 0;
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings()
    {
        // Remitente personalizado
        register_setting('event_show_settings', 'event_show_email_from_name');
        register_setting('event_show_settings', 'event_show_email_from_address');
        // Plantillas y asuntos de email
        register_setting('event_show_settings', 'event_show_email_template_registration');
        register_setting('event_show_settings', 'event_show_email_subject_registration');
        register_setting('event_show_settings', 'event_show_email_template_reminder');
        register_setting('event_show_settings', 'event_show_email_subject_reminder');
        register_setting('event_show_settings', 'event_show_email_template_update');
        register_setting('event_show_settings', 'event_show_email_subject_update');
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
        // Etiquetas disponibles
        $tags = [
            '{{Titulo evento}}' => 'Título del evento',
            '{{Fecha}}' => 'Fecha del evento',
            '{{Hora}}' => 'Hora del evento',
            '{{Nombre usuario}}' => 'Nombre del usuario',
            '{{Email usuario}}' => 'Email del usuario',
            '{{Enlace evento}}' => 'URL del evento',
            '{{Organizador}}' => 'Organizador',
            '{{Lugar}}' => 'Lugar',
        ];

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
                        <th scope="row"><label for="event_show_email_from_name">Nombre del remitente</label></th>
                        <td>
                            <input type="text" id="event_show_email_from_name" name="event_show_email_from_name" value="<?php echo esc_attr(get_option('event_show_email_from_name', 'Event Show')); ?>" class="regular-text">
                            <p class="description">Nombre que aparecerá como remitente en los emails.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="event_show_email_from_address">Correo del remitente</label></th>
                        <td>
                            <input type="email" id="event_show_email_from_address" name="event_show_email_from_address" value="<?php echo esc_attr(get_option('event_show_email_from_address', get_option('admin_email'))); ?>" class="regular-text">
                            <p class="description">Dirección de correo que aparecerá como remitente.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Etiquetas disponibles</th>
                        <td>
                            <?php foreach ($tags as $tag => $desc): ?>
                                <span style="display:inline-block;background:#f3f3f3;border-radius:3px;padding:2px 7px;margin:2px;font-family:monospace;"> <?php echo esc_html($tag); ?> </span> = <?php echo esc_html($desc); ?><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php
                    $plantillas = [
                        'registration' => 'Confirmación de registro',
                        'reminder' => 'Recordatorio de evento',
                        'update' => 'Notificación de modificación',
                    ];
                    foreach ($plantillas as $key => $label):
                        $option = 'event_show_email_template_' . $key;
                        $subject_option = 'event_show_email_subject_' . $key;
                        $value = get_option($option, '<p>Hola {{Nombre usuario}},<br>Gracias por tu interés en <b>{{Titulo evento}}</b>.<br>Fecha: {{Fecha}}<br>Hora: {{Hora}}<br>Más info: {{Enlace evento}}</p>');
                        $subject_value = get_option($subject_option, '');
                    ?>
                        <tr>
                            <th scope="row" style="width:220px;vertical-align:top;">
                                <?php echo esc_html($label); ?><br>
                                <button type="button" class="button button-secondary send-test-email-template" data-template-id="<?php echo esc_attr($key); ?>">Enviar mensaje test</button>
                            </th>
                            <td>
                                <label for="<?php echo esc_attr($subject_option); ?>"><strong>Asunto del correo:</strong></label><br>
                                <input type="text" name="<?php echo esc_attr($subject_option); ?>" id="<?php echo esc_attr($subject_option); ?>" value="<?php echo esc_attr($subject_value); ?>" style="width:100%;margin-bottom:8px;" class="regular-text">
                                <br>
                                <label for="<?php echo esc_attr($option); ?>"><strong>Contenido del correo:</strong></label><br>
                                <textarea name="<?php echo esc_attr($option); ?>" id="<?php echo esc_attr($option); ?>" rows="7" style="width:100%;font-family:monospace;"><?php echo esc_textarea($value); ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
        <!-- Modal de vista previa de email -->
        <div id="event-show-email-preview-modal" style="display:none;position:fixed;z-index:99999;left:0;top:0;width:100vw;height:100vh;background:rgba(30,40,60,0.45);align-items:center;justify-content:center;">
            <div style="background:#fff;max-width:600px;width:90vw;padding:32px 24px 24px 24px;border-radius:10px;box-shadow:0 8px 40px rgba(44,62,80,0.18);position:relative;">
                <button id="close-email-preview-modal" style="position:absolute;top:12px;right:12px;font-size:22px;background:none;border:none;cursor:pointer;">&times;</button>
                <h2 style="margin-top:0;font-size:1.3em;">Vista previa de email</h2>
                <div id="event-show-email-preview-content" style="border:1px solid #e3e3e3;padding:18px 16px;border-radius:6px;min-height:120px;max-height:60vh;overflow:auto;font-family:sans-serif;background:#fafbfc;"></div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function getExampleData() {
                    return {
                        '{{Titulo evento}}': 'Concierto de Rock',
                        '{{Fecha}}': '25/01/2026',
                        '{{Hora}}': '20:00',
                        '{{Nombre usuario}}': 'Juan Pérez',
                        '{{Email usuario}}': 'juan.perez@email.com',
                        '{{Enlace evento}}': 'https://tusitio.com/evento/concierto-rock',
                        '{{Organizador}}': 'Producciones XYZ',
                        '{{Lugar}}': 'Teatro Principal',
                    };
                }

                function renderPreview(template) {
                    let data = getExampleData();
                    let html = template;
                    Object.keys(data).forEach(function(tag) {
                        let re = new RegExp(tag.replace(/[{}]/g, m => '\\' + m), 'g');
                        html = html.replace(re, data[tag]);
                    });
                    return html;
                }

                // Botón de enviar email de prueba
                document.querySelectorAll('.send-test-email-template').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        let id = btn.getAttribute('data-template-id');
                        let textarea = document.getElementById('event_show_email_template_' + id);
                        let template = textarea.value;
                        btn.disabled = true;
                        let originalText = btn.textContent;
                        btn.textContent = 'Enviando...';

                        var ajaxUrl = eventShowAdmin.ajaxUrl;
                        var nonce = eventShowAdmin.nonce;

                        fetch(ajaxUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'action=event_show_send_test_email&nonce=' + encodeURIComponent(nonce) + '&template_type=' + encodeURIComponent(id) + '&template=' + encodeURIComponent(template)
                            })
                            .then(response => response.json())
                            .then(data => {
                                btn.disabled = false;
                                btn.textContent = originalText;
                                if (data && data.success) {
                                    alert('✓ Correo de prueba enviado correctamente al administrador.');
                                } else {
                                    alert('✗ Error al enviar el correo de prueba: ' + (data.data || 'Error desconocido'));
                                }
                            })
                            .catch(error => {
                                btn.disabled = false;
                                btn.textContent = originalText;
                                alert('✗ Error de conexión al enviar el correo de prueba.');
                                console.error('Error:', error);
                            });
                    });
                });

                // Vista previa de email
                document.querySelectorAll('.preview-email-template').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        let id = btn.getAttribute('data-template-id');
                        let textarea = document.getElementById('event_show_email_template_' + id);
                        let html = renderPreview(textarea.value);
                        document.getElementById('event-show-email-preview-content').innerHTML = html;
                        document.getElementById('event-show-email-preview-modal').style.display = 'flex';
                    });
                });

                document.getElementById('close-email-preview-modal').addEventListener('click', function() {
                    document.getElementById('event-show-email-preview-modal').style.display = 'none';
                });

                // Cerrar modal con Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        document.getElementById('event-show-email-preview-modal').style.display = 'none';
                    }
                });
            });
        </script>
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
        // Obtener todos los usuarios
        $users = get_users(array('orderby' => 'display_name'));
    ?>
        <div class="form-field">
            <label for="organizador_owner_id"><?php esc_html_e('Usuario Propietario', 'event-show-base'); ?></label>
            <select name="organizador_owner_id" id="organizador_owner_id" style="width: 95%;">
                <option value=""><?php esc_html_e('-- Seleccionar Usuario --', 'event-show-base'); ?></option>
                <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>">
                        <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Usuario propietario de este organizador', 'event-show-base'); ?></p>
        </div>
        <div class="form-field">
            <label for="organizador_status"><?php esc_html_e('Estado', 'event-show-base'); ?></label>
            <select name="organizador_status" id="organizador_status">
                <option value="approved" selected><?php esc_html_e('Aprobado', 'event-show-base'); ?></option>
                <option value="pending"><?php esc_html_e('Pendiente', 'event-show-base'); ?></option>
                <option value="rejected"><?php esc_html_e('Rechazado', 'event-show-base'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Los organizadores creados desde backend se aprueban por defecto', 'event-show-base'); ?></p>
        </div>
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
        $owner_id = get_term_meta($term->term_id, 'owner_id', true);
        $status = get_term_meta($term->term_id, 'status', true);
        if (empty($status)) {
            $status = 'approved'; // Por defecto aprobado para organizadores antiguos
        }

        // Obtener todos los usuarios
        $users = get_users(array('orderby' => 'display_name'));
    ?>
        <tr class="form-field">
            <th scope="row">
                <label for="organizador_owner_id"><?php esc_html_e('Usuario Propietario', 'event-show-base'); ?></label>
            </th>
            <td>
                <select name="organizador_owner_id" id="organizador_owner_id" style="min-width: 300px;">
                    <option value=""><?php esc_html_e('-- Sin propietario --', 'event-show-base'); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($owner_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Usuario propietario de este organizador', 'event-show-base'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="organizador_status"><?php esc_html_e('Estado', 'event-show-base'); ?></label>
            </th>
            <td>
                <select name="organizador_status" id="organizador_status">
                    <option value="approved" <?php selected($status, 'approved'); ?>><?php esc_html_e('Aprobado', 'event-show-base'); ?></option>
                    <option value="pending" <?php selected($status, 'pending'); ?>><?php esc_html_e('Pendiente', 'event-show-base'); ?></option>
                    <option value="rejected" <?php selected($status, 'rejected'); ?>><?php esc_html_e('Rechazado', 'event-show-base'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Estado de aprobación del organizador. Solo los aprobados pueden usarse en eventos.', 'event-show-base'); ?></p>
            </td>
        </tr>
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
        // Guardar propietario
        if (isset($_POST['organizador_owner_id'])) {
            $owner_id = absint($_POST['organizador_owner_id']);
            if ($owner_id) {
                update_term_meta($term_id, 'owner_id', $owner_id);
                // Guardar también el nombre del propietario para referencia
                $owner = get_user_by('ID', $owner_id);
                if ($owner) {
                    update_term_meta($term_id, 'owner_name', $owner->display_name);
                }
            } else {
                delete_term_meta($term_id, 'owner_id');
                delete_term_meta($term_id, 'owner_name');
            }
        }

        // Guardar estado (por defecto 'approved' en backend)
        if (isset($_POST['organizador_status'])) {
            $status = sanitize_text_field($_POST['organizador_status']);
            if (in_array($status, array('approved', 'pending', 'rejected'))) {
                update_term_meta($term_id, 'status', $status);
            }
        } else {
            // Si es creación nueva desde backend, establecer como aprobado
            $existing_status = get_term_meta($term_id, 'status', true);
            if (empty($existing_status)) {
                update_term_meta($term_id, 'status', 'approved');
            }
        }

        // Guardar otros campos
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

        // Mostrar alerta de organizadores pendientes solo en páginas del plugin
        $screen = get_current_screen();
        if ($screen && (strpos($screen->id, 'evento') !== false || strpos($screen->id, 'event-show') !== false)) {
            $pending_count = $this->get_pending_organizers_count();

            if ($pending_count > 0 && current_user_can('manage_options')) {
                $url = admin_url('edit.php?post_type=evento&page=event-show-approve-organizers');
        ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong><?php esc_html_e('Event Show:', 'event-show-base'); ?></strong>
                        <?php
                        printf(
                            _n(
                                'Hay %s organizador pendiente de aprobación.',
                                'Hay %s organizadores pendientes de aprobación.',
                                $pending_count,
                                'event-show-base'
                            ),
                            '<strong>' . number_format_i18n($pending_count) . '</strong>'
                        );
                        ?>
                        <a href="<?php echo esc_url($url); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php esc_html_e('Revisar ahora', 'event-show-base'); ?>
                        </a>
                    </p>
                </div>
            <?php
            }
        }

        // Validación de eventos
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

    /**
     * Renderizar página de aprobación de organizadores
     */
    public function render_approve_organizers_page()
    {
        // Procesar acciones de aprobación/rechazo
        if (isset($_POST['action']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'approve_organizers')) {
            $action = sanitize_text_field($_POST['action']);
            $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;

            if ($term_id && in_array($action, array('approve', 'reject'))) {
                $new_status = $action === 'approve' ? 'approved' : 'rejected';
                update_term_meta($term_id, 'status', $new_status);

                // Obtener info del organizador para logging
                $term = get_term($term_id, 'organizador');
                $owner_id = get_term_meta($term_id, 'owner_id', true);

                if (class_exists('Event_Show_Logger')) {
                    Event_Show_Logger::log(
                        'organizer_' . $action . 'd',
                        'organizador',
                        $term_id,
                        'Organizador ' . ($action === 'approve' ? 'aprobado' : 'rechazado') . ': ' . $term->name
                    );
                }

                echo '<div class="notice notice-success is-dismissible"><p>';
                echo $action === 'approve'
                    ? __('Organizador aprobado correctamente.', 'event-show-base')
                    : __('Organizador rechazado.', 'event-show-base');
                echo '</p></div>';
            }
        }

        // Obtener organizadores pendientes
        $pending_organizers = get_terms(array(
            'taxonomy' => 'organizador',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'status',
                    'value' => 'pending',
                    'compare' => '='
                )
            ),
        ));

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Aprobar Organizadores', 'event-show-base'); ?></h1>

            <p><?php esc_html_e('Los usuarios pueden crear organizadores desde el frontend, pero necesitan tu aprobación antes de poder usarlos en sus eventos.', 'event-show-base'); ?></p>

            <?php if (!empty($pending_organizers) && !is_wp_error($pending_organizers)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Nombre', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Descripción', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Propietario', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Contacto', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Acciones', 'event-show-base'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_organizers as $organizer) :
                            $owner_id = get_term_meta($organizer->term_id, 'owner_id', true);
                            $owner_name = get_term_meta($organizer->term_id, 'owner_name', true);
                            $contact_info = get_term_meta($organizer->term_id, 'contact_info', true);
                            $logo = get_term_meta($organizer->term_id, 'logo', true);

                            // Obtener usuario propietario
                            $owner = $owner_id ? get_user_by('ID', $owner_id) : null;
                            $owner_display = $owner ? $owner->display_name . ' (' . $owner->user_email . ')' : $owner_name;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($organizer->name); ?></strong>
                                    <?php if ($logo) : ?>
                                        <br><img src="<?php echo esc_url($logo); ?>" style="max-width: 60px; max-height: 60px; margin-top: 5px; border-radius: 4px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(wp_trim_words($organizer->description, 15)); ?></td>
                                <td>
                                    <?php echo esc_html($owner_display); ?>
                                    <?php if ($owner) : ?>
                                        <br><a href="<?php echo esc_url(get_edit_user_link($owner_id)); ?>" target="_blank"><?php esc_html_e('Ver perfil', 'event-show-base'); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($contact_info); ?></td>
                                <td>
                                    <form method="post" style="display: inline-block; margin-right: 5px;">
                                        <?php wp_nonce_field('approve_organizers'); ?>
                                        <input type="hidden" name="term_id" value="<?php echo esc_attr($organizer->term_id); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="button button-primary button-small">
                                            <?php esc_html_e('Aprobar', 'event-show-base'); ?>
                                        </button>
                                    </form>

                                    <form method="post" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e('¿Estás seguro de rechazar este organizador?', 'event-show-base'); ?>');">
                                        <?php wp_nonce_field('approve_organizers'); ?>
                                        <input type="hidden" name="term_id" value="<?php echo esc_attr($organizer->term_id); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="button button-small">
                                            <?php esc_html_e('Rechazar', 'event-show-base'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No hay organizadores pendientes de aprobación.', 'event-show-base'); ?></p>
                </div>
            <?php endif; ?>

            <hr style="margin: 30px 0;">

            <h2><?php esc_html_e('Todos los Organizadores', 'event-show-base'); ?></h2>

            <?php
            // Obtener todos los organizadores con propietario
            $all_organizers = get_terms(array(
                'taxonomy' => 'organizador',
                'hide_empty' => false,
                'meta_key' => 'owner_id',
            ));

            if (!empty($all_organizers) && !is_wp_error($all_organizers)) :
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Nombre', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Propietario', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Estado', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Eventos', 'event-show-base'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_organizers as $organizer) :
                            $owner_id = get_term_meta($organizer->term_id, 'owner_id', true);
                            $status = get_term_meta($organizer->term_id, 'status', true);
                            $owner = $owner_id ? get_user_by('ID', $owner_id) : null;

                            // Contar eventos
                            $events = get_posts(array(
                                'post_type' => 'evento',
                                'posts_per_page' => -1,
                                'tax_query' => array(
                                    array(
                                        'taxonomy' => 'organizador',
                                        'field' => 'term_id',
                                        'terms' => $organizer->term_id,
                                    ),
                                ),
                                'fields' => 'ids',
                            ));

                            $status_labels = array(
                                'approved' => __('Aprobado', 'event-show-base'),
                                'pending' => __('Pendiente', 'event-show-base'),
                                'rejected' => __('Rechazado', 'event-show-base'),
                            );
                            $status_display = isset($status_labels[$status]) ? $status_labels[$status] : __('Desconocido', 'event-show-base');
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($organizer->name); ?></strong></td>
                                <td>
                                    <?php if ($owner) : ?>
                                        <?php echo esc_html($owner->display_name); ?>
                                        <br><small><?php echo esc_html($owner->user_email); ?></small>
                                    <?php else : ?>
                                        <em><?php esc_html_e('Sin propietario', 'event-show-base'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html($status_display); ?>
                                    </span>
                                </td>
                                <td><?php echo count($events); ?> <?php esc_html_e('eventos', 'event-show-base'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <style>
                .status-badge {
                    display: inline-block;
                    padding: 3px 10px;
                    border-radius: 12px;
                    font-size: 0.85em;
                    font-weight: 500;
                }

                .status-badge.status-approved {
                    background: #d4edda;
                    color: #155724;
                }

                .status-badge.status-pending {
                    background: #fff3cd;
                    color: #856404;
                }

                .status-badge.status-rejected {
                    background: #f8d7da;
                    color: #721c24;
                }
            </style>
        </div>
<?php
    }
}
