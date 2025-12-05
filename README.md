Comic Calendar
Plugin Name: Comic Calendar Requires at least: 5.8 Tested up to: 6.4 Requires PHP: 7.4 License: GPLv3 Tags: comic, calendar, webcomic, cpt, shortcode, seo

📝 Description
Comic Calendar transforms WordPress into a flexible, streamlined webcomic publishing platform. It registers a dedicated Comic Custom Post Type and uses a simple shortcode to display your latest strip along with intuitive date-based navigation (Month Pager) and a calendar grid.

This plugin ensures every comic strip is treated as its own unique page for perfect SEO, while maintaining a clean, single-page presentation for your readers.

✨ Features
Dedicated Custom Post Type (CPT): Registers the cc_comic post type for managing all your strips separately from posts and pages.

Flexible Display: Uses a simple [comic_calendar] shortcode that can be placed on any WordPress page.

Dynamic Routing: Custom rewrite rules ensure "pretty URLs" (e.g., /your-comic-page/123/) and reliable query parameters for navigation.

Modern Navigation: Replaces old dropdowns with a Month Pager (Previous/Next arrows) and a Calendar Grid for easy date jumping.

Advanced SEO: Dynamically rewrites the page title and adds crucial Open Graph (OG) and Twitter Card meta tags, pointing them directly to the current comic strip image and description for perfect social media sharing.

Random Comic Widget: Includes a Random Comic sidebar widget to drive discovery across your site.

🛠️ Installation
1. File Installation (Manual)
Download the plugin files and upload the comic-calendar directory to the /wp-content/plugins/ directory.

Activate the plugin through the 'Plugins' menu in WordPress.

2. Post-Activation Setup (Crucial!)
After activating, you must tell the plugin which page hosts your comic:

Create a new WordPress Page (e.g., "Daily Comics").

Paste the shortcode [comic_calendar] into the content area of that page.

In the WordPress Admin, go to Comics > Settings.

Select the Page you just created (e.g., "Daily Comics") in the dropdown menu.

Click Save Changes. (This action automatically flushes permalinks, activating the custom URLs.)

📚 Usage
1. Adding Comics
Navigate to Comics > Add New.

Set the Title (e.g., Daily Strip #123).

Add the comic image by setting the Featured Image.

(Optional) Use the content editor for a brief description or transcript.

Publish the comic. The date it is published will be used for the calendar entry.

2. Display
Simply visit the Page where you placed the [comic_calendar] shortcode. The plugin handles all post querying, navigation, and display logic automatically.

3. Sidebar Widget
Go to Appearance > Widgets.

Drag the Random Comic widget into your desired sidebar area.

Set the widget title (e.g., "Need a Laugh?").

❓ Frequently Asked Questions
The date links or arrows keep taking me back to the latest comic.
This means your server environment is incorrectly handling the custom URL rewrite rules. You likely missed the crucial setup step.

Fix: Go to Settings > Permalinks and click Save Changes without changing any options. This will manually force WordPress to update the URL rules and usually resolves the issue.

How does the SEO work?
When a visitor loads a specific comic (e.g., by clicking a date), the plugin:

Intercepts the request.

Changes the <title> tag of the page to match the comic's title.

Generates and inserts Open Graph (og:image, og:title) and Twitter Card meta tags pointing directly to the current comic's featured image and unique URL. This ensures sites like Facebook, Twitter, and Discord show the correct preview when the link is shared.

⚙️ Development & License
This project is licensed under the GNU General Public License v3 (GPLv3).

You are free to use, modify, and distribute this code, provided any distributed derivative works are also licensed under the GPLv3.
