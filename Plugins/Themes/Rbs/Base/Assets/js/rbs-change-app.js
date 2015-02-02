(function(jQuery) {
	"use strict";

	var app = angular.module('RbsChangeApp', ['ngCookies', 'ngAnimate', 'ui.bootstrap', 'infinite-scroll']);
	app.config(function($interpolateProvider) {
		$interpolateProvider.startSymbol('(=').endSymbol('=)');
	});

	/**
	 * A directive to handle anchors that deals with <base href="..." />.
	 */
	app.directive('rbsAnchor', function() {
		return {
			restrict: 'A',
			compile: function(element, attributes) {
				var anchor = attributes['rbsAnchor'];
				if (anchor) {
					element.attr('href', window.location.pathname + window.location.search + '#' + anchor);
					element.click(
						function() {
							jQuery('html, body').animate({ scrollTop: jQuery('#' + anchor).offset().top - 20 }, 1000);
						}
					);
				}
			}
		}
	});

	app.directive('rbsAjaxWaitingModal', ['$rootScope', 'RbsChange.AjaxAPI', function($rootScope, AjaxAPI) {
		var navigationContext = AjaxAPI.globalVar('navigationContext');
		var themeName = (angular.isObject(navigationContext) ? navigationContext.themeName : null) || 'Rbs_Base';
		return {
			restrict: 'A',
			templateUrl: 'Theme/' + themeName.split('_').join('/') + '/directives/rbs-ajax-waiting-modal.twig',
			scope: {},
			link: function(scope, element) {
				$rootScope.$on('rbsAjaxOpenWaitingModal', function(event, message) {
					if (!event.defaultPrevented) {
						scope.message = message;
						element.find('.modal').modal({ 'keyboard': false });
						event.preventDefault();
					}
				});

				$rootScope.$on('rbsAjaxCloseWaitingModal', function(event) {
					element.find('.modal').modal('hide');
				});
			}
		}
	}]);

	app.directive('rbsMaxHeight', ['RbsChange.AjaxAPI', function(AjaxAPI) {
		var navigationContext = AjaxAPI.globalVar('navigationContext');
		var themeName = (angular.isObject(navigationContext) ? navigationContext.themeName : null) || 'Rbs_Base';
		return {
			restrict: 'A',
			templateUrl: 'Theme/' + themeName.split('_').join('/') + '/directives/rbs-max-height.twig',
			transclude: true,
			scope: {},
			link: function(scope, elm, attrs) {
				scope.containerNode = elm.find('.max-height-container');
				scope.contentNode = elm.find('.max-height-content');
				scope.deployed = false;
				scope.showButtons = false;
				scope.maxHeight = parseInt(attrs['rbsMaxHeight'], 10);
				if (isNaN(scope.maxHeight) || scope.maxHeight < 0) {
					scope.maxHeight = 0;
				}

				scope.toggle = function() {
					scope.deployed = !scope.deployed;
					refreshStyles();
				};

				function refreshStyles() {
					if (!scope.showButtons || scope.deployed) {
						scope.containerNode.css({ overflow: 'visible', 'max-height': "" });
					}
					else {
						scope.containerNode.css({ overflow: 'hidden', 'max-height': scope.maxHeight + 'px' });
					}
				}

				scope.showButtonsFunction = function showButtonsFunction() {
					if (!scope.maxHeight) {
						scope.showButtons = false;
					}
					else {
						scope.showButtons = scope.contentNode.height() > scope.maxHeight + 20;
					}
					refreshStyles();
					return scope.showButtons;
				};
			}
		}
	}]);

	app.directive('rbsPagination', ['RbsChange.AjaxAPI', function (AjaxAPI) {
		var navigationContext = AjaxAPI.globalVar('navigationContext');
		var themeName = (angular.isObject(navigationContext) ? navigationContext.themeName : null) || 'Rbs_Base';
		return {
			restrict: 'A',
			templateUrl: 'Theme/' + themeName.split('_').join('/') + '/directives/rbs-pagination.twig',
			scope: {
				pagination: '=rbsPagination',
				updateOffset: '='
			},
			link: function(scope) {
				function refreshData() {
					if (angular.isObject(scope.pagination)) {
						scope.pageNumber = Math.floor(scope.pagination.offset / scope.pagination.limit) + 1;
						scope.pageCount = Math.ceil((scope.pagination.count) / scope.pagination.limit);
					}
					else {
						scope.pageNumber = 0;
						scope.pageCount = 0;
					}

					scope.pagesToShow = [];
					var start = scope.pageNumber > 3 ? scope.pageNumber - 3 : 1;
					var end = ((scope.pageCount - scope.pageNumber) > 3) ? scope.pageNumber + 3 : scope.pageCount;
					for (var i = start; i <= end; i++) {
						scope.pagesToShow.push(i);
					}
				}

				scope.$watch('pagination', function () {
					refreshData();
				}, true);

				scope.setPageNumber = function(pageNumber) {
					scope.updateOffset((pageNumber - 1) * scope.pagination.limit);
				}
			}
		}
	}]);

	app.filter('rbsDate', ['RbsChange.AjaxAPI', '$filter', function(AjaxAPI, $filter) {
		var i18n = AjaxAPI.globalVar('i18n');

		function filter(input, format) {
			if (angular.isUndefined(format)) {
				format = i18n.dateFormat;
			}
			return $filter('date')(input, format);
		}

		return filter;
	}]);

	app.filter('rbsDateTime', ['RbsChange.AjaxAPI', '$filter', function(AjaxAPI, $filter) {
		var i18n = AjaxAPI.globalVar('i18n');

		function filter(input, format) {
			if (angular.isUndefined(format)) {
				format = i18n.dateTimeFormat;
			}
			return $filter('date')(input, format);
		}

		return filter;
	}]);

	app.provider('RbsChange.AjaxAPI', function AjaxAPIProvider() {
		var apiURL = '/ajax.V1.php/', LCID = 'fr_FR',
			defaultParams = { websiteId: null, sectionId: null, pageId: null, data: {} };

		this.$get = ['$http', '$location', '$rootScope', '$window', function($http, $location, $rootScope, $window) {
			if (angular.isObject(window.__change)) {
				if (angular.isObject(window.__change.navigationContext)) {
					var data = window.__change.navigationContext;
					if (data.websiteId) {
						defaultParams.websiteId = data.websiteId;
					}
					if (data.sectionId) {
						defaultParams.sectionId = data.sectionId;
					}
					if (data.pageIdentifier) {
						var p = data.pageIdentifier.split(',');
						if (p.length == 2) {
							defaultParams.pageId = parseInt(p[0], 10);
							LCID = p[1];
						}
					}
				}
			}

			function globalVar(name, value) {
				if (angular.isObject(window.__change)) {
					if (angular.isUndefined(value)) {
						return window.__change.hasOwnProperty(name) ? window.__change[name] : value;
					}
					else {
						return window.__change[name] = value;
					}
				}
				return window.__change;
			}

			function getVersion() {
				return 'V1';
			}

			function getLCID() {
				return LCID;
			}

			function getBlockParameters(blockId) {
				if (angular.isObject(window.__change.blockParameters[blockId])) {
					return window.__change.blockParameters[blockId];
				}
				console.log('Parameters not found for block', blockId);
				return {};
			}

			function getDefaultParams() {
				return angular.copy(defaultParams);
			}

			function getHttpConfig(method, actionPath) {
				if (angular.isArray(actionPath)) {
					actionPath = actionPath.join('/');
				}
				return {
					method: 'POST', url: apiURL + LCID + '/' + actionPath,
					headers: { "X-HTTP-Method-Override": method, "Content-Type": "application/json" }
				};
			}

			function buildConfigData(data, params) {
				var configData = getDefaultParams();
				if (angular.isObject(params)) {
					angular.extend(configData, params);
				}
				if (angular.isObject(data)) {
					angular.extend(configData.data, data);
				}
				return configData;
			}

			function getData(actionPath, data, params) {
				var config = getHttpConfig('GET', actionPath);
				config.data = buildConfigData(data, params);
				return $http(config);
			}

			function postData(actionPath, data, params) {
				var config = getHttpConfig('POST', actionPath);
				config.data = buildConfigData(data, params);
				return $http(config);
			}

			function putData(actionPath, data, params) {
				var config = getHttpConfig('PUT', actionPath);
				config.data = buildConfigData(data, params);
				return $http(config);
			}

			function deleteData(actionPath, data, params) {
				var config = getHttpConfig('DELETE', actionPath);
				config.data = buildConfigData(data, params);
				return $http(config);
			}

			function openWaitingModal(message) {
				$rootScope.$emit('rbsAjaxOpenWaitingModal', message);
			}

			function closeWaitingModal() {
				$rootScope.$emit('rbsAjaxCloseWaitingModal');
			}

			// Public API
			return {
				getVersion: getVersion,
				getLCID: getLCID,
				globalVar: globalVar,
				getBlockParameters: getBlockParameters,
				getDefaultParams: getDefaultParams,
				getData: getData,
				postData: postData,
				putData: putData,
				deleteData: deleteData,
				openWaitingModal: openWaitingModal,
				closeWaitingModal: closeWaitingModal
			};
		}];
	});

	/**
	 * This service is used to manage a modal stack where:
	 *  - opening a new modal hides the others
	 *  - closing any modal in the stack closes all other ones
	 * This is useful for cascading modals, where the child extends the scope of the parent.
	 */
	app.service('RbsChange.ModalStack', ['$modal', function ($modal) {
		var me = this;
		var className = 'modal-hidden-stack';
		var opened = [];

		this.open = function (options) {
			this.hideStack();

			if (options.windowClass) {
				options.windowClass += ' ' + className;
			}
			else {
				options.windowClass = className;
			}

			var modal = $modal.open(options);
			if (modal) {
				var closeAllFunction = function () {
					me.closeAll();
				};
				modal.result.then(closeAllFunction, closeAllFunction);
				opened.push(modal);
			}
		};

		this.closeAll = function() {
			for (var i = 0; i < opened.length; i++) {
				opened[i].dismiss();
			}
			opened = [];
		};

		this.hideStack = function() {
			jQuery('.' + className).hide();
		};
	}]);
})(window.jQuery);