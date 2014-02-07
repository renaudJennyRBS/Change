(function () {

	"use strict";

	var app = angular.module('RbsChange'), activeContexts = [];

	app.provider('RbsChange.Navigation', function ()
	{
		this.$get = ['$rootScope', '$location', '$timeout', function ($rootScope, $location, $timeout)
		{
			function NavigationContext()
			{
				var data = {}, k, v, l, valueParams = {}, hasData = false;

				this.id = (new Date()).getTime();

				this.savedData = function(key, value) {
					if (key === undefined) {
						return data;
					} else {
						if (value !== undefined) {
							hasData = true;
							data[key] = value;
							return this;
						} else {
							return data[key];
						}
					}
				};

				this.hasData = function() {
					return hasData;
				};

				this.label = function(newLabel) {
					if (newLabel !== undefined) {
						l = newLabel;
						return this;
					}
					return l;
				};

				this.redirect = function() {
					$location.url(this.url);
				};

				this.value = function(value) {
					if (value !== undefined) {
						v = value;
						return this;
					}
					return v;
				};

				this.valueKey = function(valueKey) {
					if (valueKey !== undefined) {
						k = valueKey;
						return this;
					}
					return k;
				};

				this.isSelection = function() {
					return (k !== undefined);
				};

				this.labelKey = function(labelKey) {
					return this.param('label', labelKey);
				};

				this.getSelectionValue = function(valueKey) {
					if (valueKey === k)
					{
						return v;
					}
					return undefined;
				};

				this.param = function(name, value) {
					if (name === undefined) {
						return valueParams;
					} else {
						if (value !== undefined) {
							valueParams[name] = value;
							return this;
						} else {
							return valueParams[name];
						}
					}
				}
			}

			function startSelectionContext(targetUrl, valueKey, params) {
				var context = new NavigationContext();
				$rootScope.$broadcast('Navigation.saveContext', {context : context});
				if (context.hasData()) {
					var url = $location.url(), splited = url.split('#');
					if (splited.length == 2) {
						var fromContext = splited[1];
						if (indexOfContextId(fromContext) != -1) {
							context.fromContext = fromContext;
						}
					}
					context.url = splited[0] + '#'+ context.id;
					context.targetUrl = targetUrl;
					context.valueKey(valueKey === undefined ? null : valueKey) ;
					if (angular.isObject(params)) {
						angular.forEach(params, function(value, key) {
							context.param(key, value);
						});
					}
					activeContexts.push(context);
					$location.url(targetUrl).hash(context.id);
				} else {
					$location.url(targetUrl);
				}
			}

			function setSelectionContextValue(value, context) {
				context =  (context === undefined) ? getCurrentContext() : context;
				if (context) {
					context.value(value);
					$location.url(context.url);
				}
			}

			function indexOfContextId(id) {
				var i, length = activeContexts.length;
				for (i = 0; i < length; i++) {
					if (id == activeContexts[i].id) {
						return i;
					}
				}
				return -1;
			}

			function getCurrentContext() {
				var i, context, id = $location.hash(), length = activeContexts.length;
				if (id) {
					i = indexOfContextId(id);
					if (i == -1) {
						$location.hash(null);
						return null;
					}
					context = activeContexts[i];
					if (i < (length -1)) {
						activeContexts.splice(i + 1);
					}
					return context;
				}
				return null;
			}

			function popContext(context) {
				context =  (context === undefined) ? getCurrentContext() : context;
				if (context)
				{
					$timeout(function() {
						var i = indexOfContextId(context.id);
						activeContexts.splice(i);
						if (context.hasOwnProperty('fromContext') && indexOfContextId(context.fromContext) != -1) {
							$location.hash(context.fromContext);
						} else {
							$location.hash(null);
						}
					});
				}
				return context;
			}

			function getActiveContexts() {
				return activeContexts;
			}

			function addTargetContext(targetUrl) {
				if (activeContexts.length < 1 || !targetUrl || targetUrl.length == 0) {
					return targetUrl;
				}
				var context, i, lastIndex = activeContexts.length - 1;
				for(i = lastIndex; i >= 0; i--) {
					context = activeContexts[i];
					if (context.targetUrl == targetUrl) {
						targetUrl += '#' + context.id;
						return targetUrl;
					}
				}

				var cleanTarget = targetUrl.split('?')[0];
				if (cleanTarget != targetUrl) {
					for(i = lastIndex; i >= 0; i--) {
						context = activeContexts[i];
						if (context.targetUrl == cleanTarget) {
							targetUrl += '#' + context.id;
							return targetUrl;
						}
					}
				}
				return targetUrl;
			}

			// Public API
			return {
				startSelectionContext : startSelectionContext,
				setSelectionContextValue : setSelectionContextValue,
				getCurrentContext : getCurrentContext,
				popContext : popContext,
				indexOfContextId : indexOfContextId,
				getActiveContexts : getActiveContexts,
				addTargetContext : addTargetContext
			};
		}];
	});

	/**
	 * Directive: rbsNavigationHistory
	 * Usage    : as element: <rbs-navigation-history></rbs-navigation-history>
	 */
	app.directive('rbsNavigationHistory', ['RbsChange.Navigation', function (Navigation)
	{
		return {
			restrict : 'E',
			template :
				'<div ng-repeat="c in activeContexts">' +
					'<div class="cascading-forms-collapsed" ng-style="getStyle($index)">' +
						'<a href ng-click="c.redirect()"><i class="icon-circle-arrow-left"></i> (= c.label() =)</a>' +
						'<span ng-if="c.labelKey()"> &mdash; <span ng-bind="c.labelKey()"></span></span>' +
					'</div>' +
				'</div>',
			scope : {},

			link : function (scope, iElement)
			{
				scope.activeContexts = activeContexts;
				scope.$watchCollection('activeContexts', function ()
				{
					if (scope.activeContexts.length > 0) {
						iElement.show();
					} else {
						iElement.hide();
					}
				});

				scope.getStyle = function (index)
				{
					var count = scope.activeContexts.length;
					return {
						margin   : '0 ' + ((count - index) * 15) + 'px',
						opacity  : (0.7 + ((index+1)/count * 0.3)),
						fontSize : ((1  + ((index+1)/count * 0.2))*100) + '%'
					};
				};
			}
		};
	}]);

	app.directive('rbsStartNavigation', ['RbsChange.Navigation', '$rootScope', function (Navigation, $rootScope)
	{
		return {
			restrict : 'A',
			link : function (scope, iElement, attrs)
			{
				iElement.click(function (event)
				{
					var targetUrl = attrs.targetUrl, params = {}, hasParams = false;
					var valueKey = null;
					angular.forEach(iElement.data(), function (v, n)
					{
						if (n.substr(0, 10) === 'navigation') {
							hasParams = true;
							params[angular.lowercase(n.substr(10, 1)) + n.substr(11)] = v;
						}
						if (n === 'valueKey') {
							valueKey = v;
						}
					});
					if (!hasParams) {
						params = null;
					}
					Navigation.startSelectionContext(targetUrl, valueKey, params);
					$rootScope.$apply();
				});
			}
		};
	}]);
})();