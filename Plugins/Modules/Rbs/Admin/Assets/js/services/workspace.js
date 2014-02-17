(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.service('RbsChange.Workspace', [ function () {

		var	timers = {};

		this.addResizeHandler = function (uniqueId, callback) {
			$(window).resize(function () {
				var ms = 500;
				if (!uniqueId) {
					throw new Error("A 'uniqueId' is required.");
				}
				if (timers[uniqueId]) {
					clearTimeout (timers[uniqueId]);
				}
				timers[uniqueId] = setTimeout(callback, ms);

			});
		};


		this.removeResizeHandler = function (uniqueId) {
			clearTimeout(timers[uniqueId]);
		};

	}]);

})( window.jQuery );