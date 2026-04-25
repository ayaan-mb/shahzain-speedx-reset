=== SpeedX Site Reset ===
Contributors: speedx
Tags: reset, cleanup, admin, maintenance
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Reset a WordPress website to a near-fresh state with strong admin-only safeguards.

== Description ==

SpeedX Site Reset is a destructive admin utility designed to make a site look like a fresh WordPress install while preserving WordPress core files, theme files, database connection, and the current logged-in administrator account.

Only users with `manage_options` can access or run it.

== Safety Features ==

* Admin capability checks (`manage_options`) on page access and reset action.
* Nonce verification for reset submission.
* Strong permanent-deletion warning in the admin UI.
* Typed confirmation must exactly match `reset`.
* Final browser confirmation dialog before reset executes.
* No destructive action on uninstall.

== What Gets Reset ==

* Posts, pages, custom post types, comments, taxonomies, and terms.
* Media library records and physical upload files.
* Navigation menus.
* Widgets and sidebar assignments.
* Theme customizer options/theme mods.
* All users except the currently logged-in admin.
* Common options (site title/tagline, homepage/reading/discussion/permalink settings, rewrite rules).
* Deactivates all plugins except this one while reset runs.

== Installation ==

1. Upload the plugin ZIP in **Plugins > Add New > Upload Plugin**.
2. Activate **SpeedX Site Reset**.
3. Go to **SpeedX Site Reset** in wp-admin.

== Usage ==

1. Read the warning carefully.
2. Type `reset` in the confirmation field.
3. Click **Reset Website Now**.
4. Confirm the browser prompt.

== Changelog ==

= 1.0.0 =
* Initial release.
