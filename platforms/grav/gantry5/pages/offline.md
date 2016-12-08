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
  login:
    rememberme: false
    forgot_button: false

  fields:
      - name: username
        type: text
        id: username
        placeholder: Username
        label: PLUGIN_LOGIN.USERNAME

      - name: password
        type: password
        id: password
        placeholder: Password
        label: PLUGIN_LOGIN.PASSWORD
---
