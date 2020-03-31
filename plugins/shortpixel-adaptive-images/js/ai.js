function SPAI() {}

SPAI.prototype = {
    /*DEBUG*/stop: 100000,
    //*DEBUG*/observedMutations: 0,
    //*DEBUG*/handledMutations: 0,
    //*DEBUG*/parsedMutations: 0,
    //*DEBUG*/modifiedMutations: 0,


    fancyboxId: "",
    fancyboxHooked: "none",
    mutationsCount: 0,
    mutationsList: {},
    timeOutHandle: false,
    mutationsLastProcessed: 0,

    mutationObserver: false,
    intersectionObserver: false,
    intersectionMargin: 500,

    supportsWebP: false,

    urlRegister: [], //keep a register with all URLs and sizes

    debugInfo: []
};


SPAI.prototype.init = function(){
    if(typeof window.IntersectionObserver !== 'function') {
        jQuery.getScript(spai_settings.plugin_url + '/js/intersection.min.js?' + spai_settings.version, ShortPixelAI.setupIntersectionObserverAndParse);
    } else {
        ShortPixelAI.setupIntersectionObserverAndParse(); //this also parses the document for the first time
    }
};

SPAI.prototype.record = function(action, type, value) {
    if(spai_settings.debug) {
        switch (action) {
            case 'count':
                if(typeof ShortPixelAI.debugInfo[type] === 'undefined') ShortPixelAI.debugInfo[type] = 0;
                ShortPixelAI.debugInfo[type] += value;
                break;
            case 'log':
            case 'logX':
                if(typeof ShortPixelAI.debugInfo[type] === 'undefined') ShortPixelAI.debugInfo[type] = '';
                ShortPixelAI.debugInfo[type] += (new Date().getTime()) + ' - ' + (action === 'log' ? value : ShortPixelAI.xpath(value)) + '\n';
        }
    }
}

SPAI.prototype.handleUpdatedImageUrls = function(theParent, hasMutationObserver, fromIntersection){
    /*DEBUG*/ShortPixelAI.record('count', 'observedMutations', 1);
    /*DEBUG*/if(ShortPixelAI.observedMutations > ShortPixelAI.stop) return;
    /*DEBUG*/ShortPixelAI.record('count', 'handledMutations', 1);
    //*DEBUG*/var parsed = 0, modified = 0, divModified = 0;
    /*
        if(theParent.is('body')) { //some of the excludes were not caught server side, catch them in browser and replace with the original URL
            for(var i = 0; i < spai_settings.excluded_selectors.length; i++) {
                var selector = spai_settings.excluded_selectors[i];
                jQuery(selector).each(function(elm){
                    var src = elm.attr('src');
                    if(typeof src !== 'undefined' &&  ShortPixelAI.containsPseudoSrc(src) >=0 ) {
                        var data = ShortPixelAI.parsePseudoSrc(elm.attr('href'));
                        elm.attr('src', data.src);
                    }
                });
            }
        }
    */
    if(theParent.is('img,amp-img')) {
        ShortPixelAI.updateImageUrl(theParent, hasMutationObserver, fromIntersection);
        return;
    }

    jQuery('img,amp-img', theParent).each(function(){
        var elm = jQuery(this);
        ShortPixelAI.updateImageUrl(elm, hasMutationObserver, fromIntersection);
    });

    var affectedTags = spai_settings.affected_tags !== '{{SPAI-AFFECTED-TAGS}}' ? JSON.parse(spai_settings.affected_tags)
        : ( typeof spai_affectedTags !== 'undefined' ? JSON.parse(spai_affectedTags) : {});
    //if(fromIntersection && (theParent.is('a') || theParent.is('div') || theParent.is('li') || theParent.is('header'))) { //will handle the div parents only if they're from intersection OR mutation
    if(fromIntersection) { //will handle the div parents only if they're from intersection OR mutation
        for(var tag in affectedTags) {
            if(theParent.is(tag)) {
                ShortPixelAI.updateDivUrl(theParent, hasMutationObserver, fromIntersection);
                break;
            }
        }
    }

    var affectedTagsList = '';
    for(var tag in affectedTags) {
        affectedTagsList += ',' + tag;
    }
    affectedTagsList = affectedTagsList.replace(/^,/, '');
    //jQuery('a,div,li,header,span,section,article', theParent).each(function(){
    jQuery(affectedTagsList, theParent).each(function(){
        //*DEBUG*/parsed = 1;
        var elm = jQuery(this);
        ShortPixelAI.updateDivUrl(elm, hasMutationObserver, fromIntersection);
    });

    //Check if integration is active and update lightbox URLs for each supported gallery
    //Envira
    ShortPixelAI.updateAHrefForIntegration('envira', theParent, 'a.envira-gallery-link');
    //Modula
    ShortPixelAI.updateAHrefForIntegration('modula', theParent, 'div.modula-gallery a[data-lightbox]');
    //Essential addons for Elementor
    ShortPixelAI.updateAHrefForIntegration('elementor-addons', theParent, 'div.eael-filter-gallery-wrapper a.eael-magnific-link');
    //Elementor
    ShortPixelAI.updateAHrefForIntegration('elementor', theParent, 'a[data-elementor-open-lightbox]');
    //Viba Portfolio
    ShortPixelAI.updateAHrefForIntegration('viba-portfolio', theParent, 'a.viba-portfolio-media-link');
    //Everest gallery - seems that it's not necessary, the url for the lightbox is parsed from the data:image on the lightbox's <img> creation
    //ShortPixelAI.updateAHrefForIntegration('everest', theParent, 'div.eg-each-item a[data-lightbox-type]');
    //WP Bakery Testimonials
    if(spai_settings.active_integrations['wp-bakery']) {
        jQuery('span.dima-testimonial-image', theParent).each(function(){
            ShortPixelAI.updateWpBakeryTestimonial(jQuery(this));
        });
    }
    //Foo gallery
    ShortPixelAI.updateAHrefForIntegration('foo', theParent, 'div.fg-item a.fg-thumb');
    //NextGen
    if(spai_settings.active_integrations.nextgen) {
        //provide the URL to the fancybox (which doesn't understand the data: inline images) before it tries to preload the image.
        jQuery('a.ngg-fancybox', theParent).each(function(){
            var elm = jQuery(this);
            if(!ShortPixelAI.isFullPseudoSrc(elm.attr('href'))) {
                return;
            }

            var data = ShortPixelAI.parsePseudoSrc(elm.attr('href'));
            elm.attr('href', ShortPixelAI.composeApiUrl(false, data.src, 'DEFER', false));
            elm.mousedown(function(){
                //this will calculate the width when the link is clicked just before fancybox uses the same algorithm to determine the width of the box and to preload the image...
                ShortPixelAI.fancyboxUpdateWidth(elm);
                return true;
            });

        });
    }

    //*DEBUG*/ShortPixelAI.parsedMutations += parsed;
    //*DEBUG*/ShortPixelAI.modifiedMutations += Math.max(modified, divModified);
};

SPAI.prototype.updateImageUrl = function(elm, hasMutationObserver, fromIntersection){
    ///*DEBUG*/parsed = 1;

    if (!ShortPixelAI.containsPseudoSrc(elm[0].outerHTML)){
        return;
    }
    if (typeof elm.attr('data-spai-upd') !== 'undefined'){
        return;
    }

    if(!fromIntersection && !ShortPixelAI.elementInViewport(elm[0], ShortPixelAI.intersectionMargin)) {
        //will handle this with the intersectionObserver
        ShortPixelAI.intersectionObserver.observe(elm[0]);
        return;
    }
    if(elm.attr('src'))

        var w = 0, h = 0;
    var isExcluded = ShortPixelAI.isExcluded(elm);
    if(!isExcluded) {
        try {
            var sizeInfo = ShortPixelAI.getSizesRecursive(elm, hasMutationObserver);
            //TODO if practice proves the need - discrete function for widths: Math.ceil( w / Math.ceil( w / 20 ) ) * Math.ceil( w / 20 )
            w = Math.ceil(sizeInfo.width);
            h = Math.ceil(sizeInfo.height);
        } catch (err) {
            if(err == 'defer' && hasMutationObserver) {
                return;
            }
        }
    }

    ShortPixelAI.record('count', 'modifiedImg', 1);
    ShortPixelAI.record('logX', 'modifiedImgURL', elm[0]);
    //*DEBUG*/modified = 1;

    //TODO future dev: clone()/replaceWith()
    //var newElm = elm.clone();
    var origData = ShortPixelAI.updateSrc(elm, 'src', w, h, (spai_settings.method == 'src' || spai_settings.method == 'both') && isExcluded != 2);
    ShortPixelAI.updateSrc(elm, 'data-src', false, false, isExcluded != 2);
    ShortPixelAI.updateSrc(elm, 'data-large_image', false, false, isExcluded != 2);
    if(spai_settings.active_integrations.envira) {
        ShortPixelAI.updateSrc(elm, 'data-envira-src', false, false, isExcluded != 2);
        ShortPixelAI.updateSrc(elm, 'data-safe-src', w, h, isExcluded != 2);
    }
    if(spai_settings.active_integrations.foo) {
        ShortPixelAI.updateSrc(elm, 'data-src-fg', w, h, isExcluded != 2);
    }
    if(spai_settings.method == 'src') {
        ShortPixelAI.removeSrcSet(elm);
    } else {
        ShortPixelAI.updateSrcSet(elm, w, origData);
    }
    elm.attr('data-spai-upd', w);
    //elm.replaceWith(newElm);

};

SPAI.prototype.updateWpBakeryTestimonial = function(elm) {
    if (1 == elm.attr('data-spai-upd')){
        return;
    }
    ShortPixelAI.updateAttr(elm, 'data-element-bg');

    var w = 0, h = 0, sizes = [];
    var isExcluded = ShortPixelAI.isExcluded(elm);
    if(!isExcluded) {
        try {
            //TODO if practice proves the need - discrete function for widths: Math.ceil( w / Math.ceil( w / 20 ) ) * Math.ceil( w / 20 )
            sizes = ShortPixelAI.getSizesRecursive(elm, hasMutationObserver);
            w = Math.ceil(sizes.width);
            h = Math.ceil(sizes.height);
        } catch (err) {
            if(err == 'defer' && hasMutationObserver) {
                return;
            }
        }
    }
    ShortPixelAI.updateInlineStyle(elm, w, h, true);
    elm.attr('data-spai-upd', 1);
};

SPAI.prototype.updateDivUrl = function(elm, hasMutationObserver, fromIntersection) {
    if (1 == elm.attr('data-spai-upd')){
        return;
    }
    if(   typeof elm.attr('src') === 'undefined' && typeof elm.attr('data-src') === 'undefined' && typeof elm.attr('data-thumb') === 'undefined'
        && !ShortPixelAI.getBackgroundPseudoImage(elm.attr('style'))) {
        return;
    }
    if(!fromIntersection && !ShortPixelAI.elementInViewport(elm[0], ShortPixelAI.intersectionMargin)) {
        //will handle this with the intersectionObserver
        ShortPixelAI.intersectionObserver.observe(elm[0]);
        return;
    }
    var w = 0, h = 0, sizes = [];
    var isExcluded = ShortPixelAI.isExcluded(elm);
    if(!isExcluded) {
        try {
            //TODO if practice proves the need - discrete function for widths: Math.ceil( w / Math.ceil( w / 20 ) ) * Math.ceil( w / 20 )
            sizes = ShortPixelAI.getSizesRecursive(elm, hasMutationObserver);
            w = Math.ceil(sizes.width);
            h = Math.ceil(sizes.height);
        } catch (err) {
            if(err == 'defer' && hasMutationObserver) {
                return;
            }
        }
    }
    ShortPixelAI.updateSrc(elm, 'src', w, h, isExcluded != 2);
    ShortPixelAI.updateSrc(elm, 'data-src', w, h, isExcluded != 2);
    //*DEBUG*/divModified =
    ShortPixelAI.updateSrc(elm, 'data-thumb', false, false, isExcluded != 2);
    //*DEBUG*/? 1 : 0;
    //ShortPixelAI.updateInlineStyle(elm, w, Math.ceil(sizes.height), true);
    ShortPixelAI.updateInlineStyle(elm, false, false, true);
    elm.attr('data-spai-upd', w);
};

SPAI.prototype.updateAHref = function(elm, hasMutationObserver, fromIntersection) {
    ShortPixelAI.updateAttr(elm, 'href');
};

SPAI.prototype.updateAttr = function(elm, attr) {
    if (1 == elm.attr('data-spai-upd')){
        return;
    }
    if( typeof elm.attr(attr) === 'undefined' ) {
        return;
    }
    var data = ShortPixelAI.updateSrc(elm, attr, window.screen.availWidth, window.screen.availHeight, ShortPixelAI.isExcluded(elm) != 2, true);
    elm.attr('data-spai-upd', data.newWidth);
};

SPAI.prototype.isExcluded = function(elm) {
    for(var i = 0; i < spai_settings.excluded_selectors.length; i++) { //.elementor-section-stretched img.size-full
        var selector = spai_settings.excluded_selectors[i];
        if(elm.is(selector)) return 2;
    }
    for(var i = 0; i < spai_settings.noresize_selectors.length; i++) { //.elementor-section-stretched img.size-full
        var selector = spai_settings.noresize_selectors[i];
        if(elm.is(selector)) return 1;
    }
    return false;
};

SPAI.prototype.updateAHrefForIntegration = function(integration, theParent, query) {
    if(spai_settings.active_integrations[integration]) {
        jQuery(query, theParent).each(function(){
            var elm = jQuery(this);
            ShortPixelAI.updateAHref(elm);
        });
    }
};

SPAI.prototype.setupDOMChangeObserver = function() {
    //setup DOM change observer
    //TODO vezi de ce nu merge la localadventurer pe versiunea polyfill MutationObserver - caruselul de sus
    //TODO adauga optiune in settings sa nu foloseasca polyfill si sa inlocuiasca din prima (pentru browserele vechi - daca polyfill-ul lui MutationObserver nu se descurca)
    //TODO disconnect my mutationObserver when I make changes. (https://stackoverflow.com/questions/44736209/mutationobserver-ignore-a-dom-action)
    ShortPixelAI.mutationObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            //*DEBUG*/ console.log("Mutation type: " + mutation.type + " target Id: " + jQuery(mutation.target).attr("id") + " target: " + jQuery(mutation.target).html());
            if(mutation.type === 'attributes' && mutation.attributeName === 'id') {
                // a hack to mitigate the fact that the jQuery .init() method is triggering DOM modifications. Happens in jQuery 1.12.4 in the Sizzle function. Comment of jQuery Devs:
                // qSA works strangely on Element-rooted queries
                // We can work around this by specifying an extra ID on the root
                // and working up from there (Thanks to Andrew Dupont for the technique)
                // IE 8 doesn't work on object elements
                return;
            }

            //new nodes added by JS
            if(mutation.addedNodes.length) {
                //*DEBUG*/ console.log(mutation.addedNodes[0]);
                for(var i = 0; i < mutation.addedNodes.length; i++) {
                    //TODO if practice proves necessary: window.requestIdleCallback()?
                    ShortPixelAI.handleUpdatedImageUrls(jQuery(mutation.addedNodes[i]), true, false);
                }
            }

            //attributes changes
            if(mutation.type == 'attributes') {
                var attrClass = mutation.target.getAttribute('class');
                attrClass = (typeof attrClass === 'undefined' || attrClass === null) ? '' : attrClass;
                if(mutation.target.nodeName === 'BODY' && ShortPixelAI.containsPseudoSrc(attrClass) > 0) {
                    //this is because the body seems to become a zombie and fires mutations at will when under Developer Console
                    return;
                }
                if(jQuery(mutation.target).attr('id') == 'fancybox-wrap' && ShortPixelAI.fancyboxId != ShortPixelAI.fancyboxHooked) {
                    //NextGen specific (which uses fancybox for displaying a gallery slideshow popup)
                    ShortPixelAI.hookIntoFancybox(mutation.target);
                } else {
                    if(ShortPixelAI.timeOutHandle) {
                        clearTimeout(ShortPixelAI.timeOutHandle);
                        if((new Date()).getTime() - ShortPixelAI.mutationsLastProcessed > 100) {
                            ShortPixelAI.processMutations();
                        }
                    }
                    else {
                        ShortPixelAI.mutationsLastProcessed = (new Date()).getTime();
                    }
                    /*DEBUG*/ShortPixelAI.observedMutations++;

                    //images having width 0 are deferred for further replacement, so keep a list of mutations and analyze them with a delay (setTimeout)
                    ShortPixelAI.mutationsList[ShortPixelAI.xpath(mutation.target)] = {target: mutation.target, time: (new Date).getTime()};
                    ShortPixelAI.timeOutHandle = setTimeout(ShortPixelAI.processMutations, 50);
                }
            }
        });
    });
    var target = document.querySelector('body');
    var config = { attributes: true, childList: true, subtree: true, characterData: true }
    ShortPixelAI.mutationObserver.observe(target, config);
};

SPAI.prototype.processMutations = function() {
    //TODO if practice proves necessary: window.requestIdleCallback()?
    var mutationsLeft = 0;
    for(var mutationTarget in ShortPixelAI.mutationsList) {
        var mutationTargetJQ = jQuery(ShortPixelAI.mutationsList[mutationTarget].target);
        if(ShortPixelAI.mutationsList[mutationTarget].time + 50 > (new Date).getTime()) {
            //mutations having less than 50ms of age, don't process them yet as they might not be ready - for example a jQuery animate.
            mutationsLeft++;
            continue;
        }
        var outerHTML = mutationTargetJQ[0].outerHTML;
        if (mutationTargetJQ.length && ShortPixelAI.containsPseudoSrc(outerHTML) > 0) { //Previously: 'src="data:image/gif;u=') > 0) {
            ShortPixelAI.handleUpdatedImageUrls(mutationTargetJQ, true, true);
            if (outerHTML.indexOf('background') > 0) {
                ShortPixelAI.updateInlineStyle(mutationTargetJQ, false, false, true);
            }
        }
        delete ShortPixelAI.mutationsList[mutationTarget];
    }
    ShortPixelAI.mutationsLastProcessed = (new Date()).getTime();
    if(mutationsLeft > 0) {
        ShortPixelAI.timeOutHandle = setTimeout(ShortPixelAI.processMutations, 50);
    }
}

SPAI.prototype.setupIntersectionObserverAndParse = function() {
    var options = {
        rootMargin: ShortPixelAI.intersectionMargin + 'px',
        threshold: 0
    };
    ShortPixelAI.intersectionObserver = new IntersectionObserver(function(entries, observer){
        entries.forEach(function(entry) {
            if(entry.isIntersecting) {
                var elm = jQuery(entry.target);
                ShortPixelAI.handleUpdatedImageUrls(elm, true, true);
                if (entry.target.outerHTML.indexOf('background') > 0) {
                    ShortPixelAI.updateInlineStyle(elm, false, false, true);
                }
                observer.unobserve(entry.target);
            }
        });
    }, options);

    //initial parse of the document
    //style blocks, wherever they might be
    jQuery('style').each(function(){
//      jQuery('style:not([data-spai-upd])').each(function(){
        var elm = jQuery(this);
        //var css = elm.html();
        var result = ShortPixelAI.replaceBackgroundPseudoSrc(elm.html());

        /*var replaced = false;
        //regexps are identical, need to duplicate them because the first will use is internal pointer to replace all
        css.replace(         /background(-image|)\s*:([^;]*[,\s]|\s*)url\(['"]?(data:image\/svg\+xml;u=[^'"\)]*?)(['"]?)\)/gm, function(item){
            var oneMatcher = /background(-image|)\s*:([^;]*[,\s]|\s*)url\(['"]?(data:image\/svg\+xml;u=[^'"\)]*?)(['"]?)\)/m;
            var match = oneMatcher.exec(item);
            var parsed = ShortPixelAI.parsePseudoSrc(match[3]);
            var newSrc = ShortPixelAI.composeApiUrl(parsed.src, parsed.origWidth);
            css = css.replace(match[3], newSrc);
            replaced = true;
        });*/
        if(result.replaced) {
            elm.html(result.text);
        }
        //elm.attr('data-spai-upd', 1);
    });

    //check the stylesheets, some optimizers (for example Swift Performance) extracts the inline CSS into .css files
    for(var styleSheet in document.styleSheets) {
        var style = document.styleSheets[styleSheet];
        try {
            for(var ruleKey in style.rules) {
                var rule = style.rules[ruleKey];
                if(typeof rule.cssText !== 'undefined' && ShortPixelAI.containsPseudoSrc(rule.cssText) > 0) {
                    var result = ShortPixelAI.replaceBackgroundPseudoSrc(rule.cssText);
                    if(result.replaced) {
                        rule.cssText = result.text;
                        document.styleSheets[styleSheet].removeRule(ruleKey);
                        document.styleSheets[styleSheet].insertRule(result.text, ruleKey);
                    }
                }
            }
        } catch (dex) {
            //sometimes it throws this exception:
            //DOMException: Failed to read the 'rules' property from 'CSSStyleSheet': Cannot access rules at CSSStyleSheet.invokeGetter
            //console.log(dex.message);
        }
    }
    //body
    ShortPixelAI.handleUpdatedImageUrls(jQuery('body'), true, false);

    //setup the mutation observer here too, because if the IntersectionObserver polyfill is needed, it should be done after that one is loaded.
    if(typeof window.MutationObserver !== 'function') {
        jQuery.getScript(spai_settings.plugin_url + '/js/MutationObserver.min.js?' + spai_settings.version, ShortPixelAI.setupDOMChangeObserver);
    } else {
        ShortPixelAI.setupDOMChangeObserver();
    }
};

SPAI.prototype.replaceBackgroundPseudoSrc = function(text){
    var replaced = false;
    //regexps are identical, need to duplicate them because the first will use is internal pointer to replace all
    text.replace(        /background(-image|)\s*:([^;]*[,\s]|\s*)url\(['"]?(data:image\/svg\+xml;u=[^'"\)]*?)(['"]?)\)/gm, function(item){
        var oneMatcher = /background(-image|)\s*:([^;]*[,\s]|\s*)url\(['"]?(data:image\/svg\+xml;u=[^'"\)]*?)(['"]?)\)/m;
        var match = oneMatcher.exec(item);
        var parsed = ShortPixelAI.parsePseudoSrc(match[3]);
        //devicePixelRatio is applied in composeApiUrl
        var screenWidth = window.screen.width;
        var setMaxWidth = spai_settings.backgrounds_max_width ? spai_settings.backgrounds_max_width : 99999;
        var newSrc = ShortPixelAI.composeApiUrl(false, parsed.src, Math.min(parsed.origWidth, screenWidth, setMaxWidth), false);
        text = text.replace(match[3], newSrc);
        replaced = true;
    });
    return {text: text, replaced: replaced};
};

//TODO sa luam de la WP Rocket versiunea mai versatila?
SPAI.prototype.elementInViewport = function(el, threshold) {
    var rect = el.getBoundingClientRect();

    return (
        rect.bottom + threshold    >= 0
        && rect.right + threshold   >= 0
        && rect.top - threshold <= (window.innerHeight || document.documentElement.clientHeight)
        && rect.left - threshold <= (window.innerWidth || document.documentElement.clientWidth)
    );
};

SPAI.prototype.hookIntoFancybox = function(theParent){
    if(ShortPixelAI.fancyboxId.length == 0 || ShortPixelAI.fancyboxHooked !== 'none') {
        return;
    }
    //console.log("HookIntoFancybox");
    var theOverlay = jQuery(theParent);
    var elm = jQuery('a#fancybox-right', theOverlay);
    elm.mousedown(function(e){
        var newId = ShortPixelAI.fancyboxChangeId(1);
        //console.log("right " + newId);
        var nextElm = jQuery('div#' + newId + " a.ngg-fancybox");
        if(nextElm.length) {
            ShortPixelAI.fancyboxUpdateWidth(nextElm);
        }
    });
    var elm = jQuery('a#fancybox-left', theOverlay);
    elm.mousedown(function(e){
        var newId = ShortPixelAI.fancyboxChangeId(-1);
        //console.log("left " + newId);
        var prevElm = jQuery('div#' + newId + " a.ngg-fancybox");
        if(prevElm.length) {
            ShortPixelAI.fancyboxUpdateWidth(prevElm);
        }
    });
    ShortPixelAI.fancyboxHooked = ShortPixelAI.fancyboxId;
};

SPAI.prototype.fancyboxChangeId = function(delta) {
    var parts = ShortPixelAI.fancyboxId.match(/(.*)([0-9]+)$/);
    return parts[1] + (parseInt(parts[2]) + delta);
};

SPAI.prototype.composeApiUrl = function(doRegister, src, w, h) {
    if(!src.match(/^http[s]{0,1}:\/\/|^\/\//)) {
        if(src.startsWith('/')) {
            src = spai_settings.site_url + src;
        } else {
            src = window.location.href + (window.location.href.endsWith('/') ? '' : '/') + src;
            if(src.indexOf('..') > 0) {
                //normalize the URL
                var l = document.createElement("a");
                l.href = src;
                src = l.protocol + "//" + l.hostname + (l.pathname.startsWith('/') ? '' : '/') + l.pathname + l.search + l.hash;

            }
        }
    }

    if(w > 1) {
        var pixelRatio = (typeof window.devicePixelRatio === 'undefined') ? 1 : window.devicePixelRatio;

        //TODO if practice proves the need - discrete function for widths: Math.ceil( w / Math.ceil( w / 20 ) ) * Math.ceil( w / 20 )

        w = Math.round(w * pixelRatio);
        h = h ? Math.round(h * pixelRatio) : undefined;
        //use a register to keep all the SRCs already resized to a specific sizes, if it's already there with a larger width, then use that width, if not add/update it.
        if(ShortPixelAI.urlRegister[src] === undefined || ShortPixelAI.urlRegister[src] < w) {
            if(doRegister) { //only the img src's are registered as the others might not get loaded...
                ShortPixelAI.urlRegister[src] = w;
            }
        } else {
            h = h ? Math.round(h * ShortPixelAI.urlRegister[src] / w) : undefined;
            w = ShortPixelAI.urlRegister[src];
        }

        var apiUrl = spai_settings.api_url.replace( "%WIDTH%", "" + w + (h ? "+h_" + h : ""));
    }
    else {
        var apiUrl = spai_settings.api_url.replace( "w_%WIDTH%" + spai_settings.sep, '' );
        var apiUrl = spai_settings.api_url.replace( "w_%WIDTH%", '' ); //maybe it's the last param, no separator...
    }

    apiUrl = apiUrl + (ShortPixelAI.supportsWebP ? spai_settings.sep + 'to_webp' : '') + '/' + src;
    //console.log(apiUrl, w);
    return apiUrl;
};

SPAI.prototype.isFullPseudoSrc = function(pseudoSrc) {
    //return pseudoSrc.indexOf('data:image/gif;u=') >= 0;
    return pseudoSrc.indexOf('data:image/svg+xml;u=') >= 0;
};

SPAI.prototype.containsPseudoSrc = function(pseudoSrc) {
    //return pseudoSrc.indexOf('data:image/gif;u=') >= 0;
    return pseudoSrc.indexOf('data:image/svg+xml;') >= 0;
};


SPAI.prototype.parsePseudoSrc = function(pseudoSrc) {
    var src = false;
    var origWidth = 0, origHeight = 0, full = false;

    if(ShortPixelAI.isFullPseudoSrc(pseudoSrc)) {
        var parts = pseudoSrc.split(',');
        if(parts.length == 2) {
            full = true;
            pseudoSrc = parts[0];
            var subparts = pseudoSrc.split(';');
            subparts.shift();
        } else {
            return false;
        }
    } else {
        var subparts = pseudoSrc.split(';');
    }
    if(subparts[0].indexOf('u=') == 0) {
        src = ShortPixelAI.urldecode(atob(subparts[0].substring(2)));
        if(src.lastIndexOf('//', 0) == 0) {
            //if the url doesn't have the protocol, use the current one
            src = window.location.protocol + src;
        }
    }
    if(subparts.length >= 2) {
        var p2 = subparts[1].split('=');
        if(p2.length == 2 && p2[0] == 'w') {
            origWidth = p2[1];
        } else {
            origWidth = 9999
        }
    }
    if(subparts.length >= 3) {
        var p2 = subparts[2].split('=');
        if(p2.length == 2 && p2[0] == 'h') {
            origHeight = p2[1];
        } else {
            origHeight = 9999
        }
    }
    return { src: src, origWidth: origWidth, origHeight: origHeight, full: full};
};


SPAI.prototype.updateSrc = function(elm, attr, w, h, isApi, maxHeight) {
    var pseudoSrc = elm.attr('data-spai-' + attr + '-meta');
    if(typeof pseudoSrc === 'undefined') {
        var pseudoSrc = elm.attr(attr);
        if(typeof pseudoSrc === 'undefined') {
            return false;
        }
    }
    var data = ShortPixelAI.parsePseudoSrc(pseudoSrc);
    var src = data ? data.src : false;
    if(!src) {
        return false;
    }
    data.crop = false;
    if(typeof maxHeight === 'undefined' || !maxHeight) {
        //make sure that if the image container has an imposed (min)height, it's taken into account
        if( h > data.origHeight * w / data.origWidth ) {
            if(spai_settings.crop === "1") {
                data.crop = true;
            } else {
                w = Math.ceil(data.origWidth * h / data.origHeight);
            }
        }
    } else {
        //make sure that if the image container has an imposed (max)height, it's taken into account
        if( h < data.origHeight * w / data.origWidth ) {
            w = Math.ceil(data.origWidth * h / data.origHeight);
        }
    }
    data.newWidth = data.origWidth > 1 ? Math.min(data.origWidth, w) : w;
    var newSrc = isApi ? ShortPixelAI.composeApiUrl(attr == 'src' && elm.is('img'), src, data.newWidth, data.crop ? h : false) : src;


    //load images and wait until they've succesfully loaded
    elm.attr(attr, newSrc);
    elm.removeAttr('data-spai-' + attr + '-meta');

    return data;
};

SPAI.prototype.updateSrcSet = function(elm, w, origData) {
    var srcSet = elm.attr('srcset');
    var sizes = elm.attr('sizes');
    var newSrcSet = '';
    var newSizes = '';
    if(srcSet) {
        var srcList = srcSet.split(", ");
        for(var i = 0; i < srcList.length; i++) {
            var item = srcList[i].trim();
            var newItem = '';
            if(ShortPixelAI.isFullPseudoSrc(item)) {
                var itemParts = item.split(/\s+/);
                if(itemParts.length >= 2) {
                    var itemData = ShortPixelAI.parsePseudoSrc(itemParts[0]);
                    newItem = ShortPixelAI.composeApiUrl(false, itemData.src, false, false) + " " + itemParts[1];
                    if(w == parseInt(itemParts[1])) {
                        origData = false; //no need to add the original as it's already in the srcset
                    }
                    else if(origData && w < parseInt(itemParts[1])) {
                        newSrcSet += ShortPixelAI.composeApiUrl(false, origData.src, w, false) + " " + w + 'w,';
                        origData = false;
                    }
                }
            }
            if(!newItem.length) {
                newItem = item;
            }
            newSrcSet += newItem + ', ';
        }
        newSrcSet = newSrcSet.replace(/,+\s+$/, '');
    }
    else if (origData && (spai_settings.method == 'srcset') && w < origData.origWidth * 0.9) {
        newSrcSet = ShortPixelAI.composeApiUrl(false, origData.src, w, false) + " " + w + 'w, ' + origData.src + " " + origData.origWidth + "w";
        newSizes = Math.ceil(100 * w / origData.origWidth) + "vw, 100vw";
    }
    if(newSrcSet.length) {
        elm.attr('srcset', newSrcSet);
    }
    if(newSizes.length) {
        elm.attr('sizes', newSizes);
    }
};

SPAI.prototype.removeSrcSet = function(elm) {
    var srcSet = elm.attr('srcset');
    if(typeof srcSet !== 'undefined' && srcSet.length) {
        elm.attr('srcset', '');
        elm.attr('sizes', '');
    }
};

SPAI.prototype.updateInlineStyle = function(elm, w, h, isApi) {
    var style = elm.attr('style');
    var pseudoSrc = ShortPixelAI.getBackgroundPseudoImage(elm.attr('style'));
    if(!pseudoSrc) return;
    var data = ShortPixelAI.parsePseudoSrc(pseudoSrc);
    var src = data ? data.src : false;
    if(!src) {
        return false;
    }
    //devicePixelRatio is applied in composeApiUrl
    var screenWidth = window.screen.width;
    var setMaxWidth = spai_settings.backgrounds_max_width ? spai_settings.backgrounds_max_width : 99999;
    var cappedWidth = Math.min(data.origWidth, screenWidth, w ? w : 99999, setMaxWidth);
    var newSrc = isApi ? ShortPixelAI.composeApiUrl(false, src, cappedWidth/* TODO this was 'w' but it's not that simple...*/) : src;

    elm.attr('style', style.replace(pseudoSrc, newSrc));

    return data;
};

SPAI.prototype.getBackgroundPseudoImage = function(style) {
    if( typeof style === 'undefined' || style.indexOf('background') < 0 ) {
        return false;
    }
    var matches = (/(background-image|background)\s*:([^;]*[,\s]|\s*)url\(['"]?([^'"\)]*?)(['"]?)\)/gm).exec(style);
    if(!matches || matches.length < 3 || matches[3].indexOf('data:image') < 0) {
        return false;
    }
    return matches[3];
};

SPAI.prototype.urldecode = function(str) {
    return decodeURIComponent((str + '').replace(/\+/g, '%20'));
};

/**
 *
 * @type {{width, padding}}
 */
SPAI.prototype.getSizesRecursive = function(elm, deferHidden) {
    if(!elm.is(':visible') && deferHidden) {
        throw 'defer';
    }
    var width = elm.css('width');
    var height = elm.css('height');
    var w = parseInt(width);
    var h = parseInt(height);
    if(width == '0px' && elm[0].nodeName !== 'A') {
        //will need to delay the URL replacement as the element will probably be rendered by JS later on...
        //but skip <a>'s because these haven't got any size
        throw 'defer';
    }
    if(width.slice(-1) == '%') {
        if(typeof elm.parent() === 'undefined') return {width: -1};
        var parentSizes = ShortPixelAI.getSizesRecursive(elm.parent(), deferHidden);
        if(parentSizes == -1) return {width: -1, padding: 0};
        w = parentSizes.width * w / 100;
        if(height.slice(-1) == '%') {
            h = parentSizes.height * h / 100;
        }
    }
    else if(w <= 1) {
        if(typeof elm.parent() === 'undefined') return {width: -1, padding: 0};
        var parentSizes = ShortPixelAI.getSizesRecursive(elm.parent(), deferHidden);
        if(parentSizes.width == -1) return {width: -1, padding: 0};
        w = parentSizes.width - parentSizes.padding - ShortPixelAI.percent2px(elm.css('margin-left'), w) - ShortPixelAI.percent2px(elm.css('margin-right'), w);
        h = parentSizes.height - parentSizes.padding_height - ShortPixelAI.percent2px(elm.css('margin-top'), h) - ShortPixelAI.percent2px(elm.css('margin-bottom'), h);
    }
    return {
        status: 'success',
        width: w,
        height: h,
        padding: ShortPixelAI.percent2px(elm.css('padding-left'), w) + ShortPixelAI.percent2px(elm.css('padding-right'), w),
        padding_height: ShortPixelAI.percent2px(elm.css('padding-top'), h) + ShortPixelAI.percent2px(elm.css('padding-bottom'), h),
    }
};

/**
 * if data is % then use the width to calculate its equivalent in px
 * @param data - the CSS string (200px, 30%)
 * @param width - the element width
 * @returns px equivalent of data
 */
SPAI.prototype.percent2px = function(data, width){
    return (data.slice(-1) == '%' ? width * parseInt(data) / 100 : parseInt(data))
};

//this is reverse engineered from jQuery.fancybox...
SPAI.prototype.fancyboxUpdateWidth = function(elm) {
    //TODO de ce se afiseaza imaginile mai mici?
    //debugger;
    var fancyParams = jQuery.extend({}, jQuery.fn.fancybox.defaults, typeof elm.data("fancybox") == "undefined" ? {} : elm.data("fancybox"));
    var viewport = [jQuery(window).width() - fancyParams.margin * 2, jQuery(window).height() - fancyParams.margin * 2, jQuery(document).scrollLeft() + fancyParams.margin, jQuery(document).scrollTop() + fancyParams.margin];
    var k = fancyParams.padding * 2;

    var maxWidth = viewport[0] - k;
    var maxHeight = viewport[1] - k;
    var aspectRatio = fancyParams.width / fancyParams.height;
    var screenRatio = maxWidth / maxHeight;

    var width = 0;
    var height = 0;
    if(aspectRatio > screenRatio) {
        width = maxWidth;
    } else {
        height = maxHeight;
        width = Math.round(maxHeight * aspectRatio);
    }

    /*		var width = fancyParams.width.toString().indexOf("%") > -1 ? parseInt(viewport[0] * parseFloat(fancyParams.width) / 100, 10)
                    : maxWidth;
        var height = fancyParams.height.toString().indexOf("%") > -1 ? parseInt(a[1] * parseFloat(fancyParams.height) / 100, 10)
            : maxHeight;

        if (fancyParams.autoScale && (width > viewport[0] || height > viewport[1])) {
            if (width > viewport[0]) {
                width = viewport[0];
            }
            if (height > viewport[1]) {
                width = parseInt((viewport[1] - k) * g + k, 10)
            }
        }
    */
    //use rounded widths, what is below 700 rounds up to multiples of 50, what is above to multiples of 100
    width = width < 700 ? Math.floor((width + 49) / 50) * 50 : Math.floor((width + 99) / 100) * 100;
    var href = elm.attr('href');
    if( href.indexOf('w_DEFER') > 0) {
        var newHref = href.replace('w_DEFER', 'w_' + width);
        //console.log('replace DEFER: ' + newHref);
        elm.attr('href', newHref);
    }
    else {
        var matches = href.match(/\/w_([0-9]+),._/g);
        if (matches !== null && matches[2] < width) {
            var newHref = href.replace(/\/w_[0-9]+,/, '/w_' + width + ',');
            //console.log('replace ' + href + ' with ' + newHref);
            elm.attr('href', newHref);
        } else {
            return;
        }
    }
    ShortPixelAI.fancyboxId = elm.parent().parent().attr('id');
};

SPAI.prototype.xpath = function(el) {
    if (typeof el == "string") return document.evaluate(el, document, null, 0, null);
    if (!el || el.nodeType != 1) return '';
    if (el.id) return "//*[@id='" + el.id + "']";
    var sames = [];
    try {
        sames = (el.parentNode === null || typeof el.parentNode.children === 'undefined' ? [] : [].filter.call(el.parentNode.children, function (x) { return x.tagName == el.tagName }))
    } catch(err) {
        //console.log(err.message);
    }
    return (el.parentNode === null ? '' : ShortPixelAI.xpath(el.parentNode) + '/') + el.tagName.toLowerCase() + (sames.length > 1 ? '['+([].indexOf.call(sames, el)+1)+']' : '')
};

SPAI.prototype.identifyImage = function() {
    document.getElementsByTagName("body")[0].style.cursor = "url('" + spai_settings.sniper + "'), auto";
}

//Polyfill for MSIE
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position){
        position = position || 0;
        return this.substr(position, searchString.length) === searchString;
    };
}

var shortPixelAIonDOMLoadedTimeout = false;
var shortPixelAIonDOMLoadedCounter = 0;
function shortPixelAIonDOMLoaded() {
    if(typeof window.ShortPixelAI !== 'undefined') return;
    if(typeof spai_settings === "undefined") {
        if(shortPixelAIonDOMLoadedCounter > 50) {
            return;
        }
        clearTimeout(shortPixelAIonDOMLoadedTimeout);
        shortPixelAIonDOMLoadedTimeout = setTimeout(shortPixelAIonDOMLoaded, shortPixelAIonDOMLoadedCounter > 20 ? 30 : 10);
        shortPixelAIonDOMLoadedCounter++;
        return;
    }

    window.ShortPixelAI = new SPAI();

    //detect if the browser supports WebP
    if (spai_settings.webp == '1' && self.createImageBitmap) {
        var hasWebP = (function() {
            var images = {
                basic: "data:image/webp;base64,UklGRjIAAABXRUJQVlA4ICYAAACyAgCdASoCAAEALmk0mk0iIiIiIgBoSygABc6zbAAA/v56QAAAAA==",
                lossless: "data:image/webp;base64,UklGRh4AAABXRUJQVlA4TBEAAAAvAQAAAAfQ//73v/+BiOh/AAA="
            };

            return function(feature) {
                var deferred = jQuery.Deferred();

                jQuery("<img>").on("load", function() {
                    if(this.width === 2 && this.height === 1) {
                        deferred.resolve();
                    } else {
                        deferred.reject();
                    }
                }).on("error", function() {
                    deferred.reject();
                }).attr("src", images[feature || "basic"]);

                return deferred.promise();
            }
        })();

        //can also call hasWebP('lossless') to check if the newer lossless WebP is supported
        hasWebP().then(
            function(){
                ShortPixelAI.supportsWebP = true;
                ShortPixelAI.init();
            },
            function(){
                ShortPixelAI.init();
            });
    } else {
        ShortPixelAI.init();
    }

    //if the sniper icon is present in the admin bar, activate it
    if(document.getElementById('shortpixel_ai_sniper') !== null) {
        document.getElementById('shortpixel_ai_sniper').setAttribute('onclick', 'ShortPixelAI.identifyImage();return false;');
    }
}

//jQuery(document).ready(function () {
if(document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", function() {
        shortPixelAIonDOMLoaded();
    });
} else {
    shortPixelAIonDOMLoaded();
}
/*
jQuery(document).ready(function(){
	//detect if the browser supports WebP
    if (self.createImageBitmap) {
        async function supportsWebp() {

            const webpData = 'data:image/webp;base64,UklGRh4AAABXRUJQVlA4TBEAAAAvAAAAAAfQ//73v/+BiOh/AAA=';
            const blob = await fetch(webpData).then(r => r.blob());
            return createImageBitmap(blob).then(() => true, () => false);
        }

        (async () => {
            if(await supportsWebp()) {
                ShortPixelAI.supportsWebP = true;
            }
            ShortPixelAI.init();
        })();
    } else {
        ShortPixelAI.init();
    }
});
*/
