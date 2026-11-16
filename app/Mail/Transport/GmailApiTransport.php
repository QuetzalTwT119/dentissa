<?php

namespace App\Mail\Transport;

use App\Services\Google\GoogleAccessToken;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $raw = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');

        $response = Http::withToken(app(GoogleAccessToken::class)->obtener())
            ->acceptJson()
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $raw,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gmail API no pudo enviar el mensaje (HTTP '.$response->status().').');
        }
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
