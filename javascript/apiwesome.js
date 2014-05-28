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
		});

	});
})(jQuery);
