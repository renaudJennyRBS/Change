(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Controller for Topic list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @constructor
	 */
	function TopicListController ($scope, DocumentList, Breadcrumb, MainMenu) {

		Breadcrumb.resetLocation([["Sites et pages", "Change/Website"]]);

		var DL = DocumentList.initScopeForTree($scope);

		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		DL.addActions(['reorder']);

		$scope.createActions = [
			{ 'label': "Page", 'url': 'Change/Website/StaticPage/new', 'icon': 'file' },
			{ 'label': "Rubrique", 'url': 'Change/Website/Topic/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.splice(1, 0, { id: 'type', label: "", width: "24px", align: "center" });
		DL.columns.push({ id: 'modificationDate', label: "Dernière modif." });
		DL.columns.push({ id: 'nodeOrder', label: "Ordre", align: "right" });
		DL.columns.push({ id: 'activated', label: "Activé", width: "90px", align: "center" });

		MainMenu.loadModuleMenu('Change_Website');

	}

	TopicListController.$inject = [
		'$scope',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu'
	];
	app.controller('Change_Website_TopicListController', TopicListController);


	/**
	 * Controller for Topic form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @constructor
	 */
	function TopicFormController ($scope, Breadcrumb, FormsManager) {

		Breadcrumb.setLocation([["Sites et pages", "Change/Website"]]);
		FormsManager.initResource($scope, 'Change_Website_Topic');

	}

	TopicFormController.$inject = [
		'$scope',
		'RbsChange.Breadcrumb',
		'RbsChange.FormsManager'
	];
	app.controller('Change_Website_TopicFormController', TopicFormController);

})();