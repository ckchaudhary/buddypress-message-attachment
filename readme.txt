=== BuddyPress Message Attachment ===
Contributors: ckchaudhary 
Donate link: https://twitter.com/ckchaudhary
Tags: buddypress, messages, attachments
Requires PHP: 5.6
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 3.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send attachments with private messages in BuddyPress!

== Description ==

Extend BuddyPress' private message feature by enabling attachments. This plugin enables users to send attachments in private messages. The type of file types allowed and maximum size of the attachment can be controlled by admin.

== Installation ==

You can download and install this plugin using the built in WordPress plugin installer. If you download it manually, make sure it is uploaded to "/wp-content/plugins/buddypress-message-attachment/".

If you are running a multisite installation, you can activate this plugin network wide or on individual sites. This plugin should work nicely irrespective of buddypress activated network wide or on individual sites.

After activating, make sure you change settings according to your liking/requirement. Settings can be found under **Settings>Message Attachment** in wp-admin or, on network admin screen if you have activated it network wide.

== Screenshots ==
1. Thread details screen, with attachments displayed.
2. Plugin settings screen.
3. Media library, small icons to indicate a file was sent as an attachment with a buddypress message.

== Changelog ==
= 3.0.0 =
* Made the plugin work with bp-nouveau.

= 2.1.1 =
* Added 'archive' file formats (e.g: zip, rar) on settings screen.
* Added zip and rar into default file types allowed.

= 2.1.0 =
* Hide file upload interface, when sending a notice to all users instead of private message.

= 2.0 =
* Complete rewrite of plugin.
* Made it multisite compatible.
* Removed unnecessary custom post type.

= 1.1 =
* Small Bug fixes

= 1.0 =
* Initial release