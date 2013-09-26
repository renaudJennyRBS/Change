(function ($) {

	var app = angular.module('RbsChange');

	app.provider('RbsChange.Loading', function RbsChangeLoadingProvider() {

		this.$get = ['$rootScope', function ($rootScope) {

				var messageStack = [];

				return {

					start : function (message) {
						messageStack.push(message || "Chargement...");
						$rootScope.$broadcast('Change:LoadingStart', this.getMessage());
					},

					stop : function () {
						messageStack.pop();
						$rootScope.$broadcast('Change:LoadingStop', this.getMessage());
					},

					isLoading : function () {
						return messageStack.length > 0;
					},

					getMessage : function () {
						return (messageStack.length > 0) ? messageStack[messageStack.length-1] : null;
					}

				};

		}];

	});

/*
	app.directive('loadingIndicator', ['$rootScope', 'RbsChange.Loading', function ($rootScope, Loading) {

		return {

			restrict : 'EA',
			template : '<div class="loading-indicator-icon"></div>',
			replace : true,

			link : function (scope, elm, attrs) {

				$rootScope.$on('Change:LoadingStart', function (event, message) {
					elm.attr('title', message);
					elm.show();
				});

				$rootScope.$on('Change:LoadingStop', function (event, message) {
					if (Loading.isLoading()) {
						elm.attr('title', message);
					} else {
						elm.hide();
					}
				});
			}

		};

	}]);
*/

	app.directive('rbsLoadingIndicator', ['$rootScope', 'RbsChange.Loading', function ($rootScope, Loading)
	{
		return {

			restrict : 'A',
			template : '<i class="icon-spinner"></i>',
			replace : true,

			link : function (scope, iElement)
			{
				$rootScope.$on('Change:LoadingStart', function (event, message) {
					iElement.attr('title', message);
					iElement.addClass('icon-spin');
				});

				$rootScope.$on('Change:LoadingStop', function (event, message) {
					if (Loading.isLoading()) {
						iElement.attr('title', message);
					} else {
						iElement.removeClass('icon-spin');
					}
				});
			}

		};
	}]);


	app.directive('loadingIndicatorText', ['$rootScope', 'RbsChange.Loading', function ($rootScope, Loading) {

		return {

			restrict : 'EC',
			template : '<div class="rbsc-loading-indicator" style="display:none"></div>',
			 replace : true,

			link : function (scope, elm, attrs) {

				$rootScope.$on('Change:LoadingStart', function (event, message) {
					elm.html(message+'<br/><small>Merci de bien vouloir patienter :)');
					elm.show();
				});

				$rootScope.$on('Change:LoadingStop', function (event, message) {
					if (Loading.isLoading()) {
						elm.html(message+'<br/><small>Merci de bien vouloir patienter :)');
					} else {
						elm.hide();
					}
				});
			}

		};

	}]);


	app.directive('loadingIndicatorButton', ['$rootScope', 'RbsChange.Loading', function ($rootScope, Loading) {

		return {

			restrict : 'A',

			link : function (scope, elm, attrs) {

				elm.addClass('btn');
				elm.attr('disabled', 'disabled');

				elm.html('<i class="icon-spinner"></i>');
				var icon = elm.children().first();

				$rootScope.$on('Change:LoadingStart', function (event, message) {
					elm.removeAttr('disabled');
					elm.addClass('btn-primary');
					icon.addClass('icon-spin');
					elm.attr('title', message);
				});

				$rootScope.$on('Change:LoadingStop', function (event, message) {
					if (Loading.isLoading()) {
						elm.attr('title', message);
					} else {
						elm.removeClass('btn-primary');
						icon.removeClass('icon-spin');
						elm.attr('disabled', 'disabled');
						elm.attr('title', "Aucun chargement en cours");
					}
				});
			}

		};

	}]);


})(window.jQuery);