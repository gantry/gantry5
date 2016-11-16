---
title: We are Down for Maintenance
robots: noindex,nofollow
routable: false
http_response_code: 503
process:
    markdown: false
    twig: false
gantry:
  outline: _offline

form:
  name: login
  action:
  method: post

  fields:
      - name: username
        type: text
        id: username
        placeholder: Username
        label: PLUGIN_LOGIN.USERNAME_EMAIL
        autofocus: true

      - name: password
        type: password
        id: password
        placeholder: Password
        label: PLUGIN_LOGIN.PASSWORD
---
