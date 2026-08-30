ACME hCaptcha — Add-on Kit example for a captcha add-on.

This example shows the minimal captcha add-on contract (BotBlocker Add-on API v2):

- Manifest `captcha.modes` declares ONE mode: id 90 (ids >= 90 only; 0-8 are core-owned),
  with `params_callback` and `verify_callback` living in `inc/acme-hcaptcha-core.php`.
- `assets.external` loads the provider script (https://js.hcaptcha.com/1/api.js) on the
  check page; core inlines `assets/hcaptcha.js` itself — never call wp_enqueue_script().
- `params_callback` returns `['mode' => 90, 'params' => [...]]`; core injects
  `params.hash` — never set it yourself.
- The renderer JS MUST define `renderMode90Captcha(params)`; on success it appends the
  provider token to `window.data` and calls
  `window[bbcsJsData.checkFunctionName]('post', window.data, params.hash)`.
- `verify_callback` semantics: empty token = false (provider never called);
  provider success/failure = true/false; unreachable or malformed provider response =
  THROW RuntimeException — the core then degrades to the simple captcha instead of banning.

To add a second provider (e.g. Cloudflare Turnstile): append another object to
`captcha.modes` with a distinct id (91), its own callbacks, renderer JS named
`renderMode91Captcha`, and its own `assets.external` URL. See the bundled first-party
add-on `bbcs-hcaptcha` in the BotBlocker repository for the two-provider reference.

Package root: acme-hcaptcha
Manifest: bbcs-addon.json
Settings option: acme_hcaptcha_settings
