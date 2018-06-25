# GIFT Platform (DIY)

To enable an ecosystem of apps and services that can pass gifts from giver to recipient (and back), we provide the [GIFT Platform](https://toolkit.gifting.digital/gift-platform/): a data schema for "hybrid gifts", an API and content management system (CMS).

This WordPress plugin extends WordPress to include the GIFT Platform's functionality. Please see [https://toolkit.gifting.digital/gift-platform/diy-gift/](https://toolkit.gifting.digital/gift-platform/diy-gift/) for more details on installing this plugin while setting up your own private instance of the GIFT Platform.

To use the public instance of the GIFT Platform maintained by the GIFT project, please visit the [GIFT Toolkit](https://toolkit.gifting.digital/)

## 3rd-party service credentials

This plugin uses Google Maps, Mailgun and Wordnik.

After the plugin is installed, create a file name cred.php in the plugin's folder on your web server. Edit the file and define suitable keys for Google Maps, Mailgun and Wordnik as below:

```
<?php
	define("GOOGLEMAPSAPI", "your google maps api key");
	define("MAILGUNAPI", "your mailgun api key");
	define("MAILGUNAUTH", "your mailgun authentication string");
	define("WORDNIKAPI", "your wordnik api key");
?>
```