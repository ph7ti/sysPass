# sysPass — fork ph7ti (v4.0.0)

This repository is a **fork of [sysPass](https://github.com/nuxsmin/sysPass)**, maintained at
<https://github.com/ph7ti/sysPass>. It is based on upstream sysPass **3.2.11** and is distributed
under the same GNU GPLv3 license (see `COPYING`).

> **Important:** version 4.0 requires **PHP 8.2**. PHP 7.4 is no longer supported.

---

## What's new in 4.0.0

The most important and user-observable changes in this release:

- **PHP 8.2 runtime.** The application and all main dependencies were updated to a supported
  runtime. This is a breaking change for environments still running PHP 7.4.
- **Absolute session timeout.** Sessions now expire after a maximum of **8 hours** (configurable in
  *Settings → General*), even while the user remains active. The existing inactivity timeout
  (300 seconds by default) is unchanged.
- **Complete logout.** Logout now destroys the session on the server and expires the session and
  auxiliary cookies. A cookie captured before logout can no longer be reused.
- **Hardened session cookies.** Cookies are sent with `HttpOnly` and `SameSite=Lax`, and with
  `Secure` when the application is accessed over HTTPS.
- **Stronger form encryption.** The RSA key used to protect passwords sent by forms was raised from
  1024 to **2048 bits**. The key pair is regenerated automatically on first start when an old
  1024-bit key is found.
- **Structured logs.** File logs are now written as one JSON object per line, which makes them
  easier to collect and search.
- **Container and CI assets.** A multi-stage `php:8.2-fpm` + Nginx image with a non-root user and
  healthcheck is available in `build/`, together with a sample `docker-compose.yml` and a GitHub
  Actions workflow.
- **Automated test baseline.** The PHPUnit suite was upgraded and is green on PHP 8.2
  (1,468 tests in the core suite).

### Foundations included (not yet user-facing)

The release also adds internal building blocks for the next features: a cancellable
**pre-authentication event** (`login.before`, to support SSO and MFA without opening the vault too
early), feature flags, and a migration planning/rollback harness.

### Not included in this release

SSO/OIDC, core MFA/TOTP, multiple groups per user, versioned credential history, backup scheduling,
and API service accounts are **planned but not available yet**.

---

## Upgrade notes from 3.2.x

1. **Back up** the database and the application configuration before upgrading.
2. Make sure the host/container meets the new requirements: **PHP 8.2** with the extensions listed in
   `composer.json`, and MySQL/MariaDB.
3. The RSA key pair is regenerated automatically when it is still 1024 bits. This only affects
   passwords being submitted by a form at that moment; stored account data is not affected.
4. Existing sessions are invalidated after the upgrade; users will need to log in again.
5. The new `sessionTimeoutAbsolute` setting defaults to **28800 seconds (8 hours)**. Set it to `0`
   in the configuration to disable the absolute limit temporarily.

---

## Running with containers

```sh
docker build -f build/Dockerfile -t syspass:4.0.0 .
# or, with the sample database service:
cd build && docker compose up --build
```

The application listens on port **8080**. The image includes a healthcheck at `/health`.

## Tests

```sh
composer install
vendor/bin/phpunit -c tests/phpunit.xml --testsuite Core
```

The `Modules` suite requires a running sysPass instance configured for API tests.

---

## Original sysPass features

PHP web based Password Manager for business and personal use.

- [x] AES-256 encryption in CTR mode
- [x] RSA for sending passwords from forms
- [x] Two-factor authentication support through compatible plugins
- [x] HTML5 and Ajax interface
- [x] Users, groups and profiles management with up to 29 access levels
- [x] MySQL, OpenLDAP and Active Directory authentication
- [x] Tags, custom fields, public links, private accounts, favorites, history, etc.
- [x] Activity notifications by email and in-app, and event log
- [x] Multilanguage
- [x] JSON-RPC API

Installation instructions and documentation: <https://doc.syspass.org>

---

This software is published under the GNU GPLv3 license. For details, see the `COPYING` file.

---

Upstream project:

- <https://syspass.org>
- <https://doc.syspass.org>
- <https://github.com/nuxsmin/sysPass>
- <https://hub.docker.com/r/nuxsmin/docker-syspass/>

This fork:

- <https://github.com/ph7ti/sysPass>

## Contributors

This project exists thanks to all the people who contribute.
<a href="https://github.com/nuxsmin/sysPass/graphs/contributors"><img src="https://opencollective.com/sysPass/contributors.svg?width=890&button=false" /></a>

## Backers

Thank you to all our backers! 🙏 [[Become a backer](https://opencollective.com/sysPass#backer)]

<a href="https://opencollective.com/sysPass#backers" target="_blank"><img src="https://opencollective.com/sysPass/backers.svg?width=890"></a>

## Sponsors

Support this project by becoming a sponsor. Your logo will show up here with a link to your website. [[Become a sponsor](https://opencollective.com/sysPass#sponsor)]

<a href="https://opencollective.com/sysPass/sponsor/0/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/0/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/1/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/1/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/2/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/2/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/3/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/3/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/4/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/4/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/5/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/5/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/6/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/6/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/7/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/7/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/8/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/8/avatar.svg"></a>
<a href="https://opencollective.com/sysPass/sponsor/9/website" target="_blank"><img src="https://opencollective.com/sysPass/sponsor/9/avatar.svg"></a>

---

Though it would be preferable to ask or report issues through GitHub's website, you can reach the
upstream author by email (e.g. CVEs, contribution): nuxsmin[a_t]syspass[d_o_t]org
