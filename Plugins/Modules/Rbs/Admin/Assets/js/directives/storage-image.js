(function ($) {

	"use strict";

	var	app = angular.module('RbsChange'),

		MAX_WIDTH  = 100,
		MAX_HEIGHT = 56;

	app.constant('rbsThumbnailSizes', {
		'xs' : '57x32',
		's'  : '100x56',
		'm'  : '177x100',
		'l'  : '267x150',
		'xl' : '356x200'
	});

	/**
	 * @example: <code><img rbs-storage-image="myMedia.path" thumbnail="xs"/></code>
	 */
	app.directive('rbsStorageImage', ['RbsChange.REST', 'rbsThumbnailSizes', function (REST, sizes) {
		return {
			restrict : 'A',
			scope: {
				rbsStorageImage: "="
			},

			link : function (scope, elm, attrs) {
				var	$el = $(elm),
					maxWidth = MAX_WIDTH, maxHeight = MAX_HEIGHT,
					dim;

				// Check if the directive is on an valid tag (<img/> only).
				if (!$el.is('img')) {
					throw new Error("Directive 'rbs-storage-image' must be used on <img/> elements only.");
				}

				scope.$watch('rbsStorageImage', function (value) {
					if (value) {
						if (/^\d+$/.test(value)) {
							REST.resource(parseInt(value, 10)).then(function (image) {
								elm.attr('src', REST.storage.displayUrl(image.path));
							});
						}
						else {
							elm.attr('src', REST.storage.displayUrl(value));
						}
						elm.show();
					}
					else {
						elm.hide();
					}
				});

				if (attrs.thumbnail) {
					attrs.thumbnail = angular.lowercase(attrs.thumbnail);
					if (sizes.hasOwnProperty(attrs.thumbnail)) {
						attrs.thumbnail = sizes[attrs.thumbnail];
					}
					if (/^\d+x\d+$/.test(attrs.thumbnail)) {
						dim = attrs.thumbnail.split('x');
						maxWidth = parseInt(dim[0], 10);
						maxHeight = parseInt(dim[1], 10);
					}
					$el.css({
						'max-width'  : maxWidth+'px',
						'max-height' : maxHeight+'px'
					});
				}

			}
		};
	}]);

})(window.jQuery);