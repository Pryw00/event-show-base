<?php

/**
 * Gestión de Metaboxes para Eventos
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar metaboxes
 */
class Event_Show_Metaboxes
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_metaboxes'));
        add_action('save_post_evento', array($this, 'save_metabox_data'), 10, 2);
    }

    /**
     * Añadir metaboxes
     */
    public function add_metaboxes()
    {
        // Metabox principal de información del evento
        add_meta_box(
            'event_show_details',
            __('Información del Evento', 'event-show-base'),
            array($this, 'render_details_metabox'),
            'evento',
            'normal',
            'high'
        );

        // Metabox de imágenes adicionales
        add_meta_box(
            'event_show_images',
            __('Imágenes del Evento', 'event-show-base'),
            array($this, 'render_images_metabox'),
            'evento',
            'side',
            'low'
        );

        // Metabox de opciones
        add_meta_box(
            'event_show_options',
            __('Opciones del Evento', 'event-show-base'),
            array($this, 'render_options_metabox'),
            'evento',
            'side',
            'default'
        );

        // Metabox de autor (solo para administradores)
        if (current_user_can('edit_others_posts')) {
            add_meta_box(
                'event_show_author',
                __('Autor del Evento', 'event-show-base'),
                array($this, 'render_author_metabox'),
                'evento',
                'side',
                'default'
            );
        }
    }

    /**
     * Renderizar metabox de detalles
     */
    public function render_details_metabox($post)
    {
        wp_nonce_field('event_show_metabox', 'event_show_metabox_nonce');

        $event_date = get_post_meta($post->ID, '_event_date', true);
        $event_time = get_post_meta($post->ID, '_event_time', true);
        $event_end_date = get_post_meta($post->ID, '_event_end_date', true);
        $event_end_time = get_post_meta($post->ID, '_event_end_time', true);
        $event_time_indef = get_post_meta($post->ID, '_event_time_indef', true);
        $event_end_time_indef = get_post_meta($post->ID, '_event_end_time_indef', true);
?>
        <div class="event-show-metabox-wrap">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="event_date"><?php esc_html_e('Fecha de Inicio', 'event-show-base'); ?> *</label>
                    </th>
                    <td>
                        <input type="text"
                            id="event_date"
                            name="event_date"
                            class="event-datepicker"
                            value="<?php echo esc_attr($event_date); ?>"
                            placeholder="dd/mm/yyyy"
                            required>
                        <p class="description"><?php esc_html_e('Fecha de inicio del evento', 'event-show-base'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="event_time"><?php esc_html_e('Hora de Inicio', 'event-show-base'); ?> *</label>
                    </th>
                    <td>
                        <input type="time"
                            id="event_time"
                            name="event_time"
                            value="<?php echo esc_attr($event_time); ?>"
                            <?php echo ($event_time_indef == '1') ? 'disabled' : 'required'; ?>>
                        <label style="margin-left:10px;">
                            <input type="checkbox" id="event_time_indef" name="event_time_indef" value="1" <?php checked($event_time_indef, '1'); ?>>
                            <?php esc_html_e('Hora indefinida', 'event-show-base'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Hora de inicio del evento (formato 24h)', 'event-show-base'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="event_end_date"><?php esc_html_e('Fecha de Fin', 'event-show-base'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                            id="event_end_date"
                            name="event_end_date"
                            class="event-datepicker"
                            value="<?php echo esc_attr($event_end_date); ?>"
                            placeholder="dd/mm/yyyy">
                        <p class="description"><?php esc_html_e('Fecha de fin del evento (opcional)', 'event-show-base'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="event_end_time"><?php esc_html_e('Hora de Fin', 'event-show-base'); ?></label>
                    </th>
                    <td>
                        <input type="time"
                            id="event_end_time"
                            name="event_end_time"
                            value="<?php echo esc_attr($event_end_time); ?>"
                            <?php echo ($event_end_time_indef == '1') ? 'disabled' : ''; ?>>
                        <label style="margin-left:10px;">
                            <input type="checkbox" id="event_end_time_indef" name="event_end_time_indef" value="1" <?php checked($event_end_time_indef, '1'); ?>>
                            <?php esc_html_e('Hora indefinida', 'event-show-base'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Hora de fin del evento (opcional)', 'event-show-base'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    }

    /**
     * Renderizar metabox de imágenes
     */
    public function render_images_metabox($post)
    {
        $thumbnail_id = get_post_meta($post->ID, '_event_thumbnail', true);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
    ?>
        <div class="event-show-images-metabox">
            <p>
                <label><strong><?php esc_html_e('Miniatura para Grid', 'event-show-base'); ?></strong></label>
            </p>
            <div class="event-thumbnail-preview">
                <?php if ($thumbnail_url) : ?>
                    <img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 100%; height: auto;">
                <?php else : ?>
                    <p class="description"><?php esc_html_e('No hay miniatura seleccionada', 'event-show-base'); ?></p>
                <?php endif; ?>
            </div>
            <p>
                <input type="hidden" id="event_thumbnail" name="event_thumbnail" value="<?php echo esc_attr($thumbnail_id); ?>">
                <button type="button" class="button event-upload-thumbnail">
                    <?php esc_html_e('Seleccionar Miniatura', 'event-show-base'); ?>
                </button>
                <?php if ($thumbnail_url) : ?>
                    <button type="button" class="button event-remove-thumbnail">
                        <?php esc_html_e('Eliminar', 'event-show-base'); ?>
                    </button>
                <?php endif; ?>
            </p>
            <p class="description">
                <?php esc_html_e('Imagen que se mostrará en las vistas de grid y lista', 'event-show-base'); ?>
            </p>
        </div>
    <?php
    }

    /**
     * Renderizar metabox de opciones
     */
    public function render_options_metabox($post)
    {
        $use_default_template = get_post_meta($post->ID, '_use_default_template', true);
        $max_attendees = get_post_meta($post->ID, '_max_attendees', true);
        $enable_registration = get_post_meta($post->ID, '_enable_registration', true);
        $registration_deadline = get_post_meta($post->ID, '_registration_deadline', true);

        if ('' === $use_default_template) {
            $use_default_template = '1';
        }
        if ('' === $enable_registration) {
            $enable_registration = '1';
        }
    ?>
        <div class="event-show-options-metabox">
            <p>
                <label>
                    <input type="checkbox"
                        name="use_default_template"
                        value="1"
                        <?php checked($use_default_template, '1'); ?>>
                    <?php esc_html_e('Usar plantilla por defecto', 'event-show-base'); ?>
                </label>
            </p>
            <p class="description">
                <?php esc_html_e('Si está desactivado, se usará el editor normal de WordPress', 'event-show-base'); ?>
            </p>

            <hr>

            <p>
                <label>
                    <input type="checkbox"
                        name="enable_registration"
                        value="1"
                        <?php checked($enable_registration, '1'); ?>>
                    <?php esc_html_e('Habilitar registro de asistentes', 'event-show-base'); ?>
                </label>
            </p>

            <p>
                <label>
                    <strong><?php esc_html_e('Aforo máximo', 'event-show-base'); ?></strong><br>
                    <input type="number"
                        name="max_attendees"
                        value="<?php echo esc_attr($max_attendees); ?>"
                        min="0"
                        step="1"
                        style="width: 100%;">
                </label>
                <span class="description"><?php esc_html_e('Dejar en blanco para ilimitado', 'event-show-base'); ?></span>
            </p>

            <p>
                <label>
                    <strong><?php esc_html_e('Fecha límite de registro', 'event-show-base'); ?></strong><br>
                    <input type="text"
                        name="registration_deadline"
                        class="event-datepicker"
                        value="<?php echo esc_attr($registration_deadline); ?>"
                        placeholder="dd/mm/yyyy"
                        style="width: 100%;">
                </label>
                <span class="description"><?php esc_html_e('Opcional', 'event-show-base'); ?></span>
            </p>
        </div>
    <?php
    }

    /**
     * Guardar datos de metabox
     */
    public function save_metabox_data($post_id, $post)
    {
        // Verificar nonce
        if (
            ! isset($_POST['event_show_metabox_nonce']) ||
            ! wp_verify_nonce($_POST['event_show_metabox_nonce'], 'event_show_metabox')
        ) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }


        // --- Notificación de asistentes por cambio de datos clave ---
        $old_data = array(
            'post_title' => get_post_field('post_title', $post_id),
            '_event_date' => get_post_meta($post_id, '_event_date', true),
            '_event_time' => get_post_meta($post_id, '_event_time', true),
            '_event_end_date' => get_post_meta($post_id, '_event_end_date', true),
            '_event_end_time' => get_post_meta($post_id, '_event_end_time', true),
            'lugar' => join(',', wp_get_post_terms($post_id, 'lugar', ['fields' => 'ids'])),
            'categoria_evento' => join(',', wp_get_post_terms($post_id, 'categoria_evento', ['fields' => 'ids'])),
            'clasificacion_edad' => join(',', wp_get_post_terms($post_id, 'clasificacion_edad', ['fields' => 'ids'])),
        );

        // Guardar fecha y hora
        if (isset($_POST['event_date'])) {
            update_post_meta($post_id, '_event_date', sanitize_text_field($_POST['event_date']));
        }
        // Hora de inicio indefinida
        $event_time_indef = isset($_POST['event_time_indef']) ? '1' : '0';
        update_post_meta($post_id, '_event_time_indef', $event_time_indef);
        if ($event_time_indef === '1') {
            update_post_meta($post_id, '_event_time', '');
        } elseif (isset($_POST['event_time'])) {
            update_post_meta($post_id, '_event_time', sanitize_text_field($_POST['event_time']));
        }
        if (isset($_POST['event_end_date'])) {
            update_post_meta($post_id, '_event_end_date', sanitize_text_field($_POST['event_end_date']));
        }
        // Hora de fin indefinida
        $event_end_time_indef = isset($_POST['event_end_time_indef']) ? '1' : '0';
        update_post_meta($post_id, '_event_end_time_indef', $event_end_time_indef);
        if ($event_end_time_indef === '1') {
            update_post_meta($post_id, '_event_end_time', '');
        } elseif (isset($_POST['event_end_time'])) {
            update_post_meta($post_id, '_event_end_time', sanitize_text_field($_POST['event_end_time']));
        }
        // Guardar miniatura
        if (isset($_POST['event_thumbnail'])) {
            update_post_meta($post_id, '_event_thumbnail', absint($_POST['event_thumbnail']));
        }
        // Guardar opciones
        $use_default_template = isset($_POST['use_default_template']) ? '1' : '0';
        update_post_meta($post_id, '_use_default_template', $use_default_template);
        $enable_registration = isset($_POST['enable_registration']) ? '1' : '0';
        update_post_meta($post_id, '_enable_registration', $enable_registration);
        if (isset($_POST['max_attendees'])) {
            update_post_meta($post_id, '_max_attendees', absint($_POST['max_attendees']));
        }
        if (isset($_POST['registration_deadline'])) {
            update_post_meta($post_id, '_registration_deadline', sanitize_text_field($_POST['registration_deadline']));
        }

        // --- Datos nuevos para comparar ---
        $new_data = array(
            'post_title' => isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : get_post_field('post_title', $post_id),
            '_event_date' => isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : get_post_meta($post_id, '_event_date', true),
            '_event_time' => isset($_POST['event_time']) ? sanitize_text_field($_POST['event_time']) : get_post_meta($post_id, '_event_time', true),
            '_event_end_date' => isset($_POST['event_end_date']) ? sanitize_text_field($_POST['event_end_date']) : get_post_meta($post_id, '_event_end_date', true),
            '_event_end_time' => isset($_POST['event_end_time']) ? sanitize_text_field($_POST['event_end_time']) : get_post_meta($post_id, '_event_end_time', true),
            'lugar' => isset($_POST['tax_input']['lugar']) ? join(',', (array)$_POST['tax_input']['lugar']) : join(',', wp_get_post_terms($post_id, 'lugar', ['fields' => 'ids'])),
            'categoria_evento' => isset($_POST['tax_input']['categoria_evento']) ? join(',', (array)$_POST['tax_input']['categoria_evento']) : join(',', wp_get_post_terms($post_id, 'categoria_evento', ['fields' => 'ids'])),
            'clasificacion_edad' => isset($_POST['tax_input']['clasificacion_edad']) ? join(',', (array)$_POST['tax_input']['clasificacion_edad']) : join(',', wp_get_post_terms($post_id, 'clasificacion_edad', ['fields' => 'ids'])),
        );
        // Notificar si hay cambios clave
        if ($post->post_status === 'publish') {
            if (class_exists('Event_Show_Notifications')) {
                Event_Show_Notifications::maybe_notify_event_update($post_id, $old_data, $new_data);
            }
        }

        // Validar que el evento sea al menos 5 días en el futuro
        if (! empty($_POST['event_date'])) {
            $min_days = get_option('event_show_min_days_advance', 5);
            $event_timestamp = strtotime(str_replace('/', '-', $_POST['event_date']));
            $min_timestamp = strtotime("+{$min_days} days");

            if ($event_timestamp < $min_timestamp && 'publish' === $post->post_status) {
                // Si el usuario no es admin, cambiar a borrador
                if (! current_user_can('manage_options')) {
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'draft',
                    ));

                    set_transient('event_show_validation_error_' . $post_id, sprintf(
                        __('El evento debe programarse con al menos %d días de anticipación. Se ha guardado como borrador.', 'event-show-base'),
                        $min_days
                    ), 60);
                }
            }
        }

        // Cambiar autor del evento (solo administradores)
        if (current_user_can('edit_others_posts') && isset($_POST['event_author'])) {
            $new_author_id = absint($_POST['event_author']);
            if ($new_author_id && $new_author_id !== $post->post_author) {
                // Remover el hook temporalmente para evitar bucle infinito
                remove_action('save_post_evento', array($this, 'save_metabox_data'), 10);

                wp_update_post(array(
                    'ID' => $post_id,
                    'post_author' => $new_author_id,
                ));

                // Volver a agregar el hook
                add_action('save_post_evento', array($this, 'save_metabox_data'), 10, 2);

                Event_Show_Logger::log(
                    'change_event_author',
                    'evento',
                    $post_id,
                    sprintf(__('Autor del evento "%s" cambiado al usuario ID: %d', 'event-show-base'), get_the_title($post_id), $new_author_id)
                );
            }
        }

        // Log de la acción
        Event_Show_Logger::log(
            'update_event',
            'evento',
            $post_id,
            sprintf(__('Evento "%s" actualizado', 'event-show-base'), get_the_title($post_id))
        );
    }

    /**
     * Renderizar metabox de autor
     */
    public function render_author_metabox($post)
    {
        $current_author_id = $post->post_author;

        // Obtener todos los usuarios con capacidad de publicar eventos
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
    ?>
        <div class="event-author-wrap">
            <p>
                <label for="event_author"><?php esc_html_e('Seleccionar Autor:', 'event-show-base'); ?></label>
            </p>
            <select name="event_author" id="event_author" style="width: 100%;">
                <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($current_author_id, $user->ID); ?>>
                        <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_login); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php esc_html_e('Cambia el propietario de este evento. El nuevo autor podrá editarlo desde su dashboard.', 'event-show-base'); ?>
            </p>
        </div>
<?php
    }
}
