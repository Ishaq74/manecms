# Contexte Identity

> Contrat de domaine (cahier §12), version P05. Comptes et authentification : Fortify (P00).

## Mission

Savoir qui est à l'écran : comptes, connexion, double authentification, sessions et appareils.

## Périmètre

- Comptes (`users`), mots de passe, vérification d'email, 2FA TOTP, passkeys (Fortify).
- Suppression de compte (`DeleteUser`).
- Sessions (driver `database`) : liste, déconnexion d'une session ou de toutes les autres.
- Appareils connus et alerte « Nouvelle connexion ».
- Revérification de l'identité avant une action sensible (`ConfirmIdentity`).

## Concepts possédés

| Concept | Rôle |
|:--|:--|
| `User` | Compte ; global, hors tenant |
| `UserDevice` | Navigateur et réseau (/24, /48) déjà vus pour un compte |
| `DeviceName` | Nom lisible d'un user agent (« Firefox sur Windows ») |

## Tables possédées

`users`, `password_reset_tokens`, `sessions`, `passkeys`, `personal_access_tokens`, `user_devices`.

## Invariants

| Invariant | Protection |
|:--|:--|
| Un appareil par empreinte et par compte | unique `(user_id, fingerprint)` |
| Un identifiant de session ne quitte jamais le serveur | l'écran manipule `RevokeSessions::keyOf()` (SHA-256) |
| « Déconnecter les autres sessions » exige le mot de passe et invalide « se souvenir de moi » | `RevokeSessions::others()` : rotation de `remember_token` |
| Transfert et archivage d'un tenant exigent mot de passe et code 2FA si activée | `ConfirmIdentity` |

## Commandes

`DeleteUser`, `RevokeSessions::one()`, `RevokeSessions::others()`, `RecordSignInDevice`
(sur l'événement `Login`), `ConfirmIdentity`.

## Événements

Audit (plateforme) : `identity.login.*`, `identity.logout`, `identity.device.recorded`,
`identity.session.revoked`, `identity.session.revoked_others`, `identity.account.deleted`.
Email : `NewSignInNotification` quand un compte déjà connu se connecte depuis un appareil inconnu.

## Liens avec les autres contextes

- **Authorization** : la 2FA confirmée lève l'exigence MFA des tenants.
- **Tenancy** : `DeleteUser` refuse tant que l'utilisateur possède un tenant actif.

## Tests

`tests/Feature/Identity/*`, `tests/Feature/Auth/*`, `tests/Feature/Settings/*`,
`tests/Feature/Audit/AuthenticationAuditTest.php`.
