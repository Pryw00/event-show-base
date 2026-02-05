<?php

/**
 * Formulario de envío de eventos por usuarios
 *
 * @package Event_Show
 */

$current_user = wp_get_current_user();
?>

<div class="event-submit-form-wrapper">
    <h2><?php esc_html_e('Enviar Nuevo Evento', 'event-show-base'); ?></h2>
    <p class="form-intro"><?php esc_html_e('Completa el formulario para enviar tu evento. Será revisado por un administrador antes de publicarse.', 'event-show-base'); ?></p>

    <form class="event-submit-form" id="event-submit-form">
        <div class="form-messages"></div>

        <div class="form-group">
            <label for="event_title">
                <?php esc_html_e('Título del Evento', 'event-show-base'); ?> *
            </label>
            <input type="text"
                id="event_title"
                name="title"
                class="form-control"
                required>
        </div>

        <div class="form-group">
            <label for="event_description">
                <?php esc_html_e('Descripción del Evento', 'event-show-base'); ?> *
            </label>
            <textarea
                id="event_description"
                name="description"
                class="form-control"
                rows="6"
                required></textarea>
            <p class="description"><?php esc_html_e('Describe tu evento en detalle', 'event-show-base'); ?></p>
        </div>


        <div class="form-row">
            <div class="form-group form-col-half">
                <label for="event_date_submit">
                    <?php esc_html_e('Fecha de Inicio', 'event-show-base'); ?> *
                </label>
                <input type="text"
                    id="event_date_submit"
                    name="event_date"
                    class="form-control event-datepicker"
                    placeholder="dd/mm/yyyy"
                    required>
            </div>
            <div class="form-group form-col-half">
                <label for="event_time_submit">
                    <?php esc_html_e('Hora de Inicio', 'event-show-base'); ?> *
                </label>
                <input type="time"
                    id="event_time_submit"
                    name="event_time"
                    class="form-control"
                    required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group form-col-half">
                <label for="event_date_end">
                    <?php esc_html_e('Fecha de Fin', 'event-show-base'); ?>
                </label>
                <input type="text"
                    id="event_date_end"
                    name="event_date_end"
                    class="form-control event-datepicker"
                    placeholder="dd/mm/yyyy">
            </div>
            <div class="form-group form-col-half">
                <label for="event_time_end">
                    <?php esc_html_e('Hora de Fin', 'event-show-base'); ?>
                </label>
                <input type="time"
                    id="event_time_end"
                    name="event_time_end"
                    class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="event_banner">
                <?php esc_html_e('Imagen Banner (obligatoria)', 'event-show-base'); ?> *
            </label>
            <input type="file" id="event_banner" name="event_banner" class="form-control" accept="image/*" required>
            <img id="event_banner_preview" src="" alt="" style="max-width:120px; max-height:60px; display:none; margin-top:10px; border-radius:8px; background:#f4f4f4;">
        </div>
        <div class="form-group">
            <label for="event_thumbnail">
                <?php esc_html_e('Miniatura (obligatoria)', 'event-show-base'); ?> *
            </label>
            <input type="file" id="event_thumbnail" name="event_thumbnail" class="form-control" accept="image/*" required>
            <img id="event_thumbnail_preview" src="" alt="" style="max-width:60px; max-height:60px; display:none; margin-top:10px; border-radius:8px; background:#f4f4f4;">
        </div>

        <div class="form-group">
            <label for="event_category">
                <?php esc_html_e('Categoría', 'event-show-base'); ?> *
            </label>
            <select id="event_category" name="category" class="form-control" required>
                <option value=""><?php esc_html_e('Seleccionar categoría', 'event-show-base'); ?></option>
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'categoria_evento',
                    'hide_empty' => false,
                ));
                foreach ($categories as $cat) :
                ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                <?php
                endforeach;
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="event_age_rating">
                <?php esc_html_e('Clasificación de Edad', 'event-show-base'); ?>
            </label>
            <select id="event_age_rating" name="age_rating" class="form-control">
                <option value=""><?php esc_html_e('Seleccionar clasificación', 'event-show-base'); ?></option>
                <?php
                $age_ratings = get_terms(array(
                    'taxonomy' => 'clasificacion_edad',
                    'hide_empty' => false,
                ));
                foreach ($age_ratings as $rating) :
                ?>
                    <option value="<?php echo esc_attr($rating->term_id); ?>"><?php echo esc_html($rating->name); ?></option>
                <?php
                endforeach;
                ?>
            </select>
        </div>

        <?php
        // Verificar si la integración con establecimientos está disponible
        $has_establishments = class_exists('Event_Show_Integrations') &&
            Event_Show_Integrations::has_establishments_integration();

        if ($has_establishments) :
            // MODO: Establecimientos como organizadores
            // Obtener establecimientos del usuario actual
            $current_user_id = get_current_user_id();
            $query_args = array(
                'post_type'      => 'establecimiento',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            );

            // Si no es administrador, filtrar por autor
            if (!current_user_can('manage_options')) {
                $query_args['author'] = $current_user_id;
            }

            $user_establecimientos = get_posts($query_args);
        ?>

            <?php if (!empty($user_establecimientos)) : ?>
                <div class="form-group">
                    <label for="event_organizer_id">
                        <?php esc_html_e('Establecimiento Organizador', 'event-show-base'); ?> *
                    </label>
                    <select id="event_organizer_id" name="organizer_id" class="form-control" required>
                        <option value=""><?php esc_html_e('-- Seleccionar establecimiento --', 'event-show-base'); ?></option>
                        <?php foreach ($user_establecimientos as $establecimiento) : ?>
                            <option value="<?php echo esc_attr($establecimiento->ID); ?>">
                                <?php echo esc_html($establecimiento->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php
                        if (current_user_can('manage_options')) {
                            esc_html_e('Selecciona el establecimiento que organiza este evento.', 'event-show-base');
                        } else {
                            esc_html_e('Solo puedes seleccionar tus propios establecimientos. Si no tienes uno, ve a tu Dashboard → pestaña "Organizadores" para ver tus establecimientos o crear uno nuevo.', 'event-show-base');
                        }
                        ?>
                    </p>
                </div>
            <?php else : ?>
                <div class="form-group">
                    <div class="alert alert-warning" style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin-bottom: 20px;">
                        <strong><?php esc_html_e('¡Atención!', 'event-show-base'); ?></strong>
                        <p><?php esc_html_e('No tienes establecimientos disponibles. Debes crear al menos un establecimiento para poder publicar eventos.', 'event-show-base'); ?></p>
                        <p style="margin-top: 10px;">
                            <strong><?php esc_html_e('Para crear un establecimiento:', 'event-show-base'); ?></strong><br>
                            <?php esc_html_e('Ve a tu Dashboard → pestaña "Organizadores" donde podrás gestionar y crear tus establecimientos.', 'event-show-base'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        <?php else :
            // MODO: Taxonomía organizador (fallback)
            // Obtener solo organizadores aprobados y del usuario actual
            $current_user_id = get_current_user_id();
        ?>
            <div class="form-group">
                <label for="event_organizer">
                    <?php esc_html_e('Organizador', 'event-show-base'); ?>
                </label>
                <select id="event_organizer" name="organizer" class="form-control">
                    <option value=""><?php esc_html_e('-- Seleccionar organizador --', 'event-show-base'); ?></option>
                    <?php
                    // Obtener solo organizadores del usuario actual que estén aprobados
                    $organizers = get_terms(array(
                        'taxonomy' => 'organizador',
                        'hide_empty' => false,
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key' => 'owner_id',
                                'value' => $current_user_id,
                                'compare' => '='
                            ),
                            array(
                                'key' => 'status',
                                'value' => 'approved',
                                'compare' => '='
                            )
                        ),
                    ));

                    foreach ($organizers as $org) :
                    ?>
                        <option value="<?php echo esc_attr($org->term_id); ?>"><?php echo esc_html($org->name); ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Solo puedes usar organizadores que te pertenecen y estén aprobados.', 'event-show-base'); ?>
                    <a href="#" class="create-organizer-link"><?php esc_html_e('Crear nuevo', 'event-show-base'); ?></a>
                </p>

                <?php if (empty($organizers)) : ?>
                    <div class="alert alert-info" style="padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin-top: 10px;">
                        <p style="margin: 0;">
                            <span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
                            <?php esc_html_e('No tienes organizadores aprobados. Crea uno nuevo y espera la aprobación del administrador.', 'event-show-base'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="event_location">
                <?php esc_html_e('Lugar', 'event-show-base'); ?> *
            </label>
            <select id="event_location" name="location" class="form-control" required>
                <option value=""><?php esc_html_e('Seleccionar lugar', 'event-show-base'); ?></option>
                <?php
                $locations = get_terms(array(
                    'taxonomy' => 'lugar',
                    'hide_empty' => false,
                ));
                foreach ($locations as $loc) :
                ?>
                    <option value="<?php echo esc_attr($loc->term_id); ?>"><?php echo esc_html($loc->name); ?></option>
                <?php
                endforeach;
                ?>
            </select>
            <p class="description">
                <?php esc_html_e('¿No encuentras el lugar?', 'event-show-base'); ?>
                <a href="#" class="create-location-link"><?php esc_html_e('Crear nuevo', 'event-show-base'); ?></a>
            </p>
        </div>

        <div class="form-group">
            <button type="submit" class="event-submit-btn">
                <span class="btn-text"><?php esc_html_e('Enviar Evento', 'event-show-base'); ?></span>
                <span class="btn-loading" style="display: none;"><?php esc_html_e('Enviando...', 'event-show-base'); ?></span>
            </button>
        </div>

        <input type="hidden" name="action" value="event_show_submit_event">
        <?php wp_nonce_field('event_show_nonce', 'nonce'); ?>
    </form>
</div>

<!-- Modal para crear organizador -->
<div id="create-organizer-modal" class="event-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3><?php esc_html_e('Crear Nuevo Organizador', 'event-show-base'); ?></h3>
        <form id="create-organizer-form">
            <div class="form-group">
                <label><?php esc_html_e('Nombre', 'event-show-base'); ?> *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?php esc_html_e('Email', 'event-show-base'); ?></label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="form-group">
                <label><?php esc_html_e('Teléfono', 'event-show-base'); ?></label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label><?php esc_html_e('Sitio Web', 'event-show-base'); ?></label>
                <input type="url" name="website" class="form-control">
            </div>
            <button type="submit" class="button"><?php esc_html_e('Crear', 'event-show-base'); ?></button>
        </form>
    </div>
</div>