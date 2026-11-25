<?php

/*
|--------------------------------------------------------------------------
| Permisos por rol (RBAC), 22/11/2026
|--------------------------------------------------------------------------
|
| ÚNICO lugar donde se decide qué puede ver y hacer cada rol. Lo leen:
|   - el middleware "permiso" (todas las rutas del panel),
|   - User::puede() / RolUsuario::puede() (menú, panel de inicio, políticas),
|   - Permisos::rolesCon() (a quién se mandan avisos internos, canal en vivo).
|
| "modulos": cada módulo agrupa nombres de ruta (se aceptan comodines *).
| Una ruta del panel que no esté en ningún módulo se NIEGA (falla cerrado);
| PermisosPorModuloTest revisa que ninguna quede sin módulo.
|
| "roles": qué módulos tiene cada rol. '*' = todos.
|   - super_admin: la doctora dueña; todo.
|   - doctor (25/11/2026): solo lo clínico. Agenda, pacientes con expediente,
|     historia clínica y cobros. No administra el consultorio: usuarios,
|     configuración y horarios, catálogo y precios, contenido de la portada,
|     reportes, mensajes ni control de actividades.
|   - asistente (25/11/2026): recepción. Agenda, alta y datos de contacto de
|     pacientes (directorio_pacientes, SIN expediente, consultas ni recetas),
|     cuestionario de historia clínica, cobros y mensajes de contacto.
|   - Los tres tienen "cuenta" (Mi perfil y verificación en dos pasos): antes
|     la asistente no podía cambiar su contraseña.
|     Para habilitarle algo más a un rol, agrega el módulo a su lista.
|
| Cerrar sesión no es un módulo: siempre está disponible.
*/

return [

    'modulos' => [
        'inicio' => ['dashboard'],
        'cuenta' => ['profile.*', 'dos-pasos.*'],
        'notificaciones' => ['notificaciones.*'],
        'agenda' => ['citas.*', 'asistente-agenda.*'],
        // Directorio (25/11/2026): lista, alta y datos de contacto. Va antes de
        // "pacientes" (el expediente), que se queda con el resto de admin.pacientes.*.
        'directorio_pacientes' => [
            'admin.pacientes.index', 'admin.pacientes.create', 'admin.pacientes.store',
            'admin.pacientes.edit', 'admin.pacientes.update',
        ],
        // Cuestionario de historia clínica; va antes de "pacientes" porque sus rutas
        // también empiezan con admin.pacientes.* (22/11/2026).
        'historia_clinica' => ['admin.historias.*', 'admin.pacientes.historia.*'],
        'pacientes' => ['admin.pacientes.*', 'historial.imagenes.show'],
        'usuarios' => ['admin.usuarios.*'],
        'reportes' => ['admin.reportes.*'],
        // Pagos e ingresos (24/11/2026).
        'pagos' => ['admin.pagos.*'],
        'mensajes' => ['admin.mensajes.*'],
        'control_actividades' => ['admin.auditoria.*'],
        'catalogo' => ['admin.servicios.*', 'admin.tratamientos.*'],
        'contenido' => [
            'admin.ofertas.*', 'admin.testimonios.*', 'admin.galeria.*', 'admin.casos.*', 'admin.preguntas-frecuentes.*',
            'ofertas.index', 'galeria.index',
        ],
        'configuracion' => ['admin.configuracion', 'admin.horarios.*', 'admin.consultorio.*', 'admin.bloqueos.*'],
    ],

    'roles' => [
        'super_admin' => ['*'],

        // Doctores asociados: solo lo clínico (25/11/2026; antes todo, "temporal").
        'doctor' => [
            'inicio', 'cuenta', 'notificaciones', 'agenda', 'directorio_pacientes', 'pacientes', 'historia_clinica', 'pagos',
        ],

        // Recepción (25/11/2026): agenda, directorio sin expediente, cuestionario, cobros y mensajes.
        'asistente' => [
            'inicio', 'cuenta', 'notificaciones', 'agenda', 'directorio_pacientes', 'historia_clinica', 'pagos', 'mensajes',
        ],
    ],

];
