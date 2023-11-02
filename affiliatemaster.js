function SelWPImage() {
	var meta_image_frame;
	meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
            title: 'Select Image',
            button: { text:  'Select' },
            library: { type: 'image' }
    });
	meta_image_frame.on('select', function(){
		var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
		url = media_attachment.url;
		console.log(media_attachment);
		if (url) {
			var height = 800*(media_attachment.height/media_attachment.width);
			document.getElementById('imgurl').value = media_attachment.url;
			document.getElementById('previewbox').style.backgroundImage = "url('"+url+"')";
			document.getElementById('previewbox').style.height = height+'px';
			$('#imgw').val(media_attachment.width);
			$('#imgh').val(media_attachment.height);
		}
    });
	meta_image_frame.open();
}


function SelLogo() {
	var meta_image_frame;
	meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
            title: 'Select Image',
            button: { text:  'Select' },
            library: { type: 'image' }
    });
	meta_image_frame.on('select', function(){
		var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
		url = media_attachment.url;
		console.log(media_attachment);
		if (url) {
			document.getElementById('logourl').value = media_attachment.url;
		}
    });
	meta_image_frame.open();
}

function LoadImg() {
	var url = $('#imgurl').val();
	document.getElementById('previewbox').style.backgroundImage = "url('"+url+"')";	
}

function PreviewTXT() {
	var font = $('#fontfamily').val();
	var sz = $('#fontsize').val();
	var fs = $('#fontstyle').val();
	WebFont.load({ google: { families: [font] } });
	$('#txtplaced').css('font-family', font);
	$('#txtplaced').css('font-size', sz);
	$('#txtplaced').css('color', $('#txtcolor').val());
	if (fs.indexOf('b') > -1) $('#txtplaced').css('font-weight', 'bold');
	else $('#txtplaced').css('font-weight', 'normal');
	if (fs.indexOf('i') > -1) $('#txtplaced').css('font-style', 'italic');
	else $('#txtplaced').css('font-style', 'normal');
}

function ReposTXT(e) {
	var x = e.pageX - $('#previewbox').offset().left;
	var y = e.pageY - $('#previewbox').offset().top;
	$('#txtplaced').css('left', x+'px');
	$('#txtplaced').css('top', y+'px');
	$('#posl').val(x);
	$('#post').val(y);
}


function OpSel(val) {
	$('#opbox1, #opbox2, #opbox3').hide();
	if ((val==1)|| (val==2)) $('#opbox1').fadeIn();
	if ((val==3)|| (val==4)) $('#opbox2').fadeIn();
	if (val==5) $('#opbox3').fadeIn();
}

