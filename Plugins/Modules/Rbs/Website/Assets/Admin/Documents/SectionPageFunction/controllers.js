(function () {

	"use strict";

	var app = angular.module('RbsChange'),
		INDEX_FUNCTION_CODE = 'Rbs_Website_Section';

	/**
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param $q
	 * @param Breadcrumb
	 * @param REST
	 * @param i18n
	 * @param Query
	 * @param NotificationCenter
	 * @constructor
	 */
	function SectionFunctionsController($scope, $routeParams, $q, Breadcrumb, REST, i18n, Query, NotificationCenter, ErrorFormatter) {

		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.website.admin.module_name | ucf'), "Rbs/Website"]
		]);

		var functions = [];

		$scope.sectionPageFunctionList = [];
		$scope.indexFunctionExists = false;
		$scope.sectionPage = {
			'indexPage' : null
		};


		/**
		 * Stores the codes of the functions that are attached to the current section
		 * and checks whether the 'index' function is present or not.
		 * @param functionList
		 */
		function initExistingFunctions (functionList) {
			functions.length = 0;
			angular.forEach(functionList, function (spf) {
				functions.push(spf.functionCode);
				if (spf.functionCode === INDEX_FUNCTION_CODE) {
					$scope.indexFunctionExists = true;
				}
			});
		}


		//
		// Load current Section (Website or Topic).
		//

		$scope.reload = function (sortColumn, sortDesc) {
			// Load the list of SectionPageFunction Documents
			var query = Query.simpleQuery('Rbs_Website_SectionPageFunction', 'section', $routeParams.id);
			query.limit = 500;
			query.offset = 0;
			query.order = [{ 'property': sortColumn, 'order': sortDesc ? 'desc' : 'asc' }];
			REST.query(query, {"column": ['functionCode', 'page']}).then(function (result) {
				$scope.sectionPageFunctionList = result.resources;
				initExistingFunctions(result.resources);
			});
		};

		REST.resource($routeParams.id).then(function (section) {
			$scope.document = $scope.section = section;
			Breadcrumb.setResource(null);
			Breadcrumb.setPath([
				[section.label, section.url('tree')],
				['Fonctions'] // FIXME
			]);
			$scope.reload();
		});


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

		$scope.addSectionFunction = function (func, page, extraFunctions) {

			function saveFunctions (functions) {

				var spf = REST.newResource('Rbs_Website_SectionPageFunction');
				spf.page = page.id;
				spf.section = $scope.section.id;
				spf.functionCode = functions.pop();

				REST.save(spf).then(
					// Success
					function () {
						if (functions.length) {
							saveFunctions(functions);
						} else {
							$scope.reload();
						}
					},
					// Error
					function (error) {
						var promises = [];
						$scope.reload();
						//$scope.$broadcast('Change:DocumentList:DLRbsWebsiteSectionFunctions:call', {"method": "reload", "promises": promises});
						$q.all(promises).then(function () {
							NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.save_error | ucf'), ErrorFormatter.format(error));
						});
					}
				);
			}

			var allFunctions;
			if (angular.isArray(extraFunctions)) {
				allFunctions = angular.copy(extraFunctions);
			} else {
				allFunctions = [];
			}
			if (allFunctions.indexOf(func) === -1) {
				allFunctions.unshift(func);
			}

			saveFunctions(allFunctions);
		};


		$scope.setIndexPage = function (page) {
			var spf = REST.newResource('Rbs_Website_SectionPageFunction');
			spf.page = page.id;
			spf.section = $scope.section.id;
			spf.functionCode = INDEX_FUNCTION_CODE;
			REST.save(spf).then(
				function () {
					$scope.reload();
				},
				function (error) {
					NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.save_error | ucf'), ErrorFormatter.format(error));
				}
			);
		};

	}

	SectionFunctionsController.$inject = ['$scope', '$routeParams', '$q', 'RbsChange.Breadcrumb', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.Query', 'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	app.controller('Rbs_Website_SectionFunctionsController', SectionFunctionsController);

})();