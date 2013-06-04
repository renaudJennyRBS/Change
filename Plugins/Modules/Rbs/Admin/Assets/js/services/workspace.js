(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		leftContainer = $('#leftContainer'),
		mainContainer = $('#mainContainer');


	app.service('RbsChange.Workspace', ['$rootScope', function ($rootScope) {

		var	timers = {},
			pinnedElements = {},
			topLeft = {'top':0, 'left':0};


		this.collapseLeftSidebar = function () {
			leftContainer.hide();
			mainContainer.removeClass('span9');
			$rootScope.$broadcast('Change:Workspace:SidebarCollapsed', 'left');
		};

		this.expandLeftSidebar = function () {
			mainContainer.addClass('span9');
			leftContainer.show();
			$rootScope.$broadcast('Change:Workspace:SidebarExpanded', 'left');
		};

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



		this.pin = function (el) {
			pinnedElements[el] = {
				'parent': el.parent(),
				'index' : el.index(),
				'offset': el.offset()
			};
			this.expandLeftSidebar();
			el.addClass('pinned');
			el.css(topLeft);
			leftContainer.append(el);
			$rootScope.$broadcast('Change:Workspace:Pinned', el);
		};

		this.unpin = function (el) {
			if (el in pinnedElements) {
				var pr = pinnedElements[el].parent;
				el.insertBefore(pr.children().get(pinnedElements[el].index));
				el.removeClass('pinned');
				el.css({
					'left': pinnedElements[el].offset.left + 'px',
					'top': pinnedElements[el].offset.top + 'px'
				});
				delete pinnedElements[el];
			}
			this.collapseLeftSidebar();
			$rootScope.$broadcast('Change:Workspace:Unpinned', el);
		};



		this.hideMenus = function () {
			leftContainer.hide();
			mainContainer.removeClass('span9');
			$('#mainToolbar').hide();
			$('#mainNavbar').hide();
		};

		this.restore = function () {
			mainContainer.addClass('span9');
			leftContainer.show();
			$('#mainToolbar').show();
			$('#mainNavbar').show();
		};

	}]);


})( window.jQuery );