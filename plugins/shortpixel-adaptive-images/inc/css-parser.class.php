<?php
/**
 * User: simon
 * Date: 18.07.2019
 */

class ShortPixelCssParser {
    private $ctrl;
    private $logger;

    public function __construct($controller) {
        $this->ctrl = $controller;
        $this->logger = ShortPixelAILogger::instance();
    }


    public function replace_inline_style_backgrounds($style) {
        return preg_replace_callback(
            '/(\s|{|;)(background-image|background)\s*:([^;]*[,\s]|\s*)url\((?:\'|")?([^\'"\)]+)(\'|"|)?\s*\)/s',
            array(&$this, 'replace_background_image_from_style'),
            $style);
    }

    public function replace_in_tag_style_backgrounds($style) {
        if(strpos($style, 'background') === false) return $style;
        return preg_replace_callback(
            '/\<([\w]+)(?:[^\<\>]*?)\b(background-image|background)\s*:([^;]*?[,\s]|\s*)url\((?:\'|")?([^\'"\)]+)(\'|"|)?\s*\)/s',
            //'/(^|\s|;)(background-image|background)\s*:([^;]*[,\s]|\s*)url\((?:\'|")?([^\'"\)]*)(\'|")?\s*\)/s',
            array(&$this, 'replace_background_image_from_tag'),
            $style);
    }

    public function replace_background_image_from_tag($matches) {
        $this->logger->log("REPLACE BK RECEIVES: ", $matches);
        $ret = $this->replace_background_image($matches, $this->ctrl->settings['backgrounds_lazy']);
        $this->logger->log("REPLACE BK RETURNS: ", $ret->text);
        return $ret->text;
    }

    public function replace_background_image_from_style($matches) {
        $ret = $this->replace_background_image($matches, $this->ctrl->settings['backgrounds_lazy']);
        if($ret->replaced) {
            $this->ctrl->affectedTags['script'] = 2;
        }
        return $ret->text;
    }

    public function replace_background_image($matches, $lazy = true) {
        $text = $matches[0];
        if(!isset($matches[4])) {
            $this->logger->log("REPLACE BG - NO URL", $matches);
            return (object)array('text' => $text, 'replaced' => false);
        }
        $url = trim($matches[4]);
        $tag = trim($matches[1]);
        $type = $matches[2];
        $extra = $matches[3];
        $q = isset($matches[5]) ? $matches[5] : '';
        $pristineUrl = $url;
        //WP is encoding some characters, like & ( to &#038; )
        $url = trim(html_entity_decode($url));

        if(strpos($url, 'data:image/svg+xml;u=') !== false || strpos($url, $this->ctrl->settings['api_url']) !== false) {
            return (object)array('text' => $text, 'replaced' => false);
        }
        if( !$this->ctrl->lazyNoticeThrown && (strpos($text, 'data-bg=') !== false)) {
            set_transient("shortpixelai_thrown_notice", array('when' => 'lazy', 'extra' => false), 86400);
            $this->ctrl->lazyNoticeThrown = true;
        }
        if($this->ctrl->lazyNoticeThrown) {
            return (object)array('text' => $text, 'replaced' => false);
        }
        if($this->ctrl->tagIs('excluded', $text)) {
            return (object)array('text' => $text, 'replaced' => false);
        }

        $this->logger->log('******** REPLACE BACKGROUND IMAGE ' . ($lazy ? '' : 'FROM STYLE ') . $url);

        //some URLs in css are delimited by &quot;
        $urlUnquot = ShortPixelUrlTools::trimSubstring($url, '&quot;');
        if($urlUnquot !== $url) {
            $url = $urlUnquot;
            $pristineUrl = ShortPixelUrlTools::trimSubstring($pristineUrl, '&quot;');
            $q = '&quot;';
        }
        if(   $this->ctrl->urlIsApi($url)
           || !ShortPixelUrlTools::isValid($url)
           || $this->ctrl->urlIsExcluded($url)) {
            return (object)array('text' => $text, 'replaced' => false);
        }

        if(!$lazy || $this->ctrl->tagIs('noresize', $text)) {
            $width = $this->ctrl->settings['backgrounds_max_width'] ? $this->ctrl->settings['backgrounds_max_width'] : false;
            $inlinePlaceholder = $this->ctrl->get_api_url($width, false) . '/' . ShortPixelUrlTools::absoluteUrl($url);
            $this->logger->log("API URL: " . $inlinePlaceholder);
        } else {
            $sizes = ShortPixelUrlTools::get_image_size($url);
            $inlinePlaceholder = isset($sizes[0]) ? ShortPixelUrlTools::generate_placeholder_svg($sizes[0], $sizes[1], $url) : ShortPixelUrlTools::generate_placeholder_svg(false, false, $url);
        }

//        $this->logger->log("REPLACE REGEX: " . '/' . $type . '\s*:' . preg_quote($extra, '/') . 'url\(\s*' . preg_quote($q . $pristineUrl . $q, '/') . '/'
//              . " WITH: " . ' '. $type . ':' . $extra . 'url(' . $q . $inlinePlaceholder . $q);
        $str = preg_replace('/' . $type . '\s*:' . preg_quote($extra, '/') . 'url\(\s*' . preg_quote($q . $pristineUrl . $q, '/') . '/',
            ' '. $type . ':' . $extra . 'url(' . $q . $inlinePlaceholder . $q, $text);

        if(ctype_alnum($tag)) {
            $this->ctrl->affectedTags[$tag] = 2 | (isset($this->ctrl->affectedTags[$tag]) ? $this->ctrl->affectedTags[$tag] : 0);
        }
        return (object)array('text' => $str, 'replaced' => true);// . "<!-- original url: $url -->";
    }
}