(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('dockable', ['RbsChange.Workspace', function (Workspace) {

		return {

			restrict   : 'C',
			transclude : true,

			template   :
				'<header ng-mousedown="startDrag($event)" ng-mouseup="stopDrag($event)">' +
					'<div class="btn-toolbar pull-right">' +
						//'<button class="btn btn-inverse btn-sm" type="button" ng-click="roll()"><i ng-class="{true:\'icon-chevron-down\', false:\'icon-chevron-up\'}[rolled]"></i></button>' +
						'<button class="btn btn-inverse btn-sm" type="button" ng-click="close()"><i class="icon-remove"></i></button>' +
					'</div>' +
					'<h4 style="white-space:nowrap">{{title}}</h4>' +
					'</header>' +
				'<div class="clearfix well well-small" ng-transclude></div>',

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