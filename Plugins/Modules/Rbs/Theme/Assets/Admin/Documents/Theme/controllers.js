(function ()
{
	"use strict";

	var	app = angular.module('RbsChange');


	//-----------------------------------------------------------------------------------------------------------------


	/**
	 *
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param $location
	 * @param REST
	 * @param Query
	 * @constructor
	 */
	function HeaderController ($scope, $routeParams, $location, REST, Query)
	{
		$scope.currentTheme = null;
		$scope.viewUrl = null;

		REST.query({model: 'Rbs_Theme_Theme'}).then(function (data){
			$scope.themes = data.resources;

			if (! $scope.currentTheme){
				var i,
					wsid = parseInt($location.search()['theme'], 10);
				if (! isNaN(wsid)) {
					for (i=0 ; i<data.resources.length && ! $scope.currentTheme ; i++) {
						if (data.resources[i].id === wsid) {
							$scope.currentTheme = data.resources[i];
						}
					}
				}
				if (! $scope.currentTheme) {
					$scope.currentTheme = data.resources[0];
				}

				$scope.$watch(function () { return $routeParams.view; }, function (view)
				{
					if (! view) {
						view = "PageTemplates";
					}
					$scope.view = view;
				});
			}
		});

		$scope.$watch('currentTheme', function (theme)
		{
			if (theme) {
				$location.search('theme', theme.id);
			}
		});
	}

	HeaderController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.REST', 'RbsChange.Query'];
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