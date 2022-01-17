<?php
/*
 * Plugin Name: Ngg-LightGallery
 * Plugin Script: ngg-lightgallery.php
 * Plugin URI: https://www.grendelman.net/wp/trackserver-wordpress-plugin/
 * Description: LightGallery albums for NextGen Gallery
 * Version: 0.1
 * Author: Martijn Grendelman
 * Author URI: http://www.grendelman.net/
 * Text Domain: ngglightgallery
 * Domain path: /lang
 * License: Apache License, Version 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No, sorry.' );
}

if ( ! class_exists( 'NggLightGallery' ) ) {

	define( 'NGGLIGHTGALLERY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'NGGLIGHTGALLERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'NGGLIGHTGALLERY_DIST', NGGLIGHTGALLERY_PLUGIN_URL . 'lib/lightgallery-2.3.0/' );
	define( 'NGGLIGHTGALLERY_DLURL_PREFIX', '/photo/' );
	define( 'NGGLIGHTGALLERY_DLURL_PREFIX2', 'https://' . $_SERVER['SERVER_NAME'] . '/photo/' );
	define( 'NGGLIGHTGALLERY_SHORTCODE1', 'ngglightgallery' );
	define( 'NGGLIGHTGALLERY_SHORTCODE2', 'ngglightgallerytags' );
	define( 'NGGLIGHTGALLERY_PANNELLUM_SLUG', 'pannellum' );

	/**
	 * The main plugin class.
	 */
	class NggLightGallery {

		var $shortcode1    = NGGLIGHTGALLERY_SHORTCODE1;
		var $shortcode2    = NGGLIGHTGALLERY_SHORTCODE2;
		var $have_scripts  = false;
		var $need_scripts  = false;
		var $script_config = array();

		function __construct() {
			$this->url_prefix = '';
			$this->add_actions();
		}

		function add_actions() {
			add_action( 'wp_footer', array( &$this, 'wp_footer' ) );
			add_shortcode( $this->shortcode1, array( &$this, 'handle_shortcode1' ) );
			add_shortcode( $this->shortcode2, array( &$this, 'handle_shortcode2' ) );
			add_shortcode( 'nggenav', array( &$this, 'shortcode_nggenav' ) );
			// Filter to add GPS information to image metadata
			add_filter ('ngg_get_image_metadata', array (&$this, 'ngg_get_image_metadata'), 10, 2);
			add_action( 'parse_request', array( &$this, 'parse_request' ), 1 );
		}

		function wp_enqueue_scripts( $force = false ) {
			if ( $force || $this->detect_shortcode() ) {

				wp_enqueue_script("jquery");
				wp_enqueue_style( 'lightgallery', NGGLIGHTGALLERY_DIST . 'css/lightgallery-bundle.css' );
				wp_enqueue_script( 'lightgallery', NGGLIGHTGALLERY_DIST . 'lightgallery.umd.js', array(), false, true );

				/* LightGallery plugins */
				wp_enqueue_script( 'lg-autoplay', NGGLIGHTGALLERY_DIST . 'plugins/autoplay/lg-autoplay.min.js', array(), false, true );
				wp_enqueue_script( 'lg-fullscreen', NGGLIGHTGALLERY_DIST . 'plugins/fullscreen/lg-fullscreen.min.js', array(), false, true );
				wp_enqueue_script( 'lg-hash', NGGLIGHTGALLERY_DIST . 'plugins/hash/lg-hash.min.js', array(), false, true );
				//wp_enqueue_script( 'lg-thumbnail', NGGLIGHTGALLERY_DIST . 'plugins/thumbnail/lg-thumbnail.min.js', array(), false, true );
				wp_enqueue_script( 'lg-video', NGGLIGHTGALLERY_DIST . 'plugins/video/lg-video.min.js', array(), false, true );
				wp_enqueue_script( 'lg-zoom', NGGLIGHTGALLERY_DIST . 'plugins/zoom/lg-zoom.min.js', array(), false, true );

				wp_enqueue_style( 'ngg-lightgallery', NGGLIGHTGALLERY_PLUGIN_URL . 'ngg-lightgallery.css', array(), time() );
				wp_register_script( 'ngg-lightgallery', NGGLIGHTGALLERY_PLUGIN_URL . 'ngg-lightgallery.js', array(), time(), true );
				// Localize here
				wp_enqueue_script( 'ngg-lightgallery' );

				// Instruct wp_footer() that we already have the scripts.
				$this->have_scripts = true;
			}
		}

		function detect_shortcode() {
			global $wp_query;
			$posts = $wp_query->posts;

			foreach ( $posts as $post ) {
				if ( $this->has_shortcode( $post ) ) {
					return true;
				}
			}
			return false;
		}

		function has_shortcode( $post ) {
			$pattern = get_shortcode_regex();
			if ( preg_match_all( '/' . $pattern . '/s', $post->post_content, $matches )
				&& array_key_exists( 2, $matches )
				&& ( in_array( $this->shortcode1, $matches[2] ) ) ) {
					return true;
			}
			return false;
		}

		/**
		 * This function takes a $wpdb result set with pictures
		 */
		function make_gallery( $res ) {

			// Regexps
			$video_re = '/\.(mp4|flv)\.jpg$/i';

			$out = '';

			// Output captions in separate divs
			foreach ($res as $row) {

				// NextGen 1 stored a serialized array
				if ( substr( $row['meta_data'], 0, 2 ) == 'a:' ) {
					$meta = unserialize( $row['meta_data'] );
				}
				// NextGen 3 stores a base64-encoded JSON object
				else {
					$json = base64_decode( $row['meta_data'] );
					$meta = json_decode( $json, true );
				}

				list ($gurl, $gpsc) = $this->getmapurl( $meta, '<img src="' . plugins_url( 'pin.png', __FILE__ ) . '" style="vertical-align: middle" />'  );
				$description = $row['filename'];
				if ( $gurl ) { $description .= "&nbsp; $gpsc"; }
				$description .= '<br />';
				if ( $row['description'] ) {
					if ( substr( $row['description'], 0, 10 ) == 'pannellum:' ) {
						$row['description'] = substr( $row['description'], 10 );
					}
					$description .= $row['description'] . '<br />';
				}
				$description .= '<br />';

				/*
				if (($t = $meta ["created_timestamp"]) != "") {
					$description .= $t . '<br />';
				}
				*/
				$description .= $row['imagedate'] . '<br />';

				$c = '';
				if ( isset( $meta['copyright'] ) ) {
					$c = $meta['copyright'];
				} elseif ( isset( $row['copyright'] ) ) {
					$c = $row['copyright'];
				}

				if ( $c != "" ) {
					$description .= '&copy; ' . $c . '<br />';
				}

				$out .= '<div style="display:none;" id="caption' . $row['pid'] .'">' .
					$description .
					'</div>' . "\n";
			}

			static $num_galleries = 0;
      $div_id = 'lightgallery' . ++$num_galleries;

			$out .= '<div id="' . $div_id . '" class="lightgallery">';

			foreach ($res as $row) {

				$imgsrc = get_home_url() . '/' . $row['path'] . '/' . $row['filename'];
				$tmbsrc = get_home_url() . '/' . $row['path'] . '/thumbs/thumbs_' . $row['filename'];
				$dlpath = str_replace( 'wp-content/gallery', NGGLIGHTGALLERY_DLURL_PREFIX, $row['path'] );

				if ( substr( $row['description'], 0, 5 ) == 'link:' ) {
					$out .= '<a data-iframe="true" data-src="' . htmlspecialchars( substr( $row['description'], 5 ) ) . '">' . "\n";
					$out .=   '<img class="ngglg-thumb" src="' . $tmbsrc  . '" />' . "\n";
					$out .= '</a>' . "\n";
					continue;
				}

				if ( substr( $row['description'], 0, 9 ) == 'pannellum' ) {
					$iframe_url = '/wp/' . NGGLIGHTGALLERY_PANNELLUM_SLUG . '/' . $row['pid'];
					$out .= '<a data-iframe="true" data-download-url="false" data-src="' . htmlspecialchars( $iframe_url ) . '" data-sub-html="#caption' . $row['pid'] . '">' . "\n";
					$out .=   '<img class="ngglg-thumb" src="' . $tmbsrc  . '" />' . "\n";
					$out .= '</a>' . "\n";

					continue;
				}

				$n = @preg_match($video_re, $row ['filename'], $matches);

				// Video
				if ($n == 1) {
					$video_filename = substr( $row['filename'], 0, -4 );
					$poster_suffix = $row['path'] . '/poster/' . $video_filename . '.poster.jpg';
					$poster_path = ABSPATH . '/' . $poster_suffix;
					$poster_url  = get_home_url() . '/' . $poster_suffix;
					$video_link = get_home_url() . '/' . $row['path'] . '/' . $video_filename;

					if ( file_exists( $poster_path ) ) {
						$postersrc = $poster_url;
					}
					else {
						$postersrc = $imgsrc;
					}
					//$out .= '<a href="" data-poster="' . $postersrc . '" data-sub-html="#caption' . $row['pid'] . '" data-html="#video' . $row['pid'] . '">' . "\n";
					$out .= '<a href="" data-poster="' . $postersrc . '" data-sub-html="#caption' . $row['pid'] . '" data-video=' . "'" .
						'{"source": [{"src":"' . $video_link . '", "type":"video/mp4"}], "attributes": {"preload": false, "controls": true}}' .
						"'>" . "\n";
					$out .=   '<img class="ngglg-thumb" src="' . $tmbsrc  . '" title="' . $row['filename'] . '"/>' . "\n";
					$out .= '</a>' . "\n";
				}
				// Image
				else {
					$out .= '<a href="' . $imgsrc . '" data-sub-html="#caption' . $row['pid'] . '" data-download-url="'. $dlpath . '/' . $row['filename'] . '">' . "\n";
					$out .=   '<img class="ngglg-thumb" src="' . $tmbsrc  . '" />' . "\n";
					$out .= '</a>' . "\n";
				}

			}

			$out .= '</div>';
			$out .= '<div class="lg-clear"></div>';
			return $out;

		}

		function handle_shortcode1( $atts ) {

			global $wpdb;

			$parent = wp_get_post_parent_id( get_the_ID() );
			$title  = get_the_title( $parent );
			$link   = get_permalink( $parent );
			$out    = '';

			$defaults = array(
				'id' => false,
			);
			$atts = shortcode_atts( $defaults, $atts, $this->shortcode1 );

			if ( $atts['id'] ) {
				$sql = $wpdb->prepare( 'SELECT path, title, galdesc FROM wp_ngg_gallery g WHERE gid=%d', $atts['id']);
				$gallery = $wpdb->get_row( $sql, ARRAY_A );
				// Array ( [path] => /wp-content/gallery/2014-006-motor-loire-frankrijk [title] => Motorvakantie in de Loire )
				$sql = $wpdb->prepare( 'SELECT p.*,g.path FROM wp_ngg_pictures p, wp_ngg_gallery g WHERE p.galleryid = g.gid AND p.galleryid=%d AND exclude=0 ORDER BY filename', $atts['id']);
				$res = $wpdb->get_results( $sql, ARRAY_A );

				$out .= '<h3>' . htmlspecialchars( $gallery['galdesc'] ) . '</h3>';

				$out .= $this->make_gallery( $res );
				$this->need_scripts = true;
			}
			else {
				return "Unknown gallery.";
			}

			return do_shortcode( $out );
		}

		function handle_shortcode2( $atts ) {

			global $wpdb;

			$defaults = array(
				'gallery' => false,
			);
			$atts = shortcode_atts( $defaults, $atts, $this->shortcode2 );

			$out = '';

			if ( $atts['gallery'] ) {

				$taglist = explode( ',', $atts['gallery'] );

				if ( !is_array( $taglist ) )
					$taglist = array( $taglist );

				$taglist = array_map( 'trim', $taglist );
				$new_slugarray = array_map( 'sanitize_title', $taglist );
				$sluglist   = "'" . implode( "', '", $new_slugarray ) . "'";

				//Treat % as a litteral in the database, for unicode support
				$sluglist=str_replace("%","%%",$sluglist);

				// first get all $term_ids with this tag
				$term_ids = $wpdb->get_col( $wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE slug IN ($sluglist) ORDER BY term_id ASC ", NULL));
				$picids = get_objects_in_term($term_ids, 'ngg_tag');

				$imagelist = '(' . implode( ',', $picids ) . ')';

				$sql = 'SELECT p.*,g.path FROM wp_ngg_pictures p, wp_ngg_gallery g WHERE p.galleryid = g.gid AND p.pid in ' . $imagelist . ' AND exclude=0 ORDER BY sortorder,filename';
				$res = $wpdb->get_results( $sql, ARRAY_A );

				//$out .= print_r( $sql, true );
				$out .= $this->make_gallery( $res );
				$this->need_scripts = true;

			}
			return do_shortcode( $out );
		}

		function wp_footer() {
			if ( $this->need_scripts && ! $this->have_scripts ) {
				$this->wp_enqueue_scripts( true );
			}
		}

		function shortcode_nggenav ($atts) {
			global $post;

			$atts = shortcode_atts (array (
					'scope' => 'siblings',
					'limit' => 0
				), $atts);

			if ($atts ['scope'] == 'children') {
				$parent = $post -> ID;
			}
			elseif ( $atts['scope'] == 'parent' ) {
				$parent_post = get_post( $post -> post_parent );
				$parent = $parent_post -> post_parent;
			}
			else {
				$parent = $post -> post_parent;
			}

			$opts = array (
				'meta_key'    => 'ngg_album',
				'sort_column' => 'menu_order,post_title',
				'sort_order'  => 'desc',
				'title_li'    => '',
				'parent'      => $parent,  // only get siblings or children of current page
			);

			$pages = get_pages ($opts);
			$rewrite_pattern = '/Photos? (.*)/';
			$rewrite_replace = '\\1';
			$links = array();
			$i = 0;
			foreach ($pages as $p) {
				$links[] = "<a href = \"" . get_page_link ($p -> ID) ."\">".
					ucfirst (preg_replace ($rewrite_pattern, $rewrite_replace, $p -> post_title)). "</a>";
				$i++;
				if ( (int) $atts['limit'] > 0 && $i >= (int) $atts['limit'] ) {
					if ($i < count( $pages ) ) {
						$links[] = '...';
					}
					break;
				}
			}
			return implode(' | ', $links);
		}

		function getmapurl ( $meta, $linktxt=null ) {
			$cstr = "Unknown";
			if ( $linktxt) { $cstr = $linktxt; }
			$gurl = null;
			if ( isset( $meta['GPSLatitude'] ) && $meta['GPSLatitude'] ) {
				$latref = $meta['GPSLatitudeRef'];
				$lonref = $meta['GPSLongitudeRef'];
				list( $d0, $m0, $s0 ) = $meta['GPSLatitude'];
				list( $d1, $m1, $s1 ) = $meta['GPSLongitude'];

				// These evaluations transform '42/1' to 42, '609/100' to 6.09, etc.
				$d0 = eval( "return $d0;" );
				$m0 = eval( "return $m0;" );
				$s0 = eval( "return $s0;" );
				$d1 = eval( "return $d1;" );
				$m1 = eval( "return $m1;" );
				$s1 = eval( "return $s1;" );

				$lat = $d0 + ( $m0 / 60 ) + ( $s0 / 3600 );
				$lon = $d1 + ( $m1 / 60 ) + ( $s1 / 3600 );

				if ( ! $linktxt ) { $cstr = "$latref $lat $lonref $lon"; }

				if ( $latref == 'S' ) $lat = -$lat;
				if ( $lonref == 'W' ) $lon = -$lon;

				$gurl = 'http://maps.google.nl/maps?f=q&hl=en&geocode=&ie=UTF8&z=15&q=';
				$gurl .= "$lat+$lon";
			}

			if ( $gurl ) { $gpsc = '<a href="' . $gurl . '" target="_blank">' . $cstr . '</a>'; }
			else $gpsc = $cstr;

			return array( $gurl, $gpsc );
		}

		/**
		 * Handler for 'ngg_get_image_metadata' filter.
		 * This function expands the image metadata that NGG stores
		 */
		function ngg_get_image_metadata ($meta, $pdata) {
			if ( isset($pdata->exif_data['GPS']) ) {
				$exif = $pdata->exif_data['GPS'];
				if (!empty($exif['GPSLatitudeRef']))
					$meta['common']['GPSLatitudeRef'] = trim( $exif['GPSLatitudeRef'] );
				if (!empty($exif['GPSLatitude']))
					$meta['common']['GPSLatitude'] = $exif['GPSLatitude'];
				if (!empty($exif['GPSLongitudeRef']))
					$meta['common']['GPSLongitudeRef'] = trim( $exif['GPSLongitudeRef'] );
				if (!empty($exif['GPSLongitude']))
					$meta['common']['GPSLongitude'] = $exif['GPSLongitude'];
				if (!empty($exif['GPSAltitudeRef']))
					$meta['common']['GPSAltitudeRef'] = trim( $exif['GPSAltitudeRef'] );
				if (!empty($exif['GPSAltitude']))
					$meta['common']['GPSAltitude'] = trim( $exif['GPSAltitude'] );
			}
			return $meta;
		}

		/**
		 * A function to get the real request URI, stripped of all the basics. What
		 * this function returns is meant to be used to match against, for URIs
		 * that Trackserver must handle.
		 *
		 * The code is borrowed from WP's own parse_request() function.
		 *
		 * @since 4.4
		 */
		function get_request_uri() {
			global $wp_rewrite;

			if ( ! $wp_rewrite->using_permalinks() || $wp_rewrite->using_index_permalinks() ) {
				$this->url_prefix = '/' . $wp_rewrite->index;
			}

			$home_path       = trim( parse_url( home_url(), PHP_URL_PATH ), '/' ) . $this->url_prefix;
			$home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

			$pathinfo         = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
			list( $pathinfo ) = explode( '?', $pathinfo );
			$pathinfo         = trim( $pathinfo, '/' );
			$pathinfo         = preg_replace( $home_path_regex, '', $pathinfo );
			$pathinfo         = trim( $pathinfo, '/' );

			list( $request_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
			$request_uri         = str_replace( $pathinfo, '', $request_uri );
			$request_uri         = trim( $request_uri, '/' );
			$request_uri         = preg_replace( $home_path_regex, '', $request_uri );
			$request_uri         = trim( $request_uri, '/' );

			// The requested permalink is in $pathinfo for path info requests and $request_uri for other requests.
			if ( ! empty( $pathinfo ) && ! preg_match( '|^.*' . preg_quote( $wp_rewrite->index, '|' ) . '$|', $pathinfo ) ) {
				$requested_path = $pathinfo;
			} else {
				// If the request uri is the index, blank it out so that we don't try to match it against a rule.
				if ( $request_uri === $wp_rewrite->index ) {
					$request_uri = '';
				}
				$requested_path = $request_uri;
			}
			return $requested_path;
		}

		/**
		 * Parse the request, and hijack requests that match /pannellum/<id>
		 */
		function parse_request( $wp ) {
			$req_uri = $this->get_request_uri();
			$pattern = '/^' . preg_quote( NGGLIGHTGALLERY_PANNELLUM_SLUG ) . '\/(?<id>\d+)\/?$/';

			// Match the URL
			$n = preg_match( $pattern, $req_uri, $matches );
			if ( $n === 1 ) {
					$this->pannellum_html( $matches['id'] );
					die();
			}
			return $wp;
		}

		function pannellum_config() {
			$cfg = $this->script_config;
			foreach ( (array) $cfg as $key => $value ) {
				if ( ! is_scalar( $value ) ) {
					continue;
				}
				$cfg[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
			}
			$script = "var pannellum_config = " . wp_json_encode( $cfg ) . ';';
			echo $script;
			echo "\n";
		}

		function pannellum_html( $pic_id ) {
			global $wpdb;

			$pic_id     = intval( $pic_id );
			$sql        = $wpdb->prepare( 'SELECT p.*,g.path FROM wp_ngg_pictures p, wp_ngg_gallery g WHERE p.galleryid = g.gid AND p.pid=%d AND exclude=0', $pic_id);
			$res        = $wpdb->get_results( $sql, ARRAY_A );
			$row        = $res[0];
			$dirname    = trailingslashit( end( explode( '/', trim( $row['path'], '/' ) ) ) );
			$imgsrc1    = trailingslashit( get_home_url() ) . trailingslashit( ltrim( $row['path'], '/' ) ) . $row['filename']; // resized by NextGen
			$imgsrc2    = NGGLIGHTGALLERY_DLURL_PREFIX2 . $dirname . $row['filename'];   // full size
			$p_opt      = '';
			$wp_version = get_bloginfo( 'version' );

			// Expand pic description for Pannellum config: 'pannellum(opt1:val1;opt2:val2):...'.
			$valid_options = array( 'yaw', 'pitch', 'hfov', 'autoRotate', 'autoRotateInactivityDelay' );
			$n = preg_match( '/^pannellum(\((?<opt>.*)\))?:/', $row['description'], $matches );
			if ( $matches['opt'] ) {
				$opts = explode( ';', $matches['opt'] );
				foreach ($opts as $o) {
					$kv = explode( ':', $o );
					if (in_array( $kv[0], $valid_options ) ) {
						$p_opt .= '        "' . $kv[0] . '": ' . (int) $kv[1] . ",\n";
					}
				}
				$p_opt = trim( $p_opt );
			}

			echo <<<EOF
<html>
  <head>
    <title>{$row['filename']}</title>
    <link rel="stylesheet" id="pannellum-css"  href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css?ver=${wp_version}" type="text/css" media="all" />
    <style>
      .pnlm-ngglg {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
      }
    </style>
  </head>
  <body>
    <div id="pannellum${pic_id}" class="pnlm-ngglg"></div>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js?ver=${wp_version}" id="pannellum-js"></script>
    <script type="text/javascript">
      // Check max texture size to see what image we should load
      var canvas = document.createElement('canvas');
      var gl = canvas.getContext('experimental-webgl');
      var maxSize = gl.getParameter(gl.MAX_TEXTURE_SIZE);
      var extension = gl.getExtension('WEBGL_lose_context');
      if (extension)
        extension.loseContext();

      var src1 = '${imgsrc1}';
      var src2 = '${imgsrc2}';

      if (maxSize < 8192) {
        var src = src1;
      } else {
        var src = src2;
      }

      pannellum.viewer('pannellum${pic_id}', {
        "type": "equirectangular",
        "panorama": src,
        "autoLoad": true,
        ${p_opt}
        "orientationOnByDefault": false,
        "compass": false
      });
    </script>
  </body>
</html>
EOF;

		}

	}  // Class
}  // If

$nggslick = new NggLightGallery();
