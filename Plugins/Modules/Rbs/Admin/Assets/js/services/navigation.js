(function () {

	"use strict";

	var app = angular.module('RbsChange'),
		stack = {
			entries : [],
			pointer : 0
		};


	app.provider('RbsChange.Navigation', function ()
	{
		function NavigationEntry (id, url, label, context)
		{
			this.id = id;
			this.url = url;
			this.label = label;
			this.context = context;
		}


		this.$get = ['$rootScope', '$location', 'RbsChange.Utils', function ($rootScope, $location, Utils)
		{
			function push (url, label, context)
			{
				var id = stack.entries.length;
				stack.entries.length = Math.max(0, Math.min(stack.entries.length, stack.pointer));
				stack.entries.push(new NavigationEntry(id, $location.url(), label, context || {}));
				stack.pointer = id;
				console.log("NAV: push entry=", stack.entries[id], stack.entries);
				$location.url(Utils.makeUrl(url, { 'np': id }));
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


			// Public API

			return {
				push : push,
				getCurrentContext : getCurrentContext,
				finalize : finalize,
				commit : commit,
				rollback : rollback
			};
		}];

	});


	/**
	 * Directive: rbsNavigationHistory
	 * Usage    : as element: <rbs-navigation-history></rbs-navigation-history>
	 */
	app.directive('rbsNavigationHistory', ['RbsChange.Navigation', function (NS)
	{
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
	}]);

})();