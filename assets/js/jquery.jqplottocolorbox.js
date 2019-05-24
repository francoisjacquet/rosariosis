//Francois: add a button to export the graph to an image and open it in a colorbox
function jqplotToColorBox(saveImgText) {
	if (screen.width < 768) {
		return false;
	}

	if ($.jqplot.use_excanvas) {
		return false;
	}

	var i = 1;
	$('div.jqplot-target').each(function() {

		var hiddenOuterDiv = $(document.createElement('div'));
		var outerDiv = $(document.createElement('div'));
		var header = $(document.createElement('div'));
		var div = $(document.createElement('div'));

		outerDiv.append(header);
		outerDiv.append(div);
		hiddenOuterDiv.append(outerDiv);

		outerDiv.addClass('jqplot-image-container');
		var outerDivId = 'jqplot-image-container' + i;
		outerDiv.attr('id', outerDivId);
		header.addClass('jqplot-image-container-header');
		div.addClass('jqplot-image-container-content');

		header.html(saveImgText);
		header.css('padding', '5px');

		$(this).after(hiddenOuterDiv);
		hiddenOuterDiv.hide();

		hiddenOuterDiv = outerDiv = header = div = null;

		if (!$.jqplot._noToImageButton) {

			var save = $(document.createElement('a'));
			save.html('<img src="assets/themes/WPadmin/btn/download.png" class="alignImg" />');
			save.attr('href', '#' + outerDivId); //colorbox link
			save.addClass('colorbox');
			var imgelem = $(this).jqplotToImageElem();
			div = $('#' + outerDivId);
			div.children('div.jqplot-image-container-content').empty();
			div.children('div.jqplot-image-container-content').append(imgelem);
			//div.show(500);
			div = null;

			$(this).after(save);
			save.after('<br />');
			save = null;
		}

		i++;
	});

	$('.colorbox').colorbox({
		inline: true
	});
}
