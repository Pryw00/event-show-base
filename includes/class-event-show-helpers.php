<?php

/**
 * Funciones de ayuda
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase con funciones de ayuda
 */
class Event_Show_Helpers
{

    /**
     * Convertir fecha DD/MM/YYYY a timestamp
     * 
     * @param string $date Fecha en formato dd/mm/yyyy
     * @param string $time Hora opcional en formato HH:mm
     * @return int|false Timestamp o false si falla
     */
    public static function date_to_timestamp($date, $time = '')
    {
        if (empty($date)) {
            return false;
        }

        // Limpiar espacios en blanco
        $date = trim($date);

        // Dividir la fecha en partes (dd/mm/yyyy)
        $parts = explode('/', $date);

        // Validar que tenemos 3 partes
        if (count($parts) !== 3) {
            return false;
        }

        // Convertir a enteros y validar
        $day = (int) trim($parts[0]);
        $month = (int) trim($parts[1]);
        $year = (int) trim($parts[2]);

        // Validar que la fecha sea válida
        if (!checkdate($month, $day, $year)) {
            return false;
        }

        // Si hay hora, parsearla
        $hour = 0;
        $minute = 0;
        $second = 0;
        if (!empty($time)) {
            $time = trim($time);
            $time_parts = explode(':', $time);
            if (count($time_parts) >= 2) {
                $hour = (int) $time_parts[0];
                $minute = (int) $time_parts[1];
                $second = isset($time_parts[2]) ? (int) $time_parts[2] : 0;
            }
        }

        // Crear timestamp
        $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

        return $timestamp;
    }

    /**
     * Formatear fecha
     *
     * @param string $date Fecha en formato dd/mm/yyyy
     * @param string $format Formato de salida
     * @return string
     */
    public static function format_date($date, $format = null)
    {
        if (empty($date)) {
            return '';
        }

        if (null === $format) {
            $format = get_option('event_show_date_format', 'd/m/Y');
        }

        // Usar nuestra función auxiliar para convertir a timestamp
        $timestamp = self::date_to_timestamp($date);

        if ($timestamp === false) {
            return $date; // Devolver la fecha sin formato si falla
        }

        // Intentar primero con date_i18n
        $result = date_i18n($format, $timestamp);

        // Si date_i18n devuelve vacío, usar date() como fallback
        if (empty($result)) {
            $result = date($format, $timestamp);
        }

        // Si aún está vacío, devolver la fecha original
        if (empty($result)) {
            return $date;
        }

        return $result;
    }

    /**
     * Formatear hora
     *
     * @param string $time Hora en formato HH:mm
     * @param string $format Formato de salida
     * @return string
     */
    public static function format_time($time, $format = null)
    {
        if (empty($time)) {
            return '';
        }

        if (null === $format) {
            $format = get_option('event_show_time_format', 'H:i');
        }

        $timestamp = strtotime($time);

        if ($timestamp === false) {
            return $time; // Devolver la hora sin formato si falla
        }

        $result = date_i18n($format, $timestamp);

        // Fallback a date() si date_i18n falla
        if (empty($result)) {
            $result = date($format, $timestamp);
        }

        return $result;
    }

    /**
     * Obtener fecha y hora formateadas juntas
     *
     * @param string $date Fecha
     * @param string $time Hora
     * @return string
     */
    public static function format_datetime($date, $time)
    {
        $formatted_date = self::format_date($date);
        $formatted_time = self::format_time($time);

        if (empty($formatted_time)) {
            return $formatted_date;
        }

        return sprintf('%s - %s', $formatted_date, $formatted_time);
    }

    /**
     * Verificar si un evento ya pasó
     *
     * @param int $event_id ID del evento
     * @return bool
     */
    public static function is_past_event($event_id)
    {
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);

        if (empty($event_date)) {
            return false;
        }

        // Usar la función auxiliar para convertir fecha y hora
        $event_timestamp = self::date_to_timestamp($event_date, $event_time ? $event_time : '23:59');

        if ($event_timestamp === false) {
            return false;
        }

        return time() > $event_timestamp;
    }

    /**
     * Obtener eventos próximos
     *
     * @param array $args Argumentos de WP_Query
     * @return WP_Query
     */
    public static function get_upcoming_events($args = array())
    {
        $defaults = array(
            'post_type' => 'evento',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_event_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_event_date',
                    'value' => date('d/m/Y'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            ),
        );

        $args = wp_parse_args($args, $defaults);

        return new WP_Query($args);
    }

    /**
     * Generar archivo iCal para un evento
     *
     * @param int $event_id ID del evento
     * @return string
     */
    public static function generate_ical($event_id)
    {
        $event = get_post($event_id);
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_end_date = get_post_meta($event_id, '_event_end_date', true);
        $event_end_time = get_post_meta($event_id, '_event_end_time', true);

        // Convertir fechas a formato iCal
        $start_datetime = self::date_to_ical($event_date, $event_time);
        $end_datetime = self::date_to_ical(
            $event_end_date ? $event_end_date : $event_date,
            $event_end_time ? $event_end_time : $event_time
        );

        // Obtener lugar
        $lugar = self::get_event_location($event_id);
        $location = $lugar ? $lugar['name'] : '';

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Event Show//ES//\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $event_id . "@" . parse_url(home_url(), PHP_URL_HOST) . "\r\n";
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $start_datetime . "\r\n";
        $ical .= "DTEND:" . $end_datetime . "\r\n";
        $ical .= "SUMMARY:" . self::escape_ical($event->post_title) . "\r\n";
        $ical .= "DESCRIPTION:" . self::escape_ical(wp_strip_all_tags($event->post_content)) . "\r\n";
        $ical .= "LOCATION:" . self::escape_ical($location) . "\r\n";
        $ical .= "URL:" . get_permalink($event_id) . "\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Convertir fecha a formato iCal
     *
     * @param string $date Fecha dd/mm/yyyy
     * @param string $time Hora HH:mm
     * @return string
     */
    private static function date_to_ical($date, $time = '00:00')
    {
        $timestamp = self::date_to_timestamp($date, $time);

        if ($timestamp === false) {
            return gmdate('Ymd\THis\Z'); // Fecha actual si falla
        }

        return gmdate('Ymd\THis\Z', $timestamp);
    }

    /**
     * Escapar texto para iCal
     *
     * @param string $text Texto a escapar
     * @return string
     */
    private static function escape_ical($text)
    {
        $text = str_replace(array("\r\n", "\n", "\r"), ' ', $text);
        $text = str_replace(array(',', ';', '\\'), array('\,', '\;', '\\\\'), $text);
        return $text;
    }

    /**
     * Generar URL de Google Calendar
     *
     * @param int $event_id ID del evento
     * @return string
     */
    public static function get_google_calendar_url($event_id)
    {
        $event = get_post($event_id);
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_end_date = get_post_meta($event_id, '_event_end_date', true);
        $event_end_time = get_post_meta($event_id, '_event_end_time', true);

        // Convertir a formato Google Calendar
        $start = self::date_to_google_cal($event_date, $event_time);
        $end = self::date_to_google_cal(
            $event_end_date ? $event_end_date : $event_date,
            $event_end_time ? $event_end_time : $event_time
        );

        // Obtener lugar
        $lugar = self::get_event_location($event_id);
        $location = $lugar ? $lugar['name'] : '';

        $params = array(
            'action' => 'TEMPLATE',
            'text' => $event->post_title,
            'dates' => $start . '/' . $end,
            'details' => wp_strip_all_tags($event->post_content),
            'location' => $location,
            'sf' => 'true',
            'output' => 'xml',
        );

        return 'https://www.google.com/calendar/render?' . http_build_query($params);
    }

    /**
     * Convertir fecha a formato Google Calendar
     *
     * @param string $date Fecha dd/mm/yyyy
     * @param string $time Hora HH:mm
     * @return string
     */
    private static function date_to_google_cal($date, $time = '00:00')
    {
        $timestamp = self::date_to_timestamp($date, $time);

        if ($timestamp === false) {
            return gmdate('Ymd\THis\Z'); // Fecha actual si falla
        }

        return gmdate('Ymd\THis\Z', $timestamp);
    }

    /**
     * Obtener tiempo restante para un evento
     *
     * @param int $event_id ID del evento
     * @return array
     */
    public static function get_countdown($event_id)
    {
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);

        if (empty($event_date)) {
            return null;
        }

        $datetime_str = $event_date . ' ' . ($event_time ? $event_time : '00:00');
        $event_timestamp = strtotime(str_replace('/', '-', $datetime_str));
        $now = time();
        $diff = $event_timestamp - $now;

        if ($diff <= 0) {
            return null;
        }

        $days = floor($diff / (60 * 60 * 24));
        $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
        $minutes = floor(($diff % (60 * 60)) / 60);
        $seconds = $diff % 60;

        return array(
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'total_seconds' => $diff,
        );
    }

    /**
     * Sanitizar atributos de shortcode
     *
     * @param array $atts Atributos del shortcode
     * @param array $defaults Valores por defecto
     * @return array
     */
    public static function sanitize_shortcode_atts($atts, $defaults)
    {
        $atts = shortcode_atts($defaults, $atts);

        // Sanitizar cada atributo
        foreach ($atts as $key => $value) {
            if (is_numeric($value)) {
                $atts[$key] = absint($value);
            } else {
                $atts[$key] = sanitize_text_field($value);
            }
        }

        return $atts;
    }

    /**
     * Obtener información del organizador de un evento
     * Compatible con establecimientos (si hay integración) y taxonomías
     *
     * @param int $event_id ID del evento
     * @return array|null Array con datos del organizador o null si no hay
     */
    public static function get_event_organizer($event_id)
    {
        // Primero verificar si hay un organizador asignado (formato nuevo con integración)
        $organizer_value = get_post_meta($event_id, '_event_organizer_id', true);

        if ($organizer_value) {
            // Parsear el formato tipo_id
            $parts = explode('_', $organizer_value, 2);
            $tipo = isset($parts[0]) ? $parts[0] : '';
            $id = isset($parts[1]) ? intval($parts[1]) : 0;

            if ($tipo === 'establecimiento' && $id) {
                $establecimiento = get_post($id);
                if ($establecimiento && $establecimiento->post_type === 'establecimiento') {
                    return array(
                        'id' => $establecimiento->ID,
                        'name' => $establecimiento->post_title,
                        'type' => 'establecimiento',
                        'url' => get_permalink($establecimiento->ID),
                        'logo' => get_the_post_thumbnail_url($establecimiento->ID, 'thumbnail'),
                    );
                }
            } elseif ($tipo === 'organizador' && $id) {
                $organizador = get_term($id, 'organizador');
                if ($organizador && !is_wp_error($organizador)) {
                    return array(
                        'id' => $organizador->term_id,
                        'name' => $organizador->name,
                        'type' => 'organizador',
                        'url' => get_term_link($organizador),
                        'logo' => get_term_meta($id, 'logo', true),
                    );
                }
            }
        }

        // Fallback: buscar en taxonomía organizador (compatibilidad con versiones antiguas)
        $organizadores = wp_get_post_terms($event_id, 'organizador');
        if (!empty($organizadores) && !is_wp_error($organizadores)) {
            $organizador = $organizadores[0];
            return array(
                'id' => $organizador->term_id,
                'name' => $organizador->name,
                'type' => 'organizador',
                'url' => get_term_link($organizador),
                'logo' => get_term_meta($organizador->term_id, 'logo', true),
            );
        }

        return null;
    }

    /**
     * Obtener información del lugar de un evento
     * Compatible con establecimientos (si hay integración) y taxonomías
     *
     * @param int $event_id ID del evento
     * @return array|null Array con datos del lugar o null si no hay
     */
    public static function get_event_location($event_id)
    {
        // Primero verificar si hay un lugar asignado (formato nuevo con integración)
        $lugar_value = get_post_meta($event_id, '_event_lugar_id', true);

        if ($lugar_value) {
            // Parsear el formato tipo_id
            $parts = explode('_', $lugar_value, 2);
            $tipo = isset($parts[0]) ? $parts[0] : '';
            $id = isset($parts[1]) ? intval($parts[1]) : 0;

            if ($tipo === 'establecimiento' && $id) {
                $establecimiento = get_post($id);
                if ($establecimiento && $establecimiento->post_type === 'establecimiento') {
                    return array(
                        'id' => $establecimiento->ID,
                        'name' => $establecimiento->post_title,
                        'type' => 'establecimiento',
                        'url' => get_permalink($establecimiento->ID),
                    );
                }
            } elseif ($tipo === 'lugar' && $id) {
                $lugar = get_term($id, 'lugar');
                if ($lugar && !is_wp_error($lugar)) {
                    return array(
                        'id' => $lugar->term_id,
                        'name' => $lugar->name,
                        'type' => 'lugar',
                        'url' => get_term_link($lugar),
                    );
                }
            }
        }

        // Fallback: buscar en taxonomía lugar (compatibilidad con versiones antiguas)
        $lugares = wp_get_post_terms($event_id, 'lugar');
        if (!empty($lugares) && !is_wp_error($lugares)) {
            $lugar = $lugares[0];
            return array(
                'id' => $lugar->term_id,
                'name' => $lugar->name,
                'type' => 'lugar',
                'url' => get_term_link($lugar),
            );
        }

        return null;
    }
}
