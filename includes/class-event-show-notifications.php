<?php

/**
 * Sistema de Notificaciones por Email
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar notificaciones
 */
class Event_Show_Notifications
{
    /**
     * Notificar a asistentes si se modifican datos clave del evento
     */
    public static function maybe_notify_event_update($event_id, $old_data, $new_data)
    {
        // Detectar cambios clave
        $changed = false;
        $campos = ['post_title', '_event_date', '_event_time', '_event_end_date', '_event_end_time'];
        foreach ($campos as $campo) {
            if ((isset($old_data[$campo]) ? $old_data[$campo] : '') !== (isset($new_data[$campo]) ? $new_data[$campo] : '')) {
                $changed = true;
            }
        }
        // Lugar
        $old_lugar = isset($old_data['lugar']) ? $old_data['lugar'] : '';
        $new_lugar = isset($new_data['lugar']) ? $new_data['lugar'] : '';
        if ($old_lugar !== $new_lugar) $changed = true;
        // Categoría
        $old_cat = isset($old_data['categoria_evento']) ? $old_data['categoria_evento'] : '';
        $new_cat = isset($new_data['categoria_evento']) ? $new_data['categoria_evento'] : '';
        if ($old_cat !== $new_cat) $changed = true;
        // Clasificación de edad
        $old_clas = isset($old_data['clasificacion_edad']) ? $old_data['clasificacion_edad'] : '';
        $new_clas = isset($new_data['clasificacion_edad']) ? $new_data['clasificacion_edad'] : '';
        if ($old_clas !== $new_clas) $changed = true;
        if (! $changed) return;
        self::send_event_update_notification($event_id);
    }

    /**
     * Enviar email a asistentes sobre actualización de evento
     */
    public static function send_event_update_notification($event_id)
    {
        if (! get_option('event_show_email_notifications_enabled', true)) return;
        $attendees = Event_Show_Attendees::get_attendees($event_id);
        if (empty($attendees)) return;
        $event = get_post($event_id);
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_link = get_permalink($event_id);
        $lugar = Event_Show_Helpers::get_event_location($event_id);
        $lugar_nombre = $lugar ? $lugar['name'] : '';
        $categorias = wp_get_post_terms($event_id, 'categoria_evento');
        $categoria_nombre = ! empty($categorias) ? $categorias[0]->name : '';
        $clasificaciones = wp_get_post_terms($event_id, 'clasificacion_edad');
        $clasificacion_nombre = ! empty($clasificaciones) ? $clasificaciones[0]->name : '';
        $subject = sprintf(__('Actualización importante en el evento: %s', 'event-show-base'), $event->post_title);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $template = get_option('event_show_email_template_update');
        foreach ($attendees as $attendee) {
            $vars = array(
                '{{Titulo evento}}' => $event->post_title,
                '{{Fecha}}' => $event_date,
                '{{Hora}}' => $event_time,
                '{{Nombre usuario}}' => $attendee['name'],
                '{{Email usuario}}' => $attendee['email'],
                '{{Enlace evento}}' => $event_link,
                '{{Organizador}}' => '',
                '{{Lugar}}' => $lugar_nombre,
                '{{Categoría}}' => $categoria_nombre,
                '{{Clasificación de edad}}' => $clasificacion_nombre,
            );
            $message = $template;
            foreach ($vars as $k => $v) {
                $message = str_replace($k, $v, $message);
            }
            // Remitente personalizado
            $from_name = get_option('event_show_email_from_name', 'Event Show');
            $from_email = get_option('event_show_email_from_address', get_option('admin_email'));
            add_filter('wp_mail_from', function () use ($from_email) {
                return $from_email;
            });
            add_filter('wp_mail_from_name', function () use ($from_name) {
                return $from_name;
            });
            wp_mail($attendee['email'], $subject, $message, $headers);
            remove_all_filters('wp_mail_from');
            remove_all_filters('wp_mail_from_name');
        }
    }


    /**
     * Enviar confirmación al asistente
     *
     * @param int $attendee_id ID del asistente
     * @param int $event_id ID del evento
     */
    public static function send_attendee_confirmation($attendee_id, $event_id)
    {
        if (! get_option('event_show_email_notifications_enabled', true)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'event_show_attendees';
        $attendee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $attendee_id
        ), ARRAY_A);

        if (! $attendee) {
            return;
        }

        $event = get_post($event_id);
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_link = get_permalink($event_id);

        // Obtener lugar
        $lugar = Event_Show_Helpers::get_event_location($event_id);
        $lugar_nombre = $lugar ? $lugar['name'] : '';

        $subject = sprintf(__('¡Confirmación de registro para %s!', 'event-show-base'), $event->post_title);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $template = get_option('event_show_email_template_registration');
        $vars = array(
            '{{Titulo evento}}' => $event->post_title,
            '{{Fecha}}' => $event_date,
            '{{Hora}}' => $event_time,
            '{{Nombre usuario}}' => $attendee['name'],
            '{{Email usuario}}' => $attendee['email'],
            '{{Enlace evento}}' => $event_link,
            '{{Organizador}}' => '',
            '{{Lugar}}' => $lugar_nombre,
        );
        $message = $template;
        foreach ($vars as $k => $v) {
            $message = str_replace($k, $v, $message);
        }
        $from_name = get_option('event_show_email_from_name', 'Event Show');
        $from_email = get_option('event_show_email_from_address', get_option('admin_email'));
        add_filter('wp_mail_from', function () use ($from_email) {
            return $from_email;
        });
        add_filter('wp_mail_from_name', function () use ($from_name) {
            return $from_name;
        });
        wp_mail($attendee['email'], $subject, $message, $headers);
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
    }

    /**
     * Enviar notificación al admin de nuevo registro
     *
     * @param int $attendee_id ID del asistente
     * @param int $event_id ID del evento
     */
    public static function send_admin_new_registration($attendee_id, $event_id)
    {
        if (! get_option('event_show_email_notifications_enabled', true)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'event_show_attendees';
        $attendee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $attendee_id
        ), ARRAY_A);

        if (! $attendee) {
            return;
        }

        $event = get_post($event_id);
        $admin_email = get_option('event_show_admin_email', get_option('admin_email'));

        $subject = sprintf(
            __('Nuevo registro para el evento: %s', 'event-show-base'),
            $event->post_title
        );

        $message = self::get_email_template('admin_new_registration', array(
            'event_title' => $event->post_title,
            'attendee_name' => $attendee['name'],
            'attendee_email' => $attendee['email'],
            'attendee_phone' => $attendee['phone'],
            'num_attendees' => $attendee['num_attendees'],
            'edit_link' => admin_url('post.php?post=' . $event_id . '&action=edit'),
        ));

        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Enviar recordatorio de evento
     *
     * @param int $event_id ID del evento
     */
    public static function send_event_reminder($event_id)
    {
        if (! get_option('event_show_email_notifications_enabled', true)) {
            return;
        }

        $attendees = Event_Show_Attendees::get_attendees($event_id);

        if (empty($attendees)) {
            return;
        }

        $event = get_post($event_id);
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_link = get_permalink($event_id);

        // Obtener lugar
        $lugar = Event_Show_Helpers::get_event_location($event_id);
        $lugar_nombre = $lugar ? $lugar['name'] : '';

        $subject = sprintf(__('Recordatorio: %s es mañana', 'event-show-base'), $event->post_title);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $template = get_option('event_show_email_template_reminder');
        foreach ($attendees as $attendee) {
            $vars = array(
                '{{Titulo evento}}' => $event->post_title,
                '{{Fecha}}' => $event_date,
                '{{Hora}}' => $event_time,
                '{{Nombre usuario}}' => $attendee['name'],
                '{{Email usuario}}' => $attendee['email'],
                '{{Enlace evento}}' => $event_link,
                '{{Organizador}}' => '',
                '{{Lugar}}' => $lugar_nombre,
            );
            $message = $template;
            foreach ($vars as $k => $v) {
                $message = str_replace($k, $v, $message);
            }
            $from_name = get_option('event_show_email_from_name', 'Event Show');
            $from_email = get_option('event_show_email_from_address', get_option('admin_email'));
            add_filter('wp_mail_from', function () use ($from_email) {
                return $from_email;
            });
            add_filter('wp_mail_from_name', function () use ($from_name) {
                return $from_name;
            });
            wp_mail($attendee['email'], $subject, $message, $headers);
            remove_all_filters('wp_mail_from');
            remove_all_filters('wp_mail_from_name');
        }
    }

    /**
     * Enviar notificación al admin de nuevo evento enviado por usuario
     *
     * @param int $event_id ID del evento
     */
    public static function send_admin_new_event_submission($event_id)
    {
        if (! get_option('event_show_email_notifications_enabled', true)) {
            return;
        }

        $event = get_post($event_id);
        $author = get_user_by('id', $event->post_author);
        $admin_email = get_option('event_show_admin_email', get_option('admin_email'));

        $subject = sprintf(
            __('Nuevo evento pendiente de aprobación: %s', 'event-show-base'),
            $event->post_title
        );

        $message = self::get_email_template('admin_new_event', array(
            'event_title' => $event->post_title,
            'author_name' => $author->display_name,
            'author_email' => $author->user_email,
            'edit_link' => admin_url('post.php?post=' . $event_id . '&action=edit'),
        ));

        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Obtener plantilla de email
     *
     * @param string $template Nombre de la plantilla
     * @param array  $data Datos para la plantilla
     * @return string
     */
    private static function get_email_template($template, $data)
    {
        $templates = array(
            'attendee_confirmation' => '
				<html>
				<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
					<div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
						<h2 style="color: #2c3e50;">¡Hola {attendee_name}!</h2>
						<p>Tu registro para el evento <strong>{event_title}</strong> ha sido confirmado.</p>
						<div style="background-color: #fff; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;">
							<p><strong>Fecha:</strong> {event_date}</p>
							<p><strong>Hora:</strong> {event_time}</p>
							<p><strong>Lugar:</strong> {event_location}</p>
							<p><strong>Número de asistentes:</strong> {num_attendees}</p>
						</div>
						<p>Puedes ver más detalles del evento aquí:</p>
						<p><a href="{event_link}" style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: #fff; text-decoration: none; border-radius: 5px;">Ver Evento</a></p>
						<p style="margin-top: 30px; color: #7f8c8d; font-size: 12px;">¡Te esperamos!</p>
					</div>
				</body>
				</html>
			',
            'admin_new_registration' => '
				<html>
				<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
					<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
						<h2 style="color: #2c3e50;">Nuevo registro de asistente</h2>
						<p>Se ha registrado un nuevo asistente para el evento <strong>{event_title}</strong>:</p>
						<ul>
							<li><strong>Nombre:</strong> {attendee_name}</li>
							<li><strong>Email:</strong> {attendee_email}</li>
							<li><strong>Teléfono:</strong> {attendee_phone}</li>
							<li><strong>Cantidad:</strong> {num_attendees}</li>
						</ul>
						<p><a href="{edit_link}">Editar evento y ver todos los asistentes</a></p>
					</div>
				</body>
				</html>
			',
            'event_reminder' => '
				<html>
				<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
					<div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff3cd;">
						<h2 style="color: #856404;">¡Recordatorio! {event_title}</h2>
						<p>Hola {attendee_name},</p>
						<p>Te recordamos que mañana es el evento <strong>{event_title}</strong>:</p>
						<div style="background-color: #fff; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
							<p><strong>Fecha:</strong> {event_date}</p>
							<p><strong>Hora:</strong> {event_time}</p>
							<p><strong>Lugar:</strong> {event_location}</p>
						</div>
						<p><a href="{event_link}" style="display: inline-block; padding: 10px 20px; background-color: #ffc107; color: #000; text-decoration: none; border-radius: 5px;">Ver Detalles</a></p>
						<p>¡Nos vemos pronto!</p>
					</div>
				</body>
				</html>
			',
            'admin_new_event' => '
				<html>
				<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
					<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
						<h2 style="color: #2c3e50;">Nuevo evento pendiente de aprobación</h2>
						<p>El usuario <strong>{author_name}</strong> ({author_email}) ha enviado un nuevo evento:</p>
						<p><strong>Título:</strong> {event_title}</p>
						<p><a href="{edit_link}" style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: #fff; text-decoration: none; border-radius: 5px;">Revisar y Aprobar</a></p>
					</div>
				</body>
				</html>
			',
        );

        if (! isset($templates[$template])) {
            return '';
        }

        $message = $templates[$template];

        // Reemplazar variables
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }
}
