=== ACME BotBlocker Sample Add-on ===
Contributors: acme
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Working sample package for BotBlocker Add-on API v2.

== Description ==

This add-on demonstrates a manifest-first BotBlocker package with settings, lifecycle callbacks, a polished settings help panel, an icon, asset loading, a feature declaration, and runtime behavior.

When enabled, it sends a configurable HTTP response header, can show an admin notice on BotBlocker screens, and can enqueue sample admin and frontend JavaScript files from the add-on runtime directory.

The settings view follows the native BotBlocker pattern: icon first, short help text, footer links, then grouped controls.

== Installation ==

Create a ZIP that contains the acme-botblocker-sample root folder. Upload it from BotBlocker -> Add-ons -> Upload ZIP. Activate it from the Installed tab.

== Changelog ==

= 1.0.0 =
* Initial working sample package.
