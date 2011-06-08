=== Monitor Pages ===
Contributors: markjaquith
Donate link: http://txfx.net/wordpress-plugins/donate
Tags: pages, e-mail, email, monitor, notification
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 0.4.1

Sends e-mail notifications to a configurable list of addresses when Pages are published, scheduled, or modified.

== Description ==

Sends e-mail notifications to a configurable list of addresses when Pages are published, scheduled, or modified. People on the notification list will know what page changed, and in what way it changed (e.g. updated, published, scheduled, trashed, etc).

== Installation ==

1. Upload the `monitor-pages` folder to your `/wp-content/plugins/` directory

2. Activate the "Monitor Pages" plugin in your WordPress administration interface

3. Go to Pages &rarr; Manage Notifications as an Administrator to get started

== Frequently Asked Questions ==

= When are people notified? =

Depends on your setting. With the "published or scheduled" setting, they'll be notified when a Page transitions to published or scheduled from a different status. With the "published, scheduled, or modified" setting, they'll be notified when a Page transitions to or from published or scheduled status, including published-to-published statuses (i.e. updates to published posts).

== Screenshots ==

1. The Manage Page Notifications screen

== Changelog ==
= 0.5 =
* Better encapsulate the compat code

= 0.4 =
* Fixed a bug where the title wasn't showing up in the message body
* Added a page link to the message body

= 0.3 =
* `submit_button()` and `get_submit_button()` back compat, for WordPress 3.0.x

= 0.2 =
* `esc_textarea()` back compat, for WordPress 3.0.x

= 0.1 =
* Initial version
