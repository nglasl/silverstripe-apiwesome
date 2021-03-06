;(function($) {

	var page = $(document);

	// Determine whether the preview JSON/XML button display and functionality should be enabled, based on the given security token.

	function enable(input) {

		var token = input ? input.val() : $('div.apiwesome.admin input.preview.token').val();
		$('div.apiwesome.admin a.preview').each(function() {

			var preview = $(this);
			if(token.length > 0) {
				preview.attr('href', preview.data('url') + '?token=' + token);
				preview.fadeTo(250, 1, function() {

					preview.removeClass('disabled');
				});
			}
			else {
				preview.fadeTo(250, 0.4, function() {

					preview.addClass('disabled');
				});
				preview.attr('href', preview.data('url'));
			}
		});
	};

	// Trigger a confirmation message for security token regeneration.

	page.on('click', 'div.apiwesome.admin a.regenerate', function() {

		return confirm('This will INVALIDATE any JSON/XML feeds!');
	});

	// Trigger an interface update on input.

	page.on('input', 'div.apiwesome.admin input.preview.token', function() {

		enable($(this));
	});

	page.on('change', 'div.apiwesome.admin input.preview.token', function() {

		// Make sure the edit form doesn't detect changes.

		$('#Form_EditForm').removeClass('changed');
	});

	page.on('keydown', 'div.apiwesome.admin input.preview.token', function(event) {

		// Trigger nothing on pressing enter, since there are two buttons.

		if(event.keyCode === 13) {
			return false;
		}
	});

	// The preview.

	page.on('click', 'div.apiwesome.admin a.preview', function() {

		return !$(this).hasClass('disabled');
	});

})(jQuery);
