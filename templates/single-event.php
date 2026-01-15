<?php

/**
 * Template alternativo para single evento (usado en shortcode de contenido)
 *
 * @package Event_Show
 */

$event_id = get_the_ID();
// Este archivo es incluido desde class-event-show-public.php cuando se usa la plantilla por defecto
// El contenido se muestra dentro del content filter, así que usamos buffer
?>
<div class="event-show-content">
    <?php
    // Reusar el template completo
    include EVENT_SHOW_PLUGIN_DIR . 'templates/single-evento.php';
    ?>
</div>