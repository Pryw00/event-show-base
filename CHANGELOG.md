# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.4.6] - 2026-09-25

> Resumen de 1.3.x–1.4.6, reconstruido a partir del historial de git. Este plugin quedó superado por el módulo `event-show` de `ibarra-vive`.

### Añadido

- Configuración de roles prioritarios y notificaciones urgentes para eventos.
- Eliminación de eventos finalizados desde la administración.
- Gestión de organizador y lugar en los metaboxes del evento (taxonomías y establecimientos de Simple Cards Listings).
- Procesamiento de múltiples correos electrónicos para notificaciones.

### Cambiado

- Mejoras en el registro de inscripciones y en el manejo de fechas.
- Los metaboxes de organizador y lugar pasan de `class-event-show-integrations.php` a `class-event-show-metaboxes.php` (se elimina código duplicado).

## [1.2.11] - 2026-02-19

### Añadido

#### Integración con Simple Access Control

- Sistema completo de control de acceso integrado con Advanced Role Manager (ARM)
- Nueva clase `Event_Show_Permissions` para verificación de permisos centralizada
- 16 capacidades personalizadas organizadas en 4 categorías:
  - **Eventos**: `create_eventos`, `edit_eventos`, `edit_others_eventos`, `delete_eventos`, `delete_others_eventos`, `approve_eventos`, `reject_eventos`
  - **Organizadores**: `create_organizadores`, `edit_organizadores`, `delete_organizadores`, `approve_organizadores`, `manage_organizador_terms`
  - **Asistentes**: `view_event_attendees`, `manage_event_attendees`, `export_event_attendees`, `delete_event_attendees`
  - **Sistema**: `view_event_show_logs`, `manage_event_show_settings`, `manage_event_taxonomies`
- Hook de filtro `event_show_check_permission` para integración con plugins de control de acceso
- 16 métodos estáticos de verificación de permisos:
  - `can_create_event()` - Verificar creación de eventos
  - `can_edit_event()` - Verificar edición (con lógica jerárquica)
  - `can_delete_event()` - Verificar eliminación (con lógica jerárquica)
  - `can_approve_event()` - Verificar aprobación
  - `can_reject_event()` - Verificar rechazo
  - `can_create_organizer()` - Verificar creación de organizadores
  - `can_edit_organizer()` - Verificar edición de organizadores
  - `can_delete_organizer()` - Verificar eliminación de organizadores
  - `can_approve_organizer()` - Verificar aprobación de organizadores
  - `can_view_attendees()` - Verificar visualización de asistentes
  - `can_manage_attendees()` - Verificar gestión de asistentes
  - `can_export_attendees()` - Verificar exportación de asistentes
  - `can_delete_attendee()` - Verificar eliminación de asistentes
  - `can_view_logs()` - Verificar acceso a logs
  - `can_manage_settings()` - Verificar gestión de configuración
  - `can_manage_taxonomies()` - Verificar gestión de taxonomías
- Filtrado automático de consultas WP_Query por permisos en el área de administración
- Documentación completa de integración en `INTEGRATION-ACCESS-CONTROL.md`

### Modificado

#### Permisos Actualizados

- **Admin** (`class-event-show-admin.php`):
  - `ajax_send_test_email()` - Ahora usa `can_manage_settings()`
  - `user_organizador_fields()` - Ahora usa `can_edit_organizer()`
  - `save_user_organizador_fields()` - Ahora usa `can_edit_organizer()`
  - Menú "Todos los Eventos" - Ahora usa `edit_eventos`
  - Menú "Asistentes" - Ahora usa `view_event_attendees`
  - Menú "Ajustes" - Ahora usa `manage_event_show_settings`
  - `render_settings_page()` - Añadida verificación de `can_manage_settings()`
  - `ajax_export_attendees()` - Ahora usa `can_export_attendees()`
  - `ajax_delete_attendee()` - Ahora usa `can_delete_attendee()`
- **Metaboxes** (`class-event-show-metaboxes.php`):
  - Visibilidad de metabox "Autor" - Ahora usa `can_approve_event()`
  - `save_metabox_data()` - Verificación de edición usa `can_edit_event()`
  - `save_metabox_data()` - Verificación de aprobación usa `can_approve_event()`
  - Guardado de cambio de autor - Ahora usa `can_approve_event()`
- **AJAX Público** (`class-event-show-ajax.php`):
  - `submit_event()` - Estado de publicación determina por `can_approve_event()`
  - Validación de establecimiento - Ahora usa `can_approve_event()`

### Mejorado

- Retrocompatibilidad mantenida con `current_user_can('manage_options')` como fallback
- Lógica jerárquica de permisos (usuarios pueden editar/eliminar sus propios eventos)
- Integración fluida con el sistema de roles y capacidades de WordPress
- Control de acceso granular para todas las operaciones del plugin
- Sugerencias de 4 roles predefinidos en la documentación (Administrador de Eventos, Editor de Eventos, Organizador, Revisor de Eventos)

## [1.0.0] - 2024-01-01

### Añadido

- Custom Post Type 'evento' con soporte completo
- 4 Taxonomías personalizadas:
  - Categorías de Evento (jerárquica)
  - Clasificación de Edad (jerárquica)
  - Organizadores (no jerárquica)
  - Lugares (no jerárquica)
- Metaboxes personalizados:
  - Detalles del evento (fechas, horas)
  - Imágenes (destacada y galería)
  - Opciones (plantilla, registro, capacidad, precio)
- Sistema de registro de asistentes:
  - Formulario de registro AJAX
  - Validación de capacidad
  - Validación de fecha límite
  - Validación de 5 días de anticipación
  - Prevención de registros duplicados
  - Exportación a CSV
- Sistema de notificaciones por email:
  - Confirmación al asistente
  - Notificación al administrador de nuevos registros
  - Recordatorios de eventos
  - Notificación de nuevos eventos enviados
  - Plantillas HTML personalizables
- 7 Shortcodes:
  - `[event_show_grid]` - Vista en cuadrícula
  - `[event_show_list]` - Vista de lista
  - `[event_show_calendar]` - Vista de calendario
  - `[event_show_carousel]` - Carrusel con countdown
  - `[event_show_registration]` - Formulario de registro
  - `[event_show_submit_form]` - Formulario de envío de eventos
  - `[event_show_dashboard]` - Dashboard de usuario
- Templates personalizables:
  - single-evento.php - Página individual del evento
  - archive-evento.php - Archivo de eventos
  - 4 layouts (grid, list, calendar, carousel)
  - event-card.php - Card reutilizable
  - Formularios (registro, envío, dashboard)
- Sistema de administración:
  - Columnas personalizadas en listado
  - Página de configuración
  - Visualizador de logs
  - Gestión de asistentes
  - Campos adicionales para taxonomías
- Características públicas:
  - Integración con Schema.org (SEO)
  - Exportación a iCal
  - Integración con Google Calendar
  - Botones de compartir
  - Countdown timer
- Sistema de logging/auditoría:
  - Registro de todas las acciones
  - Almacenamiento de IP y usuario
  - Limpieza automática (90 días)
- Assets completos:
  - CSS público (800+ líneas)
  - CSS administración (600+ líneas)
  - JavaScript público (interactividad completa)
  - JavaScript administración (metaboxes, media uploader)
- Internacionalización:
  - Archivo .pot con todas las cadenas traducibles
  - Preparado para múltiples idiomas
- Seguridad:
  - Nonces en todos los formularios
  - Sanitización de inputs
  - Escapado de outputs
  - Verificación de permisos
  - Prepared statements
- Documentación:
  - README.md completo
  - TECHNICAL.md con API y arquitectura
  - Comentarios inline en todo el código
  - PHPDoc en todas las funciones

### Seguridad

- Implementación de nonces para protección CSRF
- Sanitización de todos los inputs del usuario
- Escapado de todas las salidas
- Validación de permisos en acciones sensibles
- Uso de prepared statements para consultas SQL

### Rendimiento

- Carga condicional de assets
- Queries optimizadas con índices
- Uso de transients para caché
- Lazy loading de imágenes
- Limpieza automática de logs antiguos

## [Próximas versiones]

### Planeado para v1.1.0

- [ ] API REST endpoints
- [ ] Integración con calendarios externos (Outlook, iCloud)
- [ ] Eventos recurrentes
- [ ] Sistema de tickets con códigos QR
- [ ] Mapas interactivos con Google Maps API
- [ ] Sistema de valoraciones y reseñas
- [ ] Galería lightbox mejorada
- [ ] Búsqueda avanzada con filtros
- [ ] Widgets de sidebar
- [ ] Compatibilidad con Elementor/Gutenberg blocks
- [ ] Exportación de eventos a PDF

### Planeado para v1.2.0

- [ ] Multi-idioma nativo
- [ ] Sistema de pagos (WooCommerce, Stripe)
- [ ] Certificados de asistencia
- [ ] Chat en vivo durante eventos
- [ ] Streaming de eventos
- [ ] App móvil complementaria
- [ ] Estadísticas avanzadas
- [ ] Integración con redes sociales
- [ ] Newsletter automáticas
- [ ] Sistema de descuentos

## Notas de Actualización

### Actualización a 1.0.0

Primera versión estable. Instalación limpia.

#### Instrucciones

1. Subir carpeta del plugin a `/wp-content/plugins/`
2. Activar desde panel de administración
3. Ir a "Configuración > Enlaces permanentes" y guardar
4. Configurar opciones en "Eventos > Configuración"
5. Crear categorías, organizadores y lugares
6. ¡Comenzar a crear eventos!

#### Base de datos

Se crearán automáticamente 3 tablas:

- `wp_event_show_attendees`
- `wp_event_show_logs`
- `wp_event_show_termmeta`

#### Compatibilidad

- WordPress: 5.0+
- PHP: 7.4+
- MySQL: 5.6+

---

## Formato de Changelog

### Added (Añadido)

Para nuevas características.

### Changed (Cambiado)

Para cambios en funcionalidades existentes.

### Deprecated (Obsoleto)

Para características que pronto se eliminarán.

### Removed (Eliminado)

Para características eliminadas.

### Fixed (Arreglado)

Para corrección de bugs.

### Security (Seguridad)

En caso de vulnerabilidades.
