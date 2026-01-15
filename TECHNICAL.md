# Event Show - Documentación Técnica

## Arquitectura del Plugin

### Patrón de Diseño

El plugin utiliza una arquitectura orientada a objetos con el patrón **Singleton** para la clase principal y separación de responsabilidades (Separation of Concerns).

### Estructura de Clases

```
Event_Show (Singleton - Main Class)
├── Event_Show_Post_Types (CPT Registration)
├── Event_Show_Taxonomies (Taxonomy Registration)
├── Event_Show_Database (Database Management)
├── Event_Show_Metaboxes (Admin Meta Fields)
├── Event_Show_Attendees (Registration Logic)
├── Event_Show_Notifications (Email System)
├── Event_Show_Logger (Audit Trail)
├── Event_Show_Helpers (Utility Functions)
├── Event_Show_Admin (Admin Interface)
├── Event_Show_Admin_Columns (Custom Columns)
├── Event_Show_Settings (Settings Management)
├── Event_Show_Public (Public Interface)
├── Event_Show_Shortcodes (Shortcode Handlers)
├── Event_Show_Templates (Template Loader)
└── Event_Show_AJAX (AJAX Handlers)
```

## Base de Datos

### Tablas Personalizadas

#### wp_event_show_attendees

```sql
CREATE TABLE wp_event_show_attendees (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    num_attendees INT(11) DEFAULT 1,
    registration_date DATETIME NOT NULL,
    ip_address VARCHAR(100),
    KEY event_id (event_id),
    KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### wp_event_show_logs

```sql
CREATE TABLE wp_event_show_logs (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    object_type VARCHAR(50),
    object_id BIGINT(20),
    user_id BIGINT(20),
    ip_address VARCHAR(100),
    created_at DATETIME NOT NULL,
    KEY action (action),
    KEY user_id (user_id),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### wp_event_show_termmeta

```sql
CREATE TABLE wp_event_show_termmeta (
    meta_id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    meta_key VARCHAR(255),
    meta_value LONGTEXT,
    KEY term_id (term_id),
    KEY meta_key (meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Post Meta Keys

| Meta Key                      | Tipo   | Descripción                        |
| ----------------------------- | ------ | ---------------------------------- |
| \_event_start_date            | string | Fecha inicio (dd/mm/yyyy)          |
| \_event_start_time            | string | Hora inicio (HH:mm)                |
| \_event_end_date              | string | Fecha fin (dd/mm/yyyy)             |
| \_event_end_time              | string | Hora fin (HH:mm)                   |
| \_event_thumbnail             | int    | ID imagen destacada                |
| \_event_gallery               | string | IDs galería (separados por coma)   |
| \_event_template              | string | Plantilla (default/modern/minimal) |
| \_event_enable_registration   | bool   | Habilitar registro (1/0)           |
| \_event_capacity              | int    | Capacidad máxima                   |
| \_event_registration_deadline | string | Fecha límite registro              |
| \_event_price                 | float  | Precio entrada                     |
| \_event_free                  | bool   | Evento gratuito (1/0)              |

### Term Meta Keys (Organizador)

| Meta Key          | Tipo   | Descripción       |
| ----------------- | ------ | ----------------- |
| organizer_phone   | string | Teléfono contacto |
| organizer_email   | string | Email contacto    |
| organizer_website | string | Sitio web         |
| organizer_image   | int    | ID imagen/logo    |

### Term Meta Keys (Lugar)

| Meta Key         | Tipo   | Descripción        |
| ---------------- | ------ | ------------------ |
| location_address | string | Dirección completa |
| location_map_url | string | URL Google Maps    |
| location_image   | int    | ID imagen lugar    |

## API Pública

### Funciones Helper

#### event_show_get_event_dates()

```php
/**
 * Obtener fechas formateadas del evento
 *
 * @param int $event_id ID del evento
 * @return array Array con start_date, start_time, end_date, end_time
 */
function event_show_get_event_dates( $event_id ) {
    return Event_Show_Helpers::get_event_dates( $event_id );
}
```

#### event_show_register_attendee()

```php
/**
 * Registrar asistente para un evento
 *
 * @param int $event_id ID del evento
 * @param array $data Datos del asistente (name, email, phone, num_attendees)
 * @return int|WP_Error ID del registro o error
 */
function event_show_register_attendee( $event_id, $data ) {
    $attendees = Event_Show_Attendees::get_instance();
    return $attendees->register( $event_id, $data );
}
```

#### event_show_get_attendees()

```php
/**
 * Obtener asistentes de un evento
 *
 * @param int $event_id ID del evento
 * @return array Lista de asistentes
 */
function event_show_get_attendees( $event_id ) {
    $attendees = Event_Show_Attendees::get_instance();
    return $attendees->get_attendees( $event_id );
}
```

#### event_show_can_register()

```php
/**
 * Verificar si se puede registrar al evento
 *
 * @param int $event_id ID del evento
 * @return bool|WP_Error True si puede, WP_Error si no
 */
function event_show_can_register( $event_id ) {
    $attendees = Event_Show_Attendees::get_instance();
    return $attendees->can_register( $event_id );
}
```

#### event_show_generate_ical()

```php
/**
 * Generar archivo iCal para un evento
 *
 * @param int $event_id ID del evento
 * @return string Contenido del archivo iCal
 */
function event_show_generate_ical( $event_id ) {
    return Event_Show_Helpers::generate_ical( $event_id );
}
```

### Actions (Hooks)

#### event_show_init

```php
/**
 * Se ejecuta después de inicializar el plugin
 */
do_action( 'event_show_init' );
```

#### event_show_after_register

```php
/**
 * Se ejecuta después de registrar un asistente
 *
 * @param int $attendee_id ID del registro
 * @param int $event_id ID del evento
 * @param array $data Datos del asistente
 */
do_action( 'event_show_after_register', $attendee_id, $event_id, $data );
```

#### event_show_after_create_event

```php
/**
 * Se ejecuta después de crear/actualizar un evento
 *
 * @param int $event_id ID del evento
 * @param WP_Post $post Objeto del post
 */
do_action( 'event_show_after_create_event', $event_id, $post );
```

#### event_show_before_send_email

```php
/**
 * Se ejecuta antes de enviar un email
 *
 * @param string $to Destinatario
 * @param string $subject Asunto
 * @param string $message Mensaje
 */
do_action( 'event_show_before_send_email', $to, $subject, $message );
```

#### event_show_after_send_email

```php
/**
 * Se ejecuta después de enviar un email
 *
 * @param bool $sent Si se envió exitosamente
 * @param string $to Destinatario
 */
do_action( 'event_show_after_send_email', $sent, $to );
```

### Filters (Filtros)

#### event_show_email_template

```php
/**
 * Filtrar plantilla de email
 *
 * @param string $template HTML de la plantilla
 * @param string $type Tipo de email (attendee_confirmation, admin_new_registration, etc)
 * @return string
 */
$template = apply_filters( 'event_show_email_template', $template, $type );
```

Ejemplo de uso:

```php
add_filter( 'event_show_email_template', function( $template, $type ) {
    if ( $type === 'attendee_confirmation' ) {
        // Personalizar plantilla de confirmación
        $template = str_replace( '{custom}', 'Valor personalizado', $template );
    }
    return $template;
}, 10, 2 );
```

#### event_show_advance_days

```php
/**
 * Filtrar días de anticipación requeridos
 *
 * @param int $days Número de días (default: 5)
 * @return int
 */
$days = apply_filters( 'event_show_advance_days', 5 );
```

#### event_show_query_args

```php
/**
 * Filtrar argumentos de WP_Query para eventos
 *
 * @param array $args Argumentos de query
 * @param string $context Contexto (grid, list, calendar, etc)
 * @return array
 */
$args = apply_filters( 'event_show_query_args', $args, $context );
```

#### event_show_registration_fields

```php
/**
 * Filtrar campos del formulario de registro
 *
 * @param array $fields Array de campos
 * @return array
 */
$fields = apply_filters( 'event_show_registration_fields', $fields );
```

#### event_show_attendee_capacity

```php
/**
 * Filtrar capacidad de evento
 *
 * @param int $capacity Capacidad configurada
 * @param int $event_id ID del evento
 * @return int
 */
$capacity = apply_filters( 'event_show_attendee_capacity', $capacity, $event_id );
```

#### event_show_template_path

```php
/**
 * Filtrar ruta de templates
 *
 * @param string $path Ruta actual
 * @param string $template Nombre del template
 * @return string
 */
$path = apply_filters( 'event_show_template_path', $path, $template );
```

## AJAX Endpoints

### event_show_register_attendee

**Action:** `wp_ajax_event_show_register_attendee` y `wp_ajax_nopriv_event_show_register_attendee`

**Parámetros:**

- `action`: 'event_show_register_attendee'
- `event_id`: ID del evento
- `name`: Nombre del asistente
- `email`: Email del asistente
- `phone`: Teléfono (opcional)
- `num_attendees`: Número de asistentes
- `terms`: Aceptación de términos (1/0)
- `nonce`: Nonce de seguridad

**Respuesta exitosa:**

```json
{
  "success": true,
  "data": {
    "message": "¡Registro exitoso!",
    "attendee_id": 123
  }
}
```

**Respuesta error:**

```json
{
  "success": false,
  "data": {
    "message": "Error: mensaje de error"
  }
}
```

### event_show_submit_event

**Action:** `wp_ajax_event_show_submit_event`

**Parámetros:**

- `action`: 'event_show_submit_event'
- `event_title`: Título del evento
- `event_content`: Descripción
- `event_start_date`: Fecha inicio
- `event_start_time`: Hora inicio
- `event_end_date`: Fecha fin
- `event_end_time`: Hora fin
- `event_category`: ID categoría
- `event_age`: ID clasificación edad
- `event_organizer`: ID organizador
- `event_location`: ID lugar
- `nonce`: Nonce de seguridad

### event_show_create_organizer

**Action:** `wp_ajax_event_show_create_organizer`

**Parámetros:**

- `action`: 'event_show_create_organizer'
- `organizer_name`: Nombre del organizador
- `organizer_phone`: Teléfono (opcional)
- `organizer_email`: Email (opcional)
- `organizer_website`: Sitio web (opcional)
- `nonce`: Nonce de seguridad

### event_show_create_location

**Action:** `wp_ajax_event_show_create_location`

### event_show_export_attendees

**Action:** `wp_ajax_event_show_export_attendees`

### event_show_delete_attendee

**Action:** `wp_ajax_event_show_delete_attendee`

## Seguridad

### Validación de Nonces

Todos los formularios y peticiones AJAX utilizan nonces:

```php
// Generar nonce
wp_create_nonce( 'event_show_nonce' );

// Verificar nonce
wp_verify_nonce( $_POST['nonce'], 'event_show_nonce' );
```

### Sanitización de Datos

```php
// Texto
$name = sanitize_text_field( $_POST['name'] );

// Email
$email = sanitize_email( $_POST['email'] );

// URL
$url = esc_url_raw( $_POST['url'] );

// HTML
$content = wp_kses_post( $_POST['content'] );

// Integer
$id = absint( $_POST['id'] );
```

### Escapado de Salida

```php
// HTML
echo esc_html( $text );

// Atributo
echo esc_attr( $attr );

// URL
echo esc_url( $url );

// JavaScript
echo esc_js( $js );
```

### Verificación de Permisos

```php
// Verificar capacidad
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( 'No tienes permisos' );
}
```

## Optimización

### Consultas Optimizadas

```php
// Usar WP_Query con parámetros optimizados
$args = array(
    'post_type'      => 'evento',
    'posts_per_page' => 10,
    'no_found_rows'  => true,  // Desactivar paginación si no se necesita
    'fields'         => 'ids', // Solo IDs si no se necesita el objeto completo
);
```

### Carga Condicional de Assets

```php
// Solo cargar en páginas necesarias
if ( is_singular( 'evento' ) || has_shortcode( $post->post_content, 'event_show_grid' ) ) {
    wp_enqueue_style( 'event-show-public' );
}
```

### Caché

```php
// Usar transients para datos que cambian poco
$events = get_transient( 'event_show_upcoming_events' );
if ( false === $events ) {
    $events = // ... query de eventos
    set_transient( 'event_show_upcoming_events', $events, HOUR_IN_SECONDS );
}
```

## Testing

### Unit Tests

Para ejecutar tests unitarios:

```bash
phpunit
```

### Funciones a Testear

- Validación de fechas
- Cálculo de capacidad
- Generación de iCal
- Formateo de fechas
- Validación de emails
- Lógica de registro

### Integration Tests

- Creación de eventos
- Registro de asistentes
- Envío de emails
- Exportación CSV
- AJAX endpoints

## Deployment

### Checklist Pre-Deploy

- [ ] Verificar versión en archivo principal
- [ ] Ejecutar tests
- [ ] Verificar compatibilidad PHP
- [ ] Verificar compatibilidad WordPress
- [ ] Minificar CSS/JS (opcional)
- [ ] Generar .pot actualizado
- [ ] Actualizar CHANGELOG
- [ ] Revisar README
- [ ] Tag en Git

### Build para Producción

```bash
# Instalar dependencias
composer install --no-dev

# Crear ZIP
zip -r event-show-base.zip event-show-base/ -x "*.git*" "node_modules/*" "tests/*"
```

## Troubleshooting

### Eventos no aparecen

1. Verificar que el CPT está registrado: `Configuración > Enlaces permanentes > Guardar`
2. Verificar permisos de usuario
3. Verificar estado de publicación

### Emails no se envían

1. Verificar configuración SMTP de WordPress
2. Instalar plugin SMTP (ej: WP Mail SMTP)
3. Revisar logs de PHP

### Formulario AJAX no funciona

1. Verificar que jQuery está cargado
2. Verificar nonce
3. Revisar consola del navegador
4. Verificar permisos

### CSS no se aplica

1. Limpiar caché del navegador
2. Verificar que los assets se están encolando
3. Verificar conflictos con el tema
4. Inspeccionar especificidad CSS

## Recursos Adicionales

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)

## Soporte

Para asistencia técnica:

- Email: dev@eventshow.com
- Documentación: https://eventshow.com/docs
- GitHub: https://github.com/eventshow/event-show-base
