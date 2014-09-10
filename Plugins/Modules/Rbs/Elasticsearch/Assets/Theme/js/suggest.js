(function() {
	"use strict";

	var app = angular.module('RbsChangeApp');

	function rbsElasticsearchShortSearch($http, $rootScope) {
		return {
			restrict: 'A',
			scope: true,

			link: function(scope, elm, attrs) {
				scope.searchText = elm.find("[name='searchText']").val();
				scope.formAction = attrs.formAction;
				scope.suggestionsLoading = false;

				scope.loadSuggestions = function loadSuggestions(searchText) {
					scope.suggestionsLoading = true;
					return $http.post('Action/Rbs/Elasticsearch/Suggest', { searchText: searchText })
						.then(function(res) {
							var suggestions = [];
							if (res.data.items.length > 0) {
								suggestions.push(searchText);
								for (var i = 0; i < res.data.items.length; i++) {
									suggestions.push(res.data.items[i]);
								}
								scope.suggestionsLoading = false;
							}
							return suggestions;
						});
				};

				scope.submitForm = function submitForm($item) {
					if ($item) {
						scope.searchText = $item;
					}
					elm.find("[name='searchText']").val(scope.searchText);
					elm.submit();
				};

				$rootScope.$on('rbsElasticsearchSetSearchFormAction', function(event, params) {
					elm.attr('action', params.formAction);
				});
			}
		}
	}

	rbsElasticsearchShortSearch.$inject = ['$http', '$rootScope'];
	app.directive('rbsElasticsearchShortSearch', rbsElasticsearchShortSearch);
})();