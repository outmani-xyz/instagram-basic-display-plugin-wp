# Become a Patron! 
<a href="https://www.patreon.com/bePatron?u=38671402" style='font-weight:bold;font-size:24px;'>Become a Patron!</a>
# Support via Paypal
<a href="https://paypal.me/OUTMANI" style='font-weight:bold;font-size:24px;'>Paypal</a>

# WP-Instagram-API
Instagram Basic Display API plugin for Wordpress

## Setup
Create App instagram
Follow the directions here to create a Basic Display App for your Wordpress site:
https://developers.facebook.com/docs/instagram-basic-display-api/getting-started

### Change the following settings under Setting > Instagram setting

#### How to use shortcode 
Simple shortcode :
```
[instagram_feed class="your_css_class" number="number_posts_to_show"]

```
Advenced shortcode :
```
[instagram_feed number="number_posts_to_show"]
<div class='col-md-3'>
    <a href="{media_link}">
        <img src="{media_thumbnail}">
    </a>

</div>
[/instagram_feed]
```
