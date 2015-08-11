=== Before And After: Lead Capture Plugin For Wordpress ===
Contributors: ghuger, richardgabriel
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=V7HR8DP4EJSYN
Tags: lead capture, lead capture form, lead capture plugin, protected content, gated content, click wrap, click wrapper, tos wrap, tos wrapper, copyright notice, copyright wrapper
Requires at least: 3.0.1
Tested up to: 4.2.4
Stable tag: 2.5.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Before And After is a lead capture plugin for Wordpress. Use it to require visitors to complete a goal, i.e., filling out a form, before continuing. 

== Description ==

Before And After is a lead capture plugin for Wordpress. It allows a webmaster to require visitors to complete a goal, such as filling out a contact form, before viewing the contents of a page. 

This functionality is also useful when webmaster's want to ensure visitors read a Terms Of Service, Copyright Notice, or other important message before viewing a given page or bit of content.

Using this simple plugin, any number of scenarios are possible:

 - Lead Capture Forms: Ask a visitor to signup for your newsletter in return for a free download, special report, or whitepaper
 - Terms Of Service Pages: Make sure a visitor reads the terms of service first. Once they have read the TOS once, they may view any other page.
 - Age Gate - Make the visitor confirm their age before browsing a given page. 
 - Copyright Notice: Inform visitors of the copyright of a particular piece of content before allowing them to view it.
 - Instructions In Series: Make sure that a visitor reads a series of instructions in sequence. If they land on a later page, ask them to start over.
 - Guided Product Tours: Show your users the screens of your product in a sequenced progression.
 - Capture Lead Data and Submit To HubSpot.

There are many other possibilities. By offering Wordpress webmasters a simple way to gate content we hope to provide a useful tool for many scenarios.

Before & After Pro integrates directly with Gravity Forms and Contact Form 7!  Check out the instructions for more information.

= Upgrade to Pro for For Advanced Features and Support =

The GoldPlugins team does not provide direct support for the Before & After plugin on the WordPress.org forums. One on one email support is available to people who have purchased Before & After Pro only. Before & After Pro also includes conversion tracking, email notifications, submitting data to HubSpot, and other advanced features. You should [upgrade today!](http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=wp&utm_campaign=desc_upgrade1 "Upgrade to Before & After Pro")

[Upgrade To Before & After Pro](http://goldplugins.com/our-plugins/before-and-after/upgrade-to-before-and-after-pro/?utm_source=wp&utm_campaign=desc_upgrade2)


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the contents of `/before-and-after/` to the `/wp-content/plugins/` directory
2. Activate the Before And After Lead Capture Plugin through the 'Plugins' menu in WordPress
3. Read the Instructions.

= Introduction: How Does This Plugin Work? =

Before & After is a lead capture plugin that lets you offer your users something in exchange for their information. It can also be when you need to make sure your users read your Terms of Service, verify their age before entering your website, or otherwise need to see one thing, and then another.

To achieve this, Before & After uses what we call goals. A goal is simply an action, or a "gate", that your users need to pass before they will be allowed to see your protected content.

A user completes a Goal by simply encountering the [complete_goal] shortcode. You will have placed it on your Thank You page, on the terms page, or whatever other page you need the user to view to signify that they have completed the goal.

= How To Setup A New Goal =

To create a new Goal, simply follow these steps:
1. Under the Before & After menu, select Goals. This will bring up a page which lists all of your goals.
2. Click the "Add A New Goal" button
3. Give your Goal a title, and then fill out the Before & After sections. When you are done, click the Publish button.
4. Your goal has been created! Copy the [goal] shortcode from the Edit Goal screen you are currently viewing, and go paste it onto the on which page you'd like the goal to appear.

= How To Have A Visitor Complete A Goal =
		
Simply add a shortcode like this to the final step of your goal funnel. For example, you could place it on the "Thank You" page from a contact form.

	[complete_goal id="82"]

_(Replace the number 82 with the id of your goal. Tip: you can find the shortcode for each goal on the Goals page.)_

= Shortcode Reference =

**Goal Shortcode**

Add this shortcode to any page or post to display your goal there.
	
	[goal id="82"]

**Complete Goal Shortcode**

Add this shortcode to the page which signifies that a visitor has completed the goal. For example, you could put this on a "Thank You For Contacting Us" page.

	[complete_goal id="82"]

= Integrating with Contact Form 7 =

If you have the Contact Form 7 plugin installed, you'll be able to select any Contact Form 7 form as the Before option for your Goals. Simply Add a new Goal or edit an existing one, and you'll see your Contact Form 7 forms listed.
Important: be sure to redirect your Contact Form 7 form to a thank you page, and to add the complete goal shortcode to the Thank You page. <a href="http://contactform7.com/redirecting-to-another-url-after-submissions/" target="_blank">Refer to these instructions if you are unsure how to do this.</a>

= Integrating with Gravity Forms =

If you have the Gravity Forms plugin installed, you'll be able to select any Gravity Form you have created as the Before option for your Goals. Simply Add a new Goal or edit an existing one, and you'll see your Gravity Forms forms listed.
Important: be sure to redirect your Gravity Form to a thank you page, and to add the complete goal shortcode to the Thank You page. <a href="http://www.gravityhelp.com/documentation/page/Form_Settings" target="_blank">Refer to these instructions if you are unsure how to do this.</a>

= How to Submit My Data to HubSpot =

If you have Before & After Pro installed, and you have an active HubSpot account, you can have submission data from your Goals submitted through to HubSpot.  To do so, first login to your HubSpot account and create a new Form to receive our submissions.  Next, simply visit our Settings page and look for HubSpot settings.  Once here, you'll want to add your HUB ID, the GUID of the Form (on HubSpot) that you want to send data to, and (optionally) input the Titles of any forms that you don't want to send to HubSpot.  This feature will only work on Contact Form 7 or Gravity Forms that are associated with a Goal.

Before &amp; After will attempt to map your fields to HubSpot -- to adjust the field mapping array use the ```ba_hubspot_field_mappings``` filter.

Before &amp; After will block some default Gravity Forms fields from being sent to HubSpot -- to adjust the field mapping array used for blocking, use the ```ba_gform_default_fields``` filter.

== Screenshots ==

1. This is the list of Goals.
2. This is the Add New Goal page.
3. This is the Conversion Tracking Log.
4. This is the Settings screen.
5. This is the Help screen.

== Frequently Asked Questions ==

= Where Is The Settings Page? =

It is underneath the Before and After menu item, on the backend of WordPress.

== Changelog ==

= 2.5.4 =
* New Feature: Use the same Contact Form 7 or Gravity Form for many Goals, but still let your visitors complete the Goals individually.

= 2.5.3 =
* Adds the new Goal widget

= 2.5.2 =
* Allow HTML in free form fields

= 2.5.1 =
* Fix: Allow redirection to URLs with same path but different query strings

= 2.5 =
* Fix: employ a more robust method for getting start and complete goal URLs

= 2.4 =
* Fix: prevent redirect loops on after page
* Make shortcodes easier to copy from metaboxes and help pages
* Improve documentation

= 2.3 =
* Fix: error on Installed Plugins screen

= 2.2 =
* Pro Feature: Options to Submit Data to HubSpot.
* Minor bug fixes.
* Tested up to 4.1.2

= 2.1.3 =
* Admin interface updates

= 2.1.2 =
* Updates Compatibility to WP 4.1.1

= 2.1.1 =
* Fix: address issue with double output of Conversions menu.
* Update: update registration key method.
* Update: update compatibility to WordPress 4.1.

= 2.1 =
* Fix: restore compatibility with Contact Form 7

= 2.0.1 =
* Update: update compatibility to WP 4.0.
* Fix: address various PHP notices.

= 2.0 =
* Update: major upgrade.

= 1.2.1 =
* Fixing repo issue w tags

= 1.2 =
* Supports Wordpress 3.6

= 1.1 =
* Fixing a bug with the [after] shortcode

= 1.0 =
* Initial Release!

== Upgrade Notice ==

* 2.5.4: New Feature: Use the same Contact Form 7 or Gravity Form for many Goals, but still let your visitors complete the Goals individually.