<?php

/**
 * Formulario de envío de eventos para modal (sin IDs duplicados)
 * @package Event_Show
 */
$current_user = wp_get_current_user();
?>
<div class="event-submit-form-wrapper">
    <h2><?php esc_html_e('Enviar Nuevo Evento', 'event-show-base'); ?></h2>
    <p class="form-intro"><?php esc_html_e('Completa el formulario para enviar tu evento. Será revisado por un administrador antes de publicarse.', 'event-show-base'); ?></p>
    <form class="event-submit-form" id="event-submit-form-modal">
        <div class="form-messages"></div>
        <div class="form-group">
            <label for="event_title_modal">
                <?php esc_html_e('Título del Evento', 'event-show-base'); ?> *
            </label>
            <input type="text" id="event_title_modal" name="title" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="event_description_modal">
                <?php esc_html_e('Descripción del Evento', 'event-show-base'); ?> *
            </label>
            <textarea id="event_description_modal" name="description" class="form-control" rows="6" required></textarea>
            <p class="description"><?php esc_html_e('Describe tu evento en detalle', 'event-show-base'); ?></p>
        </div>
        <!-- ...repetir el resto de campos igual, cambiando los IDs por _modal... -->
        <!-- Aquí puedes copiar el resto del formulario y cambiar los IDs para evitar conflictos -->
    </form>
</div>