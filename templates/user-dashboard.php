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
                                    <?php echo esc_html(Event_Show_Helpers::format_datetime($event_date, $event_time)); ?>
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
            <div class="dashboard-section-header">
                <h3><?php esc_html_e('Mis Fichas de Organizador', 'event-show-base'); ?></h3>
            </div>
            <?php
            $user_organizadores = get_user_meta($user_id, 'organizador_ids', true);
            if (!is_array($user_organizadores)) $user_organizadores = array();
            if (count($user_organizadores) > 0) {
                $terms = get_terms(array(
                    'taxonomy' => 'organizador',
                    'hide_empty' => false,
                    'include' => $user_organizadores,
                ));
            ?>
                <div class="dashboard-grid">
                <?php foreach ($terms as $org) {
                    $phone = get_term_meta($org->term_id, 'phone', true);
                    $email = get_term_meta($org->term_id, 'email', true);
                    $website = get_term_meta($org->term_id, 'website', true);
                    $image = get_term_meta($org->term_id, 'image', true);
            ?>
                    <div class="dashboard-card">
                        <?php if ($image) : ?>
                            <div class="card-image">
                                <?php echo wp_get_attachment_image($image, 'thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        <h4><?php echo esc_html($org->name); ?></h4>
                        <?php if ($email) : ?>
                            <p><span class="dashicons dashicons-email"></span> <?php echo esc_html($email); ?></p>
                        <?php endif; ?>
                        <?php if ($phone) : ?>
                            <p><span class="dashicons dashicons-phone"></span> <?php echo esc_html($phone); ?></p>
                        <?php endif; ?>
                        <?php if ($website) : ?>
                            <p><span class="dashicons dashicons-admin-links"></span> <a href="<?php echo esc_url($website); ?>" target="_blank"><?php esc_html_e('Web', 'event-show-base'); ?></a></p>
                        <?php endif; ?>
                        <a href="#" class="button edit-organizer-link" data-org-id="<?php echo esc_attr($org->term_id); ?>" data-org-name="<?php echo esc_attr($org->name); ?>" data-org-email="<?php echo esc_attr($email); ?>" data-org-phone="<?php echo esc_attr($phone); ?>" data-org-website="<?php echo esc_attr($website); ?>" data-org-image="<?php echo esc_attr($image); ?>"><?php esc_html_e('Editar Ficha', 'event-show-base'); ?></a>
                    </div>
                <?php } ?>
                </div>
            <?php } else { ?>
                <div class="dashboard-empty">
                    <p><?php esc_html_e('No tienes ficha de organizador asociada. Contacta al administrador.', 'event-show-base'); ?></p>
                </div>
            <?php } ?>

            <!-- Modal editar organizador -->
            <div id="edit-organizer-modal" class="event-modal-overlay" style="display:none;">
                <div class="event-modal-window">
                    <span class="modal-close">&times;</span>
                    <h3><?php esc_html_e('Editar Ficha de Organizador', 'event-show-base'); ?></h3>
                    <form id="edit-organizer-form">
                        <input type="hidden" name="term_id" id="edit_org_id" value="">
                        <div class="form-group">
                            <label><?php esc_html_e('Nombre', 'event-show-base'); ?> *</label>
                            <input type="text" name="name" id="edit_org_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><?php esc_html_e('Email', 'event-show-base'); ?></label>
                            <input type="email" name="email" id="edit_org_email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><?php esc_html_e('Teléfono', 'event-show-base'); ?></label>
                            <input type="text" name="phone" id="edit_org_phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><?php esc_html_e('Sitio Web', 'event-show-base'); ?></label>
                            <input type="url" name="website" id="edit_org_website" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><?php esc_html_e('Logo / Imagen', 'event-show-base'); ?></label>
                            <input type="file" name="image_file" id="edit_org_image_file" accept="image/*" class="form-control">
                            <img id="edit_org_image_preview" src="" alt="" style="max-width:60px; max-height:60px; display:none; border-radius:8px; background:#f4f4f4; margin-top:10px;">
                        </div>
                        <button type="submit" class="button"><?php esc_html_e('Guardar Cambios', 'event-show-base'); ?></button>
                    </form>
                </div>
            </div>
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