(function ($) {

	"use strict";

	var app = angular.module('RbsChange'),
		$leftContainer = $('#leftContainer'),
		$mainContainer = $('#mainContainer'),
		$mainToolbar = $('#mainToolbar'),
		$dockables = $('#dockables');


	app.service('RbsChange.Workspace', ['$rootScope', 'RbsChange.MainMenu', 'RbsChange.Breadcrumb', function ($rootScope, MainMenu, Breadcrumb) {

		var	timers = {},
			pinnedElements = [],
			topLeft = {'top':0, 'left':0},
			self = this,

			// Full screen
			fullScreenMode = 'full',
			isFullScreen = false;


		//
		// Full screen feature
		//


		isFullScreen = false;

		this.toggleFullScreen = function () {
			if (isFullScreen) {
				this.closeFullScreen();
				return false;
			} else {
				this.openFullScreen();
				return true;
			}
		};

		this.setFullScreenMode = function (mode) {
			fullScreenMode = mode;
		};

		this.closeFullScreen = function () {
			$rootScope.$broadcast('Change:FullScreen:BeforeOff');

			isFullScreen = false;

			$mainContainer.addClass('span9');
			if (fullScreenMode !== 'hide-menu') {
				MainMenu.show();
			}
			$leftContainer.show();
			Breadcrumb.enable();

			$('#mainNavbar').show();
			$('body>footer').show();
			$('#fullScreenBar').hide();
			$('#propertiesContainer').hide();

			$('body').removeClass('fullscreen');
			// TODO Keep this? $('body').trigger('fullscreen', [ 'off', fullScreenMode ]);
			$rootScope.$broadcast('Change:FullScreen:Off');
		};

		this.openFullScreen = function () {
			$rootScope.$broadcast('Change:FullScreen:BeforeOn');

			isFullScreen = true;

			if (fullScreenMode === 'full') {
				$leftContainer.hide();
				$mainContainer.removeClass('span9');
			}
			MainMenu.hide();
			Breadcrumb.disable();
			$('#mainNavbar').hide();
			$('body>footer').hide();
			$('#fullScreenBar').fadeIn();

			$('body').addClass('fullscreen');
			// TODO Keep this? $('body').trigger('fullscreen', [ 'on', fullScreenMode ]);
			$rootScope.$broadcast('Change:FullScreen:On');
		};



		this.collapseLeftSidebar = function () {
			$leftContainer.hide();
			$mainContainer.removeClass('span9');
			$rootScope.$broadcast('Change:Workspace:SidebarCollapsed', 'left');
		};


		this.expandLeftSidebar = function () {
			$mainContainer.addClass('span9');
			$leftContainer.show();
			$rootScope.$broadcast('Change:Workspace:SidebarExpanded', 'left');
		};


		this.hideBreadcrumb = function () {
			$mainToolbar.hide();
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


		function findElement (el) {
			for (var i=0 ; i<pinnedElements.length ; i++) {
				if (pinnedElements[i].element === el) {
					return i;
				}
			}
			return -1;
		}


		this.pin = function (el) {
			if (findElement(el) === -1) {
				pinnedElements.push({
					'element': el,
					'parent' : el.parent(),
					'index'  : el.index(),
					'offset' : el.offset()
				});
				//this.expandLeftSidebar();
				el.addClass('pinned');
				el.css(topLeft);
				$dockables.append(el);
				$rootScope.$broadcast('Change:Workspace:Pinned', el);
			}
		};

		this.unpin = function (el) {
			var index = findElement(el);
			if (index !== -1) {
				var	pinned = pinnedElements[index],
					pr = pinned.parent;

				el.insertBefore(pr.children().get(pinned.index));
				el.removeClass('pinned');
				el.css({
					'left': (pinned.offset.left || 100) + 'px',
					'top' : (pinned.offset.top || 100) + 'px'
				});
				pinnedElements.splice(index, 1);
				$rootScope.$broadcast('Change:Workspace:Unpinned', el);
			}
			//this.collapseLeftSidebar();
		};



		this.hideMenus = function () {
			$leftContainer.hide();
			$mainContainer.removeClass('span9');
			$('#mainToolbar').hide();
			$('#mainNavbar').hide();
		};

		this.restore = function () {
			$mainContainer.addClass('span9');
			$leftContainer.show();
			$('#mainToolbar').show();
			$('#mainNavbar').show();
		};


		$rootScope.$on('Change:FullScreen:On', function (event) {
			var els = [];
			angular.forEach(pinnedElements, function (el) {
				els.push(el.element);
			});
			angular.forEach(els, function (el) {
				self.unpin(el);
			});
		});


		$rootScope.$on('Change:FullScreen:Off', function (event) {
			$('.dockable').each(function () {
				self.pin($(this));
			});
		});

	}]);


	/**
	 *
	 */
	app.directive('rbsFullscreenToggle', ['RbsChange.Workspace', '$rootScope', function (Workspace, $rootScope) {

		return {
			restrict : 'A',

			link : function rbsFullscreenToggleLink (scope, elm) {

				if (!elm.contents().length) {
					elm.html('<i class="icon-resize-full"></i>');
				}

				$rootScope.$on('Change:FullScreen:Off', function (event) {
					elm.removeClass('active');
					elm.find('i[class="icon-resize-small"]').removeClass('icon-resize-small').addClass('icon-resize-full');
				});

				$rootScope.$on('Change:FullScreen:On', function (event) {
					elm.addClass('active');
					elm.find('i[class="icon-resize-full"]').removeClass('icon-resize-full').addClass('icon-resize-small');
				});

				elm.click(function () {
					$rootScope.$apply(function () {
						Workspace.toggleFullScreen();
					});
				});
			}
		};
	}]);

})( window.jQuery );