/**
 * Created by simon on 02.07.2018.
 */

function SPAIAdmin(){};

SPAIAdmin.prototype.adjustSettingsTabsHeight = function(){
    var sectionHeight = jQuery('.wp-shortpixel-ai-options section#tab-settings table').height() + 80;
    sectionHeight = Math.max(sectionHeight, jQuery('.wp-shortpixel-ai-options section#tab-adv-settings table').height() + 80);
    sectionHeight = Math.max(sectionHeight, jQuery('section#tab-resources .area1').height() + 60);
    jQuery('#shortpixel-ai-settings-tabs').css('height', sectionHeight);
    jQuery('#shortpixel-ai-settings-tabs section').css('height', sectionHeight);
}

SPAIAdmin.prototype.switchSettingsTab = function(target){
    var section = jQuery("section#" +target);
    if(section.length > 0){
        jQuery("section").removeClass("sel-tab");
        jQuery("section#" +target).addClass("sel-tab");
    }
}

SPAIAdmin.prototype.dismissNotice = function(url, id, action) {
    jQuery("#short-pixel-ai-notice-" + id).hide();
    var data = { action  : 'shortpixel_ai_dismiss_notice',
        notice_id: id,
        call: action};
    jQuery.get(url, data, function(response) {
        data = JSON.parse(response);
        if(data["Status"] == 'success') {
            console.log("dismissed");
            jQuery("#short-pixel-ai-success-" + id).show();
        }
        else {
            jQuery("#short-pixel-ai-notice-" + id).show();
            jQuery("#short-pixel-ai-notice-" + id + " .spai-action-error").css('display', 'inline-block');
        }
    });
}

window.ShortPixelAIAdmin = new SPAIAdmin();

