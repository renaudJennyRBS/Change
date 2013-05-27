(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Controller for PageTemplate list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @constructor
	 */
	function ListController ($scope, $routeParams, DocumentList, Breadcrumb, MainMenu, REST) {
		var DL = DocumentList.initScope($scope);

		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		Breadcrumb.resetLocation([["Thèmes", "Change/Theme"]]);

		$scope.createActions = [
			{ 'label': "Modèle", 'url': 'Change/Theme/PageTemplate/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.push({ id: 'modificationDate', label: "Dernière modif." });
		DL.columns.push({ id: 'activated', label: "Activé", width: "90px", align: "center" });

		if ($routeParams.theme) {
			REST.resource($routeParams.theme).then(function (theme) {
				Breadcrumb.setPath([theme]);
				console.log("treeUrl for theme: ", theme.url('tree'));
				DL.query = {"model":"Change_Theme_PageTemplate","where":{"and":[{"op":"eq","lexp":{"property":"theme"},"rexp":{"value":theme.id}}]}};
				DL.reload();
			});
		} else {
			DL.setResourceUrl('Change_Theme_PageTemplate');
		}

		MainMenu.loadModuleMenu('Change_Theme');
	}

	ListController.$inject = [
		'$scope', '$routeParams',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu',
		'RbsChange.REST'
	];
	app.controller('Change_Theme_PageTemplate_ListController', ListController);

	/**
	 * Controller for PageTemplate form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @constructor
	 */
	function FormController ($scope, Breadcrumb, FormsManager, REST) {

		Breadcrumb.setLocation([["Thèmes", "Change/Theme"]]);
		FormsManager.initResource($scope, 'Change_Theme_PageTemplate').then(function (pageTemplate) {
			REST.resource(pageTemplate.theme).then(function (theme) {
				Breadcrumb.setPath([theme]);
			});
		});
	}

	FormController.$inject = [
		'$scope',
		'RbsChange.Breadcrumb',
		'RbsChange.FormsManager',
		'RbsChange.REST'
	];
	app.controller('Change_Theme_PageTemplate_FormController', FormController);

})();