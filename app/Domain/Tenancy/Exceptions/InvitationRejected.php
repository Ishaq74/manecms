<?php

namespace App\Domain\Tenancy\Exceptions;

use App\Domain\Platform\Errors\DomainError;

/**
 * An invitation that cannot be accepted, with the reason the invitee sees.
 */
final class InvitationRejected extends DomainError
{
    private function __construct(private readonly string $stableCode, string $message, private readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }

    public static function invalid(): self
    {
        return new self('INVITATION_INVALID', __('This invitation link is not valid.'), 404);
    }

    public static function expired(): self
    {
        return new self('INVITATION_EXPIRED', __('This invitation has expired. Ask for a new one.'), 410);
    }

    public static function revoked(): self
    {
        return new self('INVITATION_REVOKED', __('This invitation has been revoked.'), 410);
    }

    public static function alreadyAccepted(): self
    {
        return new self('INVITATION_ALREADY_ACCEPTED', __('This invitation has already been used.'), 410);
    }

    public static function emailMismatch(): self
    {
        return new self('INVITATION_EMAIL_MISMATCH', __('This invitation was sent to another email address.'), 403);
    }

    public static function alreadyMember(): self
    {
        return new self('INVITATION_ALREADY_MEMBER', __('You are already a member of this space.'), 409);
    }

    public static function tenantArchived(): self
    {
        return new self('INVITATION_TENANT_ARCHIVED', __('This space is archived.'), 410);
    }

    public function errorCode(): string
    {
        return $this->stableCode;
    }

    public function status(): int
    {
        return $this->httpStatus;
    }
}
