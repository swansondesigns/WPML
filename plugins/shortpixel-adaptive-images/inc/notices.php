<?php
/**
 * User: simon
 * Date: 08.11.2018
 */

/**
 * Class ShortPixelAINotice - displays a notice - currently only the startup notice that the plugin is in beta.
 */
class ShortPixelAINotice {

    /**
     * ShortPixelAINotice constructor.
     * @param string $type : beta - startup notice that the plugin is in beta, key - offer to associate domain to the found API key, ao - autoptimize's optimize images is active
     * @param array $data - custom data for that specific notice type
     */
    public function __construct($type, $data = false, $iconSuffix = ''){
        ?>
        <div class='notice notice-warning' id='short-pixel-ai-notice-<?php echo($type);?>'>
            <div style="float:right;">
                <?php if(isset($data['button'])) { ?>
                    <div class="spai-action-error" style="display:none;color:red;padding: 15px 30px 0 0;">
                        <?php isset($data['button']['errormsg']) ? _e($data['button']['errormsg'],'shortpixel-adaptive-images') : ''; ?>
                    </div>
                    <a href="javascript:ShortPixelAIAdmin.dismissNotice('<?php echo admin_url('admin-ajax.php'); ?>', '<?php echo($type);?>', '<?php echo($data['button']['action']); ?>')"
                       class="button button-primary" style="margin-top:10px;"><?php _e($data['button']['name'],'shortpixel-adaptive-images');?></a>
                <?php } ?>
                <?php if(!isset($data['always-on'])) { ?>
                    <a href="javascript:ShortPixelAIAdmin.dismissNotice('<?php echo admin_url('admin-ajax.php'); ?>', '<?php echo($type);?>', '')"
                       class="button" style="margin-top:10px;"><?php _e('Dismiss','shortpixel-adaptive-images');?></a>
                <?php } ?>
            </div>
            <img src="<?php echo(plugins_url('/shortpixel-adaptive-images/img/robo' . $iconSuffix . '.png'));?>"
                 srcset='<?php echo(plugins_url( 'shortpixel-adaptive-images/img/robo' . $iconSuffix . '.png' ));?> 1x, <?php
                           echo(plugins_url( 'shortpixel-adaptive-images/res/img/robo' . $iconSuffix . '@2x.png' ));?> 2x'
                 class='short-pixel-ai-notice-icon' style="	float: left;margin: 10px 10px 10px 0;" ><?php
                switch($type) {
                    case 'beta': ?>
                        <h3><?php _e('ShortPixel Adaptive Images is in BETA', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php _e('Currently the plugin is in the Beta phase. While we have tested it a lot, we can\'t possibly test it with all the themes out there. On Javascript-intensive themes, layout issues could occur or some images might not be replaced. If you notice any problems, you just need to deactivate the plugin and the site will return to the previous state. Please kindly <a href="https://shortpixel.com/contact">let us know</a> and we\'ll be more than happy to work them out, as we\'re frankly depending on you to find such cases.', 'shortpixel-adaptive-images'); ?></p>
                        <?php
                        break;
                    case 'key': ?>
                        <h3><?php _e('ShortPixel account', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php printf(__('You already have a ShortPixel account for this website: <strong>%s</strong>. Do you want to use ShortPixel Adaptive Images with this account?', 'shortpixel-adaptive-images'),
                                $data['email']);
                            ?></p>
                        <?php
                        break;
                    case 'credits': ?>
                        <h3><?php _e('ShortPixel Adaptive Images notice', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php
                            if ($data['status'] == 1) {
                                _e('Please note that your ShortPixel Adaptive Images quota will be exhausted soon.', 'shortpixel-adaptive-images');
                            } else {
                                _e('Your ShortPixel Adaptive Images quota has been exceeded. :-(', 'shortpixel-adaptive-images');
                                if($data['status'] == -1) {
                                    echo('<br>');
                                    _e('The already optimized images will still be served from the ShortPixel CDN for up to 30 days but the images that weren\'t already optimized and cached via CDN will be served directly from your website.', 'shortpixel-adaptive-images');
                                }
                            }
                            echo('<br>');
                            if ($data['HasAccount']) {
                                _e('Please <a href="https://shortpixel.com/plans2">login to your account</a> to purchase more credits.', 'shortpixel-adaptive-images');
                            } else {
                                _e('If you  <a href="https://shortpixel.com/otp/af/MNCMIUS28044"><strong>sign-up now</strong></a> with ShortPixel you will receive 1,000 more free credits and also you\'ll get 50% bonus credits to any purchase that you\'ll choose to make. Image optimization credits can be purchased with as little as $4.99 for 7,500 credits (including the 50% bonus).', 'shortpixel-adaptive-images');
                            }
                            ?></p>
                        <?php
                        break;
                    case 'avadalazy': ?>
                        <h3><?php _e('ShortPixel Adaptive Images conflict', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php _e('The option "Enable Lazy Loading" is active in your Avada theme options, under the Performance section. Please <a href="themes.php?page=avada_options">deactivate it</a> to let ShortPixel Adaptive Images serve the images properly optimized and scaled.', 'shortpixel-adaptive-images');
                            ?></p>
                        <?php
                        break;
                    case 'elementorexternal': ?>
                        <h3><?php _e('ShortPixel Adaptive Images conflict', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php _e('The option "CSS Print Method" is set on External File in your Elementor options. Please <a href="themes.php?page=elementor#tab-advanced">change it to Internal Embedding</a> to let ShortPixel Adaptive Images also optimize background images.', 'shortpixel-adaptive-images');
                            ?></p>
                        <?php
                        break;
                    case 'ao': ?>
                        <h3><?php _e('ShortPixel Adaptive Images conflict', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php _e('The option "Optimize images on the fly and serve them from a CDN." is active in Autoptimize. Please <a href="options-general.php?page=autoptimize_imgopt">deactivate it</a> to let ShortPixel Adaptive Images serve the images properly optimized and scaled. <a href="https://shortpixel.helpscoutdocs.com/article/198-shortpixel-adaptive-images-vs-autoptimizes-optimize-images-option">More info.</a>', 'shortpixel-adaptive-images');
                            ?></p>
                        <?php
                        break;
                    case 'twicelossy': ?>
                        <h3><?php _e('ShortPixel optimization alert', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php _e('ShortPixel Adaptive Images and ShortPixel Image Optimizer are both set to do Lossy optimization which could result in a too aggressive optimization of your images, please set one of them on Lossless.', 'shortpixel-adaptive-images');
                            ?></p>
                        <?php
                        break;
                    case 'ginger': ?>
                        <h3><?php _e('ShortPixel Adaptive Images conflict', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php _e('The option "Cookie Confirmation Type" is set to Opt-in in Ginger - EU Cookie Law and this conflicts with ShortPixel. Please <a href="admin.php?page=ginger-setup">set it differently</a> to let ShortPixel Adaptive Images serve the images properly optimized and scaled. <a href="https://shortpixel.helpscoutdocs.com/article/198-shortpixel-adaptive-images-vs-autoptimizes-optimize-images-option">More info.</a>', 'shortpixel-adaptive-images');
                            ?></p>
                        <?php
                        break;
                    case 'lazy': ?>
                        <h3><?php _e('ShortPixel Adaptive Images conflicts with other lazy-loading settings', 'shortpixel-adaptive-images'); ?></h3>
                        <p><?php _e('ShortPixel Adaptive Images has detected that your theme or another plugin is providing lazy-loading functionality to your website. ShortPixel Adaptive Images is also using a lazy-loading method as means to provide its service, so please deactivate the other lazy-loading setting.', 'shortpixel-adaptive-images'); ?></p>
                        <?php
                        if(isset($data['msg'])) echo '<p>' . $data['msg'] . '</p>';
                        break;
                }?>
        </div>
        <?php if(isset($data['button']['successmsg'])) { ?>
        <div class='notice notice-success' id='short-pixel-ai-success-<?php echo($type);?>' style="display:none;padding:10px;">
            <?php _e($data['button']['successmsg'],'shortpixel-adaptive-images'); ?>
        </div>
    <?php }
    }
}
