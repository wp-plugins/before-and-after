=== Before And After: Lead Capture Plugin For Wordpress ===
Contributors: ghuger
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=V7HR8DP4EJSYN
Tags: lead capture, lead capture form, lead capture plugin, protected content, gated content, click wrap, click wrapper, tos wrap, tos wrapper, copyright notice, copyright wrapper
Requires at least: 3.0.1
Tested up to: 3.6
Stable tag: 1.2.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Before And After is a lead capture plugin for Wordpress. Use it to require visitors to complete a goal, i.e., filling out a form, before continuing. 

== Description ==

Before And After is a lead capture plugin for Wordpress. It allows a webmaster to require visitors to complete a goal, such as filling out a contact form, before viewing the contents of a page. 

This functionality is also useful when webmaster's want to ensure visitors read a Terms Of Service, Copyright Notice, or other important message before viewing a given page or bit of content.

The secret sauce of Before And After is encapsulated by 4 new shortcodes: [goal], [before], [after], and [completed_goal]. These shortcodes are intended to be used together to display dynamic content based on whether the visitor has completed a goal.

Below is an example of a classic lead capture form. We're asking the visitor to complete the lead capture form before they are granted access to the whitepaper.

Here's how to capture a lead for your whitepaper with the Before And After plugin:

### Step 1: On The Landing Page, Add The [goal]. [before], and [after] shortcodes

<code>
	[goal name="Completed Contact Form"]
		[before]
		Please complete our contact form for access to this whitepaper.

		<contact form>
		[/before]
		[after]
		Thank you for completing our contact form! Please find your whitepaper here: http://example.com/whitepaper.pdf
		{/after]
	[/goal]
</code>

Note: Replace <contact form> with your form of choice. That can be a Wordpress plugin like Contact Form 7 or Gravity Forms, or a 3rd party form such as Mailchimp or Aweber mailing list signup forms.

### Step 2: On The Thank You Page, Add The [completed_goal] Shortcode

<code>
	[completed_goal name="Completed Contact Form"]
</code>

Note: The Thank You page is the page to which the visitor is sent after completing the contact form. You'll need to configure your form to send the visitor to the URL you've chosen for the Thank You page.

That's all! This is all that is required to setup a basic lead capture form with Wordpress and the Before And After Lead Capture plugin. 

Now that we've setup our pages and shortcodes, let's dive deeper into what will happen from the user's perspective:

When the user first encounters the landing page, they will not have completed our goal (which is identified as "Completed Contact Form"). Until they complete this goal (by filling out the form), they will always see the content inside the [before] shortcode. 

Keep in mind - the content inside the [before] shortcode can be anything: a Contact Form 7 form, a welcome video, a Terms Of Service agreement - any content that Wordpress supports can be placed inside the [before] or [after] shortcode. In our example above, we've added a short message and a contact form inside the [before] shortcode.

Next, the user completes the contact form and is sent to the Thank You page, where they encounter the [completed_goal name="Completed Contact Form"] shortcode that we added in Step 2. This marks them as having completed the "Completed Contact Form" goal.

Now, when this visitor encounters another [goal name="Completed Contact Form"] shortcode, they'll see the content inside the [after] shortcode instead. This can be a link to the whitepaper, a plot spoiler, or any other content that you'd like to reveal to the visitor.

Using these simple shortcodes, any number of scenarios are possible:

 - Lead Capture Forms: Ask a visitor to signup for your newsletter in return for a free download, special report, or whitepaper
 - Terms Of Service Pages: Make sure a visitor reads the terms of service first. Once they have read the TOS once, they may view any other page.
 - Age Gate - Make the visitor confirm their age before browsing a given page. 
 - Copyright Notice: Inform visitors of the copyright of a particular piece of content before allowing them to view it.
 - Instructions In Series: Make sure that a visitor reads a series of instructions in sequence. If they land on a later page, ask them to start over.
 - Guided Product Tours: Show your users the screens of your product in a sequenced progression

There are many other possibilities. By offering Wordpress webmasters a simple way to gate content we hope to provide a useful tool for many scenarios.

More Information Available Here: https://illuminatikarate.com/before-and-after-plugin/

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the contents of `/before-and-after/` to the `/wp-content/plugins/` directory
2. Activate the Before And After Lead Capture Plugin through the 'Plugins' menu in WordPress
3. Visit this address for information on how to configure the plugin: https://illuminatikarate.com/before-and-after-plugin/

== Frequently Asked Questions ==

= Where Is The Settings Page? =

There isn't one (yet)! Before And After's functionality is expressed through its four shourtcodes: [before],[after],[goal], and [goal_complete]

= How Do I Create A New Goal? =

Just change the value in the name attribute of your shortcode to anything you want. As long as you use the same name attribute in the [goal] and [goal_complete] shortcodes everything will work together as you want.

= How Do I Reset My Form, So That Everyone Has To Complete It Again? =

Just change the name attribute to something else. Perhaps add a "2" at the end. Just make sure you update the [goal] and the [goal_complete] shortcode so they match.

= 1.2.1 =
* Fixing repo issue w tags

= 1.2 =
* Supports Wordpress 3.6

= 1.1 =
* Fixing a bug with the [after] shortcode

= 1.0 =
* Initial Release!