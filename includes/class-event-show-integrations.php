<?php

/**
 * Clase de integración con otros plugins
 *
 * Esta clase maneja la comunicación entre Event Show y otros plugins
 * como Simple Cards Listings (establecimientos) y Advanced Role Manager
 *
 * @package Event_Show
 * @since 1.2.0
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase de integración entre plugins
 */
class Event_Show_Integrations
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Inicializar integraciones cuando todos los plugins estén cargados
        add_action('plugins_loaded', array($this, 'init_integrations'), 20);
    }

    /**
     * Inicializar integraciones con otros plugins
     */
    public function init_integrations()
    {
        // Metabox híbrido de organizador (siempre disponible)
        // Muestra organizadores (taxonomía) y/o establecimientos (si SCL activo)
        add_action('add_meta_boxes', array($this, 'add_organizer_metabox'));
        add_action('save_post_evento', array($this, 'save_organizer_metabox'), 10, 2);
        add_filter('event_show_evento_data', array($this, 'add_organizer_to_evento_data'), 10, 2);

        // Metabox híbrido de lugar (siempre disponible)
        // Muestra lugares (taxonomía) y/o establecimientos (si SCL activo)
        add_action('add_meta_boxes', array($this, 'add_lugar_metabox'));
        add_action('save_post_evento', array($this, 'save_lugar_metabox'), 10, 2);
        add_filter('event_show_evento_data', array($this, 'add_lugar_to_evento_data'), 10, 2);

        // Integración con Advanced Role Manager (Auto-publicación)
        if ($this->is_arm_active()) {
            add_filter('wp_insert_post_data', array($this, 'auto_publish_by_role'), 10, 2);
        }

        // Hook para que otros plugins puedan extender las integraciones
        do_action('event_show_integrations_loaded', $this);
    }

    /**
     * Verificar si Simple Cards Listings está activo
     *
     * @return bool
     */
    public function is_scl_active()
    {
        return class_exists('Simple_Cards_Listings') && post_type_exists('establecimiento');
    }

    /**
     * Verificar si las integraciones están disponibles (método público)
     * 
     * @return bool
     */
    public static function has_establishments_integration()
    {
        return class_exists('Simple_Cards_Listings') && post_type_exists('establecimiento');
    }

    /**
     * Verificar si Advanced Role Manager está activo
     *
     * @return bool
     */
    public function is_arm_active()
    {
        return function_exists('run_advanced_role_manager');
    }

    /**
     * Agregar metabox para seleccionar organizador (establecimiento)
     */
    public function add_organizer_metabox()
    {
        add_meta_box(
            'event_show_organizer',
            __('Organizador del Evento', 'event-show-base'),
            array($this, 'render_organizer_metabox'),
            'evento',
            'side',
            'default'
        );
    }

    /**
     * Renderizar metabox de organizador
     *
     * @param WP_Post $post
     */
    public function render_organizer_metabox($post)
    {
        wp_nonce_field('event_show_organizer_metabox', 'event_show_organizer_nonce');

        $organizer_value = get_post_meta($post->ID, '_event_organizer_id', true);
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');

        // Obtener establecimientos (si SCL está activo)
        $establecimientos = array();
        if (self::is_scl_active()) {
            $query_args = array(
                'post_type'      => 'establecimiento',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            );

            if (!$is_admin) {
                $query_args['author'] = $current_user_id;
            }

            $establecimientos = get_posts($query_args);
        }

        // Obtener organizadores (taxonomía)
        $organizadores_args = array(
            'taxonomy'   => 'organizador',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );

        // Si no es admin, filtrar por owner_id
        if (!$is_admin) {
            $organizadores_args['meta_query'] = array(
                array(
                    'key'     => 'owner_id',
                    'value'   => $current_user_id,
                    'compare' => '='
                )
            );
        }

        $organizadores = get_terms($organizadores_args);
        if (is_wp_error($organizadores)) {
            $organizadores = array();
        }

        // Filtrar solo organizadores aprobados para no-admins
        if (!$is_admin) {
            $organizadores = array_filter($organizadores, function ($org) {
                $status = get_term_meta($org->term_id, 'status', true);
                return $status === 'approved';
            });
        }

?>
        <div class="event-organizer-wrap">
            <p>
                <label for="event_organizer_id">
                    <?php esc_html_e('Seleccionar Organizador:', 'event-show-base'); ?>
                </label>
            </p>
            <select name="event_organizer_id" id="event_organizer_id" class="widefat">
                <option value=""><?php esc_html_e('-- Sin organizador --', 'event-show-base'); ?></option>

                <?php if (!empty($establecimientos)) : ?>
                    <optgroup label="<?php esc_attr_e('Establecimientos', 'event-show-base'); ?>">
                        <?php foreach ($establecimientos as $establecimiento) :
                            $value = 'establecimiento_' . $establecimiento->ID;
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($organizer_value, $value); ?>>
                                <?php echo esc_html($establecimiento->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>

                <?php if (!empty($organizadores)) : ?>
                    <optgroup label="<?php esc_attr_e('Organizadores', 'event-show-base'); ?>">
                        <?php foreach ($organizadores as $organizador) :
                            $value = 'organizador_' . $organizador->term_id;
                            $status = get_term_meta($organizador->term_id, 'status', true);
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($organizer_value, $value); ?>>
                                <?php
                                echo esc_html($organizador->name);
                                if ($is_admin && $status) {
                                    echo ' [' . esc_html(ucfirst($status)) . ']';
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
            <p class="description">
                <?php
                if ($is_admin) {
                    esc_html_e('Selecciona entre establecimientos u organizadores. El formato indica el tipo.', 'event-show-base');
                } else {
                    esc_html_e('Solo puedes seleccionar tus propios establecimientos y organizadores aprobados.', 'event-show-base');
                }
                ?>
            </p>

            <?php if (empty($establecimientos) && empty($organizadores)) : ?>
                <div class="notice notice-warning inline">
                    <p><?php esc_html_e('No tienes organizadores disponibles.', 'event-show-base'); ?></p>
                    <?php if (self::is_scl_active()) : ?>
                        <p><a href="<?php echo esc_url(admin_url('post-new.php?post_type=establecimiento')); ?>" class="button"><?php esc_html_e('Crear Establecimiento', 'event-show-base'); ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($organizer_value) :
                // Determinar tipo y enlace
                $parts = explode('_', $organizer_value, 2);
                $tipo = isset($parts[0]) ? $parts[0] : '';
                $id = isset($parts[1]) ? $parts[1] : '';
                $edit_link = '';

                if ($tipo === 'establecimiento' && $id) {
                    $edit_link = get_edit_post_link($id);
                } elseif ($tipo === 'organizador' && $id) {
                    $edit_link = admin_url('term.php?taxonomy=organizador&tag_ID=' . $id);
                }

                if ($edit_link) :
            ?>
                    <p style="margin-top: 10px;">
                        <a href="<?php echo esc_url($edit_link); ?>" class="button button-small" target="_blank">
                            <?php esc_html_e('Ver/Editar Organizador', 'event-show-base'); ?>
                        </a>
                    </p>
            <?php
                endif;
            endif;
            ?>
        </div>
    <?php
    }

    /**
     * Guardar datos del metabox de organizador
     *
     * @param int     $post_id
     * @param WP_Post $post
     */
    public function save_organizer_metabox($post_id, $post)
    {
        // Verificar nonce
        if (
            !isset($_POST['event_show_organizer_nonce']) ||
            !wp_verify_nonce($_POST['event_show_organizer_nonce'], 'event_show_organizer_metabox')
        ) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar organizador con el nuevo formato (tipo_id)
        if (isset($_POST['event_organizer_id'])) {
            $organizer_value = sanitize_text_field($_POST['event_organizer_id']);

            if (!empty($organizer_value)) {
                // Parsear el formato tipo_id
                $parts = explode('_', $organizer_value, 2);
                $tipo = isset($parts[0]) ? $parts[0] : '';
                $id = isset($parts[1]) ? intval($parts[1]) : 0;

                $can_save = false;
                $organizer_title = '';
                $current_user_id = get_current_user_id();

                if ($tipo === 'establecimiento' && $id) {
                    $establecimiento = get_post($id);
                    if ($establecimiento && $establecimiento->post_type === 'establecimiento') {
                        // Verificar permisos: admin o dueño del establecimiento
                        if (current_user_can('manage_options') || $establecimiento->post_author == $current_user_id) {
                            $can_save = true;
                            $organizer_title = $establecimiento->post_title;
                        }
                    }
                } elseif ($tipo === 'organizador' && $id) {
                    $organizador = get_term($id, 'organizador');
                    if ($organizador && !is_wp_error($organizador)) {
                        $owner_id = get_term_meta($id, 'owner_id', true);
                        $status = get_term_meta($id, 'status', true);

                        // Verificar permisos: admin o dueño del organizador, y debe estar aprobado
                        if (current_user_can('manage_options') || ($owner_id == $current_user_id && $status === 'approved')) {
                            $can_save = true;
                            $organizer_title = $organizador->name;
                        }
                    }
                }

                if ($can_save) {
                    // Guardar el valor completo (tipo_id)
                    update_post_meta($post_id, '_event_organizer_id', $organizer_value);
                    // Guardar tipo y ID por separado para facilitar consultas
                    update_post_meta($post_id, '_event_organizer_type', $tipo);
                    update_post_meta($post_id, '_event_organizer_ref_id', $id);

                    // Hook para que otros plugins puedan ejecutar acciones cuando se asigna un organizador
                    do_action('event_show_organizer_assigned', $post_id, $organizer_value, $tipo, $id);

                    // Logging
                    Event_Show_Logger::log_activity(
                        'organizer_assigned',
                        $current_user_id,
                        sprintf(__('Organizador asignado al evento #%d: %s (%s)', 'event-show-base'), $post_id, $organizer_title, $tipo),
                        array(
                            'event_id'         => $post_id,
                            'organizer_value'  => $organizer_value,
                            'organizer_type'   => $tipo,
                            'organizer_ref_id' => $id,
                            'organizer_title'  => $organizer_title,
                        )
                    );
                }
            } else {
                // Si está vacío, eliminar
                delete_post_meta($post_id, '_event_organizer_id');
                delete_post_meta($post_id, '_event_organizer_type');
                delete_post_meta($post_id, '_event_organizer_ref_id');
            }
        }
    }

    /**
     * Agregar información del organizador a los datos del evento
     *
     * @param array $data Datos del evento
     * @param int   $event_id ID del evento
     * @return array
     */
    public function add_organizer_to_evento_data($data, $event_id)
    {
        $organizer_value = get_post_meta($event_id, '_event_organizer_id', true);

        if ($organizer_value) {
            // Parsear el formato tipo_id
            $parts = explode('_', $organizer_value, 2);
            $tipo = isset($parts[0]) ? $parts[0] : '';
            $id = isset($parts[1]) ? intval($parts[1]) : 0;

            if ($tipo === 'establecimiento' && $id) {
                $organizer = get_post($id);

                if ($organizer && $organizer->post_type === 'establecimiento') {
                    $data['organizer'] = array(
                        'id'    => $organizer->ID,
                        'type'  => 'establecimiento',
                        'title' => $organizer->post_title,
                        'url'   => get_permalink($organizer->ID),
                        'logo'  => get_the_post_thumbnail_url($organizer->ID, 'medium'),
                    );
                }
            } elseif ($tipo === 'organizador' && $id) {
                $organizador = get_term($id, 'organizador');

                if ($organizador && !is_wp_error($organizador)) {
                    $logo = get_term_meta($id, 'logo', true);
                    $contact = get_term_meta($id, 'contact_info', true);

                    $data['organizer'] = array(
                        'id'      => $organizador->term_id,
                        'type'    => 'organizador',
                        'title'   => $organizador->name,
                        'url'     => get_term_link($organizador),
                        'logo'    => $logo,
                        'contact' => $contact,
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Agregar metabox para seleccionar lugar
     */
    public function add_lugar_metabox()
    {
        add_meta_box(
            'event_show_lugar',
            __('Lugar del Evento', 'event-show-base'),
            array($this, 'render_lugar_metabox'),
            'evento',
            'side',
            'default'
        );
    }

    /**
     * Renderizar metabox de lugar
     *
     * @param WP_Post $post
     */
    public function render_lugar_metabox($post)
    {
        wp_nonce_field('event_show_lugar_metabox', 'event_show_lugar_nonce');

        $lugar_value = get_post_meta($post->ID, '_event_lugar_id', true);
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');

        // Obtener establecimientos (si SCL está activo)
        $establecimientos = array();
        if (self::is_scl_active()) {
            $query_args = array(
                'post_type'      => 'establecimiento',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            );

            // Si no es admin, filtrar por autor
            if (!$is_admin) {
                $query_args['author'] = $current_user_id;
            }

            $establecimientos = get_posts($query_args);
        }

        // Obtener lugares (taxonomía)
        $lugares = get_terms(array(
            'taxonomy'   => 'lugar',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        if (is_wp_error($lugares)) {
            $lugares = array();
        }

    ?>
        <div class="event-lugar-wrap">
            <p>
                <label for="event_lugar_id">
                    <?php esc_html_e('Seleccionar Lugar:', 'event-show-base'); ?>
                </label>
            </p>
            <select name="event_lugar_id" id="event_lugar_id" class="widefat">
                <option value=""><?php esc_html_e('-- Sin lugar específico --', 'event-show-base'); ?></option>

                <?php if (!empty($establecimientos)) : ?>
                    <optgroup label="<?php esc_attr_e('Establecimientos', 'event-show-base'); ?>">
                        <?php foreach ($establecimientos as $establecimiento) :
                            $value = 'establecimiento_' . $establecimiento->ID;
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($lugar_value, $value); ?>>
                                <?php echo esc_html($establecimiento->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>

                <?php if (!empty($lugares)) : ?>
                    <optgroup label="<?php esc_attr_e('Lugares', 'event-show-base'); ?>">
                        <?php foreach ($lugares as $lugar) :
                            $value = 'lugar_' . $lugar->term_id;
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($lugar_value, $value); ?>>
                                <?php echo esc_html($lugar->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
            <p class="description">
                <?php esc_html_e('Selecciona dónde se realizará el evento.', 'event-show-base'); ?>
            </p>

            <?php if ($lugar_value) :
                // Determinar tipo y enlace
                $parts = explode('_', $lugar_value, 2);
                $tipo = isset($parts[0]) ? $parts[0] : '';
                $id = isset($parts[1]) ? $parts[1] : '';
                $edit_link = '';

                if ($tipo === 'establecimiento' && $id) {
                    $edit_link = get_edit_post_link($id);
                } elseif ($tipo === 'lugar' && $id) {
                    $edit_link = admin_url('term.php?taxonomy=lugar&tag_ID=' . $id);
                }

                if ($edit_link) :
            ?>
                    <p style="margin-top: 10px;">
                        <a href="<?php echo esc_url($edit_link); ?>" class="button button-small" target="_blank">
                            <?php esc_html_e('Ver/Editar Lugar', 'event-show-base'); ?>
                        </a>
                    </p>
            <?php
                endif;
            endif;
            ?>
        </div>
<?php
    }

    /**
     * Guardar datos del metabox de lugar
     *
     * @param int     $post_id
     * @param WP_Post $post
     */
    public function save_lugar_metabox($post_id, $post)
    {
        // Verificar nonce
        if (
            !isset($_POST['event_show_lugar_nonce']) ||
            !wp_verify_nonce($_POST['event_show_lugar_nonce'], 'event_show_lugar_metabox')
        ) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar lugar con el formato tipo_id
        if (isset($_POST['event_lugar_id'])) {
            $lugar_value = sanitize_text_field($_POST['event_lugar_id']);

            if (!empty($lugar_value)) {
                // Parsear el formato tipo_id
                $parts = explode('_', $lugar_value, 2);
                $tipo = isset($parts[0]) ? $parts[0] : '';
                $id = isset($parts[1]) ? intval($parts[1]) : 0;

                $can_save = false;
                $lugar_title = '';

                if ($tipo === 'establecimiento' && $id) {
                    $establecimiento = get_post($id);
                    if ($establecimiento && $establecimiento->post_type === 'establecimiento') {
                        $can_save = true;
                        $lugar_title = $establecimiento->post_title;
                    }
                } elseif ($tipo === 'lugar' && $id) {
                    $lugar = get_term($id, 'lugar');
                    if ($lugar && !is_wp_error($lugar)) {
                        $can_save = true;
                        $lugar_title = $lugar->name;
                    }
                }

                if ($can_save) {
                    // Guardar el valor completo (tipo_id)
                    update_post_meta($post_id, '_event_lugar_id', $lugar_value);
                    // Guardar tipo y ID por separado para facilitar consultas
                    update_post_meta($post_id, '_event_lugar_type', $tipo);
                    update_post_meta($post_id, '_event_lugar_ref_id', $id);

                    // Logging
                    Event_Show_Logger::log_activity(
                        'lugar_assigned',
                        get_current_user_id(),
                        sprintf(__('Lugar asignado al evento #%d: %s (%s)', 'event-show-base'), $post_id, $lugar_title, $tipo),
                        array(
                            'event_id'       => $post_id,
                            'lugar_value'    => $lugar_value,
                            'lugar_type'     => $tipo,
                            'lugar_ref_id'   => $id,
                            'lugar_title'    => $lugar_title,
                        )
                    );
                }
            } else {
                // Si está vacío, eliminar
                delete_post_meta($post_id, '_event_lugar_id');
                delete_post_meta($post_id, '_event_lugar_type');
                delete_post_meta($post_id, '_event_lugar_ref_id');
            }
        }
    }

    /**
     * Agregar información del lugar a los datos del evento
     *
     * @param array $data Datos del evento
     * @param int   $event_id ID del evento
     * @return array
     */
    public function add_lugar_to_evento_data($data, $event_id)
    {
        $lugar_value = get_post_meta($event_id, '_event_lugar_id', true);

        if ($lugar_value) {
            // Parsear el formato tipo_id
            $parts = explode('_', $lugar_value, 2);
            $tipo = isset($parts[0]) ? $parts[0] : '';
            $id = isset($parts[1]) ? intval($parts[1]) : 0;

            if ($tipo === 'establecimiento' && $id) {
                $lugar = get_post($id);

                if ($lugar && $lugar->post_type === 'establecimiento') {
                    $data['lugar'] = array(
                        'id'      => $lugar->ID,
                        'type'    => 'establecimiento',
                        'title'   => $lugar->post_title,
                        'url'     => get_permalink($lugar->ID),
                        'address' => get_post_meta($id, '_establecimiento_direccion', true),
                        'lat'     => get_post_meta($id, '_establecimiento_lat', true),
                        'lng'     => get_post_meta($id, '_establecimiento_lng', true),
                    );
                }
            } elseif ($tipo === 'lugar' && $id) {
                $lugar = get_term($id, 'lugar');

                if ($lugar && !is_wp_error($lugar)) {
                    $direccion = get_term_meta($id, 'direccion', true);
                    $lat = get_term_meta($id, 'latitud', true);
                    $lng = get_term_meta($id, 'longitud', true);

                    $data['lugar'] = array(
                        'id'      => $lugar->term_id,
                        'type'    => 'lugar',
                        'title'   => $lugar->name,
                        'url'     => get_term_link($lugar),
                        'address' => $direccion,
                        'lat'     => $lat,
                        'lng'     => $lng,
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Auto-publicar eventos basado en el rol del usuario
     *
     * Esta función permite que usuarios con roles específicos
     * publiquen eventos automáticamente sin necesidad de revisión
     *
     * @param array $data    Array de datos del post
     * @param array $postarr Array de datos sin filtrar
     * @return array
     */
    public function auto_publish_by_role($data, $postarr)
    {
        // Solo aplicar a eventos
        if ($data['post_type'] !== 'evento') {
            return $data;
        }

        // Solo para posts nuevos o borradores que se están guardando
        if (!in_array($data['post_status'], array('draft', 'pending', 'auto-draft'))) {
            return $data;
        }

        // Verificar si el usuario actual puede auto-publicar
        if ($this->user_can_auto_publish_events()) {
            $data['post_status'] = 'publish';

            // Hook para notificar que un evento se auto-publicó
            do_action('event_show_auto_published', $postarr['ID'], get_current_user_id());
        }

        return $data;
    }

    /**
     * Verificar si el usuario actual puede auto-publicar eventos
     *
     * @return bool
     */
    public function user_can_auto_publish_events()
    {
        // Si el usuario puede publicar posts, puede auto-publicar eventos
        if (current_user_can('publish_posts')) {
            return true;
        }

        // Obtener roles permitidos desde la configuración
        $allowed_roles = get_option('event_show_auto_publish_roles', array());

        if (empty($allowed_roles)) {
            // Roles predeterminados que pueden auto-publicar
            $allowed_roles = array('administrator', 'editor', 'event_manager');
        }

        // Verificar si el usuario tiene alguno de los roles permitidos
        $user = wp_get_current_user();

        foreach ($allowed_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return apply_filters('event_show_user_can_auto_publish', true, $user, $role);
            }
        }

        return apply_filters('event_show_user_can_auto_publish', false, $user, null);
    }

    /**
     * Obtener el organizador de un evento
     *
     * Método público para que otros plugins puedan obtener el organizador
     *
     * @param int $event_id ID del evento
     * @return WP_Post|null
     */
    public static function get_event_organizer($event_id)
    {
        $organizer_id = get_post_meta($event_id, '_event_organizer_id', true);

        if ($organizer_id) {
            return get_post($organizer_id);
        }

        return null;
    }

    /**
     * Obtener eventos de un organizador específico
     *
     * @param int   $organizer_id ID del establecimiento organizador
     * @param array $args         Argumentos adicionales para la consulta
     * @return WP_Post[]
     */
    public static function get_events_by_organizer($organizer_id, $args = array())
    {
        $defaults = array(
            'post_type'      => 'evento',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_event_organizer_id',
                    'value' => $organizer_id,
                ),
            ),
        );

        $args = wp_parse_args($args, $defaults);

        return get_posts($args);
    }
}
