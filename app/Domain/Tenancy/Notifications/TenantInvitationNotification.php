<?php

namespace App\Domain\Tenancy\Notifications;

use App\Domain\Tenancy\Models\TenantInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Carries the only copy of the invitation token: the database keeps its hash.
 */
final class TenantInvitationNotification extends Notification
{
    public function __construct(
        public readonly TenantInvitation $invitation,
        public readonly string $tenantName,
        public readonly string $inviterName,
        #[\SensitiveParameter] public readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function acceptUrl(): string
    {
        return route('invitations.show', ['invitation' => $this->invitation->id, 'token' => $this->token]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Join :space on ManeCMS', ['space' => $this->tenantName]))
            ->line(__(':inviter invites you to join the space :space.', ['inviter' => $this->inviterName, 'space' => $this->tenantName]))
            ->action(__('Accept the invitation'), $this->acceptUrl())
            ->line(__('This invitation expires on :date.', ['date' => $this->invitation->expires_at->translatedFormat('j F Y')]))
            ->line(__('If you were not expecting it, you can ignore this email.'));
    }
}
