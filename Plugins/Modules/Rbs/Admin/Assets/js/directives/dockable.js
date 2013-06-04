(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	//=========================================================================
	//
	// ACE editor widget
	//
	//=========================================================================


	app.directive('dockable', [ 'RbsChange.Workspace', function (Workspace) {

		return {

			restrict   : 'C',
			transclude : true,

			template   :
				'<header ng-mousedown="startDrag($event)" ng-mouseup="stopDrag($event)">' +
					'<div class="btn-toolbar pull-right">' +
						'<button class="btn btn-inverse btn-small" type="button" ng-class="{\'active\': pinned}" ng-click="pin()"><i class="icon-pushpin"></i></button>' +
						'<button class="btn btn-inverse btn-small" type="button" ng-click="close()"><i class="icon-remove"></i></button>' +
					'</div>' +
					'<h4 style="white-space:nowrap">{{title}}</h4>' +
					'</header>' +
				'<div class="clearfix well well-small" ng-transclude></div>',

			scope : {
				title : '@'
			},

			link : function (scope, element, attrs) {

				var dragOffsetX, dragOffsetY, $el = $(element);

				scope.pinned = $el.is('.pinned');

				scope.close = function () {
					$el.hide();
				};

				scope.pin = function () {
					if (scope.pinned) {
						Workspace.unpin($el);
					} else {
						Workspace.pin($el);
					}
					scope.pinned = ! scope.pinned;
				};

				scope.startDrag = function ($event) {
					if ( ! scope.pinned ) {
						dragOffsetX = $el.offset().left - $event.pageX - $(document).scrollLeft();
						dragOffsetY = $el.offset().top  - $event.pageY - $(document).scrollTop();
						$('body').addClass('unselectable');
						$(document).on('mousemove', mousemove);
					}
				};

				scope.stopDrag = function () {
					if ( ! scope.pinned ) {
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