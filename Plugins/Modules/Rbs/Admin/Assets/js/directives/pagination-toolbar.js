(function (jq) {

	angular.module('RbsChange').directive('paginationToolbar', ['RbsChange.Settings', 'RbsChange.ArrayUtils', '$location', '$routeParams', function (Settings, Array, $location, $routeParams) {

		return {
			// Utilisation : <pagination-toolbar></pagination-toolbar>
			restrict: 'E',

			// URL du template HTML
			templateUrl: 'Rbs/Admin/js/directives/pagination-toolbar.html',

			replace: true,

			scope: {
				DL: '=documentList'
			},

			// Initialisation du scope (logique du composant)
			link: function (scope, element, attrs) {
				var url, p;

				scope.predefinedPageSizes = [ 2, 5, 10, 15, 20, 30, 50 ];

				scope.pages = [];
				scope.currentPage = 0;

				url = $location.absUrl();
				p = url.indexOf('?');
				if (p === -1) {
					scope.url = url;
				} else {
					scope.url = url.substring(0, p);
				}

				function refresh () {
					var nbPages,
					    i;

					//console.log("Refreshing pagination toolbar: total=", scope.DL.pagination.total, ", offset=", scope.DL.pagination.offset, ", limit=", scope.DL.pagination.limit);
					Array.clear(scope.pages);
					nbPages = Math.ceil(scope.DL.pagination.total / scope.DL.pagination.limit);
					if (nbPages > 11) {
						for (i=0 ; i<5 ; i++) {
							scope.pages.push(i);
						}
						scope.pages.push('...');
						for (i=nbPages-5 ; i<nbPages ; i++) {
							scope.pages.push(i);
						}
					} else {
						for (i=0 ; i<nbPages ; i++) {
							scope.pages.push(i);
						}
					}
					scope.currentPage = scope.DL.pagination.offset / scope.DL.pagination.limit;
				}

				// Register watchers
				scope.$watch('DL.pagination', refresh, true);

				scope.isFirstPage = function () {
					return scope.currentPage === 0;
				};

				scope.isLastPage = function () {
					return scope.currentPage === (scope.pages.length-1);
				};

			}

		};
	}]);

})( window.jQuery );