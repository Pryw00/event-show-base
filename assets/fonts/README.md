# Tipografías del Plugin

## Estructura de archivos

Coloca aquí los archivos de tu tipografía personalizada para títulos.

### Archivos requeridos:

Renombra tus archivos según corresponda:

- `TituloFont-Regular.woff2` (peso 400)
- `TituloFont-Regular.woff` (peso 400)
- `TituloFont-Regular.ttf` (peso 400)
- `TituloFont-Bold.woff2` (peso 700)
- `TituloFont-Bold.woff` (peso 700)
- `TituloFont-Bold.ttf` (peso 700)
- `TituloFont-Black.woff2` (peso 900)
- `TituloFont-Black.woff` (peso 900)
- `TituloFont-Black.ttf` (peso 900)

### Formatos recomendados:

1. **WOFF2** - Formato moderno, mejor compresión (prioritario)
2. **WOFF** - Compatibilidad con navegadores antiguos
3. **TTF** - Fallback adicional

### Convertir archivos:

Si solo tienes archivos TTF u OTF, puedes convertirlos en:

- https://transfonter.org/
- https://convertio.co/es/font-converter/

### Notas:

- El CSS ya está configurado en `assets/css/public.css`
- **Poppins** (Google Fonts) se usa para textos generales
- **TituloFont** (local) se usa para títulos y encabezados
- Asegúrate de que el nombre de los archivos coincida exactamente
