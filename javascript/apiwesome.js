;(function($) {

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
			$('#Form_EditForm').removeClass('changed');
		});
	};

	// Trigger a confirmation message for security token regeneration.

	$(document).on('click', 'div.apiwesome.admin a.regenerate', function() {

		return confirm('This will INVALIDATE any JSON/XML feeds!');
	});

	// Trigger an interface update on input.

	$(document).on('input', 'div.apiwesome.admin input.preview.token', function() {

		enable($(this));
	});

	$(document).on('keydown', 'div.apiwesome.admin input.preview.token', function(event) {

		// Trigger nothing on pressing enter, since there are two buttons.

		if(event.keyCode === 13) {
			return false;
		}
	});

	// The preview.

	$(document).on('click', 'div.apiwesome.admin a.preview', function() {

		return !$(this).hasClass('disabled');
	});

})(jQuery);
