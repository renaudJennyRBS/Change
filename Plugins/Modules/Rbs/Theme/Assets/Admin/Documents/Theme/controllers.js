(function ()
{
	"use strict";

	var	app = angular.module('RbsChange');


	//-----------------------------------------------------------------------------------------------------------------
	function ThemeSelector ($scope, $route, $location, REST, $filter) {
		REST.collection('Rbs_Theme_Theme', {limit:1}).then(function (collection) {
			var path;
			if (collection.resources.length) {
				var theme = collection.resources[0];
				var options = $route.current.$$route.options;
				var view =  (angular.isObject(options) && options.hasOwnProperty('view')) ? options.view : 'pagetemplates';
				path = $filter('rbsURL')(theme, view);
				$location.path(path);
			}
		});
	}

	ThemeSelector.$inject = ['$scope', '$route', '$location', 'RbsChange.REST', '$filter'];
	app.controller('Rbs_Theme_ThemeSelector', ThemeSelector);

	/**
	 *
	 *
	 * @param $scope
	 * @param $route
	 * @param $routeParams
	 * @param $location
	 * @param REST
	 * @param $filter
	 * @constructor
	 */
	function HeaderController ($scope, $route, $routeParams, $location, REST, $filter)
	{
		$scope.currentThemeId = $routeParams.id;
		$scope.currentTheme = null;
		$scope.view = $route.current.$$route.ruleName;
		$scope.themes = [];

		REST.query({model: 'Rbs_Theme_Theme'}).then(function (data){
			$scope.themes = data.resources;

			for (var i=0 ; i < data.resources.length; i++) {
				if (data.resources[i].id == $scope.currentThemeId) {
					$scope.currentTheme = data.resources[i];
					break;
				}
			}
		});

		$scope.$watch('currentTheme', function (theme) {
			if (theme && (theme.id != $scope.currentThemeId)) {
				var path = $filter('rbsURL')(theme, $scope.view);
				$location.path(path);
			}
		});
	}

	HeaderController.$inject = ['$scope', '$route', '$routeParams', '$location', 'RbsChange.REST', '$filter'];
	app.controller('Rbs_Theme_HeaderController', HeaderController);

	/**
	 * @param $scope
	 * @param Query
	 * @constructor
	 */
	function PageTemplatesController($scope, Query)
	{
		$scope.$watch('currentTheme', function (theme)
		{
			if (theme) {
				//add filter on isMailSuitable
				var restrictions = {theme: theme.id, mailSuitable: false};
				$scope.listLoadQuery = Query.simpleQuery('Rbs_Theme_Template', restrictions);
			}
		});
	}

	PageTemplatesController.$inject = ['$scope', 'RbsChange.Query'];
	app.controller('Rbs_Theme_PageTemplatesController', PageTemplatesController);

	/**
	 * @param $scope
	 * @param Query
	 * @constructor
	 */
	function MailTemplatesController($scope, Query)
	{
		$scope.$watch('currentTheme', function (theme)
		{
			if (theme) {
				//add filter on isMailSuitable
				var restrictions = {theme: theme.id, mailSuitable: true};
				$scope.listLoadQuery = Query.simpleQuery('Rbs_Theme_Template', restrictions);
			}
		});
	}

	MailTemplatesController.$inject = ['$scope', 'RbsChange.Query'];
	app.controller('Rbs_Theme_MailTemplatesController', MailTemplatesController);

})();