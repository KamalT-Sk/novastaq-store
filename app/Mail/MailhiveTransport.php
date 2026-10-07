<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/**
 * Lets Laravel's own mail (password resets, email verification, notifications) go out
 * through Mailhive Send, the same API the store's order emails use. Mailer name: "mailhive".
 */
class MailhiveTransport extends AbstractTransport
{
    public function __construct(private readonly string $key, private readonly string $url)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $list = fn (array $addresses) => array_map(fn (Address $a) => $a->toString(), $addresses);

        $payload = array_filter([
            'from' => $list($email->getFrom())[0] ?? null,
            'to' => $list($email->getTo()),
            'cc' => $list($email->getCc()) ?: null,
            'bcc' => $list($email->getBcc()) ?: null,
            'reply_to' => $list($email->getReplyTo()) ?: null,
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
        ]);

        $res = Http::withToken($this->key)->acceptJson()->timeout(15)->post(rtrim($this->url, '/').'/send/emails', $payload);
        if (! $res->successful()) {
            throw new TransportException('Mailhive: '.$res->status().' '.$res->body());
        }
    }

    public function __toString(): string
    {
        return 'mailhive';
    }
}
