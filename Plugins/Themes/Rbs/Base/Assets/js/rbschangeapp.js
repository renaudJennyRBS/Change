var app = angular.module('RbsChangeApp', ['ngCookies', 'ngAnimate', 'ui.bootstrap']);
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

app.provider('RbsChange.AjaxAPI', function AjaxAPIProvider () {
	var apiURL = '/ajax.V1.php/', LCID = 'fr_FR',
		defaultParams = {websiteId: null, sectionId: null, pageId: null, data : {}},
		blockParameters = {};

	this.$get = ['$http', '$location', '$rootScope', '$window', function ($http, $location, $rootScope, $window) {

		if (angular.isObject($window.__change))
		{
			if (angular.isObject($window.__change.navigationContext)) {
				var data = $window.__change.navigationContext;
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

			if (angular.isObject($window.__change.blockParameters)) {
				blockParameters = $window.__change.blockParameters;
			}
		}

		function globalVar(name, value) {
			if (angular.isObject($window.__change))
			{
				if (angular.isUndefined(value)) {
					return $window.__change.hasOwnProperty(name) ? $window.__change[name] : value;
				} else {
					return $window.__change[name] = value;
				}
			}
			return $window.__change;
		}

		function getVersion() {
			return 'V1';
		}

		function getLCID() {
			return LCID;
		}

		function getBlockParameters(blockId) {
			if (angular.isObject(blockParameters[blockId])) {
				return blockParameters[blockId];
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
			return {method: 'POST', url : apiURL + LCID + '/' + actionPath,
				headers : {"X-HTTP-Method-Override":method, "Content-Type": "application/json"}};
		}

		function getData(actionPath, data, params) {

			var config = getHttpConfig('GET', actionPath);

			var defaultParams = getDefaultParams();
			if (angular.isObject(params)) {
				angular.extend(defaultParams, params);
			}
			if (angular.isObject(data)) {
				defaultParams.data = data;
			}
			config.data = defaultParams;
			return $http(config);
		}

		function postData(actionPath, data, params) {
			var config = getHttpConfig('POST', actionPath);

			var defaultParams = getDefaultParams();
			if (angular.isObject(params)) {
				angular.extend(defaultParams, params);
			}
			if (angular.isObject(data)) {
				defaultParams.data = data;
			}
			config.data = defaultParams;
			return $http(config);
		}

		function putData(actionPath, data, params) {
			var config = getHttpConfig('PUT', actionPath);

			var defaultParams = getDefaultParams();
			if (angular.isObject(params)) {
				angular.extend(defaultParams, params);
			}
			if (angular.isObject(data)) {
				defaultParams.data = data;
			}
			config.data = defaultParams;
			return $http(config);
		}

		function deleteData(actionPath, data, params) {

			var config = getHttpConfig('DELETE', actionPath);

			var defaultParams = getDefaultParams();
			if (angular.isObject(params)) {
				angular.extend(defaultParams, params);
			}
			if (angular.isObject(data)) {
				defaultParams.data = data;
			}
			config.data = defaultParams;
			return $http(config);
		}

		// Public API
		return {
			getVersion : getVersion,
			getLCID: getLCID,
			globalVar: globalVar,
			getBlockParameters: getBlockParameters,
			getDefaultParams : getDefaultParams,
			getData: getData,
			postData: postData,
			putData: putData,
			deleteData: deleteData
		};
	}];
});

