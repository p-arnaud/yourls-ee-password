YOURLS EE Password
====================

Plugin for [YOURLS](http://yourls.org) `1.7.2`.

Description
-----------
This plugin enables the feature of password for your short URLs.

API
---
This plugin can extends the API EDIT plugin: [yourls-api-edit-url](https://github.com/timcrockford/yourls-api-edit-url)
You can update password adding *url-password-active* parameter (true/false) and *url-password* parameter.

Example:
 /yourls-api.php?username=username&password=password&format=json&action=update&url=ozh&url-password-active=true&url-password=mypassword&shorturl=ozh

Installation
------------
1. In `/user/plugins`, create a new folder named `yourls-ee-password`.
2. Drop these files in that directory.
3. Go to the Plugins administration page ( *eg* `http://sho.rt/admin/plugins.php` ) and activate the plugin.
4. Have fun!

License
-------
Licence MIT.

Repository
--------------
[Plugin's sources](https://github.com/p-arnaud/yourls-ee-password)

One more thing
--------------
Started from [yourls-password-protection](https://github.com/GhostCyborg/yourls-password-protection), i mainly added a clear password column in admin view and a direct link to edit him.
