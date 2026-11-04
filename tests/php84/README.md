# PHP 8.4 nullable-rewriter checks

Run with **actual PHP 8.4 CLI**:

```sh
php tests/php84/nullable-rewriter.php
```

The standalone test loads the existing rewriter without invoking its file-writing
main function. It covers nullable intersections and DNF unions, references,
grouped attributes, scalar/array types, promoted parameters, idempotence and
unchanged function bodies. Rewritten fixtures are parsed by PHP 8.4; function
cases exercise accepted/omitted-null/rejected calls under E_ALL, while the
promotion case checks default/data preservation.

The existing self-tests also parse their expected output on PHP 8.2+, where DNF
syntax exists. Earlier builders retain their textual self-tests. The existing
Composer upper bound remains below PHP 8.5; this suite does not extend that bound
or establish whole-platform compatibility on other PHP versions.

The standalone rewriter test loads no CMS, database, network or installed-site files. Joomla, WordPress,
Grav and real template/particle rendering require separate integration tests.
The legacy SCSS Compiler interpolation change has PHP 8.4 lint coverage only;
this suite does not load its legacy dependencies or verify legacy arithmetic.

Maintenance author: Matthew C. Murphy, https://MatthewxMurphy.com.
Product/support: https://builtnotbegged.com. New maintenance/test contributions use
MIT; existing Gantry and third-party licenses and attribution remain unchanged.

## Grav error-service integration

The Grav adapter uses Grav's current Admin user when available, otherwise its
frontend user. Detailed Gantry error-service admission requires an authenticated
`UserInterface` with both `admin.login` and `admin.plugins`; unavailable users,
guests and pages-only administrators are denied. The adapter preserves Grav's
existing error object and does not change Grav's own error-display configuration.

Verify this separately on a disposable Grav installation with its real Login and
Admin plugins: guest and pages-only admin requests must be denied, plugin-settings
admin requests allowed, and missing-user bootstrap denied. Exercise both Admin-user
selection and frontend-user fallback. This is not covered by the standalone
nullable-rewriter suite and does not establish compatibility for another Grav version.

For the separate dependency-backed context/ACL checks, use an owned disposable
Grav installation with the real Admin plugin and its installed dependencies:

```sh
GANTRY_GRAV_TEST_SITE=disposable php tests/php84/grav-error-access.php /path/to/disposable/grav
```

This test uses unsaved real Grav user objects and the real Admin class, changing
only in-memory context/authentication flags to exercise ACLs and user selection.
It creates no account or session and does not prove password authentication.
It also checks incomplete second-factor authorization and that registration
preserves Grav's error object. Actual guest/pages-only/plugin-admin HTTP login
checks remain a separate integration requirement.
