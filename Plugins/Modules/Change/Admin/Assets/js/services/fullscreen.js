(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	function FullScreenService (MainMenu, Breadcrumb, $rootScope) {

		var fullScreenMode = 'full',
		    messages = {
		        buttonTooltipFullScreenOn: "Mode plein écran actif. Cliquer pour revenir au mode normal.",
		        buttonTooltipFullScreenOff: "Basculer en mode plein écran"
		    };

		$rootScope.isFullScreen = false;

		this.toggle = function () {
			if ($rootScope.isFullScreen) {
				this.close();
				return false;
			} else {
				this.open();
				return true;
			}
		};

		this.setMode = function (mode) {
			fullScreenMode = mode;
		};

		this.close = function () {

			$rootScope.isFullScreen = false;

			/* FullScreen HTML 5 API
			if (document.cancelFullScreen) {
				document.cancelFullScreen();
				return;
			} else if (document.mozCancelFullScreen) {
				document.mozCancelFullScreen();
				return;
			} else if (document.webkitCancelFullScreen) {
				document.webkitCancelFullScreen();
				return;
			}
			*/

			$('#mainContainer').addClass('span9');
			if (fullScreenMode !== 'hide-menu') {
				MainMenu.show();
			}
			$('#leftContainer').show();
			Breadcrumb.enable();
			$('#mainNavbar').show();
			$('body>footer').show();
			$('#fullScreenBar').hide();
			$('#propertiesContainer').hide();
			var buttons = $('[data-toggle="fullscreen"]');
			buttons.removeClass('active');
			buttons.attr('title', messages.buttonTooltipFullScreenOff);
			$('body').removeClass('fullscreen');
			$('body').trigger('fullscreen', [ 'off', fullScreenMode ]);
		};

		this.open = function () {

			$rootScope.isFullScreen = true;

			/* FullScreen HTML 5 API
			if (document.documentElement.requestFullScreen) {
				document.documentElement.requestFullScreen();
				return;
			} else if (document.documentElement.mozRequestFullScreen) {
				document.documentElement.mozRequestFullScreen();
				return;
			} else if (document.documentElement.webkitRequestFullScreen) {
				document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
				return;
			}
			*/

			if (fullScreenMode === 'full') {
				$('#leftContainer').hide();
				$('#mainContainer').removeClass('span9');
			}
			MainMenu.hide();
			Breadcrumb.disable();
			$('#mainNavbar').hide();
			$('body>footer').hide();
			$('#fullScreenBar').fadeIn();
			var buttons = $('[data-toggle="fullscreen"]');
			buttons.addClass('active');
			buttons.attr('title', messages.buttonTooltipFullScreenOn);
			$('body').addClass('fullscreen');
			$('body').trigger('fullscreen', [ 'on', fullScreenMode ]);
		};

	}

	app.service('RbsChange.FullScreen', ['RbsChange.MainMenu', 'RbsChange.Breadcrumb', '$rootScope', FullScreenService]);


	/**
	 *
	 */
	app.directive('fullScreenToggle', ['RbsChange.FullScreen', '$rootScope', function (FullScreen, $rootScope) {

		return {
			restrict : 'A',

			link : function fullScreenToggleLinkFn (scope, elm) {

				if (!elm.contents().length) {
					elm.html('<i class="icon-resize-full"></i>');
				}

				$rootScope.$watch('isFullScreen', function (newValue) {
					if (newValue) {
						elm.addClass('active');
						elm.find('i[class="icon-resize-full"]').removeClass('icon-resize-full').addClass('icon-resize-small');
					} else {
						elm.removeClass('active');
						elm.find('i[class="icon-resize-small"]').removeClass('icon-resize-small').addClass('icon-resize-full');
					}
				}, true);

				elm.click(function () {
					$rootScope.$apply(function () {
						FullScreen.toggle();
					});
				});
			}
		};
	}]);

})( window.jQuery );