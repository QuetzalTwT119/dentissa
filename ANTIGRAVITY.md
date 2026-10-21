# Alcance de Antigravity en Dentissa

Trabajas directamente en `C:\laragon\www\dentissa`, que es el proyecto real.

## Puedes modificar

Puedes rediseñar todas las interfaces y recursos visuales del proyecto:

- `resources/views/**`: vistas públicas, autenticación, panel administrativo,
  componentes, estados vacíos, errores, formularios, tablas y navegación.
- `resources/css/**` y `resources/js/**`: sistema visual, estilos y comportamiento
  de interfaz que no altere la lógica de negocio.
- `public/images/**`, iconos y recursos públicos estrictamente visuales.

Conserva las variables Blade, componentes, formularios, nombres de campos,
rutas enlazadas, acciones de formularios y controles de autorización ya
existentes. La mejora debe ser visual, responsive y accesible en móvil, tablet
y escritorio.

## No puedes modificar

No modifiques `app/`, `routes/`, `database/`, `config/`, `.env`,
`compose.yaml`, Docker, migraciones, seeders, controladores, modelos,
políticas, middleware, pruebas de backend ni archivos de autenticación.

No inventes datos clínicos o comerciales: teléfono, dirección, horarios,
cédula profesional, precios, testimonios y afirmaciones clínicas requieren
confirmación de la clínica. Si falta un dato, deja una marca visible de
“Pendiente de confirmación”.

Si una mejora visual requiere alterar la lógica, una ruta, un permiso o la base
de datos, detente y repórtalo sin implementarlo.

Después de terminar un grupo de vistas, reconstruye los estilos con
`npm run build` y reporta los archivos modificados. El equipo ejecutará
`scripts/antigravity.ps1` para validar el frontend en Docker antes de aceptar
la entrega.
