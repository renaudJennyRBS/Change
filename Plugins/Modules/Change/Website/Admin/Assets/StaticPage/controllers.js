(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Controller for StaticPage list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @constructor
	 */
	function StaticPageListController ($scope, DocumentList, Breadcrumb, MainMenu) {

		var DL = DocumentList.initScope($scope, 'Change_Website_StaticPage');

		DL.viewMode = 'list';
		DL.sort.column = 'modificationDate';
		DL.sort.descending = true;

		Breadcrumb.resetLocation([["Sites et pages", "Change/Website"], ["Pages", "Change/Website/StaticPage"]]);

		$scope.createActions = [{ 'label': "Page", 'url': 'Change/Website/StaticPage/new' }];

		DL.columns.push({ id: 'template', label: "Gabarit", sortable: true });
		DL.columns.push({ id: 'modificationDate', label: "Dernière modif.", sortable: true });
		DL.columns.push(new DocumentList.Column('activated', "Activé", true, 'center', '90px'));

		MainMenu.loadModuleMenu('Change_Website');
	}

	StaticPageListController.$inject = [
		'$scope',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu'
	];
	app.controller('Change_Website_StaticPage_ListController', StaticPageListController);



	// FIXME
	app.controller('Change_Website_FunctionalPage_ListController', StaticPageListController);
	app.controller('Change_Website_Page_ListController', StaticPageListController);


	/**
	 * Controller for StaticPage form.
	 *
	 * @param $scope
	 * @param $location
	 * @param FormsManager
	 * @param Breadcrumb
	 * @constructor
	 */
	function StaticPageFormController ($scope, $location, FormsManager, Breadcrumb, Utils) {

		$scope.urlAfterSave = '/Change/Website/StaticPage';

		Breadcrumb.setLocation([["Sites et pages", "Change/Website"]]);

		// Let FormsManager search for 'id' and 'LCID' parameters in the $routeParams service.
		FormsManager.initResource($scope, 'Change_Website_StaticPage');
	}

	StaticPageFormController.$inject = [
		'$scope', '$location',
		'RbsChange.FormsManager',
		'RbsChange.Breadcrumb',
		'RbsChange.Utils'
	];
	app.controller('Change_Website_StaticPage_FormController', StaticPageFormController);


	// FIXME
	app.controller('Change_Website_FunctionalPage_FormController', StaticPageFormController);
	app.controller('Change_Website_Page_FormController', StaticPageFormController);


	/**
	 * Controller for StaticPage contents editor.
	 *
	 * @param $scope
	 * @param $location
	 * @param FormsManager
	 * @param Editor
	 * @constructor
	 */
	function StaticPageContentsFormController ($scope, FormsManager, Editor, Breadcrumb, REST) {

		Breadcrumb.setLocation([["Sites et pages", "Change/Website"]]);

		FormsManager.initResource($scope, 'Change_Website_StaticPage').then(function (document) {
			Editor.initScope($scope);
			$scope.original = document;

			// Load PageTemplate
			if (document.pageTemplate) {
				REST.resource(document.pageTemplate).then(function (template) {
					$scope.pageTemplate = {
						"html" : template.htmlForBackoffice,
						"data" : template.editableContent
					};
				});
			} else {
				throw new Error("Page " + document.id + " does not have a valid PageTemplate.");
			}
		});

		// This is for the "undo" dropdown menu:
		// Each item automatically activates its previous siblings.
		// FIXME Move this in a directive: controllers should never deal with the DOM.
		$('[data-role=undo-menu]').on('mouseenter', 'li', function () {
			$(this).siblings().removeClass('active');
			$(this).prevAll().addClass('active');
		});

	}

	StaticPageContentsFormController.$inject = [
		'$scope',
		'RbsChange.FormsManager',
		'RbsChange.Editor',
		'RbsChange.Breadcrumb',
		'RbsChange.REST'
	];
	app.controller('Change_Website_StaticPage_ContentsFormController', StaticPageContentsFormController);


	// FIXME
	app.controller('Change_Website_FunctionalPage_ContentsFormController', StaticPageContentsFormController);


})();