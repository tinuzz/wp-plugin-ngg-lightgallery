# wp-plugin-ngg-lightgallery
An opinionated WordPress plugin for using NextGEN Gallery with lightGallery

# What's this?

* A [WordPress](https://www.wordpress.org/) plugin
* that bundles [lightGallery](http://sachinchoolur.github.io/lightGallery/)
* for use with [NextGEN Gallery](https://wordpress.org/plugins/nextgen-gallery/)
* and offers some extra features, like integration with [Trackserver](https://wordpress.org/plugins/trackserver/)
* but is very opinionated in how things should look.

It doesn't actually integrate with NextGEN gallery. For example, it doesn't use
NextGEN's hooks (with one exception) and it will still work fine if you disable
the NextGEN Gallery  plugin in your WordPress installation. In fact, it doesn't
even work with NextGEN's shortcodes, but introduces two of its own. Basically,
it needs the NextGEN Gallery database tables (whose names are hardcoded, so be
careful), and that's about it.

# What is it not?

* suitable for general use
* well documented
* customizable
* developed or tested for any other use case than my own
* supported in any way

# What does it do?

With this plugin, you get two shortcodes:

* [ngglightgallery id=N]
* [ngglightgallerytags gallery=TAG1,TAG2,...]

If any of these shortcodes is found in the current page or post, the plugin
queues the JS and CSS for the wonderful lightGallery viewer and outputs a
simple thumbnail gallery view that uses it. The formatting of this gallery is
completely hard-coded in this plugin, and not templated in any way. It looks a
lot like the basic thumbnail gallery from NextGEN 1.9, if you remember it. If
course, you can change the code and the CSS to make it look exactly as you
want.

The data for the gallery (image names, paths, captions, meta-data) is queried
from the NextGEN database directly. No code from NextGEN Gallery is involved,
thank goodness.

It comes with a few extras though, of which the most important are:
* video support
* composed captions containing some meta-data
* iframe support

## Video support

If the plugin encounters an image whose name ends in '.mp4.jpg', it strips the
'.jpg' off the name and opens the video file in lightGallery, using its
built-in HTML5 video support. So, to mix videos in with your photos, you'd
follow this easy 3-step plan:

1. create a 'poster image' for your 'video.mp4' and name it 'video.mp4.jpg';
2. upload both the video file and the poster image to a NextGEN gallery;
3. have NextGEN Gallery create a thumnail for the poster image.

This plugin and lightGallery will do the rest.

## Composed captions

NextGEN gallery stores some of the images' meta-data in its database, and this
plugin displays some of it in the caption of the photos. The most important
addition for me personally is a copyright notice. NextGEN already imports
copyright tags from your images' EXIF data, this plugin merely displays them.

It also uses GPS data (GPSLatitude / GPSLongitude tags) to construct a link to
Google Maps, so that when you click a nice little pin icon, you'll be taken (in
a new window) to the locaton the photo was taken.

NextGEN by itself ***does not*** import GPS meta-data from images, and that is
in fact the only real integration that this plugin offers with NextGEN
Gallery. After importing meta-data from images, NextGEN calls a filter named
'ngg_get_image_metadata', and this plugin responds to that filter by adding the
GPS data to the data that NextGEN will store in its database.

## Iframe support

The use for this is probably a little less obvious, so let me explain. The idea
is, that when you click a thumnail in a gallery, or you swipe through a gallery
to a certain position, that instead of displaying an image, a URL is opened in
an iframe. While this may sound pretty useless, try to think of this iframe as
a dynamic picture. Personally, I use it for maps. When I travel, I like to put
some maps in my gallery, for example to show GPS tracks of where I went. When
you click the thumbnail, instead of just a picture of a map, you get a real map
that you can pan/zoom and you can follow my tracks. The possibilities are endless.

To use this feature, all you have to do is put the URL, prefixed with the
identifier 'link:' in the image's description field in NextGEN, for example:

"link:https://www.google.com/maps/embed?pb=..."

This is where Trackserver comes in. Trackserver is another WordPress plugin I
wrote, and it allows you to store GPS tracks and display them directly from
WordPress. The next version of Trackserver will include a new feature called
'embedded maps', which was created for exactly this purpose. An embedded map is
a full screen map, suitable for loading in an iframe.

What I usually do, after I create a screenshot, is put the link in the
meta-data of the image ([Xmp.dc.description tag](http://www.exiv2.org/tags-xmp-dc.html)).
NextGEN Gallery will import this data and automatically put it in the
description field. No manual changes required.

# So why put it on Github?

I have been looking for a satisfactory way to show my photo galleries with
NextGEN v2 (now v3) for a long time, before I decided that I wanted
lightGallery, on my own terms and without having to buy NextGEN Plus or Pro or
any premium extensions, which meant: roll my own. So here it is, maybe it will
help you.

