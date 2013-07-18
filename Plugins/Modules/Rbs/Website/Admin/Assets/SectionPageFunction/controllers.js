(function () {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 *
	 * @param $scope
	 * @constructor
	 */
	function SectionFunctionsController($scope, $routeParams, $q, Breadcrumb, REST, i18n, Query, NotificationCenter) {

		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"]
		]);

		$scope.sectionPageFunctionList = [];
		var functions = [];


		//
		// Load current Section (Website or Topic).
		//

		function ready (section) {
			$scope.document = $scope.section = section;
			Breadcrumb.setResource(null);
			Breadcrumb.setPath([
				[section.label, section.url('tree')],
				['Fonctions'] // FIXME
			]);

			// Load the list of SectionPageFunction Documents
			var query = Query.simpleQuery('Rbs_Website_SectionPageFunction', 'section', section.id);
			REST.query(query, {"limit": 100, "offset": 0, "column": ['functionCode', 'page']}).then(function (result) {
				$scope.sectionPageFunctionList = result.resources;
				functions.length = 0;
				angular.forEach(result.resources, function (spf) {
					functions.push(spf.functionCode);
				});
			});
		}
		REST.resource($routeParams.id).then(ready);


		$scope.isFunctionAlreadyUsed = function (func) {
			return functions.indexOf(func) !== -1;
		};

		//
		// Load available functions
		//

		function loadFunctions () {
			REST.call(REST.getBaseUrl('Rbs/Website/FunctionsList')).then(function (functions) {
				$scope.allFunctions = functions;
			});
		}
		loadFunctions();

		$scope.$watch('selectedFunction', function (func) {
			if (func) {
				$scope.pagesForFunctionQuery = Query.simpleQuery('Rbs_Website_SectionPageFunction', 'functionCode', func);
				REST.call(REST.getBaseUrl('Rbs/Website/PagesForFunction'), {"function": func}).then(function (pages) {
					$scope.pagesForFunction = pages;
					if ($scope.pagesForFunction.length) {
						$scope.selectedPage = $scope.pagesForFunction[0];
					} else {
						$scope.selectedPage = null;
					}
				});
			}
		});

		$scope.addSectionFunction = function (func, page) {
			function saveFunctions () {
				var spf = REST.newResource('Rbs_Website_SectionPageFunction');
				spf.page = page.id;
				spf.section = $scope.section.id;
				spf.functionCode = func;
				REST.save(spf).then(
					// Success
					function () {
						$scope.$broadcast('Change:DocumentList:DLRbsWebsiteSectionFunctions:call', {"method": "reload"});
					},
					// Error
					function (error) {
						NotificationCenter.error("L'enregistrement a échoué", error);
					}
				);
			}
			saveFunctions();
		};

	}

	SectionFunctionsController.$inject = ['$scope', '$routeParams', '$q', 'RbsChange.Breadcrumb', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.Query', 'RbsChange.NotificationCenter'];
	app.controller('Rbs_Website_SectionFunctionsController', SectionFunctionsController);

})();