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
		});
	};

	// Bind the mouse events dynamically.

	$.entwine('ss', function($) {

		// Trigger a confirmation message for security token regeneration.

		$('div.apiwesome.admin a.regenerate').entwine({
			onclick: function() {

				return confirm('This will INVALIDATE any JSON/XML feeds!');
			}
		});

		// Trigger an interface update on key press.

		$('div.apiwesome.admin input.preview.token').entwine({
			onchange: function() {

				enable($(this));
			}
		});

		// Trigger an interface update and handle any preview request.

		$('div.apiwesome.admin a.preview').entwine({
			onmouseenter: function() {

				enable();
			},
			onclick: function() {

				return !$(this).hasClass('disabled');
			}
		});
	});

})(jQuery);
