(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('dockable', ['RbsChange.Workspace', function (Workspace) {

		return {

			restrict   : 'C',
			transclude : true,

			template   :
				'<div class="panel panel-default">' +
					'<div class="panel-heading" ng-mousedown="startDrag($event)" ng-mouseup="stopDrag($event)">' +
						'<h3 class="panel-title"><button class="close" type="button" ng-click="close()">&times;</button>(= title =)</h3>' +
					'</div>' +
					'<div class="panel-body" ng-transclude>' +
					'</div>' +
				'</div>',

			scope : {
				title : '@'
			},

			link : function (scope, element, attrs) {

				var	dragOffsetX, dragOffsetY,
					$el = $(element),
					$content = element.find('.well');

				scope.rolled = false;

				scope.close = function () {
					$el.hide();
				};

				scope.startDrag = function ($event) {
					if ( ! $el.is('.pinned') ) {
						dragOffsetX = $el.offset().left - $event.pageX - $(document).scrollLeft();
						dragOffsetY = $el.offset().top  - $event.pageY - $(document).scrollTop();
						$('body').addClass('unselectable');
						$(document).on('mousemove', mousemove);
					}
				};

				scope.roll = function () {
					$content.slideToggle('fast');
					scope.rolled = ! scope.rolled;
				};

				scope.stopDrag = function () {
					if ( ! $el.is('.pinned') ) {
						$(document).off('mousemove', mousemove);
						$('body').removeClass('unselectable');
					}
				};

				function mousemove ($event) {
					$event.preventDefault();
					var	x = $event.pageX + dragOffsetX,
						y = $event.pageY + dragOffsetY;
					$el.css({
						'left': x + 'px',
						'top' : y + 'px'
					});
				}

			}

		};
	}]);

})(window.jQuery);