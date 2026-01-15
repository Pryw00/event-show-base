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
                    <?php esc_html_e('Fecha del Evento', 'event-show-base'); ?> *
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

        <div class="form-group">
            <label for="event_organizer">
                <?php esc_html_e('Organizador', 'event-show-base'); ?> *
            </label>
            <select id="event_organizer" name="organizer" class="form-control" required>
                <option value=""><?php esc_html_e('Seleccionar organizador', 'event-show-base'); ?></option>
                <?php
                $organizers = get_terms(array(
                    'taxonomy' => 'organizador',
                    'hide_empty' => false,
                ));
                foreach ($organizers as $org) :
                ?>
                    <option value="<?php echo esc_attr($org->term_id); ?>"><?php echo esc_html($org->name); ?></option>
                <?php
                endforeach;
                ?>
            </select>
            <p class="description">
                <?php esc_html_e('¿No encuentras tu organizador?', 'event-show-base'); ?>
                <a href="#" class="create-organizer-link"><?php esc_html_e('Crear nuevo', 'event-show-base'); ?></a>
            </p>
        </div>

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

<!-- Modal para crear lugar -->
<div id="create-location-modal" class="event-modal" style="display: none;">
    <div class="modal-content">
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
            <button type="submit" class="button"><?php esc_html_e('Crear', 'event-show-base'); ?></button>
        </form>
    </div>
</div>