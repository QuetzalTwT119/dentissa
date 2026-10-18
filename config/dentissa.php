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
    | Prototipo local de IA para el asistente público
    |--------------------------------------------------------------------------
    |
    | Ollama se ejecuta en la red privada de Docker. Se mantiene apagado por
    | defecto; sin fuentes activas o si el modelo falla, el chat usa su fallback.
    |
    */

    'asistente_ia' => [
        'habilitada' => (bool) env('DENTISSA_ASISTENTE_IA_HABILITADA', false),
        'ollama_url' => env('DENTISSA_OLLAMA_BASE_URL', 'http://ollama:11434'),
        'ollama_modelo' => env('DENTISSA_OLLAMA_MODELO', 'granite3.3:2b'),
        'timeout_segundos' => (int) env('DENTISSA_OLLAMA_TIMEOUT', 60),
        'max_fuentes' => (int) env('DENTISSA_ASISTENTE_IA_MAX_FUENTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Datos de contacto (sitio público)
    |--------------------------------------------------------------------------
    |
    | El modo demostración usa información ficticia claramente identificada.
    | Desactívalo y configura los datos reales antes de abrir el sitio al público.
    |
    */

    'modo_demo' => (bool) env('DENTISSA_MODO_DEMO', true),

    'contacto' => [
        'telefono' => env('DENTISSA_TELEFONO', '+52 55 0000 0000'),
        'correo' => env('DENTISSA_CORREO', 'hola@dentissa.demo'),
        'direccion' => env('DENTISSA_DIRECCION', 'Consultorio de demostración · Ciudad de México'),
        'horario_texto' => env('DENTISSA_HORARIO_TEXTO', 'Lunes a viernes · 9:00 a 18:00'),
        'telegram_usuario' => env('DENTISSA_TELEGRAM_USUARIO', '@dentissa_demo'),
        'telegram_url' => env('DENTISSA_TELEGRAM_URL'),
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
