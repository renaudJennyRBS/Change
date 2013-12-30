(function () {

	"use strict";

	var app = angular.module('RbsChange'),
		stack = {
			entries : [],
			pointer : 0
		};
	var activeContextes = [];


	app.provider('RbsChange.Navigation', function ()
	{
		function NavigationEntry (id, url, label, context)
		{
			this.id = id;
			this.url = url;
			this.label = label;
			this.context = context;
		}


		this.$get = ['$rootScope', '$location', 'RbsChange.Utils', '$q', function ($rootScope, $location, Utils, $q)
		{
/*
			function push (url, label, context)
			{
				var id = stack.entries.length;
				stack.entries.length = Math.max(0, Math.min(stack.entries.length, stack.pointer));
				stack.entries.push(new NavigationEntry(id, $location.url(), label, context || {}));
				stack.pointer = id;
				console.log("NAV: push entry=", stack.entries[id], stack.entries);
				$location.url(Utils.makeUrl(url, { 'np': id, 'nf': null }));
			}


			function getCurrentContext ()
			{
				if (stack.pointer >= 0 && stack.pointer < stack.entries.length) {
					return stack.entries[stack.pointer].context;
				}
				return null;
			}


			function updateOnRouteChange ()
			{
				console.log('NAV: updateOnRouteChange');
				var params = $location.search();

				if (params.hasOwnProperty('np')) {
					stack.pointer = parseInt(params['np'], 10);
					if (stack.pointer >= stack.entries.length)
					{
						console.warn("Obsolete navigation entry: redirecting to most recent one: ");
						stack.pointer = stack.entries.length - 1;
						if (stack.pointer > -1) {
							$location.url(stack.entries[stack.pointer].url);
							stack.entries.length = stack.entries.length - 1;
						}
						else {
							console.warn("Empty navigation stack.");
						}
					}
				}
				else if (params.hasOwnProperty('nf')) {
					stack.pointer = parseInt(params['nf'], 10);
					console.log("route changed: pointer=", stack.pointer);
				}
				else {
					stack.pointer = -1;
					stack.entries.length = 0;
				}
				console.log("pointer=", stack.pointer, ", length=", stack.entries.length);
			}


			function commit (result)
			{
				console.log("NAV: commit: pointer=", stack.pointer, ", length=", stack.entries.length);
				var entry = stack.entries[stack.pointer];
				entry.status = 'commit';
				entry.result = result;
				console.log("entry=", entry, ", redirecting to: ", Utils.makeUrl(entry.url, { 'nc': stack.pointer }));
				$location.url(Utils.makeUrl(entry.url, { 'nf': stack.pointer }));
			}


			function rollback ()
			{
				console.log("NAV: rollback: pointer=", stack.pointer, ", length=", stack.entries.length);
				var entry = stack.entries[stack.pointer];
				entry.status = 'rollback';
				console.log("pointer=", stack.pointer, ", length=", stack.entries.length, ", entry url=", entry.url);
				$location.url(Utils.makeUrl(entry.url, { 'nf': stack.pointer }));
			}


			function finalize ()
			{
				var params = $location.search(),
					pointer,
					entry;

				if (params.hasOwnProperty('nf')) {
					pointer = parseInt(params['nf'], 10);
					entry = stack.entries[pointer];
					stack.entries.length = pointer;
					console.log("NAV: finalize end: entries=", stack.entries.length);
					return entry;
				}
				else {
					console.info("NAV: nothing to finalize.");
					return null;
				}
			}


			$rootScope.$on('$routeChangeSuccess', updateOnRouteChange);
			$rootScope.$on('$routeUpdate', updateOnRouteChange);
*/



			var lastContext;

			function NavigationContext (id, data)
			{
				// Update context info.
				if (Utils.isDocument(id)) {
					this.id = id.model;
					this.document = id;
					this.label = this.document.label || this.document.title;
				}
				else {
					this.id = id;
				}
				this.data = data;
				this.url = $location.absUrl();
				this.path = $location.path();

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
			}


			function getActiveContextById (id)
			{
				for (var i=0 ; i<activeContextes.length ; i++) {
					if (activeContextes[i].id === id) {
						return activeContextes[i];
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

					for (var i=0 ; i<activeContextes.length ; i++) {
						if (activeContextes[i].id === id) {
							activeContextes.splice(i, 1);
						}
					}
				}
			}


			function setContext (scope, id, data)
			{
				var defer = $q.defer();

				finalizeActiveContext(id, defer);

				lastContext = new NavigationContext(id, data);

				scope.$on('$destroy', function ()
				{
					lastContext = null;
				});

				return defer.promise;
			}


			function start (params)
			{
				if (! lastContext) {
					console.log("No context.");
					return;
				}
				lastContext.params = params;
				lastContext.label = params.document ? params.document.label : lastContext.id;
				activeContextes.push(lastContext);
				lastContext = null;
			}


			function resolve (result, redirect)
			{
				var ctx = activeContextes[activeContextes.length-1];
				ctx.status = 'committed';
				ctx.result = result;

				if (redirect !== false) {
					$location.path(ctx.path);
				}
			}


			function reject (reason)
			{
				var ctx = activeContextes[activeContextes.length-1];
				ctx.status = 'rejected';
				ctx.result = reason;
				$location.path(ctx.path);
			}


			function isActive ()
			{
				return activeContextes.length > 0;
			}


			function getActiveContext ()
			{
				return isActive() ? activeContextes[activeContextes.length-1] : null;
			}


			// Public API

			return {/*
				push : push,
				getCurrentContext : getCurrentContext,
				finalize : finalize,
				commit : commit,
				rollback : rollback,
*/
				// New API

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

			link : function (scope, iElement, iAttrs)
			{
				iElement.click(function ()
				{
					var params = {};
					angular.forEach(iElement.data(), function (v, n)
					{
						if (n.substr(0, 10) === 'navigation') {
							params[angular.lowercase(n.substr(10, 1)) + n.substr(11)] = v;
						}
					});
					NS.start(params, iAttrs.rbsStartNavigation);
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
				'<div ng-repeat="c in activeContextes">' +
					'<div class="cascading-forms-collapsed" ng-style="getStyle($index)">' +
						'<span class="pull-right" ng-bind-html="c.id"></span>' +
						'<a href ng-href="(= c.url =)"><i class="icon-circle-arrow-left"></i> (= c.label =)</a>' +
						'<span ng-if="c.isSelection()"> &mdash; select elements for property <strong ng-bind="c.params.label"></strong></span>' +
					'</div>' +
				'</div>',
			scope : {},

			link : function (scope, iElement)
			{
				scope.activeContextes = activeContextes;
				scope.$watchCollection('activeContextes', function ()
				{
					if (scope.activeContextes.length > 0) {
						iElement.show();
					} else {
						iElement.hide();
					}
				});

				scope.getStyle = function (index)
				{
					var count = scope.activeContextes.length;
					return {
						margin    : '0 ' + ((count - index)*15)+'px',
						opacity   : (0.7 + ((index+1)/count * 0.3)),
						fontSize  : ((1 + ((index+1)/count * 0.2))*100)+'%'
					};
				};
			}
		};


		/*
		return {
			restrict : 'E',
			template :
				'<div ng-show="stack && stack.entries && stack.entries.length > 0 && stack.pointer > -1">' +
					'<div ng-repeat="e in stack.entries | limitTo:getLimit()">' +
						'<div class="cascading-forms-collapsed" ng-style="getStyle($index)" ng-bind-html="e.label"></div>' +
					'</div>' +
				'</div>',
			scope : {},

			link : function (scope)
			{
				scope.stack = stack;

				scope.getStyle = function (index)
				{
					var count = stack.entries.length;
					return {
						margin    : '0 ' + ((count - index)*15)+'px',
						opacity   : (0.7 + ((index+1)/count * 0.3)),
						zIndex    : index + 1,
						fontSize  : ((1 + ((index+1)/count * 0.2))*100)+'%',
						lineHeight: ((1 + ((index+1)/count * 0.2))*100)+'%'
					};
				};

				scope.getLimit = function ()
				{
					return Math.min(scope.stack.entries.length, scope.stack.pointer+1);
				};
			}
		};
		*/
	}]);

})();