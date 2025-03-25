# Gantry 5 Content Security Policy (CSP) Requirements

## Overview

[Content Security Policy (CSP)](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP) is a security mechanism that helps prevent Cross-Site Scripting (XSS) and data injection attacks. While using strict CSP settings is generally recommended for websites, Gantry 5 requires certain CSP directives to function properly in the administrator area.

## Required CSP Directives

Gantry 5 administration requires the following CSP directives:

```
script-src 'self' 'unsafe-eval';
```

The `unsafe-eval` directive is specifically needed for:
- Cache clearing operations
- Editing functionality
- JSON parsing and handling
- Various admin UI interactions

## Example CSP Header for Admin Area

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;
```

## Recommendations

1. **Split CSP Policies**:
   - Use a stricter policy for your frontend website
   - Use a more permissive policy with `unsafe-eval` for the admin area only

2. **Security Balance**:
   - Consider keeping `unsafe-eval` only in the administrator sections of your site
   - Use stricter CSP settings for all public-facing pages

## Technical Explanation

Gantry 5 uses JavaScript bundling tools like Browserify which rely on `eval()` or `new Function()` constructs for certain operations. Additionally, the dynamic nature of the admin interface requires runtime code evaluation in some cases.

These requirements may change in future versions as we continue to improve Gantry's CSP compatibility.