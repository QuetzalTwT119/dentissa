<?php

// Teléfono de la clínica: el mismo que publica la portada. El enlace tel:
// se arma aquí una sola vez; un número nacional de 10 dígitos recibe +52.
// `?:` y no el default de env(): .env.example trae DENTISSA_TELEFONO vacío y
// env() devolvería '' en lugar del número.
// 22/11/2026: celular del consultorio (antes 755 102 0089).
$telefonoClinica = env('DENTISSA_TELEFONO') ?: '755 113 1833';
$digitosTelefono = preg_replace('/\D/', '', (string) $telefonoClinica);

return [
    /*
     * Módulos del panel que hoy no se muestran en ninguna página pública
     * (revisión del 20/11/2026): se OCULTAN del menú, no se borran. Rutas,
     * datos y pruebas siguen intactos; para reactivarlos basta con la variable.
     */
    'modulos' => [
        'galeria' => (bool) env('DENTISSA_MODULO_GALERIA', false),
        'casos' => (bool) env('DENTISSA_MODULO_CASOS', false),
        // Pagos e ingresos (24/11/2026): hecho pero oculto por decisión del usuario
        // ("no se va a ocupar"). Apagado, nadie tiene el módulo "pagos" (Permisos).
        'pagos' => (bool) env('DENTISSA_MODULO_PAGOS', false),
    ],

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
    | Teléfono y WhatsApp ya usan por defecto el número que publica la portada:
    | la portada y la página del recordatorio los leen de aquí, para que un
    | paciente nunca vea un número de ejemplo.
    |
    */

    'modo_demo' => (bool) env('DENTISSA_MODO_DEMO', true),

    'contacto' => [
        'telefono' => $telefonoClinica,
        'telefono_enlace' => 'tel:+'.(strlen($digitosTelefono) === 10 ? '52'.$digitosTelefono : $digitosTelefono),
        'whatsapp' => env('DENTISSA_WHATSAPP') ?: '527551131833',
        'correo' => env('DENTISSA_CORREO', 'hola@dentissa.demo'),
        'direccion' => env('DENTISSA_DIRECCION', 'Blvd. Montebello 228, 78421 Pozos, S.L.P.'),
        // Portada, bloque "La clínica" (24/11/2026). Se cambian en Configuración → Datos del consultorio.
        'mision' => 'Cuidar y transformar sonrisas con atención odontológica confiable, ética y accesible, explicando cada paso con calma para que decidas informado y sin presión.',
        'vision' => 'Ser el consultorio dental de confianza de las familias de la región, reconocido por su trato humano, su puntualidad y resultados que duran.',
        'horario_texto' => env('DENTISSA_HORARIO_TEXTO', 'Lunes a viernes · 9:00 a 18:00'),
        'telegram_usuario' => env('DENTISSA_TELEGRAM_USUARIO', '@dentissa_demo'),
        'telegram_url' => env('DENTISSA_TELEGRAM_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aviso de privacidad
    |--------------------------------------------------------------------------
    |
    | Los datos del responsable los debe llenar la clínica. Mientras falten, la
    | página /aviso-de-privacidad los marca como "Por completar". Mientras
    | `revisado` sea false, muestra que es un borrador pendiente de revisión
    | legal. Cambia `version` cada vez que cambie el texto: cada paciente
    | guarda la versión que aceptó.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Respaldos automáticos
    |--------------------------------------------------------------------------
    |
    | `php artisan dentissa:respaldar` (diario, 03:00) empaqueta la base y
    | storage/app/private, lo cifra con DENTISSA_RESPALDO_CLAVE y lo sube a la
    | carpeta de Google Drive. Sin la llave los respaldos no se pueden leer:
    | guárdala también fuera del servidor (gestor de contraseñas).
    |
    */

    'respaldos' => [
        'clave' => env('DENTISSA_RESPALDO_CLAVE'),
        'carpeta_drive' => env('DENTISSA_RESPALDO_CARPETA', 'Dentissa respaldos'),
        'dias_retencion' => (int) (env('DENTISSA_RESPALDO_DIAS') ?: 30),
        // Aunque sean viejas, nunca se borran las copias más recientes.
        'minimo_conservar' => 3,
        // Archivos privados que se respaldan (imágenes clínicas, fotos de perfil).
        // DENTISSA_RESPALDO_DIRECTORIO solo sirve para ensayar con archivos
        // ficticios; en el servidor se deja vacío.
        'directorio_archivos' => env('DENTISSA_RESPALDO_DIRECTORIO') ?: storage_path('app/private'),
        // Credenciales que nunca deben salir del servidor, ni cifradas.
        'excluir' => ['google-oauth-client.json', 'google-oauth-token.json'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seguridad del personal
    |--------------------------------------------------------------------------
    |
    | La verificación en dos pasos es obligatoria para el administrador, que ve
    | los historiales clínicos. En un entorno de demostración se puede apagar
    | con DENTISSA_2FA_ADMIN=false; con pacientes reales debe quedar en true.
    |
    */

    // Recetas en PDF (25/11/2026): exigir la cédula profesional está apagado
    // TEMPORALMENTE, mientras la doctora la captura en Mi perfil. Apagado, la
    // receta se imprime y omite la línea de la cédula si está vacía. Para volver
    // a exigirla: DENTISSA_RECETA_EXIGE_CEDULA=true en Railway.
    'recetas' => [
        'exigir_cedula' => filter_var(env('DENTISSA_RECETA_EXIGE_CEDULA', false), FILTER_VALIDATE_BOOL),
    ],

    'seguridad' => [
        'dos_pasos_admin_obligatorio' => filter_var(env('DENTISSA_2FA_ADMIN', true), FILTER_VALIDATE_BOOL),
        // Nombre que verá el personal en su app de autenticación.
        'emisor_dos_pasos' => env('DENTISSA_2FA_EMISOR', 'Dentissa'),
    ],

    'privacidad' => [
        'version' => '2026-11-16',
        'revisado' => (bool) env('DENTISSA_AVISO_REVISADO', false),
        'responsable' => env('DENTISSA_PRIVACIDAD_RESPONSABLE') ?: null,
        'domicilio' => env('DENTISSA_PRIVACIDAD_DOMICILIO') ?: null,
        'correo' => env('DENTISSA_PRIVACIDAD_CORREO') ?: null,
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
        'api_version' => env('DENTISSA_WHATSAPP_API_VERSION', 'v25.0'),
        'plantilla_idioma' => env('DENTISSA_WHATSAPP_IDIOMA', 'es_MX'),
        // Webhook de respuestas (botones del recordatorio). El secreto de la
        // app firma cada aviso de Meta; el token de verificación lo eliges tú
        // y lo escribes igual en el panel de Meta.
        'app_secret' => env('DENTISSA_WHATSAPP_APP_SECRET'),
        'verify_token' => env('DENTISSA_WHATSAPP_VERIFY_TOKEN'),
    ],

    /* Recursos visuales estáticos aprobados para el sitio público. */
    'imagenes' => [
        'hero' => 'images/dentissa/hero-inicio.jpg',
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

    /*
     * Descripción pública de cada especialidad cuando el administrador no ha
     * escrito una propia (Servicio::descripcionPublica). La comparten la
     * portada y la agenda en línea.
     */
    'descripciones_servicios' => [
        'Odontología General' => 'Valoración, limpieza dental, retiro de caries y extracciones para toda la familia.',
        'Odontología Estética' => 'Blanqueamiento dental para devolver la luminosidad natural a tu sonrisa.',
        'Ortodoncia' => 'Alineación dental con brackets estéticos y alineadores invisibles de precisión.',
        'Implantología' => 'Rehabilitación y reposición de piezas con implantes dentales de titanio.',
        'Periodoncia' => 'Tratamiento especializado de encías, hueso de soporte y salud bucal.',
        'Endodoncia' => 'Terapia de conductos radiculares para aliviar dolor y salvar tus piezas naturales.',
        'Odontopediatría' => 'Cuidado dental preventivo y amable diseñado especialmente para niñas y niños.',
    ],

];
