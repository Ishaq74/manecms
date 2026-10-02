<?php

namespace App\Domain\Platform\Models;

use App\Domain\Audit\Models\AuditEvent;

/**
 * Platform-level audit events (no tenant), as operators read them: the `platform_audit_events` view.
 */
class PlatformAuditEvent extends AuditEvent
{
    protected $table = 'platform_audit_events';
}
