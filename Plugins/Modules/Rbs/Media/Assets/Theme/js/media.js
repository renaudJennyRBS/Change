(function(jQuery) {
	"use strict";

	var app = angular.module('RbsChangeApp');

	function rbsMediaPictograms() {
		return {
			restrict: 'A',
			templateUrl: '/rbsMediaPictograms.tpl',
			scope: {
				pictograms: '=rbsMediaPictograms'
			},
			link: function(scope, elm, attrs) {
				scope.format = angular.isString(attrs['pictogramFormat']) ? attrs['pictogramFormat'] : 'pictogram';

				scope.position = {
					vertical: 'bottom',
					horizontal: 'right'
				};

				var positionParts = angular.isString(attrs['pictogramPosition']) ? attrs['pictogramPosition'].split(' ') : [];
				for (var i = 0; i < positionParts.length; i++) {
					switch (positionParts[i]) {
						case 'top':
						case 'bottom':
							scope.position.vertical = positionParts[i];
							break;

						case 'left':
						case 'center':
						case 'right':
							scope.position.horizontal = positionParts[i];
							break;
					}
				}

				scope.ngClasses = {
					main: {},
					size: {},
					maxSize: {}
				};
				scope.ngClasses.main['media-pictograms-' + scope.position.vertical] = true;
				scope.ngClasses.main['media-pictograms-' + scope.position.horizontal] = true;
				scope.ngClasses.main['media-pictograms-format-' + scope.format] = true;
				scope.ngClasses.size['image-format-' + scope.format + '-size'] = true;
				scope.ngClasses.maxSize['image-format-' + scope.format + '-max-size'] = true;
			}
		}
	}

	app.directive('rbsMediaPictograms', rbsMediaPictograms);

	function rbsMediaVisuals() {
		return {
			restrict: 'A',
			templateUrl: '/rbsMediaVisuals.tpl',
			scope: {
				visuals: '=rbsMediaVisuals',
				pictograms: '='
			},
			link: function(scope, elm, attrs) {
				scope.visualFormat = angular.isString(attrs['visualFormat']) ? attrs['visualFormat'] : 'detail';
				scope.thumbnailFormat = angular.isString(attrs['thumbnailFormat']) ? attrs['thumbnailFormat'] : 'detailThumbnail';
				scope.thumbnailPosition = angular.isString(attrs['thumbnailPosition']) ? attrs['thumbnailPosition'] : 'right';
				scope.pictogramFormat = angular.isString(attrs['pictogramFormat']) ? attrs['pictogramFormat'] : 'pictogram';
				scope.pictogramPosition = angular.isString(attrs['pictogramPosition']) ? attrs['pictogramPosition'] : null;

				scope.shownIndex = 0;
				scope.zoom = {
					enabled: attrs['enableZoom'] && attrs['enableZoom'] != 'false',
					shown: false
				};

				scope.ngClasses = {
					main: {}
				};
				if (angular.isArray(scope.visuals) && scope.visuals.length > 1) {
					scope.ngClasses.main['media-visuals-multiple'] = true;
					scope.ngClasses.main['media-visuals-multiple-' + scope.thumbnailPosition] = true;
				}
				else {
					scope.ngClasses.main['media-visuals-single'] = true;
				}
				scope.ngClasses.main['media-visuals-format-' + scope.visualFormat + '-' + scope.thumbnailFormat] = true;

				scope.showVisual = function(event, index) {
					scope.shownIndex = index;
					event.preventDefault();
				};

				scope.startZoom = function() {
					scope.zoom.shown = true;

					var linkNode = elm.find('.media-visuals-main [data-index=' + scope.shownIndex + '] a');
					var image = linkNode.find('img');

					var zoomDiv = elm.find('.media-visuals-zoom');
					var bigImage = jQuery('<img src="' + linkNode.attr('href') + '" alt="" />');
					zoomDiv.append(bigImage);

					image.mousemove(function(event) {
						var scaleX = (bigImage.width() / image.width());
						var scaleY = (bigImage.height() / image.height());
						var offset = image.offset();
						bigImage.css({
							top: Math.max(zoomDiv.height() - bigImage.height(),
								Math.min(0, zoomDiv.height() / 2 - (event.pageY - offset.top) * scaleY)),
							left: Math.max(zoomDiv.width() - bigImage.width(),
								Math.min(0, zoomDiv.width() / 2 - (event.pageX - offset.left) * scaleX))
						});
					});

					image.mouseout(function() {
						scope.zoom.shown = false;
						bigImage.remove();
						scope.$digest();

						image.unbind('mousemove');
						image.unbind('mouseout');
					});

					// Disable the link on the image.
					linkNode.click(function(event) { event.preventDefault(); });
				};
			}
		}
	}

	app.directive('rbsMediaVisuals', rbsMediaVisuals);
})(jQuery);