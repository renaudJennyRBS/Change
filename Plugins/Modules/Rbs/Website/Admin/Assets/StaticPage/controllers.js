(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @constructor
	 */
	function ListController($scope, DocumentList, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"],
			[i18n.trans('m.rbs.website.admin.js.staticpage-list | ucf'), "Rbs/Website/StaticPage"]
		]);

		var DL = DocumentList.initScope($scope, 'Rbs_Website_StaticPage');
		DL.viewMode = 'list';
		DL.sort.column = 'modificationDate';
		DL.sort.descending = true;

		$scope.createActions = [
			{ 'label': i18n.trans('m.rbs.website.admin.js.staticpage | ucf'), 'url': 'Rbs/Website/StaticPage/new' }
		];

		DL.columns.push({ id: 'template', label: i18n.trans('m.rbs.theme.admin.js.pagetemplate | ucf'), sortable: true });
		DL.columns.push({ id: 'modificationDate', label: i18n.trans('m.rbs.admin.admin.js.modification-date | ucf'), sortable: true });
		DL.columns.push({ id: 'activated', label: i18n.trans('m.rbs.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Rbs_Website');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Website_StaticPage_ListController', ListController);

	// FIXME
	app.controller('Rbs_Website_FunctionalPage_ListController', ListController);
	app.controller('Rbs_Website_Page_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param FormsManager
	 * @param Breadcrumb
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		$scope.urlAfterSave = '/Rbs/Website/StaticPage';

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"]
		]);
		FormsManager.initResource($scope, 'Rbs_Website_StaticPage');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_Website_StaticPage_FormController', FormController);

	// FIXME
	app.controller('Rbs_Website_FunctionalPage_FormController', FormController);
	app.controller('Rbs_Website_Page_FormController', FormController);

	/**
	 * Controller for contents editor.
	 *
	 * @param $scope
	 * @param FormsManager
	 * @param Editor
	 * @param Breadcrumb
	 * @param i18n
	 * @constructor
	 */
	function ContentsFormController($scope, FormsManager, Editor, Breadcrumb, REST, i18n)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.website.admin.js.module-name | ucf'), "Rbs/Website"]
		]);

		FormsManager.initResource($scope, 'Rbs_Website_StaticPage').then(function (document)
		{
			Editor.initScope($scope);
			$scope.original = document;

			// Load PageTemplate
			if (document.pageTemplate)
			{
				REST.resource(document.pageTemplate).then(function (template)
				{
					$scope.pageTemplate = { "html": template.htmlForBackoffice, "data": template.editableContent };
				});
			}
			else
			{
				throw new Error("Page " + document.id + " does not have a valid PageTemplate.");
			}
		});

		// This is for the "undo" dropdown menu:
		// Each item automatically activates its previous siblings.
		// FIXME Move this in a directive: controllers should never deal with the DOM.
		$('[data-role=undo-menu]').on('mouseenter', 'li', function ()
		{
			$(this).siblings().removeClass('active');
			$(this).prevAll().addClass('active');
		});

	}

	ContentsFormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Editor', 'RbsChange.Breadcrumb', 'RbsChange.REST', 'RbsChange.i18n'];
	app.controller('Rbs_Website_StaticPage_ContentsFormController', ContentsFormController);

	// FIXME
	app.controller('Rbs_Website_FunctionalPage_ContentsFormController', ContentsFormController);
})();