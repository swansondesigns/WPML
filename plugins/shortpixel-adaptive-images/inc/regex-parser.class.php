<?php
/**
 * User: simon
 * Date: 10.06.2019
 */

class ShortPixelRegexParser {
    protected $ctrl;
    private $logger;
    private $cssParser;

    private $scripts;
    private $styles;
    private $CDATAs;

    private $classFilter = false;
    private $attrFilter = false;
    private $attrValFilter = false;
    private $attrToIntegrateAndRemove = false;

    private $isEager = false;

    public function __construct(ShortPixelAI $ctrl)
    {
        $this->ctrl = $ctrl;
        $this->logger = ShortPixelAILogger::instance();
        $this->cssParser = new ShortPixelCssParser($ctrl);
    }

    public function parse($content) {
        $this->logger->log("******** REGEX PARSER *********");
        $parsed = json_decode($content);
        $isJson = !(json_last_error() === JSON_ERROR_SYNTAX);
        if ($isJson) {
            $this->logger->log("JSON CONTENT: " . $content);
            if (!isset($parsed->html)) {
                $this->logger->log("MISSING HTML");
                return $content; //not containing HTML, don't replace
            }
            $content = $parsed->html;
        }

        // EXTRACT all CDATA and inline script to be reinserted after the replaces
        // -----------------------------------------------------------------------

        $this->CDATAs = array();
        // this ungreedy regex fails with catastrophic backtracking if the CDATA is very long so will do it the manual way...
        /*$content = preg_replace_callback(
        //     this part matches the scripts of the page, we don't replace inside JS
            '/\<\!\[CDATA\[(.*)\]\]\>/sU', // U flag - UNGREEDY
            array($this, 'replace_cdatas'),
            $content
        );*/
        $content = $this->replace_cdatas($content);

        $this->scripts = array();
        $content = preg_replace_callback(
        //     this part matches the scripts of the page, we don't replace inside JS
            '/\<script(.*)\<\/script\>/sU', // U flag - UNGREEDY
            array($this, 'replace_scripts'),
            $content
        );
        $this->styles = array();
        $content = preg_replace_callback(
        //     this part matches the styles of the page, we replace inside CSS afterwards.
            '/\<style(.*)\<\/style\>/sU', // U flag - UNGREEDY
            array($this, 'replace_styles'),
            $content
        );

        // Replace different cases of image URL usages
        // -------------------------------------------

        /* $content = preg_replace_callback(
        //     this part matches URLs without quotes
            '/\<img[^\<\>]*?\ssrc\=([^\s\'"][^\s>]*)(?:.+?)\>/s',
            array( $this, 'replace_images_no_quotes' ),
            $content
        ); */

        $regexMaster = '/\<({{TAG}})(?:\s|\s[^\<\>]*?\s)({{ATTR}})\=(?:(\"|\')([^\>\'\"]+)(?:\'|\")|([^\>\'\"\s]+))(?:.*?)\>/s';
        $regexMasterSrcset = '/\<({{TAG}})(?:\s|\s[^\<\>]*?\s)({{ATTR}})\=(\"|\')([^\>\'\"]+)(?:\'|\")(?:.*?)\>/s';
        $regexItems = $this->ctrl->getTagRules();

        foreach ($regexItems as $regexItem) {
            $regex = str_replace(array('{{TAG}}', '{{ATTR}}'), array($regexItem[0], $regexItem[1]), $regexMaster);
            //$this->logger->log("REGEX: $regex");
            $this->classFilter = (isset($regexItem[2])) ? $regexItem[2] : false;
            $this->attrFilter = (isset($regexItem[3])) ? $regexItem[3] : false;
            $this->attrValFilter = (isset($regexItem[5])) ? $regexItem[5] : false;
            $this->attrToIntegrateAndRemove = (isset($regexItem[4])) ? $regexItem[4] : false;
            $this->isEager = (isset($regexItem[6])) ? $regexItem[6] : false;
            $this->extMeta = (isset($regexItem[7])) ? $regexItem[7] : false;
            $content = preg_replace_callback($regex,
                array($this, 'replace_images'),
                $content
            );
            $this->classFilter = false;
            $this->attrFilter = false;
        }

        $this->logger->log("******** REGEX PARSER replace_wc_gallery_thumbs *********");

        $content = preg_replace_callback(
            '/\<div[^\<\>]*?\sdata-thumb\=(?:\"|\')(.+?)(?:\"|\')(?:.+?)\>\<\/div\>/s',
            array($this, 'replace_wc_gallery_thumbs'),
            $content
        );

        $this->logger->log("******** REGEX PARSER replace_background_image_from_tag *********");

        $content = preg_replace_callback(
            '/\<([\w]+)(?:[^\<\>]*?)\b(background-image|background)\s*:([^;]*?[,\s]|\s*)url\((?:\'|")?([^\'"\)]*)(\'|")?\s*\).*?\>/s',
            array($this->cssParser, 'replace_background_image_from_tag'),
            $content
        );

        $this->logger->log("******** REGEX PARSER getActiveIntegrations *********");

        $integrations = $this->ctrl->getActiveIntegrations();

        $this->logger->log("******** REGEX PARSER getActiveIntegrations returns:  ", $integrations);

        if ($integrations['envira']) {
            $regex = str_replace(array('{{TAG}}', '{{ATTR}}'), array('img', 'data-envira-srcset'), $regexMasterSrcset);
            $this->logger->log("REGEX: $regex");
            $content = preg_replace_callback($regex,
                array($this, 'replace_custom_srcset'),
                $content
            );
        }

        /*if($this->integrations['modula']) {
            $regex = str_replace(array('{{TAG}}','{{ATTR}}'), array('img', 'data-envira-srcset'), $regexMasterSrcset);
            $this->logger->log("REGEX: $regex");
            $content = preg_replace_callback( $regex,
                array( $this, 'replace_custom_srcset' ),
                $content
            );
        }*/

        //NextGen uses a data-src and data-thumbnail inside the <a> tag for the image popup
        /*        $content = preg_replace_callback(
                    '/\<(?:a|div)[^\<\>]*?\sdata-src\=(\"|\'?)(.+?)(?:\"|\')(?:.+?)\>/s',
                    array( $this, 'replace_images_data_src' ),
                    $content
                );
                $content = preg_replace_callback(
                    '/\<a[^\<\>]*?\sdata-thumbnail\=(\"|\'?)(.+?)(?:\"|\')(?:.+?)\>/s',
                    array( $this, 'replace_link_data_thumbnail' ),
                    $content
                );
        */
        //TODO this is not working because NextGen is not handling the inline data: images properly
        //TODO check with them
        if ($integrations['nextgen']) {
            $content = preg_replace_callback(
                '/\<a[^\<\>]*?\shref\=(\"|\'?)(.+?)(?:\"|\')(?:.+?)\>/s',
                array($this, 'replace_link_href'),
                $content
            );
        }

        //$content = preg_replace_callback(
        //	'/\<div.+?data-src\=(?:\"|\')(.+?)(?:\"|\')(?:.+?)\>\<\/div\>/s',
        //	array( $this, 'replace_wc_gallery_thumbs' ),
        //	$content
        //);

        //put back the styles, scripts and CDATAs.
        for ($i = 0; $i < count($this->styles); $i++) {
            $style = $this->styles[$i];
            //$this->logger->log("STYLE $i: $style");
            //replace all the background-image's
            $style = $this->cssParser->replace_inline_style_backgrounds($style);
            //$this->logger->log("STYLE REPLACED: $style");

            $content = str_replace("<style>__sp_style_plAc3h0ldR_$i</style>", $style, $content);
        }
        for ($i = 0; $i < count($this->scripts); $i++) {
            $content = str_replace("<script>__sp_script_plAc3h0ldR_$i</script>", $this->scripts[$i], $content);
        }
        for ($i = 0; $i < count($this->CDATAs); $i++) {
            $content = str_replace("<![CDATA[\n__sp_cdata_plAc3h0ldR_$i\n]]>", $this->CDATAs[$i], $content);
        }

        //$content = str_replace('{{SPAI-AFFECTED-TAGS}}', implode(',', array_keys($this->ctrl->affectedTags)), $content);
        unset($this->ctrl->affectedTags['img']);
        if(strpos($content, '{{SPAI-AFFECTED-TAGS}}')) {
            $content = str_replace('{{SPAI-AFFECTED-TAGS}}', addslashes(json_encode($this->ctrl->affectedTags)), $content);
        } else {
            $content = str_replace('</body>', '<script>var spai_affectedTags = "' . addslashes(json_encode($this->ctrl->affectedTags)) . '";</script></body>', $content);
        }

        $this->logger->log("******** REGEX PARSER RETURN *********");

        if ($isJson) {
            $parsed->html = $content;
            return json_encode($parsed);
        } else return $content;
    }

    /*
    public function replace_cdatas($matches)
    {
        $index = count($this->CDATAs);
        $this->CDATAs[] = $matches[0];
        $this->logger->log("PLACEHOLDER FOR CDATA $index ");
        $this->logger->log("containing " . strlen($matches[0]) . 'chars: '
                           . strlen($matches[0]) > 100 ? substr($matches[0], 0, 45) . '..........' . substr($matches[0], -45) : $matches[0]);
        return "<![CDATA[\n__sp_cdata_plAc3h0ldR_$index\n]]>";
    }*/

    public function replace_cdatas($content)
    {
        $matches = array();
        for($idx = 0, $match = false, $len = strlen($content); $idx < $len - 2; $idx++) {
            if($match) {
                if($content[$idx] == ']' && $content[$idx + 1] == ']' && $content[$idx + 2] == '>') {
                    //end of CDATA block
                    $matches[] = (object)array('start' => $match ? $match : 0, 'end' => $idx + 2);
                    $idx += 2;
                    $match = false;
                }
            } else {
                if(substr($content, $idx, 9) == '<![CDATA[') {
                    $match = $idx;
                    $idx += 8;
                }
            }
        }
        $this->logger->log(" MATCHED CDATAS: " . json_encode($matches));
        $replacedContent = '';
        for($idx = 0; $idx < count($matches); $idx++) {
            $start = isset($matches[$idx - 1]) ? $matches[$idx - 1]->end + 1 : 0;
            $replacedContent .= substr($content, $start, $matches[$idx]->start - $start) . "<![CDATA[\n__sp_cdata_plAc3h0ldR_$idx\n]]>";
            $cdata = substr($content, $matches[$idx]->start, $matches[$idx]->end - $matches[$idx]->start + 1);
            $this->logger->log(" MATCHED AND EXTRACTED: " . $cdata);
            $this->CDATAs[] = $cdata;
        }
        $replacedContent .= substr($content, isset($matches[$idx - 1]) ? $matches[$idx - 1]->end + 1 : 0);
        return $replacedContent;
    }

    public function replace_scripts($matches)
    {
        $index = count($this->scripts);
        $this->scripts[] = $matches[0];
        return "<script>__sp_script_plAc3h0ldR_$index</script>";
    }

    public function replace_styles($matches)
    {
        //$this->logger->log("STYLE: " . $matches[0]);
        $index = count($this->styles);
        $this->styles[] = $matches[0];
        return "<style>__sp_style_plAc3h0ldR_$index</style>";
    }

    public function replace_images($matches)
    {
        if (count($matches) < 5 || strpos($matches[0], $matches[2] . '=' . $matches[3] . 'data:image/svg+xml;' . ($this->extMeta ? 'base64' : 'u='))) {
            //avoid duplicated replaces due to filters interference
            return $matches[0];
        }
        if ($this->classFilter && !preg_match('/\bclass=(\"|\').*?\b' . $this->classFilter . '\b.*?(\"|\')/s', $matches[0])) {
            return $matches[0];
        }

        if ($this->attrFilter) {
            if($this->attrValFilter) {
                if (!preg_match('/\b' . $this->attrFilter . '=((\"|\')[^\"|\']*\b|)' . preg_quote($this->attrValFilter, '/') . '/s', $matches[0])) {
                    return $matches[0];
                }
            } else {
                $stripped = preg_replace('/(\"|\').*?(\"|\')/s', ' ', $matches[0]); //keep only the attribute's names
                if (!preg_match('/\b' . $this->attrFilter . '=/s', $stripped)) {
                    return $matches[0];
                }
            }
        }
        //$matches[2] will be either " or '
        return $this->_replace_images($matches[1], $matches[2], $matches[0], isset($matches[5]) ? $matches[5] : trim($matches[4]), $matches[3]);
    }

    protected function _replace_images($tag, $attr, $text, $url, $q = '') {
        $this->logger->log("******** REPLACE IMAGE: " . $url);
        //}
        if($this->ctrl->urlIsApi($url)) return $text;
        if(!ShortPixelUrlTools::isValid($url)) return $text;
        if($this->ctrl->urlIsExcluded($url)) return $text;

        $pristineUrl = $url;
        //WP is encoding some characters, like & ( to &#038; )
        $url = html_entity_decode($url);

        if(   !$this->ctrl->lazyNoticeThrown && substr($url, 0,  10) == 'data:image'
            && (   strpos($text, 'data-lazy-src=') !== false
                || strpos($text, 'data-src=') !== false
                || (strpos($text, 'data-orig-src=') !== false && strpos($text, 'lazyload')) //found for Avada theme with Fusion Builder
            )) {
            set_transient("shortpixelai_thrown_notice", array('when' => 'lazy', 'extra' => false), 86400);
            $this->ctrl->lazyNoticeThrown = true;
        }
        if($this->ctrl->lazyNoticeThrown) {
            $this->logger->log("Lazy notice thrown");
            return $text;
        }
        //early check for the excluded selectors - only the basic cases when the selector is img.class
        if($this->ctrl->tagIs('excluded', $text)) {
            $this->logger->log("Excluding: " . $text);
            return $text;
        }
        //prevent cases when html code including data-spai attributes gets copied into new articles
        if(strpos($text, 'data-spai=') > 0) {
            if(strpos($text, 'data:image/svg+xml;' . ($this->extMeta ? 'base64' : 'u=')) > 0) {
                //for cases when the src is pseudo
                //Seems that Thrive Architect is doing something like this under the hood? (see https://secure.helpscout.net/conversation/862862953/16430/)
                return $text;
            }
            //for cases when it's normal URL, just get rid of data-spai's
            $text = preg_replace('/data-spai(-upd|)=["\'][0-9]*["\']/s', '', $text);
        }

        $noresize = $this->ctrl->tagIs('noresize', $text);

        $this->logger->log("Including: " . $url);


        //Get current image size
        $sizes = ShortPixelUrlTools::get_image_size($url);
        $qex = strlen($q) ? '' : '"';
        $qm = strlen($q) ? $q : '"';

        $spaiMeta = '';
        if($noresize || $this->isEager) {
            $inlinePlaceholder = $this->ctrl->get_api_url(false) . '/' . ShortPixelUrlTools::absoluteUrl($url);
        } else {
            if($this->extMeta){
                $data = isset($sizes[0]) ? ShortPixelUrlTools::generate_placeholder_svg_pair($sizes[0], $sizes[1], /*$this->absoluteUrl(*/$url) : ShortPixelUrlTools::generate_placeholder_svg_pair(false, false, $url);
                $inlinePlaceholder = $data->image;
                $spaiMeta = $data->meta ? ' data-spai-' . $attr . '-meta="' . $data->meta . '"' : '';
            } else {
                $inlinePlaceholder = isset($sizes[0]) ? ShortPixelUrlTools::generate_placeholder_svg($sizes[0], $sizes[1], /*$this->absoluteUrl(*/$url) : ShortPixelUrlTools::generate_placeholder_svg(false, false, $url);
            }
        }
        $pattern = '/\s' . $attr . '=' . preg_quote($q . $pristineUrl . $q, '/') . '/';
        $replacement = ' '. $attr . '=' . $qm . $inlinePlaceholder . $qm . ' data-spai="1"' . $spaiMeta;
        $str = preg_replace($pattern, $replacement, $text);
        if($this->attrToIntegrateAndRemove) {
            $str = preg_replace('/' . $this->attrToIntegrateAndRemove . '=(\"|\').*?(\"|\')/s',' ', $str);
        }
        $this->ctrl->affectedTags[$tag] = 1 | (isset($this->ctrl->affectedTags[$tag]) ? $this->ctrl->affectedTags[$tag] : 0);
        return $str;// . "<!-- original url: $url -->";
    }

    public  function replace_wc_gallery_thumbs( $matches ) {
        $url = ShortPixelUrlTools::absoluteUrl($matches[1]);
        $str = str_replace($matches[1], ShortPixelUrlTools::generate_placeholder_svg(1000, 1000, $url) , $matches[0]);
        return $str;
    }

    /**
     * for data-envira-srcset currently
     * @param $matches
     * @return null|string|string[]
     */
    public function replace_custom_srcset($matches)
    {
        $qm = strlen($matches[3]) ? $matches[3] : '"';
        $pattern = '/\s' . $matches[2] . '=' . preg_quote($matches[3] . $matches[4] . $matches[3], '/') . '/';
        $replacement = ' ' . $matches[2] . '=' . $qm . $this->replace_srcset($matches[4]) . $qm;
        $str = preg_replace($pattern, $replacement, $matches[0]);
        return $str;// . "<!-- original url: $url -->";
    }

    //NextGen specific
    //TODO make gallery specific
    public function replace_link_href($matches)
    {
        if (count($matches) < 3 || strpos($matches[0], 'href=' . $matches[1] . 'data:image/svg+xml;u=')
            || strpos($matches[0], 'ngg-fancybox') === false) { //this is to limit replacing the href to NextGen's fancybox links
            //avoid duplicated replaces due to filters interference
            return $matches[0];
        }
        //$matches[1] will be either " or '
        return $this->_replace_images('a', 'href', $matches[0], $matches[2], $matches[1]);
    }

    public static function parseInlineStyle() {

    }
}