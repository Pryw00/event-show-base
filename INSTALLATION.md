# Guía de Instalación y Despliegue - Event Show Base

## Tabla de Contenidos

1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Instalación](#instalación)
3. [Configuración Inicial](#configuración-inicial)
4. [Verificación Post-Instalación](#verificación-post-instalación)
5. [Configuración Avanzada](#configuración-avanzada)
6. [Solución de Problemas](#solución-de-problemas)
7. [Despliegue en Producción](#despliegue-en-producción)

## Requisitos del Sistema

### Mínimos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o MariaDB 10.0 o superior
- Memoria PHP: 128 MB (recomendado 256 MB)
- Módulos PHP requeridos:
  - mysqli
  - json
  - mbstring
  - curl
  - gd o imagick (para procesamiento de imágenes)

### Recomendados

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ o MariaDB 10.3+
- Memoria PHP: 512 MB
- HTTPS habilitado
- Servidor Apache 2.4+ o Nginx 1.18+

## Instalación

### Método 1: Instalación Manual

1. **Descargar el plugin**

   ```bash
   # Desde repositorio
   git clone https://github.com/eventshow/event-show-base.git

   # O descargar ZIP desde releases
   ```

2. **Subir al servidor**

   ```bash
   # Via FTP/SFTP
   # Subir carpeta 'event-show-base' a:
   /wp-content/plugins/

   # Via SSH
   cd /path/to/wordpress/wp-content/plugins/
   unzip event-show-base.zip
   ```

3. **Configurar permisos**

   ```bash
   # Permisos recomendados
   find event-show-base/ -type d -exec chmod 755 {} \;
   find event-show-base/ -type f -exec chmod 644 {} \;
   ```

4. **Activar en WordPress**
   - Ir a: `Panel de WordPress > Plugins > Plugins Instalados`
   - Buscar "Event Show Base"
   - Click en "Activar"

### Método 2: Instalación via WP-CLI

```bash
# Navegar al directorio de WordPress
cd /path/to/wordpress/

# Instalar y activar
wp plugin install /path/to/event-show-base.zip --activate

# O desde repositorio (cuando esté disponible)
wp plugin install event-show-base --activate
```

### Método 3: Instalación desde Panel de WordPress

1. `Panel > Plugins > Añadir nuevo`
2. Click en "Subir plugin"
3. Seleccionar archivo ZIP
4. Click en "Instalar ahora"
5. Click en "Activar plugin"

## Configuración Inicial

### Paso 1: Verificar Activación

Después de activar, deberías ver:

- Nuevo item "Eventos" en el menú principal
- Mensaje de bienvenida (puede aparecer)
- Tablas de BD creadas automáticamente

### Paso 2: Actualizar Permalinks

**IMPORTANTE:** Esto es necesario para que funcionen correctamente las URLs de eventos.

1. Ir a: `Ajustes > Enlaces permanentes`
2. No cambiar nada
3. Simplemente hacer click en "Guardar cambios"

### Paso 3: Configurar Opciones Básicas

1. Ir a: `Eventos > Configuración`

2. **Email**

   - Email de notificaciones: `eventos@tudominio.com`
   - Nombre del remitente: `Tu Sitio - Eventos`

3. **Registro**

   - Capacidad predeterminada: `100`
   - Requiere aprobación de administrador: `No` (recomendado)
   - Días de anticipación mínima: `5`

4. **Notificaciones**

   - Habilitar notificaciones: `Sí`
   - Enviar recordatorios: `Sí`
   - Días antes para recordatorio: `3`

5. **Logs**

   - Días de retención de logs: `90`

6. Guardar cambios

### Paso 4: Crear Taxonomías Básicas

#### Categorías de Evento

1. Ir a: `Eventos > Categorías de Evento`
2. Crear categorías básicas:
   - Música
   - Deportes
   - Arte y Cultura
   - Educación
   - Tecnología
   - Familia

#### Clasificación de Edad

1. Ir a: `Eventos > Clasificación de Edad`
2. Crear clasificaciones:
   - Todos los públicos
   - +13 años
   - +16 años
   - +18 años
   - Solo adultos

#### Organizadores

1. Ir a: `Eventos > Organizadores`
2. Crear al menos un organizador de ejemplo:
   - Nombre: "Organización Principal"
   - Teléfono: "555-1234"
   - Email: "info@organizacion.com"
   - Website: "https://organizacion.com"

#### Lugares

1. Ir a: `Eventos > Lugares`
2. Crear al menos un lugar de ejemplo:
   - Nombre: "Centro de Convenciones Principal"
   - Dirección: "Calle Principal 123, Ciudad"
   - URL del mapa: "https://maps.google.com/..."

### Paso 5: Crear Evento de Prueba

1. Ir a: `Eventos > Añadir nuevo`

2. **Información básica:**

   - Título: "Evento de Prueba"
   - Contenido: Descripción del evento

3. **Detalles del Evento:**

   - Fecha inicio: [Fecha futura, mínimo 6 días adelante]
   - Fecha fin: [Mismo día o posterior]
   - Hora inicio: "19:00"
   - Hora fin: "22:00"

4. **Taxonomías:**

   - Categoría: Seleccionar una
   - Clasificación: Seleccionar una
   - Organizador: Seleccionar uno
   - Lugar: Seleccionar uno

5. **Opciones:**

   - Habilitar registro: ☑
   - Capacidad: 50
   - Precio: "Gratis" o un monto

6. **Publicar**

### Paso 6: Crear Páginas Necesarias

#### Página de Eventos

1. Crear nueva página: "Eventos"
2. Agregar shortcode:
   ```
   [event_show_grid limit="12" columns="3"]
   ```
3. Publicar

#### Página de Registro

1. Crear nueva página: "Registrarse a Evento"
2. Agregar shortcode:
   ```
   [event_show_registration event_id="ID_DEL_EVENTO"]
   ```
   (Reemplazar ID_DEL_EVENTO con el ID real)
3. Publicar

#### Página de Envío de Eventos

1. Crear nueva página: "Enviar Evento"
2. Agregar shortcode:
   ```
   [event_show_submit_form]
   ```
3. Publicar

#### Página de Dashboard de Usuario

1. Crear nueva página: "Mi Dashboard"
2. Agregar shortcode:
   ```
   [event_show_dashboard]
   ```
3. Configurar página como privada o requerir login
4. Publicar

### Paso 7: Añadir al Menú

1. Ir a: `Apariencia > Menús`
2. Crear o editar menú principal
3. Añadir páginas creadas:
   - Eventos
   - Enviar Evento
   - Mi Dashboard
4. Guardar menú

## Verificación Post-Instalación

### Checklist de Verificación

- [ ] Plugin activado correctamente
- [ ] No hay errores PHP en logs
- [ ] Menú "Eventos" visible en admin
- [ ] Permalinks actualizados
- [ ] Tablas de BD creadas (verificar con phpMyAdmin)
- [ ] Taxonomías creadas y accesibles
- [ ] Evento de prueba creado
- [ ] Páginas con shortcodes funcionando
- [ ] CSS y JS cargando correctamente
- [ ] Formularios AJAX funcionando
- [ ] Emails de prueba enviándose

### Verificar Tablas de Base de Datos

Ejecutar en phpMyAdmin o via WP-CLI:

```sql
SHOW TABLES LIKE 'wp_event_show_%';
```

Deberías ver:

- wp_event_show_attendees
- wp_event_show_logs
- wp_event_show_termmeta

### Probar Funcionalidades

1. **Registro de Asistente:**

   - Ir a evento de prueba
   - Completar formulario de registro
   - Verificar email de confirmación
   - Verificar asistente en panel admin

2. **Exportación CSV:**

   - Ir a listado de eventos
   - Hover sobre evento con asistentes
   - Click "Exportar asistentes"
   - Verificar descarga de CSV

3. **Logs:**
   - Ir a: `Eventos > Logs del Sistema`
   - Verificar que hay registros de actividad

## Configuración Avanzada

### Configurar SMTP para Emails

**Opción 1: Plugin WP Mail SMTP**

```bash
wp plugin install wp-mail-smtp --activate
```

Configurar:

1. `Ajustes > WP Mail SMTP`
2. Seleccionar proveedor (Gmail, SendGrid, etc.)
3. Ingresar credenciales
4. Enviar email de prueba

**Opción 2: Configuración en wp-config.php**

```php
// Añadir a wp-config.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu-email@gmail.com');
define('SMTP_PASS', 'tu-password');
define('SMTP_SECURE', 'tls');
```

### Optimización de Rendimiento

**1. Caché de Objetos**

```bash
wp plugin install redis-cache --activate
wp redis enable
```

**2. CDN para Assets**

Añadir a functions.php del tema:

```php
add_filter('event_show_asset_url', function($url) {
    return str_replace(
        home_url(),
        'https://cdn.tudominio.com',
        $url
    );
});
```

**3. Lazy Loading de Imágenes**

Ya implementado en el plugin, verificar que esté activo.

**4. Minificación de Assets**

```bash
# Instalar Autoptimize
wp plugin install autoptimize --activate
```

Configurar para minificar CSS/JS.

### Seguridad Adicional

**1. Rate Limiting para AJAX**

Añadir a wp-config.php:

```php
define('EVENT_SHOW_RATE_LIMIT', 10); // Requests por minuto
```

**2. reCAPTCHA en Formularios**

Instalar plugin:

```bash
wp plugin install google-captcha --activate
```

**3. Backup Automático**

```bash
wp plugin install updraftplus --activate
```

Configurar backups diarios.

### Multiidioma

**Opción 1: WPML**

```bash
# Instalar WPML
# Registrar plugin en WPML > String Translation
```

**Opción 2: Polylang**

```bash
wp plugin install polylang --activate
# Crear traducción de .pot a .po/.mo
```

### Personalización de Templates

Copiar templates al tema:

```bash
mkdir -p wp-content/themes/tu-tema/event-show/
cp wp-content/plugins/event-show-base/templates/* \
   wp-content/themes/tu-tema/event-show/
```

Editar según necesidades.

## Solución de Problemas

### Error: "Plugin no se activa"

**Causa:** Incompatibilidad de PHP  
**Solución:**

```bash
# Verificar versión PHP
php -v

# Debe ser >= 7.4
```

### Error: "Página en blanco después de activar"

**Causa:** Error fatal de PHP  
**Solución:**

1. Activar debug en wp-config.php:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
2. Revisar /wp-content/debug.log
3. Desactivar plugin via FTP si es necesario

### Error: "Shortcodes no funcionan"

**Solución:**

1. Verificar que plugin esté activado
2. Actualizar permalinks
3. Verificar que shortcode esté escrito correctamente
4. Probar en página simple sin builders

### Error: "AJAX retorna 0"

**Solución:**

1. Verificar que esté en página del evento correcto
2. Limpiar caché de navegador
3. Verificar en consola de desarrollador
4. Revisar nonces en eventShowData

### Error: "CSS no se aplica"

**Solución:**

1. Limpiar caché (navegador + plugin)
2. Verificar que archivo CSS existe:
   ```
   /wp-content/plugins/event-show-base/assets/css/public.css
   ```
3. Verificar permisos del archivo (644)
4. Forzar regeneración:
   ```bash
   wp cache flush
   ```

### Error: "Emails no se envían"

**Solución:**

1. Instalar plugin de logging:
   ```bash
   wp plugin install wp-mail-logging --activate
   ```
2. Verificar logs de email
3. Configurar SMTP si hosting no lo soporta
4. Verificar configuración en spam

## Despliegue en Producción

### Checklist Pre-Despliegue

- [ ] Backup completo del sitio
- [ ] Backup de base de datos
- [ ] Probar en staging primero
- [ ] Desactivar WP_DEBUG
- [ ] Minificar CSS/JS
- [ ] Optimizar imágenes
- [ ] Configurar CDN
- [ ] Configurar SMTP
- [ ] Habilitar caché
- [ ] SSL/HTTPS activo
- [ ] Probar todos los formularios
- [ ] Verificar emails funcionando
- [ ] Probar en móviles
- [ ] Verificar compatibilidad con tema

### Configuración de Producción

**wp-config.php:**

```php
// Desactivar debug
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// Optimizaciones
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', 5);
define('EMPTY_TRASH_DAYS', 30);

// Seguridad
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);
```

### Migración de Staging a Producción

```bash
# 1. Exportar staging
wp db export staging-backup.sql
tar -czf staging-files.tar.gz wp-content/

# 2. Subir a producción
scp staging-backup.sql user@production:/path/
scp staging-files.tar.gz user@production:/path/

# 3. En producción
wp db import staging-backup.sql
tar -xzf staging-files.tar.gz

# 4. Actualizar URLs
wp search-replace 'https://staging.com' 'https://production.com'

# 5. Limpiar caché
wp cache flush
wp rewrite flush
```

### Monitoring Post-Despliegue

**1. Configurar Uptime Monitoring**

- UptimeRobot
- Pingdom
- StatusCake

**2. Error Tracking**

- Sentry
- Rollbar
- New Relic

**3. Performance Monitoring**

- Query Monitor (plugin)
- GTmetrix
- Google PageSpeed Insights

### Mantenimiento Continuo

**Semanal:**

- Revisar logs de errores
- Verificar emails enviándose
- Revisar registros de asistentes
- Backup incremental

**Mensual:**

- Actualizar plugin (si hay actualizaciones)
- Limpiar logs antiguos
- Optimizar base de datos
- Revisar performance
- Backup completo

**Trimestral:**

- Auditoría de seguridad
- Revisar capacidad de servidor
- Optimizar imágenes antiguas
- Revisar y actualizar documentación

## Recursos Adicionales

- **Documentación Completa:** README.md
- **API Técnica:** TECHNICAL.md
- **Historial de Cambios:** CHANGELOG.md
- **Soporte:** soporte@eventshow.com
- **Comunidad:** https://community.eventshow.com

## Soporte Profesional

Para asistencia profesional en instalación, configuración o personalización:

- Email: pro@eventshow.com
- Teléfono: +1-555-EVENT-01
- Soporte Premium: https://eventshow.com/support

---

**Última actualización:** Enero 2024  
**Versión del documento:** 1.0.0
