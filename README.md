# WP Arda-Report Updater

A tiny **network-activated** companion plugin that runs the GitHub update checker for
[WP Arda-Report](https://github.com/Ardaganbao/mose-wordpress) from the **Network Admin**
context on a WordPress multisite.

## Why it exists

On WordPress multisite, the Network Admin screens only run network-activated (and must-use)
plugins. When WP Arda-Report is activated **per-site** (the recommended setup), its own update
checker never runs in Network Admin, so no "Check for updates" link or auto-update toggle appears
there. This plugin loads the same checker (plugin-update-checker, bundled with WP Arda-Report)
against WP Arda-Report's files, wherever it is installed, so updates can be managed from
**Network Admin → Plugins**.

## Install

1. Install and keep **WP Arda-Report** activated **per-site** (not network-activated).
2. Upload this plugin and **Network-activate ONLY this one**.
3. Manage WP Arda-Report updates from **Network Admin → Plugins**.

After the first release, this plugin **updates itself** from its own GitHub releases too (under its
own slug `arda-report-updater`, so it never clashes with WP Arda-Report's `multi-block` slug). Pushing
a `vX.Y.Z` tag builds `arda-report-updater.zip` and publishes a release the sites pick up; a suffixed
tag like `v1.1.0-rc.1` is a pre-release the sites ignore.

## Collision safety

Both this plugin and WP Arda-Report register plugin-update-checker under the slug `multi-block`.
A network-activated plugin also loads on every front-end / per-site admin request, where
WP Arda-Report has already claimed that slug — registering it twice is a fatal error
("Slugs must be unique"). To prevent that, `arda_report_updater_boot()` bails out when:

- `ARDAREPORT_POST_TYPE` is defined (WP Arda-Report is loaded in this request), or
- PUC's own `puc_is_slug_in_use-multi-block` filter reports the slug is already taken.

So this plugin only registers the checker in Network Admin, where the per-site plugin doesn't run.

## License

GPL 3.0
