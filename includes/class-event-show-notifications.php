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
     * Procesar múltiples emails del administrador y autores
     * 
     * @param string $admin_emails Emails separados por comas
     * @return array Array de emails válidos
     */
    private static function process_admin_emails($admin_emails)
    {
        $all_emails = array();

        // Agregar emails configurados en ajustes
        if (!empty($admin_emails)) {
            // Separar por comas y limpiar espacios
            $emails = array_map('trim', explode(',', $admin_emails));

            // Filtrar solo emails válidos
            $valid_emails = array_filter($emails, function ($email) {
                return is_email($email);
            });

            $all_emails = $valid_emails;
        }

        // Agregar emails de usuarios con capacidad de editar eventos de otros (autores)
        $authors = get_users(array(
            'capability__in' => array('edit_others_eventos'),
            'fields' => array('user_email'),
        ));

        foreach ($authors as $author) {
            if (is_email($author->user_email) && !in_array($author->user_email, $all_emails)) {
                $all_emails[] = $author->user_email;
            }
        }

        // Si no hay emails válidos, usar el email predeterminado del sitio
        if (empty($all_emails)) {
            return array(get_option('admin_email'));
        }

        return $all_emails;
    }

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
        $admin_emails_string = get_option('event_show_admin_email', get_option('admin_email'));
        $admin_emails = self::process_admin_emails($admin_emails_string);

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

        // Enviar a cada email del administrador
        foreach ($admin_emails as $admin_email) {
            wp_mail($admin_email, $subject, $message, $headers);
        }
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
        $admin_emails_string = get_option('event_show_admin_email', get_option('admin_email'));
        $admin_emails = self::process_admin_emails($admin_emails_string);

        // Obtener el role principal del usuario

        $roles = isset($author->roles) ? $author->roles : array();
        // Obtener roles prioritarios desde la configuración
        $roles_prioritarios_str = get_option('event_show_priority_roles', 'gold,platinum,vip');
        $roles_prioritarios = array_map('trim', explode(',', $roles_prioritarios_str));
        // Detectar si alguno de los roles del usuario es prioritario
        $roles_usuario_prioritarios = array_intersect($roles, $roles_prioritarios);
        $es_urgente = !empty($roles_usuario_prioritarios);
        // Para mostrar en el correo, listar todos los roles del usuario
        $role_name = !empty($roles) ? implode(', ', $roles) : 'usuario';

        $subject = sprintf(
            $es_urgente
                ? __('URGENTE: Nuevo evento de usuario prioritario (%s): %s', 'event-show-base')
                : __('Nuevo evento pendiente de aprobación: %s', 'event-show-base'),
            $role_name,
            $event->post_title
        );

        $message = self::get_email_template('admin_new_event', array(
            'event_title' => $event->post_title,
            'author_name' => $author->display_name,
            'author_email' => $author->user_email,
            'author_role' => ucfirst($role_name),
            'edit_link' => admin_url('post.php?post=' . $event_id . '&action=edit'),
            'urgent_label' => $es_urgente ? __('Prioridad Alta', 'event-show-base') : '',
        ));

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Enviar a cada email del administrador
        foreach ($admin_emails as $admin_email) {
            wp_mail($admin_email, $subject, $message, $headers);
        }
    }

    /**
     * Enviar notificación al usuario de que su evento fue recibido y está en revisión
     *
     * @param int $event_id ID del evento
     */
    public static function send_user_event_submission_confirmation($event_id)
    {
        if (! get_option('event_show_email_notifications_enabled', true)) {
            return;
        }

        $event = get_post($event_id);
        $author = get_user_by('id', $event->post_author);

        if (!$author || !$author->user_email) {
            return;
        }

        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);

        $subject = sprintf(
            __('¡Evento recibido! "%s" está en revisión', 'event-show-base'),
            $event->post_title
        );

        $message = self::get_email_template('user_event_submission', array(
            'author_name' => $author->display_name,
            'event_title' => $event->post_title,
            'event_date' => $event_date,
            'event_time' => $event_time,
        ));

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Remitente personalizado
        $from_name = get_option('event_show_email_from_name', 'Event Show');
        $from_email = get_option('event_show_email_from_address', get_option('admin_email'));
        add_filter('wp_mail_from', function () use ($from_email) {
            return $from_email;
        });
        add_filter('wp_mail_from_name', function () use ($from_name) {
            return $from_name;
        });

        wp_mail($author->user_email, $subject, $message, $headers);

        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
    }

    /**
     * Enviar notificación al usuario de que su evento fue aprobado
     *
     * @param int $event_id ID del evento
     */
    public static function send_user_event_approved($event_id)
    {
        if (! get_option('event_show_email_notifications_enabled', true)) {
            return;
        }

        $event = get_post($event_id);
        $author = get_user_by('id', $event->post_author);

        if (!$author || !$author->user_email) {
            return;
        }

        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_link = get_permalink($event_id);

        $subject = sprintf(
            __('¡Evento aprobado! "%s" ya está publicado', 'event-show-base'),
            $event->post_title
        );

        $message = self::get_email_template('user_event_approved', array(
            'author_name' => $author->display_name,
            'event_title' => $event->post_title,
            'event_date' => $event_date,
            'event_time' => $event_time,
            'event_link' => $event_link,
        ));

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Remitente personalizado
        $from_name = get_option('event_show_email_from_name', 'Event Show');
        $from_email = get_option('event_show_email_from_address', get_option('admin_email'));
        add_filter('wp_mail_from', function () use ($from_email) {
            return $from_email;
        });
        add_filter('wp_mail_from_name', function () use ($from_name) {
            return $from_name;
        });

        wp_mail($author->user_email, $subject, $message, $headers);

        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
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
                           <p><strong>Role:</strong> {author_role}</p>
                           {urgent_label}
                           <p><strong>Título:</strong> {event_title}</p>
                           <p><a href="{edit_link}" style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: #fff; text-decoration: none; border-radius: 5px;">Revisar y Aprobar</a></p>
                       </div>
                   </body>
                   </html>
               ',
            'user_event_submission' => '
				<html>
				<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
					<div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
						<h2 style="color: #2c3e50;">¡Hola {author_name}!</h2>
						<p style="font-size: 16px;">Hemos recibido tu evento <strong>{event_title}</strong> correctamente.</p>
						<div style="background-color: #fff; padding: 15px; border-left: 4px solid #f39c12; margin: 20px 0;">
							<p style="margin: 5px 0;"><strong>📅 Fecha:</strong> {event_date}</p>
							<p style="margin: 5px 0;"><strong>🕔 Hora:</strong> {event_time}</p>
						</div>
						<p style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
							<strong>⌛ En revisión:</strong> Tu evento está siendo revisado por nuestro equipo. Te notificaremos por email cuando sea aprobado y publicado.
						</p>
						<p style="color: #7f8c8d; font-size: 14px; margin-top: 30px;">¡Gracias por tu paciencia!</p>
					</div>
				</body>
				</html>
			',
            'user_event_approved' => '
				<html>
				<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
					<div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #d4edda; border-radius: 8px;">
						<h2 style="color: #155724;">✅ ¡Felicidades {author_name}!</h2>
						<p style="font-size: 16px;">Tu evento <strong>{event_title}</strong> ha sido aprobado y ya está publicado.</p>
						<div style="background-color: #fff; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
							<p style="margin: 5px 0;"><strong>📅 Fecha:</strong> {event_date}</p>
							<p style="margin: 5px 0;"><strong>🕔 Hora:</strong> {event_time}</p>
						</div>
						<p>Tu evento ahora es visible para todos los usuarios y pueden registrarse para asistir.</p>
						<p style="text-align: center; margin-top: 30px;">
							<a href="{event_link}" style="display: inline-block; padding: 12px 30px; background-color: #28a745; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Ver Mi Evento Publicado</a>
						</p>
						<p style="color: #7f8c8d; font-size: 14px; margin-top: 30px; text-align: center;">¡Que tengas un evento exitoso!</p>
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
