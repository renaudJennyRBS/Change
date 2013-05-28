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
			[i18n.trans('m.change.media.admin.js.module-name | ucf'), "Change/Media"],
			[i18n.trans('m.change.media.admin.js.image-list | ucf'), "Change/Media/Image"]
		]);

		var DL = DocumentList.initScope($scope, 'Change_Media_Image');
		DL.viewMode = 'list';
		DL.sort.column = 'nodeOrder';
		DL.sort.descending = false;

		$scope.createActions = [
			{ 'label': i18n.trans('m.change.media.admin.js.image | ucf'), 'url': 'Change/Media/Image/new', 'icon': 'folder-close' }
		];

		// Configure DataTable columns
		DL.columns.push({ id: 'modificationDate', label: i18n.trans('m.change.admin.admin.js.modification-date | ucf'), sortable: true });
		DL.columns.push({ id: 'activated', label: i18n.trans('m.change.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Change_Media');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Change_Media_Image_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, Breadcrumb, FormsManager, i18n)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.change.media.admin.js.module-name | ucf'), "Change/Media"],
			[i18n.trans('m.change.media.admin.js.image-list | ucf'), "Change/Media/Image"]
		]);
		FormsManager.initResource($scope, 'Change_Media_Image');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Change_Media_Image_FormController', FormController);
})();