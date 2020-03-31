class Envira_Link {

	constructor(data, images, lightbox) {

		var self = this;

		// Setup our Vars
		self.data = data;
		self.images = images;
		self.id = this.get_config('gallery_id');
		self.envirabox_config = lightbox;

		// Log if ENVIRA_DEBUG enabled
		self.log(self.data);
		self.log(self.images);
		self.log(self.envirabox_config);
		self.log(self.id);

		self.init();

	}

	init() {

		var self = this;

		self.lightbox();

	}

	/**
	 * Outputs the gallery init script in the footer.
	 *
	 * @since 1.7.1
	 */
	lightbox() {

		var self = this,
			touch = self.get_config('mobile_touchwipe') ? { vertical: true, momentum: true } : true,
			thumbs = self.get_config('thumbnails') ? { autoStart: true, hideOnClose: true, position: self.get_lightbox_config('thumbs_position') } : false,
			slideshow = self.get_config('slideshow') ? { autoStart: self.get_config('autoplay'), speed: self.get_config('ss_speed') } : false,
			fullscreen = self.get_config('fullscreen') && self.get_config('open_fullscreen') ? { autoStart: true } : true,
			animationEffect = self.get_config('lightbox_open_close_effect') == 'zomm-in-out' ? 'zoom-in-out' : self.get_config('lightbox_open_close_effect'),
			transitionEffect = self.get_config('effect') == 'zomm-in-out' ? 'zoom' : self.get_config('effect'),
			lightbox_images = [];
		self.lightbox_options = {
			selector: '[data-envirabox="' + self.id + '"]',
			loop: self.get_config('loop'), // Enable infinite gallery navigation
			margin: self.get_lightbox_config('margins'), // Space around image, ignored if zoomed-in or viewport width is smaller than 800px
			gutter: self.get_lightbox_config('gutter'), // Horizontal space between slides
			keyboard: self.get_config('keyboard'), // Enable keyboard navigation
			arrows: self.get_lightbox_config('arrows'), // Should display navigation arrows at the screen edges
			arrow_position: self.get_lightbox_config('arrow_position'),
			infobar: self.get_lightbox_config('infobar'), // Should display infobar (counter and arrows at the top)
			toolbar: self.get_lightbox_config('toolbar'), // Should display toolbar (buttons at the top)
			idleTime: self.get_lightbox_config('idle_time') ? self.get_lightbox_config('idle_time') : false, // by default there shouldn't be any, otherwise value is in seconds
			smallBtn: self.get_lightbox_config('show_smallbtn'),
			protect: false, // Disable right-click and use simple image protection for images
			image: { preload: false },
			animationEffect: animationEffect,
			animationDuration: self.get_lightbox_config('animation_duration') ? self.get_lightbox_config('animation_duration') : 300, // Duration in ms for open/close animation
			btnTpl: {
				smallBtn: self.get_lightbox_config('small_btn_template'),
			},
			zoomOpacity: 'auto',
			transitionEffect: transitionEffect, // Transition effect between slides
			transitionDuration: self.get_lightbox_config('transition_duration') ? self.get_lightbox_config('transition_duration') : 200, // Duration in ms for transition animation
			baseTpl: self.get_lightbox_config('base_template'), // Base template for layout
			spinnerTpl: '<div class="envirabox-loading"></div>', // Loading indicator template
			errorTpl: self.get_lightbox_config('error_template'), // Error message template
			fullScreen: self.get_config('fullscreen') ? fullscreen : false,
			touch: touch, // Set `touch: false` to disable dragging/swiping
			hash: false,
			insideCap: self.get_lightbox_config('inner_caption'),
			capPosition: self.get_lightbox_config('caption_position'),
			capTitleShow: self.get_config('lightbox_title_caption') && self.get_config('lightbox_title_caption') != 'none' && self.get_config('lightbox_title_caption') != '0' ? self.get_config('lightbox_title_caption') : false,
			media: {
				youtube: {
					params: {
						autoplay: 0
					}
				}
			},
			wheel: this.get_config('mousewheel') == false ? false : true,
			slideShow: slideshow,
			thumbs: thumbs,
			mobile: {
				clickContent: function (current, event) {
					return current.type === 'image' ? 'toggleControls' : false;
				},
				clickSlide: function (current, event) {
					return current.type === 'image' ? 'toggleControls' : 'close';
				},
				dblclickContent: false,
				dblclickSlide: false,
			},
			// Clicked on the content
			clickContent: self.get_lightbox_config('click_content') ? self.get_lightbox_config('click_content') : 'toggleControls', // clicked on the image itself
			clickSlide: self.get_lightbox_config('click_slide') ? self.get_lightbox_config('click_slide') : 'close', // clicked on the slide
			clickOutside: self.get_lightbox_config('click_outside') ? self.get_lightbox_config('click_outside') : 'toggleControls', // clicked on the background (backdrop) element

			// Same as previous two, but for double click
			dblclickContent: false,
			dblclickSlide: false,
			dblclickOutside: false,

			// Video settings
			videoPlayPause: self.get_config('videos_playpause') ? true : false,
			videoProgressBar: self.get_config('videos_progress') ? true : false,
			videoPlaybackTime: self.get_config('videos_current') ? true : false,
			videoVideoLength: self.get_config('videos_duration') ? true : false,
			videoVolumeControls: self.get_config('videos_volume') ? true : false,
			videoControlBar: self.get_config('videos_controls') ? true : false,
			videoFullscreen: self.get_config('videos_fullscreen') ? true : false,
			videoDownload: self.get_config('videos_download') ? true : false,
			videoPlayIcon: self.get_config('videos_play_icon_thumbnails') ? true : false,

			// Callbacks
			// ==========
			onInit: function (instance, current) {

				$(document).trigger('envirabox_api_on_init', [self, instance, current]);
			},

			beforeLoad: function (instance, current) {

				$(document).trigger('envirabox_api_before_load', [self, instance, current]);

			},
			afterLoad: function (instance, current) {

				$(document).trigger('envirabox_api_after_load', [self, instance, current]);

			},

			beforeShow: function (instance, current) {

				$(document).trigger('envirabox_api_before_show', [self, instance, current]);

			},
			afterShow: function (instance, current) {

				if (prepend == undefined || prepend_cap == undefined) {

					var prepend = false,
						prepend_cap = false;

				}

				if (prepend != true) {

					$('.envirabox-position-overlay').each(
						function () {
							$(this).prependTo(current.$content);
						}
					);

					prepend = true;
				}

				/* support older galleries or if someone overrides the keyboard configuration via a filter, etc. */

				if (self.get_config('keyboard') !== undefined && self.get_config('keyboard') === 0) {

					$(window).keypress(
						function (event) {

							if ([32, 37, 38, 39, 40].indexOf(event.keyCode) > -1) {
								event.preventDefault();
							}

						}
					);

				}

				/* legacy theme we hide certain elements initially to prevent user seeing them for a second in the upper left until the CSS fully loads */
				$('.envirabox-caption').show();
				$('.envirabox-navigation').show();
				$('.envirabox-navigation-inside').show();

				$(document).trigger('envirabox_api_after_show', [self, instance, current]);

			},

			beforeClose: function (instance, current) {

				$(document).trigger('envirabox_api_before_close', [self, instance, current]);

			},
			afterClose: function (instance, current) {

				$(document).trigger('envirabox_api_after_close', [self, instance, current]);

			},

			onActivate: function (instance, current) {

				$(document).trigger('envirabox_api_on_activate', [self, instance, current]);

			},
			onDeactivate: function (instance, current) {

				$(document).trigger('envirabox_api_on_deactivate', [self, instance, current]);

			},

		};

		$(document).trigger('envirabox_options', self);

		// Mobile Overrides
		if (self.is_mobile()) {

			if (self.get_config('mobile_thumbnails') !== 1) {
				self.lightbox_options.thumbs = false;
			}

		}

		$.each(
			self.images,
			function (i) {

				lightbox_images.push(this);

				// Formats matching url to final form
				var format = function (url, rez, params) {
					if (!url) {
						return;
					}

					params = params || '';

					if ($.type(params) === "object") {
						params = $.param(params, true);
					}

					$.each(
						rez,
						function (key, value) {
							url = url.replace('$' + key, value || '');
						}
					);

					if (params.length) {
						url += (url.indexOf('?') > 0 ? '&' : '?') + params;
					}

					return url;
				};

				/* is video? */
				var video_defaults = {
					youtube_playlist: {
						matcher: /^http:\/\/(?:www\.)?youtube\.com\/watch\?((v=[^&\s]*&list=[^&\s]*)|(list=[^&\s]*&v=[^&\s]*))(&[^&\s]*)*$/,
						params: {
							autoplay: 1,
							autohide: 1,
							fs: 1,
							rel: 0,
							hd: 1,
							wmode: 'transparent',
							enablejsapi: 1,
							html5: 1
						},
						paramPlace: 8,
						type: 'iframe',
						url: '//www.youtube.com/embed/videoseries?list=$4',
						thumb: '//img.youtube.com/vi/$4/hqdefault.jpg'
					},

					youtube: {
						matcher: /(youtube\.com|youtu\.be|youtube\-nocookie\.com)\/(watch\?(.*&)?v=|v\/|u\/|embed\/?)?(videoseries\?list=(.*)|[\w-]{11}|\?listType=(.*)&list=(.*))(.*)/i,
						params: {
							autoplay: 1,
							autohide: 1,
							fs: 1,
							rel: 0,
							hd: 1,
							wmode: 'transparent',
							enablejsapi: 1,
							html5: 1
						},
						paramPlace: 8,
						type: 'iframe',
						url: '//www.youtube.com/embed/$4',
						thumb: '//img.youtube.com/vi/$4/hqdefault.jpg'
					},

					vimeo: {
						matcher: /^.+vimeo.com\/(.*\/)?([\d]+)(.*)?/,
						params: {
							autoplay: 1,
							hd: 1,
							show_title: 1,
							show_byline: 1,
							show_portrait: 0,
							fullscreen: 1,
							muted: 1, // not sure is this is a real setting
							api: 1
						},
						paramPlace: 3,
						type: 'iframe',
						url: '//player.vimeo.com/video/$2'
					},

					metacafe: {
						matcher: /metacafe.com\/watch\/(\d+)\/(.*)?/,
						type: 'iframe',
						url: '//www.metacafe.com/embed/$1/?ap=1'
					},

					dailymotion: {
						matcher: /dailymotion.com\/video\/(.*)\/?(.*)/,
						params: {
							additionalInfos: 0,
							autoStart: 1
						},
						type: 'iframe',
						url: '//www.dailymotion.com/embed/video/$1'
					},

					facebook: {
						matcher: /facebook.com\/facebook\/videos\/(.*)\/?(.*)/,
						type: 'genericDiv',
						subtype: 'facebook',
						url: '//www.facebook.com/facebook/videos/$1'
					},

					instagram: {
						matcher: /(instagr\.am|instagram\.com)\/p\/([a-zA-Z0-9_\-]+)\/?/i,
						type: 'image',
						url: '//$1/p/$2/media/?size=l'
					},

					instagram_tv: {
						matcher: /(instagr\.am|instagram\.com)\/tv\/([a-zA-Z0-9_\-]+)\/?/i,
						type: 'iframe',
						url: '//$1/p/$2/media/?size=l'
					},

					// https://scontent-mia3-1.cdninstagram.com/vp/f31edadb32713082be585c276d336aa8/5C66C69A/t50.2886-16/26203486_930585467119082_6733111627396153344_n.mp4?_nc_ht=scontent-mia3-1.cdninstagram.com

					instagram_video: {
						matcher: /(cdninstagr\.am|cdninstagram\.com)\/vp\/([a-zA-Z0-9_\-]+)\/?/i,
						type: 'instagram_video',
						url: '//$1/p/$2/media/?size=l'
					},

					wistia: {
						matcher: /wistia.com\/medias\/(.*)\/?(.*)/,
						type: 'iframe',
						url: '//fast.wistia.net/embed/iframe/$1'
					},

					// Example: //player.twitch.tv/?video=270862436ß
					twitch: {
						matcher: /player.twitch.tv\/[\\?&]video=([^&#]*)/,
						type: 'iframe',
						url: '//player.twitch.tv/?video=$1'
					},

					// Example: //videopress.com/v/DK5mLrbr    ?at=1374&loop=1&autoplay=1
					videopress: {
						matcher: /videopress.com\/v\/(.*)\/?(.*)/,
						type: 'iframe',
						url: '//videopress.com/embed/$1'
					},

					self_hosted: {
						matcher: /(.mp4|.flv|.ogv|.webm)/,
						type: 'video'
					},

					pdf: {
						matcher: /(.pdf)/,
						type: 'iframe'
					},

				};

				var image = this,
				url       = this.link || '',
				type      = false,
				subtype   = false,
				media,
				thumb,
				rez,
				params,
				urlParams,
				o,
				provider  = false,
				media     = $.extend( true, {}, video_defaults, this.opts.media );

				// Look for any matching media type
				$.each(
					media,
					function (n, el) {
						rez = url.match(el.matcher);
						o = {};

						if ( ! rez || rez === undefined ) {
							return;
						}

						provider = n;
						type = el.type;
						subtype = el.subtype !== undefined ? el.subtype : false;

						if (el.paramPlace && rez[el.paramPlace]) {
							urlParams = rez[el.paramPlace];

							if (urlParams[0] == '?') {
								urlParams = urlParams.substring(1);
							}

							urlParams = urlParams.split('&');

							for (var m = 0; m < urlParams.length; ++m) {
								var p = urlParams[m].split('=', 2);

								if (p.length == 2) {
									o[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
								}
							}
						}


						if ( el.type !== 'instagram_video' && el.url !== undefined ) {
							url = $.type( el.url ) === "function" ? el.url.call( this, rez, params, item ) : format( el.url, rez, params );
						}

						if ( url === undefined ) {
							return;
						}

						if (self.data.videos_autoplay == 1) {
							if (provider === 'vimeo' || provider === 'youtube') {
								url = url + '/?autoplay=1';
							}
						}

						if (provider === 'vimeo') {
							url = url.replace('&%23', '#');
						}

					}
				);

				// If it is found, then change content type and update the url
				if (type) {
					this.video = true;
					this.src = url;
					this.type = type;
					this.subtype = subtype;

					if (type === 'iframe') {
						$.extend(
							true,
							this.opts,
							{
								iframe: {
									preload: true,
									provider: provider,
									attr: {
										scrolling: "no"
									}
								}
							}
						);

						this.contentProvider = provider;

					}

				} else {

					// If no content type is found, then set it to `image` as fallback
					this.type = 'image';

				}

			}
		);

		$('#envira-links-' + self.id).on(
			'click',
			function (e) {

				e.preventDefault();
				e.stopImmediatePropagation();

				var $this = $(this),
					images = [],
					$envira_images = $this.data('gallery-images'),
					sorted_ids = $this.data('gallery-sort-ids'), // sort by sort ids, not by output of gallery-images, because retaining object key order between unserialisation and serialisation in JavaScript is never guaranteed.
					sorting_factor = sorted_ids !== undefined && self.data.gallery_sort == 'gallery' ? 'id' : 'image',
					sorting_factor_data = sorted_ids !== undefined && self.data.gallery_sort == 'gallery' ? sorted_ids : $envira_images,
					active = $.envirabox.getInstance();

				// backup plan in case there isn't sorted_ids, we keep the lightbox_images even though this is probably not sorted
				if (sorted_ids !== undefined && sorted_ids != '') {

					lightbox_images = [];

					$.each(
						sorted_ids,
						function (i, val) {
							lightbox_images.push(sorting_factor_data[val]);
						}
					);

				}

				if (active) {
					return;
				}

				$.envirabox.open( lightbox_images, self.lightbox_options );


			}
		);

	}

	/**
	 * Get a config option based off of a key.
	 *
	 * @since 1.7.1
	 */
	get_config(key) {

		var self = this;

		return self.data[key];

	}

	/**
	 * Helper method to get config by key.
	 *
	 * @since 1.7.1
	 */
	get_lightbox_config(key) {

		var self = this;

		return self.envirabox_config[key];

	}

	/**
	 * Helper method to get image from id
	 *
	 * @since 1.7.1
	 */
	get_image(id) {

		var self = this;

		return self.images[id];

	}
	is_mobile() {
		if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
			return true;
		}
		return false;
	}
	/**
	 * Helper method for logging if ENVIRA_DEBUG is true.
	 *
	 * @since 1.7.1
	 */
	log(log) {

		// Bail if debug or log is not set.
		if (envira_gallery.debug == undefined || !envira_gallery.debug || log == undefined) {

			return;

		}
		console.log(log);

	}

}

module.exports = Envira_Link;
