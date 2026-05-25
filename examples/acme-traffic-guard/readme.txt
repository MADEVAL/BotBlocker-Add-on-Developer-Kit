=== ACME Traffic Guard ===
Contributors: acme
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Dangerous-by-design sample traffic decision provider for BotBlocker Security.

== Description ==

This example demonstrates the BotBlocker Add-on API v2 pre-run traffic decision provider contract.

It can match visitor country plus path prefix and return either a log-only decision or a redirect decision inside the BotBlocker request cycle. It ships disabled and in dry-run mode by default.

Traffic add-ons are operationally dangerous. Incorrect rules can redirect real customers, break checkout/payment callbacks, hide BotBlocker security pages, create loops, or bypass security policy. Use this example only as a controlled starting point.

== Safety Defaults ==

* Disabled on activation.
* Dry-run enabled.
* GET and HEAD only.
* Same-site target paths only.
* Skip admin, AJAX, cron, REST, unsafe methods, payment bypasses, BotBlocker security pages, and verified legal bots by default.

== Installation ==

Create a ZIP that contains the acme-traffic-guard root folder. Upload it from BotBlocker -> Add-ons -> Upload ZIP. Activate it from the Installed tab, then configure it from BotBlocker -> Tools.

== Validation ==

php ../../tools/validate-addon.php ./acme-traffic-guard

== Changelog ==

= 1.0.0 =
Initial traffic decision provider example.
