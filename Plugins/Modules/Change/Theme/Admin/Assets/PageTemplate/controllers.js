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
	function PageTemplateListController ($scope, DocumentList, Breadcrumb, MainMenu) {

		var DL = DocumentList.initScope($scope, 'Change_Theme_PageTemplate');

		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		Breadcrumb.resetLocation([["Thèmes", "Change/Theme"]]);

		$scope.createActions = [
			{ 'label': "Modèle", 'url': 'Change/Theme/PageTemplate/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.push({ id: 'modificationDate', label: "Dernière modif.", sortable: true });
		DL.columns.push({ id: 'activated', label: "Activé", width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Change_Theme');

	}

	PageTemplateListController.$inject = [
		'$scope',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu'
	];
	app.controller('Change_Theme_PageTemplateListController', PageTemplateListController);


	/**
	 * Controller for PageTemplate form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @constructor
	 */
	function PageTemplateFormController ($scope, Breadcrumb, FormsManager) {

		Breadcrumb.setLocation([["Thèmes", "Change/Theme"]]);
		FormsManager.initResource($scope, 'Change_Theme_PageTemplate');

	}

	PageTemplateFormController.$inject = [
		'$scope',
		'RbsChange.Breadcrumb',
		'RbsChange.FormsManager'
	];
	app.controller('Change_Theme_PageTemplateFormController', PageTemplateFormController);

})();