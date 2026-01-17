# Event Show Base

Sistema completo de gestión de eventos para WordPress con funcionalidades avanzadas de registro, notificaciones y administración.

## Descripción

Event Show es un plugin completo para WordPress que permite crear, gestionar y promover eventos de manera profesional. Incluye sistema de registro de asistentes, notificaciones por email, múltiples vistas (grid, lista, calendario, carrusel), y un completo panel de administración.

## Características principales

### Gestión de Eventos

- Custom Post Type dedicado para eventos
- Metaboxes personalizados para fecha, hora, ubicación
- Galería de imágenes para cada evento
- Taxonomías: Categorías, Clasificación de edad, Organizadores, Lugares
- Plantillas personalizables por evento

### Sistema de Registro

- Formulario de registro de asistentes
- Control de capacidad máxima
- Fecha límite de registro
- Validación de 5 días de anticipación para usuarios no administradores
- Exportación de asistentes a CSV
- Prevención de registros duplicados

### Visualización

- **Grid**: Vista en cuadrícula responsiva
- **Lista**: Vista de lista detallada
- **Calendario**: Agrupación por meses
- **Carrusel**: Con countdown y navegación automática
- **Slider**: Panel informativo con slider de imágenes con efecto 3D
- Página de evento individual completa
- Archivo de eventos

### Notificaciones

- Email de confirmación al asistente
- Notificación al administrador de nuevos registros
- Recordatorios de eventos
- Notificación de nuevos eventos enviados
- Plantillas HTML personalizables

### Integraciones

- Exportación a formato iCal
- Integración con Google Calendar
- Schema.org para SEO
- Botones de compartir en redes sociales

### Panel de Usuario

- Dashboard personalizado
- Gestión de eventos propios
- Administración de organizadores
- Administración de lugares
- Información de perfil

### Administración

- Columnas personalizadas en listado
- Sistema de logs/auditoría
- Exportación de asistentes
- Campos adicionales para organizadores y lugares
- Configuración centralizada

## Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

## Instalación

1. Descarga el plugin
2. Sube la carpeta `event-show-base` a `/wp-content/plugins/`
3. Activa el plugin desde el menú 'Plugins' en WordPress
4. Configura las opciones desde "Eventos > Configuración"

## Uso

### Crear un evento

1. Ve a "Eventos > Añadir nuevo"
2. Completa los campos obligatorios:
   - Título del evento
   - Descripción
   - Fecha y hora de inicio/fin
3. Selecciona categoría, organizador y lugar
4. Configura opciones de registro (opcional)
5. Publica el evento

### Shortcodes disponibles

#### Vista Grid

```
[event_show_grid category="" limit="12" columns="3" show="upcoming"]
```

Parámetros:

- `category`: ID de categoría para filtrar
- `limit`: Número de eventos a mostrar
- `columns`: Número de columnas (2, 3 o 4)
- `show`: "upcoming" (próximos, default), "past" (pasados), "all" (todos)

#### Vista Lista

```
[event_show_list category="" limit="10" show="upcoming"]
```

Parámetros:

- `category`: ID de categoría para filtrar
- `limit`: Número de eventos a mostrar
- `show`: "upcoming" (próximos, default), "past" (pasados), "all" (todos)

#### Vista Calendario

```
[event_show_calendar category="" show="upcoming"]
```

Parámetros:

- `category`: ID de categoría para filtrar
- `show`: "upcoming" (próximos, default), "past" (pasados), "all" (todos)

#### Carrusel

```
[event_show_carousel limit="5" autoplay="true" show="upcoming"]
```

Parámetros:

- `limit`: Número de eventos en el carrusel
- `autoplay`: Activar rotación automática (true/false)
- `show`: "upcoming" (próximos, default), "past" (pasados), "all" (todos)

#### Slider

```
[event_show_slider limit="5" autoplay="true" autoplay_speed="5000" show="upcoming"]
```

Muestra eventos con un diseño moderno: panel de información a la izquierda y slider de imágenes/posters a la derecha con efecto de profundidad.

Parámetros:

- `limit`: Número de eventos en el slider
- `category`: ID de categoría para filtrar
- `autoplay`: Activar rotación automática (true/false)
- `autoplay_speed`: Velocidad de rotación en milisegundos (default: 5000)
- `show`: "upcoming" (próximos, default), "past" (pasados), "all" (todos)

#### Formulario de registro

```
[event_show_registration event_id="123"]
```

Parámetros:

- `event_id`: ID del evento

#### Formulario de envío

```
[event_show_submit_form]
```

Permite a usuarios registrados enviar eventos para revisión.

#### Dashboard de usuario

```
[event_show_dashboard]
```

Muestra el panel de control del usuario actual.

### Taxonomías

#### Categorías de Evento

Jerarquía de categorías para organizar eventos (ej: Música, Deportes, Arte).

#### Clasificación de Edad

Restricción por edad (ej: Todos los públicos, +18, Familiar).

#### Organizadores

Entidades que organizan eventos. Campos adicionales:

- Teléfono
- Email
- Sitio web
- Imagen/logo

#### Lugares

Ubicaciones donde se realizan eventos. Campos adicionales:

- Dirección completa
- URL del mapa
- Imagen del lugar

### Registro de asistentes

El sistema de registro incluye:

1. **Validación de capacidad**: No permite registros si se alcanzó el límite
2. **Validación de fecha límite**: Respeta la fecha límite configurada
3. **Validación de 5 días**: Usuarios no administradores deben registrar eventos con 5+ días de anticipación
4. **Prevención de duplicados**: Un email solo puede registrarse una vez por evento
5. **Confirmación automática**: Envío de email de confirmación

### Exportar asistentes

Desde el listado de eventos:

1. Pasa el mouse sobre el evento
2. Click en "Exportar asistentes"
3. Se descargará un archivo CSV con todos los datos

### Sistema de Logs

Registra todas las acciones importantes:

- Creación de eventos
- Registros de asistentes
- Actualizaciones
- Eliminaciones

Acceso: "Eventos > Logs del Sistema"

## Estructura de archivos

```
event-show-base/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── public.css
│   └── js/
│       ├── admin.js
│       └── public.js
├── includes/
│   ├── class-event-show-post-types.php
│   ├── class-event-show-taxonomies.php
│   ├── class-event-show-database.php
│   ├── class-event-show-metaboxes.php
│   ├── class-event-show-attendees.php
│   ├── class-event-show-notifications.php
│   ├── class-event-show-logger.php
│   └── class-event-show-helpers.php
├── admin/
│   ├── class-event-show-admin.php
│   ├── class-event-show-admin-columns.php
│   └── class-event-show-settings.php
├── public/
│   ├── class-event-show-public.php
│   ├── class-event-show-shortcodes.php
│   ├── class-event-show-templates.php
│   └── class-event-show-ajax.php
├── templates/
│   ├── single-evento.php
│   ├── single-event.php
│   ├── archive-evento.php
│   ├── layouts/
│   │   ├── grid.php
│   │   ├── list.php
│   │   ├── calendar.php
│   │   ├── carousel.php
│   │   └── slider.php
│   ├── partials/
│   │   └── event-card.php
│   ├── registration-form.php
│   ├── submit-event-form.php
│   └── user-dashboard.php
├── languages/
│   └── event-show-base.pot
├── event-show.php
└── README.md
```

## Base de datos

El plugin crea 3 tablas personalizadas:

### wp_event_show_attendees

Almacena registros de asistentes:

- ID
- event_id
- name
- email
- phone
- num_attendees
- registration_date
- ip_address

### wp_event_show_logs

Sistema de auditoría:

- ID
- action
- object_type
- object_id
- user_id
- ip_address
- created_at

### wp_event_show_termmeta

Metadatos de taxonomías (organizadores y lugares).

## Hooks y Filtros

### Actions

```php
// Después de registrar un asistente
do_action('event_show_after_register', $attendee_id, $event_id, $data);

// Después de crear un evento
do_action('event_show_after_create_event', $event_id, $post_data);

// Antes de enviar email
do_action('event_show_before_send_email', $to, $subject, $message);
```

### Filters

```php
// Modificar plantilla de email
add_filter('event_show_email_template', function($template, $type) {
    // Modificar $template
    return $template;
}, 10, 2);

// Modificar días de anticipación requeridos
add_filter('event_show_advance_days', function($days) {
    return 7; // Cambiar a 7 días
});

// Modificar argumentos de query de eventos
add_filter('event_show_query_args', function($args) {
    // Modificar $args
    return $args;
});
```

## Personalización

### Estilos CSS

El plugin carga estilos con baja especificidad que pueden ser sobrescritos fácilmente:

```css
/* En el tema */
.event-card {
  /* Tus estilos personalizados */
}
```

### Templates

Puedes sobrescribir cualquier template copiándolo a tu tema:

```
tu-tema/
└── event-show/
    ├── single-evento.php
    ├── archive-evento.php
    └── layouts/
        ├── grid.php
        └── ...
```

### Traducciones

El plugin está preparado para traducción. Archivo .pot incluido en `/languages/`.

Para crear traducción:

1. Usa Poedit u otra herramienta
2. Carga `event-show-base.pot`
3. Traduce las cadenas
4. Guarda como `event-show-base-{locale}.mo`
5. Coloca en `/wp-content/languages/plugins/`

## Seguridad

El plugin implementa:

- ✅ Nonces en todos los formularios AJAX
- ✅ Sanitización de entradas con `sanitize_text_field()`, `sanitize_email()`, etc.
- ✅ Escapado de salidas con `esc_html()`, `esc_url()`, `esc_attr()`
- ✅ Verificación de permisos con `current_user_can()`
- ✅ Prepared statements para queries SQL
- ✅ Validación de datos
- ✅ Prevención de SQL injection
- ✅ Prevención de XSS

## Rendimiento

Optimizaciones implementadas:

- Carga condicional de assets (solo cuando es necesario)
- Queries optimizadas con índices
- Lazy loading de imágenes
- Minificación recomendada de CSS/JS
- Caché de consultas frecuentes
- Limpieza automática de logs antiguos (90 días)

## Compatibilidad

Probado con:

- WordPress 5.0+
- PHP 7.4, 8.0, 8.1, 8.2
- Temas principales: Twenty Twenty-Four, Astra, GeneratePress, OceanWP
- Plugins de página: Elementor, Gutenberg

## Soporte

Para reportar bugs o solicitar características:

- Email: soporte@eventshow.com
- Issues: GitHub repository

## Changelog

### 1.0.0 - 2024-01-01

- Release inicial
- CPT para eventos
- Sistema de registro de asistentes
- 7 shortcodes
- 4 taxonomías
- Panel de usuario
- Sistema de notificaciones
- Exportación CSV
- Integración calendario
- Logs de auditoría

## Créditos

Desarrollado siguiendo el estándar IEEE 830-1998 para especificaciones de software.

## Licencia

GPL v2 or later

## Autor

Event Show Development Team

---

**¿Necesitas ayuda?** Consulta la documentación completa en nuestro sitio web o contacta con soporte.
