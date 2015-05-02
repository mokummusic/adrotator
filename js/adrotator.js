jQuery(document).ready(function($) {

	var adSlots = [];

	$.each($('.madrotator'), function(i) {
		var width = $(this).width();
		var height = $(this).parent().height();

		adSlots[adSlots.length] = {width: width, height: height};

	});

	adSlots = JSON.stringify(adSlots);

	var data = {
		action: 'ads_call',
		dataType: 'json',
		mar_nonce: mAdRotator.mar_nonce,
		adslots: adSlots
    };

    $.post(mAdRotator.ajaxurl, data, function(response) {
    	var adHtml = $.parseJSON(response);

    	$.each($('.madrotator'), function(i) {

    		if (adHtml[i].indexOf('class="ad-image"') > -1) {
    			$(this).html(adHtml[i]);
    		}

    		if (adHtml[i].indexOf('class="ad-text"') > -1) {
    			var adDom = $.parseHTML(adHtml[i]);
    			var href = $(adDom).attr('href');
    			var adImage = $(adDom).find('img').attr('src');
    			var adText = $(adDom).find('.ad-text-content').text();
    			var textlines = adText.split("\n");
    	
				$(this).html('<div class="adrotator-text" id="adrotator-text-'+i+'"><div class="ad-text"><h2><a href="'+href+'">'+textlines[0].replace(/\\/g, '')+'</a></h2><p>'+textlines[1].replace(/\\/g, '')+'</p></div><a href="'+href+'"><div class="ad-textbutton">></div></a></div>');	
				resizeTextAd(this, href, adText, adImage);
    		}

    		if (adHtml[i].indexOf('class="ad-script"') > -1) {
    			var iframeHtml = ('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"><html xmlns="http://www.w3.org/1999/xhtml"><body id="adbody-'+i+'" style="margin:0;display:flex;height:auto;overflow:auto;">' + adHtml[i] + '<script> browserWindowHeight = document.body.offsetHeight; browserWindowWidth = document.body.offsetWidth; window.onload = function() { parent.adframeLoaded( '+i+' , browserWindowHeight, browserWindowWidth ); } <\/script></body></html>').replace(/"/g, "'");
    			$(this).html('<iframe style="border:0;width:100%;" name="adframe-'+i+'" scrolling="no" srcdoc="'+iframeHtml+'" id="adframe-'+i+'"></iframe>');
    		}
			

		});

    });

	function resizeTextAd(that, href, alt, src) {
		var textlines = alt.split("\n");
		if ($(that).width() >= 550) {
			if (textlines.length > 1) {
				$(that).find('.adrotator-text').html('<div class="adtop-title"><h2><a href="'+href+'">'+textlines[0].replace(/\\/g, '')+'</h2></div><div class="ad-text with-thumb"><a href="'+href+'"><img class="horzad-thumb" src="'+src+'" /></a><p>'+textlines[1].replace(/\\/g, '')+'</p></div><a href="'+href+'"><div class="ad-textbutton">></div></a>');
			} else {
				$(that).find('.adrotator-text').html('<div class="ad-text"><h2><a href="'+href+'">'+textlines[0].replace(/\\/g, '')+'</a></h2><p>'+textlines[1].replace(/\\/g, '')+'</p></div><a href="'+href+'"><div class="ad-textbutton">></div></a>');
			}
			$(that).find('.ad-text').css('float', 'left');
			$(that).find('.ad-text').width($(that).width()*0.75);
			var buttonXMargin = (($(that).width()*0.25 - $(that).find('.ad-textbutton').width())/2)-20;
			var buttonYMargin = (($(that).find('.ad-text').height() - $(that).find('.ad-textbutton').height())/2);
			$(that).find('.ad-textbutton').css('margin-left', buttonXMargin+'px');
			$(that).find('.ad-textbutton').css('margin-top', buttonYMargin+'px');
		} else {
			if (src.length > 0) {
				$(that).find('.adrotator-text').html('<div class="ad-text"><h3><a href="'+href+'">'+textlines[0].replace(/\\/g, '')+'</a></h3><p>'+textlines[1].replace(/\\/g, '')+'</p></div><a href="'+href+'"><div class="ad-textbutton ad-img-button" style="background:url('+src+');">></div></a>');
			} else {
				$(that).find('.adrotator-text').html('<div class="ad-text"><h3><a href="'+href+'">'+textlines[0].replace(/\\/g, '')+'</a></h3><p>'+textlines[1].replace(/\\/g, '')+'</p></div><a href="'+href+'"><div class="ad-textbutton ad-img-button" style="background:url('+src+');">></div></a>');
			}
			$(that).find('.ad-text').css('text-align', 'center');
			$(that).find('.ad-textbutton').width($(that).width() * 0.6 );
			$(that).find('.ad-textbutton').css('margin', '10px auto');
			$(that).find('.ad-textbutton').css('display', 'block');
		}
	}
					
});

function adframeLoaded(i, browserWindowHeight,browserWindowWidth) {
	document.getElementById('adframe-'+i).style.height = browserWindowHeight+'px';
	document.getElementById('adframe-'+i).style.width = browserWindowWidth+'px';
}