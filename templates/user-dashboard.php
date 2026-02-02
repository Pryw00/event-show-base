<?php

/**
 * Dashboard de usuario
 *
 * @package Event_Show
 */

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Obtener eventos del usuario
$user_events = new WP_Query(array(
    'post_type' => 'evento',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => array('publish', 'pending', 'draft'),
));
?>

<div class="event-show-dashboard">
    <div class="dashboard-header">
        <h2><?php printf(esc_html__('Hola, %s', 'event-show-base'), esc_html($current_user->display_name)); ?></h2>
        <p><?php esc_html_e('Gestiona tus eventos, organizadores y lugares desde aquí', 'event-show-base'); ?></p>
    </div>

    <!-- Tabs de navegación -->
    <div class="dashboard-tabs">
        <button class="dashboard-tab active" data-tab="my-events">
            <?php esc_html_e('Mis Eventos', 'event-show-base'); ?>
        </button>
        <button class="dashboard-tab" data-tab="organizers">
            <?php esc_html_e('Organizadores', 'event-show-base'); ?>
        </button>
        <button class="dashboard-tab" data-tab="locations">
            <?php esc_html_e('Lugares', 'event-show-base'); ?>
        </button>
        <button class="dashboard-tab" data-tab="profile">
            <?php esc_html_e('Mi Perfil', 'event-show-base'); ?>
        </button>
    </div>

    <!-- Contenido de tabs -->
    <div class="dashboard-content">

        <!-- Mis Eventos -->
        <div class="dashboard-tab-content active" id="tab-my-events">
            <div class="dashboard-section-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h3><?php esc_html_e('Mis Eventos', 'event-show-base'); ?></h3>
                <button class="button" id="open-create-event-modal" type="button"><?php esc_html_e('Crear Nuevo Evento', 'event-show-base'); ?></button>
            </div>
            <?php if ($user_events->have_posts()) : ?>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Título', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Fecha', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Estado', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Asistentes', 'event-show-base'); ?></th>
                            <th><?php esc_html_e('Acciones', 'event-show-base'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($user_events->have_posts()) :
                            $user_events->the_post();
                            $event_id = get_the_ID();
                            $event_date = get_post_meta($event_id, '_event_date', true);
                            $event_time = get_post_meta($event_id, '_event_time', true);
                            $event_time_indef = get_post_meta($event_id, '_event_time_indef', true);
                            $total_attendees = Event_Show_Attendees::get_total_attendees($event_id);
                            $status = get_post_status();

                            // Etiqueta de estado
                            $status_labels = array(
                                'publish' => array('label' => __('Publicado', 'event-show-base'), 'class' => 'status-published'),
                                'pending' => array('label' => __('Pendiente', 'event-show-base'), 'class' => 'status-pending'),
                                'draft' => array('label' => __('Borrador', 'event-show-base'), 'class' => 'status-draft'),
                            );

                            $status_info = isset($status_labels[$status]) ? $status_labels[$status] : array('label' => $status, 'class' => 'status-default');

                            // Verificar si se puede editar (7 días antes del evento)
                            $can_edit = false;
                            if ($event_date) {
                                $event_timestamp = strtotime(str_replace('/', '-', $event_date));
                                $days_until_event = floor(($event_timestamp - time()) / (60 * 60 * 24));
                                $can_edit = $days_until_event >= 7;
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong><a href="<?php the_permalink(); ?>" target="_blank"><?php the_title(); ?></a></strong>
                                </td>
                                <td>
                                    <?php echo esc_html(Event_Show_Helpers::format_date($event_date)); ?>
                                    <?php if ($event_time && $event_time_indef !== '1') : ?>
                                        - <?php echo esc_html(Event_Show_Helpers::format_time($event_time)); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="event-status <?php echo esc_attr($status_info['class']); ?>">
                                        <?php echo esc_html($status_info['label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($total_attendees > 0) : ?>
                                        <a href="#" class="view-attendees-link" data-event-id="<?php echo esc_attr($event_id); ?>" data-event-title="<?php echo esc_attr(get_the_title()); ?>">
                                            <?php echo esc_html($total_attendees); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html($total_attendees); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="dashboard-actions">
                                    <a href="<?php the_permalink(); ?>" target="_blank" class="dashboard-action-link">
                                        <?php esc_html_e('Ver', 'event-show-base'); ?>
                                    </a>
                                    <?php if ($can_edit) : ?>
                                        <a href="#" class="dashboard-action-link edit-event-link" data-event-id="<?php echo esc_attr($event_id); ?>">
                                            <?php esc_html_e('Editar', 'event-show-base'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dashboard-empty">
                    <p><?php esc_html_e('No has creado ningún evento todavía', 'event-show-base'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal crear nuevo evento -->
        <div id="create-event-modal" class="event-modal-overlay" style="display:none;">
            <div class="event-modal-window">
                <span class="modal-close">&times;</span>
                <?php include EVENT_SHOW_PLUGIN_DIR . 'templates/submit-event-form.php'; ?>
            </div>
        </div>

        <!-- Modal editar evento -->
        <div id="edit-event-modal" class="event-modal-overlay" style="display:none;">
            <div class="event-modal-window">
                <span class="modal-close">&times;</span>
                <h2><?php esc_html_e('Editar Evento', 'event-show-base'); ?></h2>
                <p class="form-intro"><?php esc_html_e('Al editar el evento, pasará nuevamente a revisión.', 'event-show-base'); ?></p>
                <form id="edit-event-form" enctype="multipart/form-data">
                    <input type="hidden" id="edit_event_id" name="event_id">
                    <div class="form-messages"></div>

                    <div class="form-group">
                        <label><?php esc_html_e('Descripción', 'event-show-base'); ?> *</label>
                        <textarea id="edit_event_description" name="description" class="form-control" rows="6" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-col-half">
                            <label><?php esc_html_e('Fecha de Inicio', 'event-show-base'); ?> *</label>
                            <input type="text" id="edit_event_date" name="event_date" class="form-control event-datepicker" required>
                        </div>
                        <div class="form-group form-col-half">
                            <label><?php esc_html_e('Hora de Inicio', 'event-show-base'); ?> *</label>
                            <input type="time" id="edit_event_time" name="event_time" class="form-control" required>
                            <label style="margin-left:10px; font-weight:normal;">
                                <input type="checkbox" id="edit_event_time_indef" name="event_time_indef" value="1">
                                <?php esc_html_e('Hora indefinida', 'event-show-base'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-col-half">
                            <label><?php esc_html_e('Fecha de Fin', 'event-show-base'); ?></label>
                            <input type="text" id="edit_event_end_date" name="event_end_date" class="form-control event-datepicker">
                        </div>
                        <div class="form-group form-col-half">
                            <label><?php esc_html_e('Hora de Fin', 'event-show-base'); ?></label>
                            <input type="time" id="edit_event_end_time" name="event_end_time" class="form-control">
                            <label style="margin-left:10px; font-weight:normal;">
                                <input type="checkbox" id="edit_event_end_time_indef" name="event_end_time_indef" value="1">
                                <?php esc_html_e('Hora indefinida', 'event-show-base'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Categoría', 'event-show-base'); ?></label>
                        <select id="edit_event_category" name="category" class="form-control">
                            <option value=""><?php esc_html_e('-- Seleccionar --', 'event-show-base'); ?></option>
                            <?php
                            $categories = get_terms(array('taxonomy' => 'categoria_evento', 'hide_empty' => false));
                            foreach ($categories as $cat) {
                                echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Clasificación de Edad', 'event-show-base'); ?></label>
                        <select id="edit_event_age" name="age_classification" class="form-control">
                            <option value=""><?php esc_html_e('-- Seleccionar --', 'event-show-base'); ?></option>
                            <?php
                            $age_classifications = get_terms(array('taxonomy' => 'clasificacion_edad', 'hide_empty' => false));
                            foreach ($age_classifications as $age) {
                                echo '<option value="' . esc_attr($age->term_id) . '">' . esc_html($age->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Lugar', 'event-show-base'); ?></label>
                        <select id="edit_event_location" name="location" class="form-control">
                            <option value=""><?php esc_html_e('-- Seleccionar --', 'event-show-base'); ?></option>
                            <?php
                            $locations = get_terms(array('taxonomy' => 'lugar', 'hide_empty' => false));
                            foreach ($locations as $loc) {
                                echo '<option value="' . esc_attr($loc->term_id) . '">' . esc_html($loc->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Aforo Máximo', 'event-show-base'); ?></label>
                        <input type="number" id="edit_event_capacity" name="max_attendees" class="form-control" min="0" step="1">
                        <small class="form-text"><?php esc_html_e('Dejar en blanco para ilimitado', 'event-show-base'); ?></small>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Banner del Evento', 'event-show-base'); ?></label>
                        <input type="file" id="edit_event_banner" name="banner" class="form-control" accept="image/*">
                        <div id="edit_banner_preview" style="margin-top: 10px;"></div>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Imagen para Grid', 'event-show-base'); ?></label>
                        <input type="file" id="edit_event_grid_image" name="grid_image" class="form-control" accept="image/*">
                        <div id="edit_grid_image_preview" style="margin-top: 10px;"></div>
                    </div>

                    <button type="submit" class="button"><?php esc_html_e('Guardar Cambios', 'event-show-base'); ?></button>
                </form>
            </div>
        </div>

        <!-- Organizadores -->
        <div class="dashboard-tab-content" id="tab-organizers">
            <?php
            // Verificar si la integración con establecimientos está disponible
            $has_establishments = class_exists('Event_Show_Integrations') &&
                Event_Show_Integrations::has_establishments_integration();

            if ($has_establishments) :
                // Modo: Establecimientos como organizadores
            ?>
                <div class="dashboard-section-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h3><?php esc_html_e('Mis Establecimientos Organizadores', 'event-show-base'); ?></h3>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=establecimiento')); ?>" class="button">
                        <?php esc_html_e('Crear Nuevo Establecimiento', 'event-show-base'); ?>
                    </a>
                </div>

                <p class="description" style="margin-bottom: 20px;">
                    <?php esc_html_e('Los establecimientos que crees aquí podrás usarlos como organizadores de tus eventos.', 'event-show-base'); ?>
                </p>

                <?php
                // Obtener establecimientos del usuario
                $query_args = array(
                    'post_type'      => 'establecimiento',
                    'posts_per_page' => -1,
                    'post_status'    => array('publish', 'draft', 'pending'),
                    'author'         => $user_id,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                );

                $user_establecimientos = get_posts($query_args);

                if (!empty($user_establecimientos)) :
                    // Contar eventos por establecimiento si Event Show Integrations existe
                    $eventos_por_establecimiento = array();
                    if (class_exists('Event_Show_Integrations')) {
                        foreach ($user_establecimientos as $establecimiento) {
                            $eventos = Event_Show_Integrations::get_events_by_organizer($establecimiento->ID);
                            $eventos_por_establecimiento[$establecimiento->ID] = count($eventos);
                        }
                    }
                ?>
                    <div class="dashboard-grid">
                        <?php foreach ($user_establecimientos as $establecimiento) :
                            $logo = get_the_post_thumbnail_url($establecimiento->ID, 'thumbnail');
                            $eventos_count = isset($eventos_por_establecimiento[$establecimiento->ID]) ? $eventos_por_establecimiento[$establecimiento->ID] : 0;

                            // Obtener metadatos del establecimiento
                            $telefono = get_post_meta($establecimiento->ID, '_scl_telefono', true);
                            $email = get_post_meta($establecimiento->ID, '_scl_email', true);
                            $direccion = get_post_meta($establecimiento->ID, '_scl_direccion', true);
                            $website = get_post_meta($establecimiento->ID, '_scl_website', true);

                            // Estado del post
                            $status = get_post_status($establecimiento->ID);
                            $status_labels = array(
                                'publish' => array('label' => __('Publicado', 'event-show-base'), 'class' => 'status-published'),
                                'pending' => array('label' => __('Pendiente', 'event-show-base'), 'class' => 'status-pending'),
                                'draft'   => array('label' => __('Borrador', 'event-show-base'), 'class' => 'status-draft'),
                            );
                            $status_info = isset($status_labels[$status]) ? $status_labels[$status] : array('label' => $status, 'class' => 'status-default');
                        ?>
                            <div class="dashboard-card establecimiento-card">
                                <?php if ($logo) : ?>
                                    <div class="card-image">
                                        <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($establecimiento->post_title); ?>" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px 8px 0 0; margin: -15px -15px 15px -15px;">
                                    </div>
                                <?php endif; ?>

                                <h4 style="margin-bottom: 10px;">
                                    <?php echo esc_html($establecimiento->post_title); ?>
                                </h4>

                                <div class="establecimiento-status" style="margin-bottom: 10px;">
                                    <span class="status-badge <?php echo esc_attr($status_info['class']); ?>" style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.85em; font-weight: 500;">
                                        <?php echo esc_html($status_info['label']); ?>
                                    </span>
                                </div>

                                <div class="establecimiento-info" style="margin-bottom: 15px; font-size: 0.9em; color: #666;">
                                    <?php if ($direccion) : ?>
                                        <p style="margin: 5px 0;"><span class="dashicons dashicons-location" style="font-size: 16px; vertical-align: middle;"></span> <?php echo esc_html($direccion); ?></p>
                                    <?php endif; ?>

                                    <?php if ($telefono) : ?>
                                        <p style="margin: 5px 0;"><span class="dashicons dashicons-phone" style="font-size: 16px; vertical-align: middle;"></span> <?php echo esc_html($telefono); ?></p>
                                    <?php endif; ?>

                                    <?php if ($email) : ?>
                                        <p style="margin: 5px 0;"><span class="dashicons dashicons-email" style="font-size: 16px; vertical-align: middle;"></span> <?php echo esc_html($email); ?></p>
                                    <?php endif; ?>

                                    <?php if ($website) : ?>
                                        <p style="margin: 5px 0;"><span class="dashicons dashicons-admin-links" style="font-size: 16px; vertical-align: middle;"></span> <a href="<?php echo esc_url($website); ?>" target="_blank"><?php esc_html_e('Sitio Web', 'event-show-base'); ?></a></p>
                                    <?php endif; ?>
                                </div>

                                <div class="establecimiento-stats" style="background: #f0f0f1; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center;">
                                    <strong style="font-size: 1.5em; color: #0073aa;"><?php echo esc_html($eventos_count); ?></strong>
                                    <br>
                                    <span style="font-size: 0.85em; color: #666;">
                                        <?php echo _n('Evento organizado', 'Eventos organizados', $eventos_count, 'event-show-base'); ?>
                                    </span>
                                </div>

                                <div class="card-actions" style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="<?php echo esc_url(get_permalink($establecimiento->ID)); ?>" class="button button-small" target="_blank" style="flex: 1;">
                                        <?php esc_html_e('Ver', 'event-show-base'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(get_edit_post_link($establecimiento->ID)); ?>" class="button button-small" style="flex: 1;">
                                        <?php esc_html_e('Editar', 'event-show-base'); ?>
                                    </a>
                                    <?php if ($eventos_count > 0) : ?>
                                        <a href="<?php echo esc_url(add_query_arg('organizador', $establecimiento->ID, home_url('/eventos/'))); ?>" class="button button-small button-primary" style="flex: 1;">
                                            <?php esc_html_e('Ver Eventos', 'event-show-base'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <style>
                        .establecimiento-card {
                            position: relative;
                        }

                        .status-badge {
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 12px;
                            font-size: 0.85em;
                            font-weight: 500;
                        }

                        .status-published {
                            background: #d4edda;
                            color: #155724;
                        }

                        .status-pending {
                            background: #fff3cd;
                            color: #856404;
                        }

                        .status-draft {
                            background: #d1ecf1;
                            color: #0c5460;
                        }
                    </style>

                <?php else : ?>
                    <div class="dashboard-empty" style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px;">
                        <span class="dashicons dashicons-store" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></span>
                        <h4><?php esc_html_e('No tienes establecimientos aún', 'event-show-base'); ?></h4>
                        <p style="color: #666; margin-bottom: 20px;">
                            <?php esc_html_e('Necesitas crear al menos un establecimiento para poder usarlo como organizador de tus eventos.', 'event-show-base'); ?>
                        </p>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=establecimiento')); ?>" class="button button-primary button-large">
                            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                            <?php esc_html_e('Crear mi Primer Establecimiento', 'event-show-base'); ?>
                        </a>
                        <div style="margin-top: 20px; padding: 15px; background: #fff; border-left: 4px solid #0073aa; text-align: left;">
                            <strong><?php esc_html_e('¿Qué es un establecimiento?', 'event-show-base'); ?></strong>
                            <p style="margin: 10px 0 0 0; color: #666; font-size: 0.95em;">
                                <?php esc_html_e('Un establecimiento representa tu negocio, organización o entidad. Al crear eventos, podrás seleccionar tus establecimientos como organizadores.', 'event-show-base'); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
        </div>
    <?php else :
                // Modo: Taxonomía organizador (fallback cuando no hay plugin de establecimientos)
    ?>
        <div class="dashboard-section-header">
            <h3><?php esc_html_e('Organizadores', 'event-show-base'); ?></h3>
            <button class="button" id="create-organizer-btn"><?php esc_html_e('Crear Nuevo Organizador', 'event-show-base'); ?></button>
        </div>

        <p class="description" style="margin-bottom: 20px;">
            <?php esc_html_e('Gestiona los organizadores que se asignarán a tus eventos.', 'event-show-base'); ?>
        </p>

        <?php
                // Obtener organizadores (taxonomía) - SOLO los del usuario actual
                $organizers = get_terms(array(
                    'taxonomy' => 'organizador',
                    'hide_empty' => false,
                    'meta_query' => array(
                        array(
                            'key' => 'owner_id',
                            'value' => $user_id,
                            'compare' => '='
                        )
                    ),
                ));

                if (!empty($organizers) && !is_wp_error($organizers)) :
        ?>
            <div class="dashboard-grid">
                <?php foreach ($organizers as $organizer) :
                        $logo = get_term_meta($organizer->term_id, 'logo', true);
                        $contact_info = get_term_meta($organizer->term_id, 'contact_info', true);
                        $status = get_term_meta($organizer->term_id, 'status', true);
                        $owner_name = get_term_meta($organizer->term_id, 'owner_name', true);

                        // Estado por defecto si no existe
                        if (empty($status)) {
                            $status = 'approved';
                        }

                        // Etiquetas de estado
                        $status_labels = array(
                            'approved' => array('label' => __('Aprobado', 'event-show-base'), 'class' => 'status-published'),
                            'pending' => array('label' => __('Pendiente Aprobación', 'event-show-base'), 'class' => 'status-pending'),
                            'rejected' => array('label' => __('Rechazado', 'event-show-base'), 'class' => 'status-draft'),
                        );
                        $status_info = isset($status_labels[$status]) ? $status_labels[$status] : array('label' => $status, 'class' => 'status-default');

                        // Contar eventos con este organizador (solo si está aprobado)
                        $eventos_count = 0;
                        if ($status === 'approved') {
                            $events_with_organizer = get_posts(array(
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
                            $eventos_count = count($events_with_organizer);
                        }
                ?>
                    <div class="dashboard-card organizer-card">
                        <?php if ($logo) : ?>
                            <div class="card-image">
                                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($organizer->name); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 15px;">
                            </div>
                        <?php endif; ?>

                        <h4 style="margin-bottom: 10px;">
                            <?php echo esc_html($organizer->name); ?>
                        </h4>

                        <!-- Badge de estado -->
                        <div class="organizer-status" style="margin-bottom: 10px;">
                            <span class="status-badge <?php echo esc_attr($status_info['class']); ?>" style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.85em; font-weight: 500;">
                                <?php echo esc_html($status_info['label']); ?>
                            </span>
                        </div>

                        <?php if ($organizer->description) : ?>
                            <p style="font-size: 0.9em; color: #666; margin-bottom: 15px;">
                                <?php echo esc_html(wp_trim_words($organizer->description, 20)); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($contact_info) : ?>
                            <div class="organizer-contact" style="margin-bottom: 15px; font-size: 0.9em; color: #666;">
                                <span class="dashicons dashicons-email" style="font-size: 16px; vertical-align: middle;"></span>
                                <?php echo esc_html($contact_info); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($status === 'approved') : ?>
                            <div class="organizer-stats" style="background: #f0f0f1; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center;">
                                <strong style="font-size: 1.5em; color: #0073aa;"><?php echo esc_html($eventos_count); ?></strong>
                                <br>
                                <span style="font-size: 0.85em; color: #666;">
                                    <?php echo _n('Evento', 'Eventos', $eventos_count, 'event-show-base'); ?>
                                </span>
                            </div>
                        <?php elseif ($status === 'pending') : ?>
                            <div class="organizer-pending-notice" style="background: #fff3cd; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 0.85em;">
                                <span class="dashicons dashicons-clock" style="font-size: 16px; vertical-align: middle; color: #856404;"></span>
                                <?php esc_html_e('Esperando aprobación del administrador', 'event-show-base'); ?>
                            </div>
                        <?php elseif ($status === 'rejected') : ?>
                            <div class="organizer-rejected-notice" style="background: #f8d7da; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 0.85em;">
                                <span class="dashicons dashicons-dismiss" style="font-size: 16px; vertical-align: middle; color: #721c24;"></span>
                                <?php esc_html_e('Rechazado. Contacta al administrador.', 'event-show-base'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="card-actions" style="display: flex; gap: 5px;">
                            <button class="button button-small edit-organizer-btn" data-organizer-id="<?php echo esc_attr($organizer->term_id); ?>" style="flex: 1;">
                                <?php esc_html_e('Editar', 'event-show-base'); ?>
                            </button>
                            <?php if ($status === 'approved' && $eventos_count > 0) : ?>
                                <a href="<?php echo esc_url(get_term_link($organizer)); ?>" class="button button-small button-primary" target="_blank" style="flex: 1;">
                                    <?php esc_html_e('Ver Eventos', 'event-show-base'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else : ?>
            <div class="dashboard-empty" style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px;">
                <span class="dashicons dashicons-groups" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></span>
                <h4><?php esc_html_e('No tienes organizadores aún', 'event-show-base'); ?></h4>
                <p style="color: #666; margin-bottom: 20px;">
                    <?php esc_html_e('Los organizadores te ayudan a identificar quién organiza cada evento.', 'event-show-base'); ?>
                </p>
                <button class="button button-primary button-large" id="create-organizer-btn-empty">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e('Crear Primer Organizador', 'event-show-base'); ?>
                </button>
            </div>
        <?php endif; ?>

        <!-- Modal crear/editar organizador -->
        <div id="organizer-modal" class="event-modal-overlay" style="display:none;">
            <div class="event-modal-window">
                <span class="modal-close">&times;</span>
                <h2 id="organizer-modal-title"><?php esc_html_e('Crear Organizador', 'event-show-base'); ?></h2>
                <form id="organizer-form">
                    <input type="hidden" id="organizer_id" name="organizer_id" value="">
                    <div class="form-messages"></div>

                    <div class="form-group">
                        <label><?php esc_html_e('Nombre', 'event-show-base'); ?> *</label>
                        <input type="text" id="organizer_name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Descripción', 'event-show-base'); ?></label>
                        <textarea id="organizer_description" name="description" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Información de Contacto', 'event-show-base'); ?></label>
                        <input type="text" id="organizer_contact" name="contact_info" class="form-control" placeholder="Email, teléfono, etc.">
                    </div>

                    <div class="form-group">
                        <label><?php esc_html_e('Logo/Imagen', 'event-show-base'); ?></label>
                        <input type="url" id="organizer_logo" name="logo" class="form-control" placeholder="URL de la imagen">
                    </div>

                    <button type="submit" class="button button-primary"><?php esc_html_e('Guardar', 'event-show-base'); ?></button>
                </form>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Abrir modal para crear organizador
                $('#create-organizer-btn, #create-organizer-btn-empty').on('click', function(e) {
                    e.preventDefault();
                    $('#organizer-modal-title').text('<?php esc_html_e('Crear Organizador', 'event-show-base'); ?>');
                    $('#organizer-form')[0].reset();
                    $('#organizer_id').val('');
                    $('#organizer-modal').fadeIn();
                });

                // Abrir modal para editar organizador
                $('.edit-organizer-btn').on('click', function(e) {
                    e.preventDefault();
                    var organizerId = $(this).data('organizer-id');

                    // Cargar datos del organizador vía AJAX
                    $.post(eventShowData.ajaxurl, {
                        action: 'get_organizer_data',
                        nonce: eventShowData.nonce,
                        organizer_id: organizerId
                    }, function(response) {
                        if (response.success) {
                            $('#organizer-modal-title').text('<?php esc_html_e('Editar Organizador', 'event-show-base'); ?>');
                            $('#organizer_id').val(response.data.id);
                            $('#organizer_name').val(response.data.name);
                            $('#organizer_description').val(response.data.description);
                            $('#organizer_contact').val(response.data.contact_info);
                            $('#organizer_logo').val(response.data.logo);
                            $('#organizer-modal').fadeIn();
                        }
                    });
                });

                // Guardar organizador
                $('#organizer-form').on('submit', function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    var $button = $form.find('button[type="submit"]');

                    $button.prop('disabled', true).text('<?php esc_html_e('Guardando...', 'event-show-base'); ?>');

                    $.post(eventShowData.ajaxurl, {
                        action: 'save_organizer',
                        nonce: eventShowData.nonce,
                        organizer_id: $('#organizer_id').val(),
                        name: $('#organizer_name').val(),
                        description: $('#organizer_description').val(),
                        contact_info: $('#organizer_contact').val(),
                        logo: $('#organizer_logo').val()
                    }, function(response) {
                        if (response.success) {
                            // Mostrar mensaje según si necesita aprobación
                            if (response.data.needs_approval) {
                                alert(response.data.message + '\\n\\n<?php esc_html_e('Una vez aprobado, podrás usarlo en tus eventos.', 'event-show-base'); ?>');
                            } else {
                                alert(response.data.message);
                            }
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php esc_html_e('Error al guardar', 'event-show-base'); ?>');
                            $button.prop('disabled', false).text('<?php esc_html_e('Guardar', 'event-show-base'); ?>');
                        }
                    }).fail(function() {
                        alert('<?php esc_html_e('Error de conexión', 'event-show-base'); ?>');
                        $button.prop('disabled', false).text('<?php esc_html_e('Guardar', 'event-show-base'); ?>');
                    });
                });
            });
        </script>

    <?php endif; // Fin modo taxonomía organizador 
    ?>
    </div>

    <!-- Lugares -->
    <div class="dashboard-tab-content" id="tab-locations">
        <div class="dashboard-section-header">
            <h3><?php esc_html_e('Mis Lugares', 'event-show-base'); ?></h3>
            <button class="button create-location-link"><?php esc_html_e('Crear Nuevo Lugar', 'event-show-base'); ?></button>
        </div>

        <?php
        $locations = get_terms(array(
            'taxonomy' => 'lugar',
            'hide_empty' => false,
        ));

        if (! empty($locations)) :
        ?>
            <div class="dashboard-grid">
                <?php foreach ($locations as $loc) :
                    $address = get_term_meta($loc->term_id, 'address', true);
                    $map_url = get_term_meta($loc->term_id, 'map_url', true);
                    $image = get_term_meta($loc->term_id, 'image', true);
                ?>
                    <div class="dashboard-card">
                        <?php if ($image) : ?>
                            <div class="card-image">
                                <?php echo wp_get_attachment_image($image, 'thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        <h4><?php echo esc_html($loc->name); ?></h4>
                        <?php if ($address) : ?>
                            <p><span class="dashicons dashicons-location"></span> <?php echo esc_html($address); ?></p>
                        <?php endif; ?>
                        <?php if ($map_url) : ?>
                            <p><a href="<?php echo esc_url($map_url); ?>" target="_blank"><?php esc_html_e('Ver en Google Maps', 'event-show-base'); ?></a></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="dashboard-empty">
                <p><?php esc_html_e('No hay lugares creados', 'event-show-base'); ?></p>
                <button class="button create-location-link"><?php esc_html_e('Crear Lugar', 'event-show-base'); ?></button>
            </div>
        <?php endif; ?>

        <!-- Modal crear lugar -->
        <div id="create-location-modal" class="event-modal-overlay" style="display:none;">
            <div class="event-modal-window">
                <span class="modal-close">&times;</span>
                <h3><?php esc_html_e('Crear Nuevo Lugar', 'event-show-base'); ?></h3>
                <form id="create-location-form">
                    <div class="form-group">
                        <label><?php esc_html_e('Nombre', 'event-show-base'); ?> *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php esc_html_e('Dirección', 'event-show-base'); ?></label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php esc_html_e('URL de Google Maps', 'event-show-base'); ?></label>
                        <input type="url" name="map_url" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?php esc_html_e('Imagen / Foto', 'event-show-base'); ?></label>
                        <input type="file" name="image_file" id="create_loc_image_file" accept="image/*" class="form-control">
                        <img id="create_loc_image_preview" src="" alt="" style="max-width:60px; max-height:60px; display:none; border-radius:8px; background:#f4f4f4; margin-top:10px;">
                    </div>
                    <button type="submit" class="button"><?php esc_html_e('Crear Lugar', 'event-show-base'); ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mi Perfil -->
    <div class="dashboard-tab-content" id="tab-profile">
        <div class="dashboard-section-header">
            <h3><?php esc_html_e('Mi Perfil', 'event-show-base'); ?></h3>
        </div>

        <div class="dashboard-profile">
            <p><strong><?php esc_html_e('Nombre:', 'event-show-base'); ?></strong> <?php echo esc_html($current_user->display_name); ?></p>
            <p><strong><?php esc_html_e('Email:', 'event-show-base'); ?></strong> <?php echo esc_html($current_user->user_email); ?></p>
            <p><strong><?php esc_html_e('Usuario:', 'event-show-base'); ?></strong> <?php echo esc_html($current_user->user_login); ?></p>
            <p>
                <a href="<?php echo esc_url(get_edit_profile_url($user_id)); ?>" class="button">
                    <?php esc_html_e('Editar Perfil', 'event-show-base'); ?>
                </a>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="button">
                    <?php esc_html_e('Cerrar Sesión', 'event-show-base'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

<!-- Modal de asistentes -->
<div id="attendees-modal" class="event-modal-overlay" style="display:none;">
    <div class="event-modal-window">
        <span class="modal-close">&times;</span>
        <h2><?php esc_html_e('Asistentes del Evento', 'event-show-base'); ?></h2>
        <h3 id="attendees-event-title"></h3>
        <div class="attendees-modal-actions">
            <button type="button" id="export-attendees-csv" class="button" style="margin-bottom: 15px;">
                <?php esc_html_e('Descargar CSV', 'event-show-base'); ?>
            </button>
        </div>
        <div id="attendees-list-container">
            <p><?php esc_html_e('Cargando...', 'event-show-base'); ?></p>
        </div>
    </div>
</div>
</div>