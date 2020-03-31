<?php

class ShortPixelAI_Settings{
	/**
	 		* @var $instance
	 		*/
	 		private static $instance;

			/**
			 * Make sure only one instance is only running.
			 */
			
			public static function instance() {
				if ( ! isset ( self::$instance ) ) {
					self::$instance = new self;
				}

				return self::$instance;
			}
			public function __construct(){
				add_action( 'admin_init', array( $this, 'settings_api_init' ) );
				add_action( 'admin_menu', array( $this, 'custom_admin_menu' ) );
			}
			/*
			 * Admin Settings 
			 */
			function custom_admin_menu() {
				add_options_page(
					__('ShortPixel AI Settings','shortpixel-adaptive-images'),
					'ShortPixel AI',
					'manage_options',
					'shortpixel_ai_settings_page',
					array( $this, 'options_page' )
				);
			}
			public function settings_api_init() {

				register_setting( 'spai_setting_group', 'spai_settings_api_url');
				register_setting( 'spai_setting_group', 'spai_settings_compress_level');
                register_setting( 'spai_setting_group', 'spai_settings_eager_selectors');
                register_setting( 'spai_setting_group', 'spai_settings_noresize_selectors');
                register_setting( 'spai_setting_group', 'spai_settings_excluded_selectors');
                register_setting( 'spai_setting_group', 'spai_settings_excluded_paths');
                register_setting( 'spai_setting_group', 'spai_settings_type');
                register_setting( 'spai_setting_group', 'spai_settings_crop');
                register_setting( 'spai_setting_group', 'spai_settings_webp');
                register_setting( 'spai_setting_group', 'spai_settings_fadein');
				register_setting( 'spai_setting_group', 'spai_settings_remove_exif');
                register_setting( 'spai_setting_group', 'spai_settings_backgrounds_lazy');
                register_setting( 'spai_setting_group', 'spai_settings_backgrounds_max_width');
			}
			function spRadio($id, $extraClass, $optionName, $optionValue, $radioOptions, $msg, $details){
			    ?>
                <div class="shortpixel-ai-radio <?php echo($extraClass);?>" id="<?php echo($id);?>">
                    <div class="shortpixel-ai-radio-options">
                        <?php foreach($radioOptions as $key => $option) { ?>
                        <label class="lossy" title="<?php echo($option['title']) ?>">
                            <input type="radio" class="shortpixel-radio-<?php echo($key);?>" name="<?php echo($optionName);?>" value="<?php echo($option['val']);?>"
                                   <?php checked( $option['val'], $optionValue, true );  ?>>
                            <span><?php echo($option['name']);?></span>
                        </label>
                        <?php };
                        echo($msg);?>
                    </div>
                    <?php
                    $jsNeeded = false;
                    if(is_array($details)) {
                        foreach($details as $key => $detail) {
                            if(isset($radioOptions[$key])) {
                                $jsNeeded = true;?>
                                <p class="settings-info shortpixel-radio-info shortpixel-radio-<?php echo($key);?>"
                                    <?php echo( $optionValue == $radioOptions[$key]['val'] ? "" : 'style="display:none"' );?>>
                                    <?php echo($detail) ?>
                                </p>
                            <?php }
                        }
                    } else {
                        ?>
                        <p class="settings-info">
                            <?php echo($details) ?>
                        </p><?php
                    }
                    ?>
                </div>
                <?php if($jsNeeded) { ?>
                <script>
                    function shortpixelCompressionLevelInfo() {
                        jQuery("#<?php echo($id);?> p").css("display", "none");
                        jQuery("#<?php echo($id);?> p." + jQuery("#<?php echo($id);?> input:radio:checked").attr('class')).css("display", "block");
                    }
                    //shortpixelCompressionLevelInfo();
                    jQuery("#<?php echo($id);?> .shortpixel-ai-radio-options input:radio").change(shortpixelCompressionLevelInfo);
                </script>
                <?php }
            }
			function options_page($showAdvanced) {
				if ( ! isset( $_REQUEST['settings-updated'] ) ) {
                    $_REQUEST['settings-updated'] = false;
                }
                $maxWidth = intval(get_option( 'spai_settings_backgrounds_max_width' ));
				$maxWidth = $maxWidth ? $maxWidth : '';
				?>
				<div class="wpf-settings">
					<div class="wrap">
						<h1><?php echo esc_html( get_admin_page_title() ); ?> Settings</h1>
                        <p class="spai-settings-top-menu">
                            <a href="https://shortpixel.helpscoutdocs.com/category/131-shortpixel-adaptive-images" target="_blank">FAQ</a>
                            | <a href="https://shortpixel.com/contact" target="_blank">Support </a>
                        </p>
                        <div id="poststuff ">
							<div id="post-body">
								<div id="post-body-content" class="wp-shortpixel-ai-options">
                                    <form method="post" action="options.php">
                                        <?php settings_fields( 'spai_setting_group' ); ?>
                                        <article id="shortpixel-ai-settings-tabs" class="sp-tabs">
                                            <section <?php echo($showAdvanced ? "" : "class='sel-tab'");?> id="tab-settings">
                                                <h2><a class='tab-link' href='javascript:void(0);' data-id="tab-settings"><?php _e('General','shortpixel-adaptive-images');?></a></h2>
                                                <p><b><?php _e('If it is the first time you\'re using ShortPixel Adaptive Images please read our quick introduction on <a href="https://shortpixel.helpscoutdocs.com/article/231-step-by-step-guide-to-install-and-use-shortpixel-adaptive-images-spai#get-started" target="_blank">how to get started</a>.','shortpixel-adaptive-images');?></b></b></p>
                                                <table class="form-table">
                                                    <tr>
                                                        <th scope="row"><?php _e('Compression Level','shortpixel-adaptive-images');?></th>
                                                        <td>
                                                            <?php
                                                            $this->spRadio('shortpixel-compression', '', 'spai_settings_compress_level', get_option( 'spai_settings_compress_level' ),
                                                                array(
                                                                    'lossy' => array('val' => 1, 'name' => __('Lossy','shortpixel-adaptive-images'),
                                                                            'title' => __('This is the recommended option in most cases, producing results that look the same as the original to the human eye.','shortpixel-adaptive-images')),
                                                                    'glossy' => array('val' => 2, 'name' => __('Glossy','shortpixel-adaptive-images'),
                                                                            'title' => __('Best option for photographers and other professionals that use very high quality images on their sites and want best compression while keeping the quality untouched.','shortpixel-adaptive-images')),
                                                                    'lossless' => array('val' => 0, 'name' => __('Lossless','shortpixel-adaptive-images'),
                                                                            'title' => __('Make sure not a single pixel looks different in the optimized image compared with the original. In some rare cases you will need to use this type of compression. Some technical drawings or images from vector graphics are possible situations.','shortpixel-adaptive-images'))
                                                                ), '<a href="https://shortpixel.com/online-image-compression" target="_blank">' . __('Make a few tests</a> to help you decide.','shortpixel-adaptive-images'),
                                                                array(
                                                                    'lossy' => __('<b>Lossy compression (recommended): </b>offers the best compression rate.</br> This is the recommended option for most users, producing results that look the same as the original to the human eye.','shortpixel-adaptive-images'),
                                                                    'glossy' => __('<b>Glossy compression: </b>creates images that are almost pixel-perfect identical to the originals.</br> Best option for photographers and other professionals that use very high quality images on their sites and want best compression while keeping the quality untouched.','shortpixel-adaptive-images'),
                                                                    'lossless' => __('<b>Lossless compression: </b> the resulting image is pixel-identical with the original image.</br>Make sure not a single pixel looks different in the optimized image compared with the original.
                                    In some rare cases you will need to use this type of compression. Some technical drawings or images from vector graphics are possible situations.','shortpixel-adaptive-images')
                                                                )
                                                            ); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row"><?php _e('WebP Support','shortpixel-adaptive-images');?></th>
                                                        <td><label><input type="checkbox" name="spai_settings_webp" class="spai_settings_webp" value="1" <?php checked( 1, get_option( 'spai_settings_webp', 1 ), true );  ?>/>
                                                                <?php _e('Serve the images in the next-gen WebP image format to all the browsers that <a href="https://caniuse.com/#search=webp" target="_blank">support</a> it.','shortpixel-adaptive-images');?>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('The conversion and optimization from the original image format to WebP will be done one-the-fly by ShortPixel. Recommended for SEO.','shortpixel-adaptive-images');?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row"><?php _e('Fade-in effect','shortpixel-adaptive-images');?></th>
                                                        <td><label><input type="checkbox" name="spai_settings_fadein" class="spai_settings_fadein" value="1" <?php checked( 1, get_option( 'spai_settings_fadein', 1 ), true );  ?>/>
                                                                <?php _e('Fade-in the lazy-loaded images.','shortpixel-adaptive-images');?>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('If you experience problems with images that zoom on hover or have other special effects, try deactivating this option.','shortpixel-adaptive-images');?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row"><?php _e('Smart crop','shortpixel-adaptive-images');?></th>
                                                        <td><label><input type="checkbox" name="spai_settings_crop" class="spai_settings_crop" value="1" <?php checked( 1, get_option( 'spai_settings_crop', 0 ), true );  ?>/>
                                                                <?php _e('Smartly crop the images when possible and safe.','shortpixel-adaptive-images');?>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('The plugin will identify cases when not all the image is displayed and crop it accordingly. This might not work for some backgrounds (won\'t harm them though).','shortpixel-adaptive-images');?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <tr><th scope="row"><?php _e('Remove EXIF','shortpixel-adaptive-images');?></th>
                                                        <td><label><input type="checkbox" name="spai_settings_remove_exif" class="spai_settings_remove_exif"
                                                                          value="1" <?php checked( 1, get_option( 'spai_settings_remove_exif' ), true );  ?>/>
                                                                <?php _e('Remove the EXIF info from the images.','shortpixel-adaptive-images');?>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('The images will be smaller and no information about author/location will be present in the image.','shortpixel-adaptive-images');?>
                                                                <a href="https://blog.shortpixel.com/how-much-smaller-can-be-images-without-exif-icc/" target="_blank"><?php _e('Read more','shortpixel-adaptive-images');?></a>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <!-- TODO
                                                    <tr>
                                                        <th scope="row"><?php _e('Galleries integration','shortpixel-adaptive-images');?></th>
                                                        <td>
                                                            <?php
                                                            $this->spRadio('shortpixel-galleries', 'shortpixel-ai-small', 'spai_settings_galleries', get_option( 'spai_settings_galleries', 1),
                                                                array(
                                                                    'auto' => array('val' => 1, 'name' => __('Auto','shortpixel-adaptive-images'),
                                                                        'title' => __('This is the recommended option in most cases, producing results that look the same as the original to the human eye.','shortpixel-adaptive-images')),
                                                                    'manual' => array('val' => 2, 'name' => __('Manual','shortpixel-adaptive-images'),
                                                                        'title' => __('Best option for photographers and other professionals that use very high quality images on their sites and want best compression while keeping the quality untouched.','shortpixel-adaptive-images')),
                                                                    'none' => array('val' => 0, 'name' => __('None','shortpixel-adaptive-images'),
                                                                        'title' => __('Make sure not a single pixel looks different in the optimized image compared with the original. In some rare cases you will need to use this type of compression. Some technical drawings or images from vector graphics are possible situations.','shortpixel-adaptive-images'))
                                                                ), __('Integrate with the most common image galleries.','shortpixel-adaptive-images'),
                                                                array(
                                                                    'auto' => __('<b>Activate it for the installed gallery plugins. Currently Envira, Modula, Elementor, Essential add-ons for Elementor, Everest and the default WordPress gallery are supported','shortpixel-adaptive-images'),
                                                                    'manual' => __('Lets you chose which galleries will have their images replaced.','shortpixel-adaptive-images'),
                                                                    'none' => __('Don\'t integrate with any of the image galleries','shortpixel-adaptive-images')
                                                                )
                                                            ); ?>
                                                        </td>
                                                    </tr>
                                                    -->
                                                </table>
                                                <?php submit_button(); ?>
                                            </section>
                                            <section <?php echo($showAdvanced ? "class='sel-tab'" : "");?> id="tab-adv-settings">
                                                <h2><a class='tab-link' href='javascript:void(0);' data-id="tab-adv-settings"><?php _e('Advanced','shortpixel-adaptive-images');?></a></h2>
                                                <table class="form-table">
                                                    <tr><th scope="row"><?php _e('API URL','shortpixel-adaptive-images');?></th>
                                                        <td><label>
                                                                <input type="text" size="40" name="spai_settings_api_url" class="spai_settings_api_url"
                                                                       value="<?php echo get_option( 'spai_settings_api_url' ); ?>"/>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('Do <strong>not</strong> change this unless you plan on using your own CDN and you have it already configured to use ShortPixel.ai service. Check out <a href="https://shortpixel.helpscoutdocs.com/article/180-can-i-use-a-different-cdn-with-shortpixel-adaptive-images" target="_blank">here</a> or <a href="https://shortpixel.helpscoutdocs.com/article/200-setup-your-stackpath-account-so-that-it-can-work-with-shortpixel-adaptive-images-api" target="_blank">here</a> for examples','shortpixel-adaptive-images');?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <tr><th scope="row"><?php _e('Replace method','shortpixel-adaptive-images');?></th>
                                                        <td>
                                                            <div class="shortpixel-ai-radio shortpixel-ai-small">
                                                                <div class="shortpixel-compression-options">
                                                                    <label class="lossy" title="<?php _e('SRC makes sure as many images as possible are used with best fit.','shortpixel-adaptive-images');?>">
                                                                        <input type="radio" class="shortpixel-radio-src" name="spai_settings_type" value="1"  <?php checked( 1, get_option( 'spai_settings_type', 1), true );  ?>><span>SRC</span>
                                                                    </label>
                                                                    <label class="lossless" title="<?php _e('<b>EXPERIMENTAL:</b> Use BOTH if you have images that dynamically change size (enlarge on hover, etc.)','shortpixel-adaptive-images');?>">
                                                                        <input type="radio" class="shortpixel-radio-srcset" name="spai_settings_type" value="3" <?php checked( 3, get_option( 'spai_settings_type' ), true );  ?>><span>BOTH</span>
                                                                    </label>
                                                                    <label class="lossless" title="<?php _e('<b>EXPERIMENTAL:</b> Use SRCSET if you still encounter problems with specific content.','shortpixel-adaptive-images');?>">
                                                                        <input type="radio" class="shortpixel-radio-srcset" name="spai_settings_type" value="0" <?php checked( 0, get_option( 'spai_settings_type' ), true );  ?>><span>SRCSET</span>
                                                                    </label>
                                                                    <p class="description">
                                                                        <?php _e('SRC makes sure as many images as possible are used with best fit.','shortpixel-adaptive-images');?>
                                                                        <?php _e('Use SRCSET if you have images that dynamically change size (enlarge on hover, etc.)','shortpixel-adaptive-images');?>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr><th scope="row"><?php _e('Lazy-load the backgrounds','shortpixel-adaptive-images');?></th>
                                                        <td><label><input type="checkbox" name="spai_settings_backgrounds_lazy" class="spai_settings_backgrounds_lazy"
                                                                          value="1" <?php checked( 1, get_option( 'spai_settings_backgrounds_lazy' ), true );  ?>/>
                                                                <?php _e('Lazy-load the background images from inline STYLE blocks.','shortpixel-adaptive-images');?>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('This will make the backgrounds in STYLE blocks be loaded after the device with is determined. Will also impose a maximum width of the backgrounds equal to the viewport width.','shortpixel-adaptive-images');?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <tr><th scope="row"><?php _e('Backgrounds maximum width','shortpixel-adaptive-images');?></th>
                                                        <td><label><input type="text" name="spai_settings_backgrounds_max_width" class="spai_settings_backgrounds_max_width"
                                                                          value="<?php echo($maxWidth);  ?>" size="6"/> px.</label>
                                                            <p class="description">
                                                                <?php _e('Maximum width of the backgrounds, on all devices. Use to scale down huge backgrounds that are not lazy-loaded. Recommended value is 1920px','shortpixel-adaptive-images');?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <tr><th scope="row"><?php _e('Excluded selectors','shortpixel-adaptive-images');?></th>
                                                        <td><label>
                                                                <div style="display:inline-block;"><?php _e('Don\'t lazy-load:','shortpixel-adaptive-images');?><br>
                                                                    <textarea cols="84" rows="5" name="spai_settings_eager_selectors" class="spai_settings_eager_selectors"><?php
                                                                        echo get_option( 'spai_settings_eager_selectors' ) ? get_option( 'spai_settings_eager_selectors') : '';
                                                                        ?></textarea>
                                                                </div>
                                                                <div style="display:inline-block;"><?php _e('Don\'t resize:','shortpixel-adaptive-images');?><br>
                                                                    <textarea cols="84" rows="5" name="spai_settings_noresize_selectors" class="spai_settings_noresize_selectors"><?php
                                                                        echo get_option( 'spai_settings_noresize_selectors' ) ? get_option( 'spai_settings_noresize_selectors') : '';
                                                                    ?></textarea>
                                                                </div>
                                                                <div style="display:inline-block;"><?php _e('Leave out completely:','shortpixel-adaptive-images');?><br>
                                                                    <textarea cols="84" rows="5" name="spai_settings_excluded_selectors" class="spai_settings_excluded_selectors"><?php
                                                                        echo get_option( 'spai_settings_excluded_selectors' ) ? get_option( 'spai_settings_excluded_selectors') : '';
                                                                        ?></textarea>
                                                                </div>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('Specify  a coma separated list of CSS selectors for images which should be left to their original width on the page, or should be kept with their original URLs. Needed for images which can for example zoom in on hover. Keep these lists as small as possible. Rules like <strong>\'img.myclass\'</strong> are preferable as they are treated server-side at page rendering.','shortpixel-adaptive-images');?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                    <tr><th scope="row"><?php _e('Excluded URLs','shortpixel-adaptive-images');?></th>
                                                        <td><label>
                                                                <div style="display:inline-block;">
                                                                    <textarea cols="84" rows="5" name="spai_settings_excluded_paths" class="spai_settings_excluded_paths"><?php
                                                                        echo get_option( 'spai_settings_excluded_paths' ) ? get_option( 'spai_settings_excluded_paths') : '';
                                                                        ?></textarea>
                                                                </div>
                                                            </label>
                                                            <p class="description">
                                                                <?php _e('Specify a list of URL exclusion rules, one per line. An exclusion rule starts either by '
                                                                    . '<strong>path:</strong> or by <strong>regex:</strong>. After the colon:','shortpixel-adaptive-images');?>
                                                                <ul>
                                                                    <li>
                                                                        <i><?php _e('If it\'s a <strong>regex:</strong>, you can specify a full regex (ex: /.*\.gif$/i will exclude GIF images).','shortpixel-adaptive-images');?></i>
                                                                    </li>
                                                                    <li>
                                                                        <i><?php _e('If it\'s a <strong>path:</strong> rule, you can specify full URLs, '
                                                                            . 'domain names like gravatar.com or paths like /my-custom-image-folder/.','shortpixel-adaptive-images');?></i>

                                                                    </li>
                                                                </ul>
                                                            </p>
                                                            <p class="description">
                                                                <?php _e(' You can test your regex online, for example here: <a href="https://regex101.com/" target="_blank">regex101.com</a>.'
                                                                    . ' The rule for gravatar.com is included by default because many sites use gravatar and these images cannot be optimized, '
                                                                    . 'but if you\'re sure your site doesn\'t include gravatar URLs, feel free to remove it. ','shortpixel-adaptive-images');?>
                                                                <a href="https://shortpixel.helpscoutdocs.com/article/229-how-to-exclude-images-from-optimization-in-the-shortpixel-adaptive-images-plugin" target="_blank">
                                                                    <?php _e('Read more','shortpixel-adaptive-images');?>
                                                                </a>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <?php submit_button(); ?>
                                                <?php if(SHORTPIXEL_AI_DEBUG) { ?>
                                                    <div id="spai-debug">
                                                        <pre>
THEME NAME: <?php $theme = wp_get_theme(); print_r($theme->Name); ?>

DISMISSED NOTICES:
<?php print_r(get_option('spai_settings_dismissed_notices', array())); ?>
                                                        </pre>
                                                    </div>
                                                <?php } ?>
                                            </section>
                                        </article>
                                    </form>

							</div> <!-- end post-body-content -->
						</div> <!-- end post-body -->
						<script>
							jQuery(document).ready(function () {
								ShortPixelAIAdmin.adjustSettingsTabsHeight();
								jQuery( window ).resize(function() {
									ShortPixelAIAdmin.adjustSettingsTabsHeight();
								});
								if(window.location.hash) {
									var target = ('tab-' + window.location.hash.substring(window.location.hash.indexOf("#")+1)).replace(/\//, '');
									if(jQuery("section#" + target).length) {
										ShortPixelAIAdmin.switchSettingsTab(target);
									}
								}
								jQuery("article.sp-tabs a.tab-link").click(function(){ShortPixelAIAdmin.switchSettingsTab(jQuery(this).data("id"))});
							});
						</script>

						</div> <!-- end poststuff -->
				</div>
			</div>
			<?php
		}
	}
	new ShortPixelAI_Settings();

