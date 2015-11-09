(function($){
jQuery.fn.gwfwFontPreview = (function(options) {
	var settings = jQuery.extend({
		clear: false,
		form_id: 'gwfc_settings_form',
		font_selector_class: 'font-selector',
		preview_text: {
			// See http://stackoverflow.com/questions/18931489/google-webfont-subset-sample-strings
			// This list will be extended as Google releases more subsets for preview.
			'latin': 'Grumpy wizards make toxic brew for the evil Queen and Jack',
			'latin-ext': 'ĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘ...',
			'greek': 'Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός',
			'greek-ext': 'ἀἁἂἃἄἅἆἇἈἉἊἋἌἍἎἏἑἒἓἔἕἘἙἚἛἜἝἠἡἢἣἤἥἦἧ',
			'cyrillic': 'В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!',
			'cyrillic-ext': 'ꙢꙣꙤꙥꙦꙧꙨꙩꙪꙫꙬꙭꙮ',

			'arabic': 'نص حكيم له سر قاطع وذو شأن عظيم مكتوب على ثوب أخضر ومغلف بجلد أزرق',
			'bengali': 'பழுவேட்டரையரையும், சம்புவரையரையும் தவிர',
			'devanagari': 'एक पल का क्रोध आपका भविष्य बिगाड सकता है',
			'devanagari': 'एक पल का क्रोध आपका भविष्य बिगाड सकता है',
			'gujarati': 'பழுவேட்டரையரையும், சம்புவரையரையும் தவிர',
			'hebrew': 'דג סקרן שט בים מאוכזב ולפתע מצא חברה',
			'khmer': 'ខ្ញុំអាចញ៉ាំកញ្ចក់បាន ដោយគ្មានបញ្ហា',
			'tamil': 'பழுவேட்டரையரையும், சம்புவரையரையும் தவிர',
			'thai': 'เป็นมนุษย์สุดประเสริฐเลิศคุณค่า กว่าบรรดาฝูงสัตว์เดรัจฉาน',
			'telugu': 'దేశ భాషలందు తెలుగు లెస్స',
			'vietnamese': 'Tôi có thể ăn thủy tinh mà không hại gì.'
		},
		google_base_url: 'http://fonts.googleapis.com/css?family=',
		subset_field_class: 'gwfc_google_webfont_subset_select'
	}, options);

	// If 'clear' is set, then remove any fonts previewed so far.
	if (settings.clear) {
		$('#gwfw-font-previews').html('');
	}

	// Get a list of fonts that are selected.
	// TODO: pass the form ID in as a parameter.
	var font_list = [];

	// For each select element that contains a list of fonts (identified by the form id
	// and the class name we have given them), pull out
	// all the selected fonts so we have a full list.
	$('#' + settings.form_id + ' select.' + settings.font_selector_class + '.new-fonts :selected')
		.each(function(i){
			font_list.push(this);
		});
	;

	// Collect the list of subsets (international glyphs) from the select boxes.
	var checked_subsets = $('input.' + settings.subset_field_class + ':checked');
	var subset_list = [];
	if (checked_subsets.length == 0) {
		// No subsets selected.
		subset_list[0] = 'latin';
	} else {
		// Collect the subsets.
		// subset_list.join(',') gives us the csv string that Google needs.
		$(checked_subsets).each(function(i){
			subset_list[i] = this.value;
		});
	}

	// If we have any selected fonts, then loop through them to create the previews.
	if (font_list.length > 0) {
		var font_style = ($('input#preview-italic:checked').length ? 'italic' : 'normal');
		var font_weight = $('select#preview-weight').val();

		// Create the preview text, and this will depend on the subsets selected.
		var preview_text = '';
		for(var j = 0; j < subset_list.length; j++) {
			if (typeof settings.preview_text[subset_list[j]] != "undefined") {
				preview_text = preview_text
					+ '<span title="Subset '
					+ subset_list[j] + '">'
					+ settings.preview_text[subset_list[j]]
					+ '</span><br />';
			}
		}

		for(var i = 0; i < font_list.length; i++) {
			// Display the preview text.
			$('#gwfw-font-previews').prepend(
				'<p style="font-weight: bold">'
				+ $(font_list[i]).val()
				+ '</p>'
				+ '<p style="font-family:' + $(font_list[i]).val() + '; font-size: 36pt; line-height: 36pt; font-weight: ' + font_weight + '; font-style: ' + font_style + ';">'
				+ preview_text
				+ '</p>'
			);

			// Since the Woo framework does not load the fonts in the admin section until needed, 
			// we will load them through AJAX.
			$('head').append(
				'<link href="'
				+ settings.google_base_url
				+ escape($(font_list[i]).val())
				+ $(font_list[i]).attr('variants')
				+ '&amp;subset=' + subset_list.join(',')
				+ '" rel="stylesheet" type="text/css">'
			);
		}
	}

	return this;
});
}) (jQuery);