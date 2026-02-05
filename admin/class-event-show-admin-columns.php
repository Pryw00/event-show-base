<?php

/**
 * Columnas personalizadas en el admin
 *
 * @package Event_Show
 */

// Si se accede directamente, salir
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar columnas personalizadas en el admin
 */
class Event_Show_Admin_Columns
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_filter('manage_evento_posts_columns', array($this, 'add_columns'));
        add_action('manage_evento_posts_custom_column', array($this, 'render_columns'), 10, 2);
        add_filter('manage_edit-evento_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'sort_columns'));
    }

    /**
     * Añadir columnas personalizadas
     */
    public function add_columns($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Insertar columnas después del título
            if ('title' === $key) {
                $new_columns['event_date'] = __('Fecha del Evento', 'event-show-base');
                $new_columns['event_location'] = __('Lugar', 'event-show-base');
                $new_columns['event_organizer'] = __('Organizador', 'event-show-base');
                $new_columns['event_attendees'] = __('Asistentes', 'event-show-base');
                $new_columns['event_status'] = __('Estado', 'event-show-base');
            }
        }

        return $new_columns;
    }

    /**
     * Renderizar contenido de columnas
     */
    public function render_columns($column, $post_id)
    {
        switch ($column) {
            case 'event_date':
                $event_date = get_post_meta($post_id, '_event_date', true);
                $event_time = get_post_meta($post_id, '_event_time', true);

                if ($event_date) {
                    echo esc_html(Event_Show_Helpers::format_datetime($event_date, $event_time));

                    if (Event_Show_Helpers::is_past_event($post_id)) {
                        echo '<br><span class="event-past-badge" style="background: #dc3232; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html__('Pasado', 'event-show-base') . '</span>';
                    }
                } else {
                    echo '-';
                }
                break;

            case 'event_location':
                $lugar = Event_Show_Helpers::get_event_location($post_id);
                if ($lugar) {
                    echo esc_html($lugar['name']);
                } else {
                    echo '-';
                }
                break;

            case 'event_organizer':
                $organizador = Event_Show_Helpers::get_event_organizer($post_id);
                if ($organizador) {
                    echo esc_html($organizador['name']);
                } else {
                    echo '-';
                }
                break;

            case 'event_attendees':
                $total = Event_Show_Attendees::get_total_attendees($post_id);
                $max = get_post_meta($post_id, '_max_attendees', true);

                $attendees_url = admin_url('edit.php?post_type=evento&page=event-show-attendees&event_id=' . $post_id);

                if ($max) {
                    $percentage = ($total / $max) * 100;
                    $color = $percentage >= 90 ? '#dc3232' : ($percentage >= 70 ? '#f0b849' : '#46b450');
                    if ($total > 0) {
                        echo '<a href="' . esc_url($attendees_url) . '" style="text-decoration: none; color: inherit;">';
                    }
                    echo '<strong style="color: ' . esc_attr($color) . ';">' . esc_html($total) . ' / ' . esc_html($max) . '</strong>';
                    echo '<br><progress value="' . esc_attr($total) . '" max="' . esc_attr($max) . '" style="width: 100%;"></progress>';
                    if ($total > 0) {
                        echo '</a>';
                    }
                } else {
                    if ($total > 0) {
                        echo '<a href="' . esc_url($attendees_url) . '" style="text-decoration: none; color: #2271b1; font-weight: 600;">';
                        echo '<strong>' . esc_html($total) . '</strong>';
                        echo '</a>';
                    } else {
                        echo '<strong>' . esc_html($total) . '</strong>';
                    }
                }
                break;

            case 'event_status':
                $enable_registration = get_post_meta($post_id, '_enable_registration', true);
                $deadline = get_post_meta($post_id, '_registration_deadline', true);

                if ('0' === $enable_registration) {
                    echo '<span class="event-status-badge" style="background: #999; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html__('Registro Deshabilitado', 'event-show-base') . '</span>';
                } elseif ($deadline && strtotime(str_replace('/', '-', $deadline)) < time()) {
                    echo '<span class="event-status-badge" style="background: #dc3232; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html__('Registro Cerrado', 'event-show-base') . '</span>';
                } else {
                    echo '<span class="event-status-badge" style="background: #46b450; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html__('Abierto', 'event-show-base') . '</span>';
                }
                break;
        }
    }

    /**
     * Hacer columnas ordenables
     */
    public function sortable_columns($columns)
    {
        $columns['event_date'] = 'event_date';
        return $columns;
    }

    /**
     * Ordenar por columnas
     */
    public function sort_columns($query)
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ('event_date' === $orderby) {
            $query->set('meta_key', '_event_date');
            $query->set('orderby', 'meta_value');
        }
    }
}
