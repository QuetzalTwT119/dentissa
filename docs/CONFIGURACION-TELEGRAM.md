# Telegram para pruebas locales

Dentissa recibe y responde mensajes privados con un proceso de sondeo de la
API de Telegram. Para desarrollo no necesita un hosting ni abrir puertos de
entrada: Docker necesita permanecer encendido y tener acceso a internet. Al
desplegar el proyecto, el contenedor `telegram` se reinicia automáticamente.

## Configuración

1. Crea o usa el bot de prueba desde [@BotFather](https://t.me/BotFather) y
   copia su token.
2. Guarda el token únicamente en `.env`:

   ```dotenv
   DENTISSA_TELEGRAM_BOT_TOKEN=token_del_bot
   ```

3. Arranca la app y el bot desde la carpeta del proyecto:

   ```powershell
   docker compose --profile telegram up -d
   docker compose ps
   ```

   Deben aparecer `app`, `mysql` y `telegram` en ejecución. No compartas ni
   publiques el token.

## Probar el bot

Abre [@DentissaCitabot](https://t.me/DentissaCitabot) y pulsa **Iniciar** o
envía `/start` una vez. Después puedes escribir preguntas normales; no hace
falta repetir `/start` en cada conversación. Telegram exige que la persona
inicie el chat antes de que un bot pueda responderle.

Comandos disponibles: `/ayuda` muestra las opciones, `/agendar` inicia la
agenda y `/cancelar` detiene el proceso sin guardar una cita. El bot permite
elegir servicio, tratamiento y horario con botones; luego solicita nombre,
apellido, teléfono y correo, muestra un resumen y solo reserva al tocar
**Confirmar cita**. La fecha se escribe con formato `AAAA-MM-DD`; el motivo de
la visita es opcional.

El asistente usa la información aprobada para responder dudas y orientar casos
de urgencia. Tanto Telegram como la página pública usan las mismas reglas de
catálogo y disponibilidad al guardar; el horario se vuelve a validar en una
transacción y una confirmación repetida no duplica la cita. La conversación
temporal de agenda vence a los 20 minutos. Para pruebas, utiliza datos
ficticios.

## Operación

- Ver el estado: `docker compose ps`
- Ver el registro del bot: `docker compose logs -f telegram`
- Pausar solo el bot: `docker compose stop telegram`
- Reanudarlo: `docker compose start telegram`
- Reconstruir la imagen después de cambiar código: `docker compose --profile telegram up -d --build app telegram`

El bot usa sondeo largo (`getUpdates`), no webhook. Si más adelante se configura
un webhook de Telegram, habrá que quitarlo para volver a usar sondeo; Telegram
no permite ambos mecanismos simultáneamente.
