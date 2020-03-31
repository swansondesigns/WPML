=== ShortPixel Adaptive Images ===
Contributors: ShortPixel
Tags: adaptive images, responsive images, resize images, scale images, cdn, optimize images, compress images, on the fly, webp, lazy load
Requires at least: 3.2.0
Tested up to: 5.2
Requires PHP: 5.2
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display properly sized, smartly cropped and optimized images on your website; Images are processed on the fly and served from our CDN.

== Description ==

**An easy to use plugin that can help you solve within minutes all your website’s image-related problems.**

= Do I need this plugin? =
If you have a WordPress website with images then the answer is: most probably yes!
Did you ever test your website with tools like <a href="https://developers.google.com/speed/pagespeed/insights/" target="_blank">PageSpeed Insights</a> or <a href="https://gtmetrix.com/">GTmetrix</a> and received complains about images not being properly sized or being too large? Or that you should use "next-gen" images like WebP? Or that the website should "defer offscreen images"?
ShortPixel Adaptive Images comes to the rescue and resolves your site's image-related problems in no time.

= What are the benefits? =
Imagine that you could have all your image-related website problems solved with a simple click, wouldn't that be great?
Usually the images are the biggest resource on a website page. With just one click, ShortPixel Adaptive Images replaces all your website's pics with properly sized, smartly-cropped, optimized images and serves them from ShortPixel's global CDN.
And for more Google love the plugin serves <a href="https://en.wikipedia.org/wiki/WebP">WebP</A> images to the right browsers auto-magically!

= What are the features? =
* same visual quality but smaller images thanks to ShortPixel algorithms
* smart cropping - <a href="https://shortpixel.helpscoutdocs.com/article/182-what-is-smart-cropping" target="_blank">see an example</a>
* serve only appropriately sized images depending on the visitor's viewport
* lazy load support
* automatically serves WebP images to browsers that support this format
* caching and serving from a global CDN
* all major image galleries, sliders, page builders are supported
* SVG place holders
* support for JPEG, WebP, PNG, GIF, TIFF, BMP
* traffic is not counted

= Do I need an account to test this plugin? =
No, just go ahead and install then activate it on your WordPress website and you’ll automatically receive 500 image optimization credits.

= How much does it cost? =
When using ShortPixel Adaptive Images, only the image optimization <a href="https://shortpixel.helpscoutdocs.com/article/96-how-are-the-credits-counted">credits are counted</a>. That means the CDN traffic is not metered (considering a fair usage). The free tier receives 100 image optimization credits, paid plans start at $4.99 and both <a href="https://shortpixel.com/pricing-one-time">one-time</a> and <a href="https://shortpixel.com/pricing">monthly</a> plans are available.
Even better: if you already use <a href="https://wordpress.org/plugins/shortpixel-image-optimiser/">ShortPixel Image Optimizer</a> then you can use the same credits with ShortPixel Adaptive Images!

= How does this work? =
Different visitors have different devices (laptop, mobile phone, tablet) each with its own screen resolution. ShortPixel AI considers the device's resolution and then serves the right sized image for each placeholder.
Let's consider a webpage with a single 640x480 pixels image.
When viewed from a laptop the image will retain it 640x480px size but it will be optimized and served from a CDN.
When the same webpage is viewed from a mobile phone, the image will be resized (for example) to 300x225px, optimized and served from CDN.
This way, neither time nor bandwidth will be wasted by visitors.
Please note that the first time the call for a specific image is made to our servers, the original image will be served temporarily.


== Frequently Asked Questions ==

= What happens when the quota is exceeded? =

In your WP dashboard you'll be warned when your quota is about to be exhausted and also when it was exceeded. The already optimized and cached images will still be served from our CDN for up to 30 days.
The images that weren't already optimized will be served directly from your website.

= What Content Delivery Network (CDN) do you use? =

ShortPixel Adaptive Images uses <a href="https://www.stackpath.com/">STACKPATH</a> - a global CDN with <a href="https://www.stackpath.com/platform/network/">45 edge locations</a> around the world.
Both free and paid plans use the same CDN with the same number of locations.
You can independently check out how StackPath CDN compares to other CDN providers <a href="https://www.cdnperf.com/">here</a> (wordlwide) and <a href="https://www.cdnperf.com/#!performance,North%20America">here</a> (North America).

= Can I use a different CDN? =

Sure. <a href="https://shortpixel.helpscoutdocs.com/article/180-can-i-use-a-different-cdn-with-shortpixel-adaptive-images">Here</a> you can see how to configure it with Cloudflare and <a href="https://shortpixel.helpscoutdocs.com/article/200-setup-your-stackpath-account-so-that-it-can-work-with-shortpixel-adaptive-images-api">here</a>’s how to configure it with STACKPATH.
If you need further assistance please <a href="https://shortpixel.com/contact">contact us</a>

= What happens if I deactivate the plugin? =
You can stop using the SPAI whenever you want but this means your site will suddenly become slower.
Basically, your website will revert to the original, un-optimized images served from your server.

= Are there different image optimization levels available? =
Yes, you can compress images as Lossy, Glossy or Lossless.
You can find out more about each optimization level <a href="https://shortpixel.helpscoutdocs.com/article/11-lossy-glossy-or-lossless-which-one-is-the-best-for-me">here</a> or can run some free tests to optimize images <a href="https://shortpixel.com/online-image-compression">here</a>

= I already used ShortPixel Image Optimizer, can I also use this? =
Certainly!

= What is the difference between this plugin and ShortPixel Image Optimizer =
You can see <a href="https://shortpixel.helpscoutdocs.com/article/179-shortpixel-adaptive-images-vs-shortpixel-image-optimizer">here</a> the differences between the two services.



== Screenshots ==

1. Example site metrics on PageSpeed Insights before: Low

2. Example site metrics on PageSpeed Insights after: Good

3. Example site metrics on GTMetrix before: F score

4. Example site metrics on GTMetrix after: B score

5. Main settings page

6. Advanced settings page

== Changelog ==

= 1.3.4 =

Release date: 14th September 2019
* fix replacing images in <img data-src> tags
* Language – 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

= 1.3.3 =

Release date: 12th September 2019
* Fix SRCSET parsing
* Language – 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

= 1.3.2 =

Release date: 11th September 2019
* Fix IE problem and DOM syntax errors due to the non-standard data:image
* If an image is resized to a specific size and later on in the same page the same image needs a smaller size, use again the previously resized image.
* Fix CSS backgrounds regex in some cases
* Language – 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

= 1.3.1 =

Release date: 10th September 2019
* Better integration with Modula
* Fixed: background regex in some cases
* Language – 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

= 1.3.0 =

Release date: 9th September 2019
* Add option to cap backgrounds to a max width
* Add option to lazy-load backgrounds and limit their width to device width
* Improve performance of front-end JS by parsing only tags that were affected on back-end.
* Better handling for cropped images if crop option set.
* Keep EXIF option.
* Fixed: wrong API url on multisites
* Fixed: catastrophic backtracking on pages with huge ( > 1M ) CDATA blocks
* Fixed: background images in inline style not caught properly in some cases
* Language – 15 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted

= 1.2.6 =

Release date: 28th August 2019

* Improve the main image regex in order to catch some malformed cases
* Replace also link rel="icon" in the header
* Fix warning strpos needle is empty
* Be able to find file on disk based on urlencoded name from URL, for images with spaces in the name (try with urldecode too).
* Language – 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

= 1.2.5 =

Release date: 17th July 2019

* improve the load time of images displayed on DOM changes (as menus for instance)
* code refactoring in preparation of DOM Parser
* Fix JS error settings not an object
* Fix some replacing issues when URLs contain encoded characters.
* Fix replacing urls when pages under edit in some builders (Thrive Architect, Avia among them)

= 1.2.4 =

Release date: 3rd July 2019

* Fix bug in span background-image
* Compatibility with Ginger – EU Cookie Law plugin
* Parse also <section>'s backgrounds
* Fix bug when parsing some background-images containing spaces

= 1.2.3 =

Release date: 20th June 2019

* Add help links and Support beacon
* Compatibility with Thrive Architect and Avia Layout Builder
* Fix problem with sites having the WP install in a subfolder (site_url vs. home_url)
* Fix notice on sites with older Autoptimize versions
* Skip the <a> tags when determining the size of an element recursively, based on parent size
* Fix: background images of spans
* Refactoring in preparation for DOM Parse

= 1.2.2 =

Release date: 7th June 2019

* Fix for image URLs containing &'s
* fix for eagerly loaded background-image URLs not containing the protocol (//some.site/img.jpg)

= 1.2.1 =

Release date: 6th June 2019

* Fix JS not triggering when DOMContentLoaded was fired before the JS load

= 1.2.0 =

Release date: 4th June 2019

* Integrate with Viba Portfolio
* Integrate with the Elementor paralax section
* Work around random jQuery not loaded error due to jQuery bug (https://github.com/jquery/jquery/issues/3271)
* Don't lazy-load the images set on backgrounds in <style> blocks.
* Move ai.min.js to footer
* Fix exclude pattern matching when class defined without quotes (<div class=myclass>)

= 1.1.3 =

Release date: 30th May 2019

* Fix JS issues on iPhone 6s
* Make Elementor External CSS warning dismissable
* Fix exclude regexes added on Windows and having \r\n at the end.
* Fix replacing images that are not in Media Library but directly in wp_content

= 1.1.2 =

Release date: 29th May 2019

* Thrive Architect preview compatibility
* Parse also the <amp-img> tag
* Fix not parsing AJAX in some circumstances
* Fix compatibility with Safari in some cases when ai.min.js is loaded later (async)
* Fix translations by adding load_plugin_textdomain

== Changelog ==

= 1.1.1 =

Release date: 27th May 2019

* Retina displays - properly take into account pixel ratio when resizing images.
* Fix feed-back loop on MutationObserver caused by some jQuery versions which set id as a hack to implement qSA thus trigger a mutation
* Parse also the .css files in browser - in order to catch some optimization plugins (like Swift Performance) which extract the inline CSS to external .css resources
* Notify if Elementor has the option to use External File for CSS Print Method because it conflicts with replacing background-image's

= 1.1.0 =

Release date: 23th May 2019

* option to exclude images based on URL parts or patterns
* option to either do or do not the fade-in effect when lazy-loading
* fix for urls starting with '//'
* fix for urls starting with '../' even if the page is in the root of the site ( https://example.com/../pictures/pic1.jpg )

= 1.0.3 =

Release date: 20th May 2019

* fix replacing background image on elements not initially visible
* MSIE fixes: String.startsWith polyfill, fix IntersectionExplorer polyfill, handle cases when element.parentNode.children is undefined ( IE magic:) )
* Fix compatibility with WooCommerce's magnifier lens when using the fade-in effect of the lazy-loaded images.

= 1.0.2 =

Release date: 16th May 2019

* integrate Avada - notify to deactivate the lazy-loading of Avada

= 1.0.1 =

Release date: 10th May 2019

* better handling of the excludes by tag ID
* do not replace the images src if plugin's JS script was dequeued (like on logins or coming soon pages).
* check if the URL has host before, in order to prevent some warnings.

= 1.0.0 =

Release date: 8th May 2019

* alert when quota is low or exhausted.
* fade-in effect for lazy-loaded images
* replace also the background CSS shorthand
* do not replace the unsupported image types (like SVG) in backgrounds either

= 0.9.6 =

Release date: 25th April 2019

* updates of the verification of Autoptimize's setting for image optimization after changes in version 2.5.0.

= 0.9.5 =

Release date: 25th April 2019

* fix JS error on Firefox

= 0.9.4 =

Release date: 23rd April 2019

* Parse the CSS <style> blocks for background-image rules and replace them
* Smarter replace for background-image rules - cover cases when there is also a gradient
* Alert for double compression when ShortPixel Image Optimization is present has the same lossy setting
* Alert for conflict when Autoptimize has the option to deliver images using ShortPixel's service.
* Make sure it doesn't replace the URL of any image type (by extension) which is not supported
* Exclude the AMP endpoints from replacing
* fix bug for the Elementor gallery which was replacing other links having common CSS class

= 0.9.3 =

Release date: 4th March 2019

* Integrate galleries: Foo Gallery, Envira, Modula, Elementor, Essential add-ons for Elementor, Everest, default WordPress gallery
* Integrate with WP Bakery's Testimonial widget
* activate the integrations only if the respective plugins are active (also for existing NextGen integration)
* use the '+' separator for optimization params, which integrates better with some plugins which parse the srcset and get puzzled by the comma.
* display a notice about conflicts with other lazy-loading plugins.

= 0.9.2 =

Release date: 13th February 2019

* exclude from parsing the <script> and <![CDATA[ sections
* honour the GET parameter PageSpeed=off used by some third parties as the Divi Builder
* add settings link in the plugins list
* lazy-load the images referred by inline background-image CSS
* Fixed: image src's without quotes followed immediately by >, URLs containing non-encoded UTF8, inline background-image URL with &quot; etc

= 0.9.1 =

Release date: 30th January 2019

* handle <div data-src="...">
* handle &nbsp;'s, &quot;'s in background-image CSS
* handle images with empty href
* handle more cases of hrefs without quotes

= 0.9.0 =

Release date: 23rd January 2019

* Use the Babel generated replacement for the async/await WebP code
* parse the background-image inline style
* check also if the element is :visible
* add to settings a list of excludes which leaves the URL as it is
* use svg instead of gif for the inline image replacement, for better compatibility with Safari
* use minified javascript for front-end
* fixed: IntersectionObserver on Safari

= 0.8.0 =

Release date: 9th December 2018

* WebP support

= 0.7.2 =

Release date: 28th October 2018

* add MutationObserver polyfill
* add alert that plugin is in beta

= 0.7.1 =

Release date: 7th October 2018

* Fix performance problems when page has many modifications by JS.

= 0.7.0 =

Release date: 3rd November 2018

* added lazy loading of images.

= 0.6.4 =

Release date: 7th October 2018

* add the SRCSET and BOTH (both src and srcset) option
* urlencode the URLS before base64 to circumveit incompatibility with atob on some characters like (R)
