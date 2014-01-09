(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	function rbsVerticalIfAnimation() {
		return {
			enter : function(element, done) {
				jQuery(element).css({
					overflow: 'hidden',
					height: 0
				});
				jQuery(element).animate({
					height: element.find('.vertical-if-animation-content').height()
				}, 500, function () {
					element.css('height', 'auto');
					done();
				});
			},

			leave : function(element, done) {
				jQuery(element).css({
					height: element.find('.vertical-if-animation-content').height()
				});
				jQuery(element).animate({
					overflow: 'hidden',
					height: 0
				}, 500, done);
			}
		};
	}
	app.animation('.vertical-if-animation', rbsVerticalIfAnimation);

	function rbsVerticalShowHideAnimation() {
		return {
			beforeAddClass : function(element, className, done) {
				if (className == 'ng-hide') {
					jQuery(element).animate({
						overflow: 'hidden',
						height: 0
					}, done);
				}
				else {
					done();
				}
			},

			removeClass : function(element, className, done) {
				if (className == 'ng-hide') {
					element.css({
						height: 0,
						overflow: 'hidden'
					});
					jQuery(element).animate({
						height: element.find('.vertical-show-hide-animation-content').height()
					}, function () {
						element.css('height', 'auto');
						done();
					});
				}
				else {
					done();
				}
			}
		};
	}
	app.animation('.vertical-show-hide-animation', rbsVerticalShowHideAnimation);
})();