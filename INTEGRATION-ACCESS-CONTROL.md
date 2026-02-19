# Integración con Advanced Role Manager

## Descripción

Este documento describe la integración del plugin **Event Show** con el sistema de control de acceso del plugin **Advanced Role Manager** (simple-access-control-pryw).

## Capacidades Personalizadas

El sistema de control de acceso ahora reconoce y puede gestionar las siguientes capacidades personalizadas para Event Show:

### Eventos

- `create_eventos` - Crear nuevos eventos
- `edit_eventos` - Editar eventos propios
- `edit_others_eventos` - Editar eventos de otros usuarios
- `delete_eventos` - Eliminar eventos propios
- `delete_others_eventos` - Eliminar eventos de otros usuarios
- `approve_eventos` - Aprobar eventos (publicarlos)
- `reject_eventos` - Rechazar eventos

### Organizadores

- `create_organizadores` - Crear nuevas fichas de organizadores
- `edit_organizadores` - Editar organizadores
- `delete_organizadores` - Eliminar organizadores
- `approve_organizadores` - Aprobar organizadores pendientes
- `manage_organizador_terms` - Gestionar términos de la taxonomía organizadores

### Asistentes

- `view_event_attendees` - Ver asistentes de eventos
- `manage_event_attendees` - Gestionar asistentes (confirmar, modificar)
- `export_event_attendees` - Exportar listados de asistentes
- `delete_event_attendees` - Eliminar asistentes

### Sistema

- `view_event_show_logs` - Ver los registros de actividad del sistema
- `manage_event_show_settings` - Gestionar la configuración del plugin
- `manage_event_taxonomies` - Gestionar categorías, lugares y organizadores

## Cómo Asignar Permisos

### Desde el Panel de Advanced Role Manager

1. Ve a **Roles y Permisos** > **Roles** en el menú de administración de WordPress
2. Selecciona el rol que deseas modificar
3. En la lista de capacidades agrupadas, encontrarás cuatro nuevas secciones:
   - **Eventos** - Permisos relacionados con eventos
   - **Organizadores** - Permisos de gestión de organizadores
   - **Asistentes** - Permisos de gestión de asistentes
   - **Sistema Event Show** - Permisos del sistema

4. Marca las capacidades que deseas asignar al rol
5. Guarda los cambios

### Desde la Página de Integraciones

1. Ve a **Roles y Permisos** > **Integraciones**
2. Encontrarás una sección dedicada a **Event Show**
3. Ahí se muestra la lista completa de capacidades disponibles
4. Haz clic en "Ir a Gestión de Roles" para configurar los permisos

## Funcionamiento de los Permisos

### Permisos Jerárquicos

Los permisos funcionan de manera jerárquica:

- Si un usuario tiene `edit_others_eventos`, puede editar cualquier evento
- Si solo tiene `edit_eventos`, solo puede editar sus propios eventos
- Lo mismo aplica para eliminación y gestión de organizadores

### Aprobación de Contenido

Los usuarios con la capacidad `approve_eventos` pueden:

- Cambiar el estado de publicación de pendiente a publicado
- Ver todos los eventos pendientes de aprobación
- Gestionar el flujo de trabajo de eventos
- Sus propios eventos se publican automáticamente sin revisión

Los usuarios con `approve_organizadores` pueden:

- Aprobar solicitudes de nuevas fichas de organizadores
- Gestionar organizadores pendientes

### Gestión de Asistentes

Los permisos de asistentes funcionan escalonadamente:

- `view_event_attendees` - Permite ver listados de asistentes (los autores pueden ver asistentes de sus eventos)
- `manage_event_attendees` - Permite modificar y gestionar asistentes
- `export_event_attendees` - Permite exportar listados en formato CSV
- `delete_event_attendees` - Permite eliminar registros de asistentes

### Filtrado Automático

El sistema filtra automáticamente los listados en el panel de administración:

- Los usuarios sin `edit_others_eventos` solo verán sus propios eventos
- Los administradores y usuarios con permisos avanzados ven todo el contenido
- El acceso a asistentes se restringe según permisos

## Compatibilidad con el Sistema de Auto-Publicación

La integración mantiene compatibilidad con el sistema existente de auto-publicación por roles:

- Los roles configurados en **Integraciones** > **Auto-publicación de Eventos** pueden seguir publicando automáticamente
- Las nuevas capacidades ofrecen control más granular
- Ambos sistemas funcionan en conjunto

## Compatibilidad con Versiones Anteriores

La integración mantiene la compatibilidad con versiones anteriores:

- Los administradores (`manage_options`) mantienen acceso completo
- Los usuarios existentes mantienen sus permisos basados en capacidades estándar de WordPress
- Las capacidades personalizadas son opcionales - el sistema funciona con o sin ellas

## Roles Sugeridos

### Gestor de Eventos

Asignar las siguientes capacidades:

- `edit_others_eventos`
- `delete_others_eventos`
- `approve_eventos`
- `reject_eventos`
- `approve_organizadores`
- `manage_event_attendees`
- `export_event_attendees`
- `view_event_show_logs`

### Organizador

Asignar las siguientes capacidades:

- `create_eventos`
- `edit_eventos`
- `delete_eventos`
- `view_event_attendees`
- `export_event_attendees` (opcional)

### Moderador de Organizadores

Asignar las siguientes capacidades:

- `edit_organizadores`
- `approve_organizadores`
- `manage_organizador_terms`

### Asistente de Gestión

Asignar las siguientes capacidades:

- `view_event_attendees`
- `manage_event_attendees`
- `export_event_attendees`

## Desarrollo - Uso Programático

### Verificar Permisos en Código

```php
// Verificar si el usuario puede crear eventos
if (Event_Show_Permissions::can_create_event()) {
    // Código...
}

// Verificar si el usuario puede editar un evento específico
if (Event_Show_Permissions::can_edit_event($post_id)) {
    // Código...
}

// Verificar si el usuario puede aprobar eventos
if (Event_Show_Permissions::can_approve_event()) {
    // Código...
}

// Verificar si el usuario puede gestionar asistentes
if (Event_Show_Permissions::can_manage_attendees()) {
    // Código...
}

// Verificar si el usuario puede ver asistentes de un evento
if (Event_Show_Permissions::can_view_attendees($event_id)) {
    // Código...
}

// Para organizadores
if (Event_Show_Permissions::can_create_organizer()) {
    // Código...
}

if (Event_Show_Permissions::can_approve_organizer()) {
    // Código...
}
```

### Hook de Integración

Se expone un filtro para verificación de permisos:

```php
$can_edit = apply_filters('event_show_check_permission', false, 'edit_others_eventos', $user_id);
```

Este filtro es usado internamente por la integración con Advanced Role Manager.

## Archivos Modificados

### Advanced Role Manager (simple-access-control-pryw)

- `includes/class-arm-integrations.php` - Añadida integración con Event Show
- `includes/class-arm-capability-manager.php` - Añadido agrupamiento de capacidades Event Show

### Event Show

- `event-show.php` - Añadida carga de class-event-show-permissions.php
- `includes/class-event-show-permissions.php` - **NUEVO** - Sistema de permisos completo
- `admin/class-event-show-admin.php` - Actualizado para verificar permisos con capacidades
- `includes/class-event-show-metaboxes.php` - Actualizado para verificar permisos con capacidades
- `public/class-event-show-ajax.php` - Actualizado para verificar permisos con capacidades

## Troubleshooting

### Los permisos no se aplican

1. Verifica que el usuario tenga el rol correcto asignado
2. Verifica que las capacidades estén asignadas al rol
3. Limpia la caché de WordPress y del navegador
4. Verifica que ambos plugins estén activos

### No aparecen las capacidades de Event Show

1. Verifica que el plugin Event Show esté activo
2. Ve a Roles y Permisos > Integraciones para verificar que la integración está activa
3. Recarga la página de gestión de roles

### Los usuarios no pueden ver sus propios eventos

Asegúrate de que el usuario tenga al menos la capacidad `read` de WordPress y que:

- Tenga `edit_eventos` para ver los propios
- O tenga `edit_others_eventos` para ver todos

### Los organizadores no pueden crear eventos

Verifica que:

- Tengan la capacidad `create_eventos` asignada
- Estén registrados y autenticados
- El evento cumple con los requisitos mínimos (fecha, validaciones, etc.)

### La auto-publicación no funciona

Los eventos se publican automáticamente si:

- El usuario tiene la capacidad `approve_eventos` O
- El rol del usuario está configurado en Integraciones > Auto-publicación de Eventos

Verifica ambas configuraciones.

## Soporte

Para soporte sobre esta integración, contacta al desarrollador del plugin o revisa la documentación en:

- Advanced Role Manager: `simple-access-control-pryw/README.md`
- Event Show: `event-show-base/README.md`

---

**Última actualización:** 19 de febrero de 2026
**Versión:** 1.3.0
