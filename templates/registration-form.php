<?php

/**
 * Formulario de registro de asistentes
 *
 * @package Event_Show
 */

// Variables disponibles: $event_id
?>

<div class="event-registration-form-wrapper">
    <form class="event-registration-form" id="event-registration-form-<?php echo esc_attr($event_id); ?>" data-event-id="<?php echo esc_attr($event_id); ?>">
        <div class="form-messages"></div>

        <div class="form-group">
            <label for="attendee_name_<?php echo esc_attr($event_id); ?>">
                <?php esc_html_e('Nombre completo', 'event-show-base'); ?> *
            </label>
            <input type="text"
                id="attendee_name_<?php echo esc_attr($event_id); ?>"
                name="name"
                class="form-control"
                required>
        </div>

        <div class="form-group">
            <label for="attendee_email_<?php echo esc_attr($event_id); ?>">
                <?php esc_html_e('Correo electrónico', 'event-show-base'); ?> *
            </label>
            <input type="email"
                id="attendee_email_<?php echo esc_attr($event_id); ?>"
                name="email"
                class="form-control"
                required>
        </div>

        <div class="form-group">
            <label for="attendee_phone_<?php echo esc_attr($event_id); ?>">
                <?php esc_html_e('Teléfono', 'event-show-base'); ?>
            </label>
            <input type="tel"
                id="attendee_phone_<?php echo esc_attr($event_id); ?>"
                name="phone"
                class="form-control">
        </div>

        <div class="form-group">
            <label for="num_attendees_<?php echo esc_attr($event_id); ?>">
                <?php esc_html_e('Número de asistentes', 'event-show-base'); ?> *
            </label>
            <input type="number"
                id="num_attendees_<?php echo esc_attr($event_id); ?>"
                name="num_attendees"
                class="form-control"
                min="1"
                max="10"
                value="1"
                required>
        </div>

        <div class="form-group form-checkbox">
            <label>
                <input type="checkbox"
                    id="accepted_terms_<?php echo esc_attr($event_id); ?>"
                    name="accepted_terms"
                    required>
                <?php esc_html_e('Acepto los términos y condiciones y la política de privacidad', 'event-show-base'); ?> *
            </label>
        </div>

        <div class="form-group">
            <button type="submit" class="event-register-btn">
                <span class="btn-text"><?php esc_html_e('+ Solicitar', 'event-show-base'); ?></span>
                <span class="btn-loading" style="display: none;"><?php esc_html_e('Procesando...', 'event-show-base'); ?></span>
            </button>
        </div>

        <input type="hidden" name="action" value="event_show_register_attendee">
        <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
        <?php wp_nonce_field('event_show_nonce', 'nonce'); ?>
    </form>
</div>