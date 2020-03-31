<?php
/**
 * User: simon
 * Date: 11.06.2019
 */

class ShortPixelDomParser {
    protected $ctrl;
    protected $lazy = true;

    private $logger;

    public function __construct(ShortPixelAI $ctrl)
    {
        $this->ctrl = $ctrl;
        $this->logger = ShortPixelAILogger::instance();
        $this->rulesMap = $ctrl->getTagRulesMap();
        //$this->lazy = true; //TODO option
    }

    public function parse($content)
    {
        $this->logger->log('******** DOM PARSER *********');
        $dom = new DOMDocument();
        $dom->loadHTML($content);
        foreach($dom->childNodes as $childNode) {
            $this->parseNode($childNode, $this->ctrl->getExceptionsMap());
        }
        return $dom->saveHTML();
    }

    private function parseNode(&$node, $excludeMatchStatus) {
        $cls = get_class($node);
        $lazy = $this->lazy;
        switch($cls) {
            case 'DOMElement':
                $attributes = ' ATTRIBUTES: ';
                $cssClasses = array();
                foreach($node->attributes as $attr) {
                    $attributes .= $attr->name . '="' . $node->getAttribute($attr->name) . '" ';
                    switch($attr->name) {
                        case 'class':
                            $cssClasses = explode(' ', $node->getAttribute($attr->name));
                            break;
                    }
                }
                $this->logger->log($node->tagName . $attributes);

                if(isset($this->rulesMap[$node->tagName])) {
                    foreach($this->rulesMap[$node->tagName] as $rule) {
                        $url = $node->getAttribute($rule->attr);
                        if($rule->extraAttr) {
                            $extraUrls = $node->getAttribute($rule->srcsetAttr);
                            //TODO aici alegem URL-ul care corespunde imaginii celei mai mari.
                        }
                        if(ShortPixelUrlTools::isValid($url)){
                            $sizes = ShortPixelUrlTools::get_image_size($url);
                            if($lazy) {
                                $inlinePlaceholder = isset($sizes[0]) ? ShortPixelUrlTools::generate_placeholder_svg($sizes[0], $sizes[1], $url) : ShortPixelUrlTools::generate_placeholder_svg(false, false, $url);
                            } else {
                                $inlinePlaceholder = $this->ctrl->get_api_url(false) . '/' . $url;
                            }
                            $node->setAttribute($rule->attr, $inlinePlaceholder);
                            $node->setAttribute('data-spai', '1');
                        }
                    }
                }

                switch($node->tagName) {
                    case 'img':
                        break;
                    case 'div':
                        break;
                    case 'a':
                        break;
                }

                foreach($node->childNodes as $childNode) {
                    $this->parseNode($childNode, $excludeMatchStatus);
                }
                break;
            case 'DOMText':
                $this->logger->log($node->wholeText);
                break;
            default:
                $this->logger->log("TODO: " . $cls);

        }
    }
}