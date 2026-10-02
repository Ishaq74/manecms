<?php

namespace App\Domain\Authorization\Enums;

/**
 * Why the Policy Engine denied a request. The values are stable: they are
 * stored in the audit trail.
 */
enum DenialReason: string
{
    case UnknownPermission = 'unknown_permission';
    case NoTenantContext = 'no_tenant_context';
    case ActorMismatch = 'actor_mismatch';
    case TenantArchived = 'tenant_archived';
    case WorkspaceRestricted = 'workspace_restricted';
    case NotGrantable = 'not_grantable';
    case MissingPermission = 'missing_permission';
    case MfaRequired = 'mfa_required';
    case ForeignTarget = 'foreign_target';
    case OwnerProtected = 'owner_protected';
    case SelfTarget = 'self_target';
    case PeerAdmin = 'peer_admin';
    case OwnerRoleNotAssignable = 'owner_role_not_assignable';
    case RoleExceedsActor = 'role_exceeds_actor';
    case SegregationOfDuties = 'segregation_of_duties';
}
