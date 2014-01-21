(function () {

	"use strict";

	var app = angular.module('RbsChange'),
		activeContexts = [];


	app.provider('RbsChange.Navigation', function ()
	{
		this.$get = ['$rootScope', '$location', 'RbsChange.Utils', 'RbsChange.i18n', '$q', function ($rootScope, $location, Utils, i18n, $q)
		{
			var lastContext;

			function NavigationContext (id, label)
			{
				// Update context info.
				if (Utils.isDocument(id)) {
					this.id = id.model;
					this.document = id;
					this.label = label || this.document.label || this.document.title;
				}
				else {
					this.id = id;
					this.label = label;
				}

				this.url = $location.absUrl();
				this.path = $location.path();
				this.search = angular.copy($location.search());

				this.getParam = function (name)
				{
					return angular.isObject(this.params) ? this.params[name] : undefined;
				};

				this.isSelection = function (model)
				{
					var result = this.getParam('selector');
					if (result)
					{
						if (angular.isString(model))
						{
							return this.getParam('model') === model.trim();
						}
						else if (angular.isArray(model))
						{
							angular.forEach(model.split(/\s+/), function (modelName) {
								if (modelName === model) {
									return true;
								}
							});
							return false;
						}
					}
					return result;
				};

				this.isForDocumentProperty = function ()
				{
					var ngModel = this.getParam('ngModel');
					if (angular.isString(ngModel)) {
						return ngModel.substr(0, 9) === 'document.';
					}
					return false;
				};
			}


			function getActiveContextById (id)
			{
				for (var i=0 ; i<activeContexts.length ; i++) {
					if (activeContexts[i].id === id) {
						return activeContexts[i];
					}
				}
				return null;
			}


			function finalizeActiveContext (id, defer)
			{
				var context = getActiveContextById(id);
				// Search for a context with the same ID and remove it.
				if (context) {
					if (context.status === 'committed') {
						defer.resolve(context);
					} else {
						defer.reject(context);
					}

					for (var i=0 ; i<activeContexts.length ; i++) {
						if (activeContexts[i].id === id) {
							activeContexts.splice(i, 1);
						}
					}
				}
			}


			function setContext (scope, id, label)
			{
				var defer = $q.defer();

				finalizeActiveContext(id, defer);

				lastContext = new NavigationContext(id, label);

				scope.$on('$destroy', function ()
				{
					lastContext = null;
				});

				return defer.promise;
			}


			function start (data, additionalParams)
			{
				if (! lastContext) {
					console.log("No context.");
					return;
				}

				var params;
				if (data instanceof jQuery) {
					params = angular.extend({}, extractParamsFromElement(data), additionalParams);
				} else if (angular.isObject(data)) {
					params = data;
				}

				lastContext.params = params;
				if (! lastContext.label) {
					if (Utils.isDocument(params.document)){
						if (params.document.id < 0 && !params.document.label){
							lastContext.label = i18n.trans('m.rbs.admin.adminjs.new_resource | ucf');
						} else {
							lastContext.label = params.document.label;
						}

					} else {
						lastContext.label = lastContext.id;
					}
				}
				activeContexts.push(lastContext);
				lastContext = null;
			}


			function resolve (result, redirect)
			{
				var ctx = activeContexts[activeContexts.length-1];
				ctx.status = 'committed';
				ctx.result = result;

				if (redirect !== false) {
					$location.path(ctx.path).search(ctx.search);
				}
			}


			function reject (reason)
			{
				var ctx = activeContexts[activeContexts.length-1];
				ctx.status = 'rejected';
				ctx.result = reason;
				$location.path(ctx.path);
			}


			function isActive ()
			{
				return activeContexts.length > 0;
			}


			function getActiveContext ()
			{
				return isActive() ? activeContexts[activeContexts.length-1] : null;
			}


			function extractParamsFromElement (el)
			{
				var params = {};
				angular.forEach(el.data(), function (v, n)
				{
					if (n.substr(0, 10) === 'navigation') {
						params[angular.lowercase(n.substr(10, 1)) + n.substr(11)] = v;
					}
				});
				return params;
			}


			// Public API

			return {
				setContext : setContext,
				start : start,
				resolve : resolve,
				reject : reject,
				isActive : isActive,
				getActiveContext : getActiveContext
			};
		}];

	});




	app.directive('rbsStartNavigation', ['RbsChange.Navigation', function (NS)
	{
		return {
			restrict : 'A',

			link : function (scope, iElement)
			{
				iElement.click(function ()
				{
					NS.start(iElement);
				});
			}
		};
	}]);




	/**
	 * Directive: rbsNavigationHistory
	 * Usage    : as element: <rbs-navigation-history></rbs-navigation-history>
	 */
	app.directive('rbsNavigationHistory', ['RbsChange.Navigation', function (NS)
	{
		return {
			restrict : 'E',
			template :
				'<div ng-repeat="c in activeContexts">' +
					'<div class="cascading-forms-collapsed" ng-style="getStyle($index)">' +
						'<a href ng-href="(= c.url =)"><i class="icon-circle-arrow-left"></i> (= c.label =)</a>' +
						'<span ng-if="c.isSelection()"> &mdash; <span ng-bind="c.params.label"></span></span>' +
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

})();