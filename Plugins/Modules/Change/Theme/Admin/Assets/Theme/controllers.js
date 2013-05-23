(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Controller for Theme list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @constructor
	 */
	function ListController ($scope, DocumentList, Breadcrumb, MainMenu) {

		var DL = DocumentList.initScope($scope, 'Change_Theme_Theme');

		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		Breadcrumb.resetLocation([["Thèmes", "Change/Theme"]]);

		$scope.createActions = [
			{ 'label': "Modèle", 'url': 'Change/Theme/Theme/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.push({ id: 'modificationDate', label: "Dernière modif." });
		DL.columns.push({ id: 'templates', label: "Modèles", width: "90px", align: "center" });
		DL.columns.push({ id: 'activated', label: "Activé", width: "90px", align: "center" });

		MainMenu.loadModuleMenu('Change_Theme');

	}

	ListController.$inject = [
		'$scope',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu'
	];
	app.controller('Change_Theme_Theme_ListController', ListController);


	/**
	 * Controller for Theme form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @constructor
	 */
	function FormController ($scope, Breadcrumb, FormsManager) {

		Breadcrumb.setLocation([["Thèmes", "Change/Theme"]]);
		FormsManager.initResource($scope, 'Change_Theme_Theme');

	}

	FormController.$inject = [
		'$scope',
		'RbsChange.Breadcrumb',
		'RbsChange.FormsManager'
	];
	app.controller('Change_Theme_Theme_FormController', FormController);


	function MenuController ($scope, REST) {

		REST.collection('Change_Theme_Theme').then(function (themes) {
			$scope.themes = themes.resources;
		});
	}

	MenuController.$inject = [
		'$scope',
		'RbsChange.REST'
	];
	app.controller('Change_Theme_MenuController', MenuController);

})();