(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param Query
	 * @param Loading
	 * @param NotificationCenter
	 * @constructor
	 */
	function ListController($scope, $q, $location, Breadcrumb, MainMenu, i18n, REST, Query, Loading, NotificationCenter)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"]
		]);

		function initCurrentSection (section) {
			$scope.currentSection = section;
		}

		var currentPath = $location.path();

		Breadcrumb.ready().then(function (breadcrumbData) {
			initCurrentSection(breadcrumbData.currentNode);
			$scope.$on('Change:TreePathChanged', function (event, breadcrumbData) {
				functionsLoaded = false;
				if (currentPath === $location.path()) {
					initCurrentSection(breadcrumbData.currentNode);
					if ($scope.showFunctions) {
						loadFunctions();
					}
				}
			});
		});

		var functionsLoaded = false;
		$scope.showFunctions = false;
		$scope.loadingFunctions = false;

		function loadFunctions () {
			functionsLoaded = true;
			$scope.loadingFunctions = true;
			$scope.sectionFunctions = [];
			$scope.allFunctions = [];
			Loading.start();
			$q.all([
				REST.action('collectionItems', { 'code': 'Rbs_Website_AvailablePageFunctions' }),
				REST.query(Query.simpleQuery('Rbs_Website_SectionPageFunction', 'section', $scope.currentSection.id), {'column': ['page', 'section', 'functionCode']})
			]).then(
				function (results) {
					$scope.loadingFunctions = false;
					$scope.sectionFunctions = results[1].resources;
					$scope.allFunctions = results[0].items;
					$scope.allFunctions['Rbs_Website_Section'] = {"label": i18n.trans('m.rbs.website.admin.js.index-page | ucf')};
					angular.forEach($scope.sectionFunctions, function (func) {
						func.functionLabel = $scope.allFunctions[func.functionCode].label;
					});
					Loading.stop();
				},
				function (error) {
					$scope.loadingFunctions = false;
					NotificationCenter.error("Fonctions", error);
					Loading.stop();
				}
			);
		}

		$scope.toggleFunctions = function ($event, show) {
			if (angular.isDefined(show)) {
				$scope.showFunctions = show;
			} else {
				$scope.showFunctions = ! $scope.showFunctions;
			}
			if ($scope.showFunctions && (! functionsLoaded || $event.shiftKey)) {
				loadFunctions();
			}
		};

		MainMenu.loadModuleMenu('Rbs_Website');
	}

	ListController.$inject = [
		'$scope', '$q', '$location',
		'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n', 'RbsChange.REST', 'RbsChange.Query',
		'RbsChange.Loading', 'RbsChange.NotificationCenter'
	];
	app.controller('Rbs_Website_Topic_ListController', ListController);

})();