var app = angular.module('RbsChangeApp', ['ngCookies', 'ngAnimate']);
app.config(function ($interpolateProvider) {
	$interpolateProvider.startSymbol('(=').endSymbol('=)');
});

/**
 * A directive to handle anchors that deals with <base href="..." />.
 */
app.directive('rbsAnchor', rbsAnchorDirective);
function rbsAnchorDirective () {
	return {
		restrict: 'A',
		compile: function(element, attributes) {
			var anchor = attributes['rbsAnchor'];
			if (anchor) {
				element.attr('href', window.location.pathname + window.location.search + '#' + anchor);
			}
		}
	}
}