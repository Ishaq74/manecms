<?php

namespace App\Domain\Identity\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "New sign-in to your account": sent when a known account signs in from an unknown device.
 */
final class NewSignInNotification extends Notification
{
    public function __construct(
        public readonly string $device,
        public readonly ?string $ipAddress,
        public readonly CarbonImmutable $signedInAt,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New sign-in to your ManeCMS account'))
            ->line(__('Your account was signed in from a new device: :device.', ['device' => $this->device]))
            ->line(__('Address: :ip — :date', ['ip' => $this->ipAddress ?? '—', 'date' => $this->signedInAt->translatedFormat('j F Y H:i')]))
            ->action(__('Review your sessions'), route('sessions.index'))
            ->line(__('If it was not you, change your password and sign out the other sessions.'));
    }
}
