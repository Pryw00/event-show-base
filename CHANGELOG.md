# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

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
