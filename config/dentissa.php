<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de disponibilidad de citas
    |--------------------------------------------------------------------------
    |
    | Referencia: sección 1.2 del documento de especificaciones ("Valoración
    | de canal"). El documento describe una capacidad configurable por el
    | admin por bloque horario y por día de la semana; esta es una versión
    | simplificada (un único valor global) hasta que se construya el panel
    | de configuración completo.
    |
    */

    'capacidad_simultanea' => env('DENTISSA_CAPACIDAD_SIMULTANEA', 2),

    'horario_apertura' => env('DENTISSA_HORARIO_APERTURA', 8),

    'horario_cierre' => env('DENTISSA_HORARIO_CIERRE', 18),

    'intervalo_minutos' => env('DENTISSA_INTERVALO_MINUTOS', 30),

    /*
    |--------------------------------------------------------------------------
    | Propuestas del asistente de agenda
    |--------------------------------------------------------------------------
    |
    | Para tratamientos largos (90 minutos o más) y procedimientos quirúrgicos
    | se reserva un margen de preparación/limpieza después de la cita.
    |
    */

    'asistente_agenda' => [
        'margen_minutos' => env('DENTISSA_MARGEN_ENTRE_CITAS_MINUTOS', 15),
        'duracion_larga_minutos' => env('DENTISSA_DURACION_CITA_LARGA_MINUTOS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Datos de contacto (sitio público)
    |--------------------------------------------------------------------------
    |
    | Se muestran en la sección de contacto del landing público. Son valores
    | de ejemplo — reemplázalos con los datos reales del consultorio.
    |
    */

    'contacto' => [
        'telefono' => env('DENTISSA_TELEFONO', '+52 55 1234 5678'),
        'whatsapp' => env('DENTISSA_WHATSAPP', '5215512345678'),
        'correo' => env('DENTISSA_CORREO', 'contacto@melissalopez.dental'),
        'direccion' => env('DENTISSA_DIRECCION', 'Av. Ejemplo 123, Ciudad de México'),
        'horario_texto' => env('DENTISSA_HORARIO_TEXTO', 'Lunes a viernes, 8:00 a 18:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API (Meta) — notificaciones de citas
    |--------------------------------------------------------------------------
    |
    | Objetivo específico 4: recordatorios, confirmaciones y cambios de
    | horario por WhatsApp, con correo como respaldo. Sin phone_number_id o
    | access_token, WhatsappService no intenta la llamada HTTP — solo la deja
    | escrita en el log y NotificacionService cae directo a correo.
    |
    */

    'whatsapp' => [
        'phone_number_id' => env('DENTISSA_WHATSAPP_PHONE_ID'),
        'access_token' => env('DENTISSA_WHATSAPP_TOKEN'),
        'api_version' => env('DENTISSA_WHATSAPP_API_VERSION', 'v21.0'),
        'plantilla_idioma' => env('DENTISSA_WHATSAPP_IDIOMA', 'es_MX'),
    ],

    /* Recursos visuales estáticos aprobados para el sitio público. */
    'imagenes' => [
        'hero' => 'images/dentissa/hero-inicio.jpg',
        'urgencias' => 'images/dentissa/urgencias-dentales.jpg',
        'lista_espera' => 'images/dentissa/lista-espera.jpg',
        'servicios' => [
            'Odontología General' => 'images/dentissa/odontologia-general.jpg',
            'Odontología Estética' => 'images/dentissa/odontologia-estetica.jpg',
            'Ortodoncia' => 'images/dentissa/ortodoncia.jpg',
            'Implantología' => 'images/dentissa/implantologia.jpg',
            'Periodoncia' => 'images/dentissa/periodoncia.jpg',
            'Endodoncia' => 'images/dentissa/endodoncia.jpg',
            'Odontopediatría' => 'images/dentissa/odontopediatria.jpg',
        ],
    ],

];
