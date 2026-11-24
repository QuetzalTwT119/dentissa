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
|   - doctor: por ahora exactamente lo que tenía el antiguo "admin".
|     Para restringirlo más adelante, quita módulos de su lista.
|   - asistente: el panel de inicio, la Agenda con su asistente y la campana
|     (24/11/2026: es quien agenda, confirma y reagenda) y la historia clínica
|     (cuestionario de salud, 22/11/2026). No ve consultas, notas privadas ni
|     el expediente.
|     Para habilitarle algo más, agrega el módulo a su lista.
|
| Cerrar sesión no es un módulo: siempre está disponible.
*/

return [

    'modulos' => [
        'inicio' => ['dashboard'],
        'cuenta' => ['profile.*', 'dos-pasos.*'],
        'notificaciones' => ['notificaciones.*'],
        'agenda' => ['citas.*', 'asistente-agenda.*'],
        // Cuestionario de historia clínica; va antes de "pacientes" porque sus rutas
        // también empiezan con admin.pacientes.* (22/11/2026).
        'historia_clinica' => ['admin.historias.*', 'admin.pacientes.historia.*'],
        'pacientes' => ['admin.pacientes.*', 'historial.imagenes.show'],
        'usuarios' => ['admin.usuarios.*'],
        'reportes' => ['admin.reportes.*'],
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

        // Igual que el antiguo "admin" (todas las rutas del panel). Temporal.
        'doctor' => [
            'inicio', 'cuenta', 'notificaciones', 'agenda', 'historia_clinica', 'pacientes', 'usuarios', 'reportes',
            'mensajes', 'control_actividades', 'catalogo', 'contenido', 'configuracion',
        ],

        // Inicio, Agenda y campana (24/11/2026) y cuestionario de historia clínica (22/11/2026).
        'asistente' => ['inicio', 'agenda', 'notificaciones', 'historia_clinica'],
    ],

];
