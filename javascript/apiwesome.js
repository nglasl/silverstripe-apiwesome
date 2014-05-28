;(function($) {
	$(function() {

		// Bind the mouse events dynamically.

		$.entwine('ss', function($) {

			// Trigger a confirmation message for security token regeneration.

			$('div.apiwesome.admin a.regenerate').entwine({
				onclick: function() {

					return confirm('This will INVALIDATE any JSON/XML feeds.');
				}
			});

			// Determine whether the preview JSON/XML button display should be disabled, based on the given security token.

			$('div.apiwesome.admin input.preview.token').entwine({
				onkeyup: function() {

					var preview = $('div.apiwesome.admin a.preview');
					($(this).val().length > 0) ? preview.removeClass('disabled') : preview.addClass('disabled');
				}
			});

			// Determine whether the preview JSON/XML button functionality should be disabled, based on the given security token.

			$('div.apiwesome.admin a.preview').entwine({
				onclick: function() {

					return !$(this).hasClass('disabled');
				}
			});
		});

	});
})(jQuery);
