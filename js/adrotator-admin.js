jQuery(document).ready(function($) {

	$( "#ad_type_select_ad_type" )
	.change(function () {
		var str = "";
		$( "#ad_type_select_ad_type option:selected" ).each(function() {
			str = $( this ).text();
		});
		if (str === 'Image') {
			$('#postimagediv').show('slow');
			$('#ad_type-ad-type_image').show('slow');
			$('#ad_type-ad-type_href').show('slow');
			$('#text-info').hide('slow');
			$('#ad_type-ad-type_text').find('h3.hndle span').text('Advert Title / Alt Attribute');
			$('#ad-text-note').text('A short desciption for the tooltip.');
			$('#ad_type_href_ok').hide();
			$('#ad_type_image_url_ok').hide();
			$('#ad-image-note').text('Enter the location of the ad image here. Alternatively, upload or choose one in the WordPress media library.');
			updateImagePreview($('#ad_type_image_url').val());
		}
		if (str === 'Text') {
			$('#ad_type-ad-type_image').show('slow');
			$('#postimagediv').hide('slow');
			$('#ad_type-ad-type_href').show('slow');
			$('#text-info').hide('slow');
			$('#ad_type_href_ok').hide();
			$('#ad_type_image_url_ok').hide();
			$('#ad_type-ad-type_text').find('h3.hndle span').text('Advert Text');
			$('#ad-text-note').text('Enter text with only ONE linebreak. 1st line is the headline, 2nd is sub-text.');
			$('#ad-image-note').text('You can add a small thumbnail image (150px max) to text ads. Paste the URL, upload or choose one in the WordPress media library.');
			updateTextPreview($('#ad_type_text').val());
		}
		if (str === 'Script/HTML') {
			$('#postimagediv').hide('slow');
			$('#ad_type-ad-type_image').hide('slow');
			$('#ad_type-ad-type_href').hide('slow');
			$('#text-info').show('slow');
			$('#ad_type-ad-type_text').find('h3.hndle span').text('Advert Code');
			$('#ad-text-note').text('Enter the advert code here. This should work with HTML, iframes, javascripts. NOT PHP!');
			updateIframePreview($('#ad_type_text').val());	
		}	
	}).change();
	
	$('#ad_type_image_url_ok').click( function(event){
		event.preventDefault();
		updateImagePreview($('#ad_type_image_url').val());
		return;
	});

	$('#ad_type_text').blur( function(){
		if ($( "#ad_type_select_ad_type option:selected" ).val() === "Script/HTML") {
			updateIframePreview($('#ad_type_text').val());
		} else if ($( "#ad_type_select_ad_type option:selected" ).val() === "Text") {
			updateTextPreview($('#ad_type_text').val());
		}
		
		return;
	});

	$('#ad_type_href').focus(function() {
		$('#ad_type_href_ok').show();
		$('#ad_type_href_ok').html('Make PrettyLink?');
		if ($('#ad_type_href').val() == '') $('#ad_type_href').val('http://').select();
	});

	$('#ad_type_image_url').focus(function() {
		$('#ad_type_image_url_ok').show();
	});

	$('#ad_img_width').bind('contentchanged', function() {
		alert('width'+$('#ad_img_width').text());
	  if ($('#ad_img_width').text() == '') $('#image-info').hide();
	});

	$("#ad_responsive").change(function() {
	    if(this.checked) {
	    	if ($('#ad_type_min_width').val() < 1) $('#ad_type_min_width').val($('#ad_img_width_input').val());
	    	if ($('#ad_type_max_width').val() < 1) $('#ad_type_max_width').val($('#ad_img_width_input').val());
	        $('#between').show();
	    } else {
	    	$('#between').hide();
	    }
	});

	var file_frame;
	$('#mca_tray_button').live('click', function( event ){

		event.preventDefault();
		if ( file_frame ) {
			file_frame.open();
			return;
		}
		file_frame = wp.media.frames.file_frame = wp.media({
			title: $( this ).data( 'uploader_title' ),
			button: {
				text: $( this ).data( 'uploader_button_text' ),
			},
			multiple: false  
		});
		file_frame.on( 'select', function() {
			attachment = file_frame.state().get('selection').first().toJSON();

			$("#ad_type_image_url").val(attachment.url);
			$("#ad-image-preview").html('<img src="'+attachment.url+'" />');
			updateImagePreview(attachment.url);

		});

		file_frame.open();

	});

	function updateImagePreview(imgSrc){
		var img = new Image();
		img.src = imgSrc;
		img.onload = function() {
			$('.ad_type_width').text(this.width);
			$('.ad_type_height').text(this.height);
			$('.ad_type_width').val(this.width);
			$('.ad_type_height').val(this.height);
			$('#ad_img_height').text(this.height);
			$('#ad_img_width').text(this.width);
			$("#ad_img_width_input").val(this.width);
			$("#ad-image-preview").html('<img src="'+img.src+'" />');
			if ($('#ad_img_width').text() !== '') $('#image-info').css('display', 'block');

		}
	}

	function updateIframePreview(code) {

		var iframeHtml = ('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"><html xmlns="http://www.w3.org/1999/xhtml"><body id="adbody-1" style="margin:-18px 0 0 0;"><div id="widthcontainer" style="display:inline-block;">' + code + '</div><script> browserWindowHeight = document.body.offsetHeight-22; browserWindowWidth = parseInt(getComputedStyle(document.getElementById("widthcontainer")).width); window.onload = function() { parent.adframeLoaded( browserWindowHeight, browserWindowWidth ); } <\/script></body></html>').replace(/"/g, "'");
		iframeHtml = '<iframe style="border:0;width:100%;" name="adframe-1" scrolling="no" srcdoc="'+iframeHtml+'" id="adframe-1"></iframe>';

		if ($('#ad_img_width').text() !== '') iframeHtml = '<div style="margin:0 auto;width:'+$('#ad_img_width').text()+'px;height:'+$('#ad_img_height').text()+'px;">'+iframeHtml+'</div>';
    	$('#ad-image-preview').html(iframeHtml);
    	if ($('#ad_img_width').text() !== '') $('#image-info').css('display', 'block');
	}

	function updateTextPreview(text) {

		var textlines = text.split("\n");
		textlines = $.grep(textlines,function(n){ return(n) });

		if (textlines.length == 0) textlines = ['Ad Title','A small advertisment description. Add the text above, split the title and description with a line break (enter).'];
		if (textlines.length == 1) textlines = [text,''];
		$('#ad-image-preview').html('<div class="adrotator-text" style="width:300px;" id="adrotator-text"><div class="ad-text"><h2><a href="'+$('#ad_type_href').val()+'">'+textlines[0].replace(/\\/g, '')+'</a></h2><p>'+textlines[1].replace(/\\/g, '')+'</p></div><a href="'+$('#ad_type_href').val()+'"><div class="ad-textbutton">></div></a></div>');	
		if ($('#ad_img_width').text() !== '') $('#image-info').css('display', 'block');
	}

	var parentHref = '';
	$('#ad_type_href_ok').click( function(event) {
		event.preventDefault();
	    hrefOked();
	    return false;
	});

	$('#ad_type_href').on("keypress", function(e) {
	  var code = e.keyCode || e.which; 
	  if (code  == 13) {     
	    e.preventDefault();
	    hrefOked();
	    return false;
	  }
	});

	$('#ad_type_image_url').on("keypress", function(e) {
	  var code = e.keyCode || e.which; 
	  if (code  == 13) {
	    e.preventDefault();
	    updateImagePreview($('#ad_type_image_url').val());
	    return false;
	  }
	});

	$('#ad_type_image_url_ok').click( function(event){
		event.preventDefault();
		updateImagePreview($('#ad_type_image_url').val());
		return;
	});


	function hrefOked() {
		var href = $('#ad_type_href').val();
		var alt = $('#ad_type_text').val();

		var data = {
			action: 'ar_href',
			href: href,
			parenthref: parentHref,
			alt: alt,
			mar_nonce: marvars.mar_nonce
		};

		$.post(ajaxurl, data, function (response) {
			
			if(response.length == 0) {
				parentHref = $('#ad_type_href').val();
				$('#ad_type_href').val(window.location.protocol+'//'+window.location.hostname+'/go/');
				var originalValue = $('#ad_type_href').val();
			    $('#ad_type_href').val('');
			    $('#ad_type_href').blur().focus().val(originalValue);
				$('#ad_type_href_ok').html('&larr; Append Unique Slug');

			} else {
				if (response.indexOf("<:ERROR:> ") > -1) {
					$('#pretty-alert').text(response.replace("<:ERROR:>", "Error!"));
					$('#pretty-alert').fadeIn().delay(3000).fadeOut(500);
				} else if (response.indexOf("<:SUCCESS:> ") > -1) {
					$('#pretty-alert').text("OK! PrettyLink Created. Don't forget to click 'update', when you have finished editing this advert.");
					$('#pretty-alert').fadeIn().delay(5000).fadeOut(500);
					setTimeout(function() {
						$('#ad_type_href_ok').hide();
					}, 1000);
			
    			} else if (response === "<:EXISTS:>") {
    				$('#pretty-alert').text("OK! (existing PrettyLink) Don't forget to click 'update', when you have finished editing this advert.");
    				$('#pretty-alert').fadeIn().delay(5000).fadeOut(500);
    				setTimeout(function() {
						$('#ad_type_href_ok').hide();
					}, 1000);
				} else {
					$('#ad_type_href').val(response);
				}
				parentHref = '';
			}
	
		});
		return false;

	};

});

function adframeLoaded(browserWindowHeight,browserWindowWidth) {
	window.onload = function() {
		document.getElementById('adframe-1').style.height = browserWindowHeight+'px';
		document.getElementById('adframe-1').style.width = browserWindowWidth+'px';
		document.getElementById('ad_img_height').innerHTML = browserWindowHeight;
		document.getElementById('ad_img_width').innerHTML = browserWindowWidth;
		document.getElementById("ad_img_width_input").value = browserWindowWidth;
	}
}
