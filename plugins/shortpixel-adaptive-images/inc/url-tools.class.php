<?php
/**
 * User: simon
 * Date: 10.06.2019
 */

class ShortPixelUrlTools {
    const PX_ENCODED = 'R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
    const PX_SVG_ENCODED = 'PHN2ZyB2aWV3Qm94PSIwIDAgMSAxIiB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjwvc3ZnPg==';
    const PX_SVG_TPL = '<svg viewBox="0 0 %WIDTH% %HEIGHT%" width="%WIDTH%" height="%HEIGHT%" xmlns="http://www.w3.org/2000/svg"></svg>';
    public static $PROCESSABLE_EXTENSIONS = array('jpg', 'jpeg', 'gif', 'png', 'pdf');

    public static function isValid($url)
    {
        $url = trim(html_entity_decode($url), " \t\n\r\0\x0B\xC2\xA0'\"");
        if (strlen($url) == 0) return false;

        //handle URLs that contain unencoded UTF8 characters like: https://onlinefox.xyz/wp-content/uploads/2018/10/Kragerup-gods-go-high-klatrepar-åbningstider.jpg"
        $parsed = parse_url($url);
        $path = (isset($parsed['host']) ? $parsed['host'] : '') . implode('/', array_map('urlencode', explode('/', $parsed['path'])));

        if (isset($parsed['host']) && $parsed['host'] != '') {
            // URL has http/https/...
            $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : self::getCurrentScheme();
            $first  = $scheme . '://' . $parsed['host'] . $path;
            $url = $first . substr($url, strlen($first)); //make sure we keep query or hashtags
            $isValid = !(filter_var($url, FILTER_VALIDATE_URL) === false);
        } else {
            // PHP filter_var does not support relative urls, so we simulate a full URL
            $url = $path . substr($url, strlen($path)); //make sure we keep query or hashtags
            $isValid = !(filter_var('http://www.example.com/'.ltrim($url,'/'), FILTER_VALIDATE_URL) === false);
        }
        if($isValid) { //lastly check if is processable by ShortPixel
            return in_array(pathinfo($url, PATHINFO_EXTENSION), self::$PROCESSABLE_EXTENSIONS);
        }
        return false;
    }

    static function absoluteUrl($url) {
        $url = trim($url);
        $URI = parse_url($url);
        if(isset($URI['host']) && strlen($URI['host'])) {
            if(!isset($URI['scheme']) || !strlen($URI['scheme'])) {
                $url = (is_ssl() ? 'https' : 'http') . '://' . ltrim($url, '/');
            }
            return $url;
        } elseif(substr($url, 0, 1) === '/') {
            return home_url() . $url;
        } else {
            global $wp;
            return trailingslashit(home_url( $wp->request)) . $url;
        }
    }

    /**
     * remove fragment from the beginning and from the end, if found. If $frag is array, do this for each item
     * @param string $text
     * @param string|array $frag
     * @return string
     */
    public static function trimSubstring($text, $frag) {
        if(is_array($frag)) {
            foreach($frag as $f) {
                $text = self::trimSubstring($text, $f);
            }
            return $text;
        }
        $fragLen = strlen($frag);
        if(0 === strpos($text, $frag)) {
            $text = substr($text, $fragLen).'';
        }
        $textLen = strlen($text);
        if ($textLen - $fragLen === strrpos($text, $frag)) {
            $text = substr($text, 0, $textLen - $fragLen);
        }
        return $text;
    }

    public static function getCurrentScheme() {
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return 'https';
        }
        else {
            return 'http';
        }
    }

    public static function get_image_size($url) {
        preg_match("/-([0-9]+)x([0-9]+)\.[a-zA-Z0-9]+/", $url, $matches); //the filename suffix way
        if(isset($matches[1]) && isset($matches[2]) && $matches[2] < 10000) { //encountered cases when the height was set to 99999 in the name, rely on actual image sizes in that case
            //ShortPixelAILogger::instance()->log("Sizes from filename: {$matches[1]} x {$matches[2]}");
            $sizes = array($matches[1], $matches[2]);
        } elseif(!$sizes = self::url_to_path_to_sizes($url)) { //the file way
            //make sure we're not searching in the DB an external URL
            //$urlHost = parse_url($url, PHP_URL_HOST);
            //$siteHost = parse_url(site_url(), PHP_URL_HOST);
            $sizes = self::url_to_metadata_to_sizes($url);//the DB way
            ShortPixelAILogger::instance()->log("Sizes from DB: {$sizes[0]} x {$sizes[1]}");
        }
        return $sizes;
    }

    public static function url_to_path_to_sizes($image_url) {
        $updir = wp_upload_dir();
        $baseUrl = parse_url($updir['baseurl']);
        if(!isset($baseUrl['host'])) {
            $updir['baseurl'] = home_url() . $updir['baseurl'];
        }
        $baseUrlPattern = "/" . str_replace("/", "\/", preg_replace("/^http[s]{0,1}:\/\//", "^http[s]{0,1}://", $updir['baseurl'])) . "/";

        //ShortPixelAILogger::instance()->log("MATCH? $image_url PATTERN: $baseUrlPattern UPDIR: " . json_encode($updir), FILE_APPEND);

        if(preg_match($baseUrlPattern, $image_url)) {
            $path = preg_replace($baseUrlPattern, $updir['basedir'], $image_url);
        }
        else { //search in the wp-content directory too
            $baseUrlPattern = "/" . str_replace("/", "\/", preg_replace("/^http[s]{0,1}:\/\//", "^http[s]{0,1}://", dirname($updir['baseurl']))) . "/";
            if(preg_match($baseUrlPattern, $image_url)) {
                $path = preg_replace($baseUrlPattern, dirname($updir['basedir']), $image_url);
            }
            elseif($image_url[0] == '/') {
                $path = dirname(dirname($updir['basedir'])) . $image_url;
            } else {
                $path = dirname(dirname($updir['basedir'])) . '/' . $image_url;
            }
        }

        //if(strpos($image_url, 'feat1')) file_put_contents("/tmp/shortpixelai_log", "\n\nUUUUNDDD? $path", FILE_APPEND );
        //ShortPixelAILogger::instance()->log("URL TO PATH TO SIZES, checking: " . $path . ' EXISTS? ' . (file_exists($path) ? 'YEE, sizes: ' . json_encode(getimagesize($path)) : 'Nope. UPLOAD url:' . $updir['baseurl'] . ' BaseUrlPattern:' . $baseUrlPattern));

        if(file_exists($path)) {
            return getimagesize($path);
        } elseif (file_exists(urldecode($path))) {
            return getimagesize(urldecode($path));
        } else {
            //try the default location for cases like this one which had wrong baseurl so the replace above did not work: https://secure.helpscout.net/conversation/943639884/20602?folderId=1117588
            $path = trailingslashit(ABSPATH) . 'wp-content/uploads/' . wp_basename(dirname(dirname($image_url))) . '/' . wp_basename(dirname($image_url)) . '/' . wp_basename($image_url);
            if(file_exists($path)) {
                return getimagesize($path);
            } elseif (file_exists(urldecode($path))) {
                return getimagesize(urldecode($path));
            }
        }
        return false;
    }

    //TODO use get_attached_url()
    static function get_from_meta_by_guid($image_url, $fuzzy = false) {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $condition = 'guid' . ($fuzzy ? " like'%%%s'" : "='%s'");
        $sqlPosts = "SELECT id FROM {$prefix}posts WHERE ";
        $postId = $wpdb->get_var($wpdb->prepare("$sqlPosts $condition;", $image_url ));

        $meta = false;
        if(!empty($postId)) {
            $sqlMeta = "SELECT meta_value FROM {$prefix}postmeta WHERE m.meta_key = '_wp_attachment_metadata' AND post_id =";
            $meta = $wpdb->get_var($wpdb->prepare("$sqlMeta %d;", $postId ));
        }
        return $meta;
    }
    /**
     * @param $image_url
     * @return array
     */
    public static function url_to_metadata_to_sizes ( $image_url ) {
        //TODO be smart. If a certain url's domain is not found in the metadata, doesn't make sense to search for the other URLs on the same domain
        // Thx to https://github.com/kylereicks/picturefill.js.wp/blob/master/inc/class-model-picturefill-wp.php
        global $wpdb;

        $original_image_url = $image_url;
        $image_url = preg_replace('/^(.+?)(-\d+x\d+)?\.(jpg|jpeg|png|gif)((?:\?|#).+)?$/i', '$1.$3', $image_url);

        $meta = self::get_from_meta_by_guid($image_url);

        //previous joined query - slower in some cases?
        //$sql = "SELECT m.meta_value FROM {$prefix}posts p INNER JOIN {$prefix}postmeta m on p.id = m.post_id WHERE m.meta_key = '_wp_attachment_metadata' AND ";
        //$meta = $wpdb->get_var($wpdb->prepare("$sql p.guid='%s';", $image_url ));

        //try the other proto (https - http) if full urls are used
        if ( empty($meta) && strpos($image_url, 'http://') === 0 ) {
            $image_url_other_proto =  strpos($image_url, 'https') === 0 ?
                str_replace('https://', 'http://', $image_url) :
                str_replace('http://', 'https://', $image_url);
            $meta = self::get_from_meta_by_guid($image_url_other_proto);
        }

        //try using only path
        if (empty($meta) ) {
            $image_path = parse_url($image_url, PHP_URL_PATH); //some sites have different domains in posts guid (site changes, etc.)
            //keep only the last two elements of the path because some CDN's add path elements in front ( Google Cloud adds the project name, etc. )
            $image_path_elements = explode('/', $image_path);
            $image_path_elements = array_slice($image_path_elements, max(0, count($image_path_elements) - 3));
            $meta = self::get_from_meta_by_guid($image_path_elements, true);
            //$meta = $wpdb->get_var($wpdb->prepare("$sql p.guid like'%%%s';", implode('/', $image_path_elements) ));
        }

        //try using the initial URL
        if ( empty($meta) ) {
            $meta = self::get_from_meta_by_guid($original_image_url);
            //$meta = $wpdb->get_var($wpdb->prepare("$sql p.guid='%s';", $original_image_url ));
        }

        if(!empty($meta)) { //get the sizes from meta
            $meta = unserialize($meta);
            if(preg_match("/".preg_quote($meta['file'], '/') . "$/", $original_image_url)) {
                return array($meta['width'], $meta['height']);
            }
            foreach($meta['sizes'] as $size) {
                if($size['file'] == wp_basename($original_image_url)) {
                    return array($size['width'], $size['height']);
                }
            }
        }
        return array(1, 1);
    }

    public static function generate_placeholder_svg($width = false, $height = false, $url = false) {
        $ret = 'data:image/svg+xml;base64,' . self::PX_SVG_ENCODED;
        if($width && $height && ($width > 1 || $height > 1)) {
            $ret = self::_generate_placeholder_svg($width, $height, $url);
        } elseif ($url) { //external images - we don't know the width...
            $ret = 'data:image/svg+xml' . ';u=' . base64_encode(urlencode($url)) . ';base64,' . self::PX_SVG_ENCODED;
        }
        //self::log('GENERATE for ' . $url . ' : ' . $ret);
        return $ret;
    }

    protected static function _generate_placeholder_svg($width, $height, $url) {
        return 'data:image/svg+xml;u=' . base64_encode(urlencode($url)) . ";w=$width;h=$height;base64,"
            . base64_encode(str_replace('%WIDTH%', $width, str_replace('%HEIGHT%', $height, self::PX_SVG_TPL)));
    }

    public static function generate_placeholder_svg_pair($width = false, $height = false, $url = false) {
        $ret = (object)array('image' => 'data:image/svg+xml;base64,' . self::PX_SVG_ENCODED, 'meta' => false);
        if($width && $height && ($width > 1 || $height > 1)) {
            $ret = self::_generate_placeholder_svg_pair($width, $height, $url);
        } elseif ($url) { //external images - we don't know the width...
            $ret = (object)array('image' => 'data:image/svg+xml;base64,' . self::PX_SVG_ENCODED, 'meta' => 'u=' . base64_encode(urlencode($url)));
        }
        //self::log('GENERATE for ' . $url . ' : ' . $ret);
        return $ret;
    }

    protected static function _generate_placeholder_svg_pair($width, $height, $url) {
        return (object)array(
            'image' => 'data:image/svg+xml;base64,' . base64_encode(str_replace('%WIDTH%', $width, str_replace('%HEIGHT%', $height, self::PX_SVG_TPL))),
            'meta' => 'u=' . base64_encode(urlencode($url)) . ";w=$width;h=$height");
    }

    public static function generate_placeholder_gif($width = false, $height = false, $url = false) {
        if($width && $height && ($width > 1 || $height > 1)) {
            return self::_generate_placeholder_gif($width, $height, $url);
        } elseif ($url) { //external images - we don't know the width...
            return 'data:image/gif' . ';u=' . base64_encode(urlencode($url)) . ';base64,' . self::PX_ENCODED;
        }
        return 'data:image/gif;base64,' . self::PX_ENCODED;
    }

    protected static function _generate_placeholder_gif($width, $height, $data = '') {
        $pseudoData = strlen($data) ? ";u=" . base64_encode(urlencode($data)) . ";w=$width;h=$height" : '';
        $pxGif = base64_decode(self::PX_ENCODED);
        $pxGif[6] = chr(intval($width) % 256);
        $pxGif[7] = chr(intval($width) / 256);
        $pxGif[8] = chr(intval($height) % 256);
        $pxGif[9] = chr(intval($height) / 256);
        return 'data:image/gif' . $pseudoData . ';base64' . ',' . base64_encode($pxGif);
    }
}