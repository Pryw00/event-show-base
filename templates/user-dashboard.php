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
            <div class="dashboard-section-header">
                <h3><?php esc_html_e('Mis Eventos', 'event-show-base'); ?></h3>
                <a href="#" class="button dashboard-new-event"><?php esc_html_e('Crear Nuevo Evento', 'event-show-base'); ?></a>
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
                                    <?php echo esc_html($total_attendees); ?>
                                </td>
                                <td class="dashboard-actions">
                                    <?php if (current_user_can('edit_post', $event_id)) : ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($event_id)); ?>" class="dashboard-action-link">
                                            <?php esc_html_e('Editar', 'event-show-base'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php the_permalink(); ?>" target="_blank" class="dashboard-action-link">
                                        <?php esc_html_e('Ver', 'event-show-base'); ?>
                                    </a>
                                    <?php if ($total_attendees > 0) : ?>
                                        <a href="#" class="dashboard-action-link event-export-attendees" data-event-id="<?php echo esc_attr($event_id); ?>">
                                            <?php esc_html_e('Exportar', 'event-show-base'); ?>
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
                    <a href="#" class="button dashboard-new-event"><?php esc_html_e('Crear Mi Primer Evento', 'event-show-base'); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Organizadores -->
        <div class="dashboard-tab-content" id="tab-organizers">
            <div class="dashboard-section-header">
                <h3><?php esc_html_e('Mis Organizadores', 'event-show-base'); ?></h3>
                <button class="button create-organizer-link"><?php esc_html_e('Crear Nuevo Organizador', 'event-show-base'); ?></button>
            </div>

            <?php
            $organizers = get_terms(array(
                'taxonomy' => 'organizador',
                'hide_empty' => false,
            ));

            if (! empty($organizers)) :
            ?>
                <div class="dashboard-grid">
                    <?php foreach ($organizers as $org) :
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="dashboard-empty">
                    <p><?php esc_html_e('No hay organizadores creados', 'event-show-base'); ?></p>
                    <button class="button create-organizer-link"><?php esc_html_e('Crear Organizador', 'event-show-base'); ?></button>
                </div>
            <?php endif; ?>
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
                    ?>
                        <div class="dashboard-card">
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
</div>

<!-- Modales incluidos (reutilizados del formulario de envío) -->
<?php include EVENT_SHOW_PLUGIN_DIR . 'templates/submit-event-form.php'; ?>