(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @param $scope
	 * @param Query
	 * @param $routeParams
	 * @param $location
	 * @param REST
	 * @param Utils
	 * @constructor
	 */
	function ListController($scope, Query, $routeParams, $location, REST, Utils)
	{
		$scope.selectedWebsite = null;

		REST.collection('Rbs_Website_Website').then(function (result) {
			$scope.websites = result.resources;

			// Only one website? Select it.
			if ($scope.websites.length === 1 && ! $routeParams.id) {
				$location.path($scope.websites[0].url('menus'));
			} else {
				if ($routeParams.id) {
					var websiteId = parseInt($routeParams.id, 10);
					if (isNaN(websiteId)) {
						throw new Error("Parameter 'websiteId' should be an integer.");
					}
					$scope.selectedWebsite = Utils.getById($scope.websites, websiteId);
					loadMenuList(websiteId);
				}
				registerWatches();
			}
		});

		function loadMenuList (websiteId) {
			$scope.listLoadQuery = Query.simpleQuery('Rbs_Website_Menu', 'website', websiteId);
		}

		function registerWatches () {
			$scope.$watch('selectedWebsite', function (website, oldValue) {
				if (website && website !== oldValue) {
					$location.path(website.url('menus'));
				}
			}, true);
		}
	}

	ListController.$inject = ['$scope', 'RbsChange.Query', '$routeParams', '$location', 'RbsChange.REST', 'RbsChange.Utils'];
	app.controller('Rbs_Website_Menu_ListController', ListController);


})();