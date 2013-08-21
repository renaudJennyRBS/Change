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
			[i18n.trans('m.rbs.media.admin.js.module-name | ucf'), "Rbs/Media"],
			[i18n.trans('m.rbs.media.admin.js.image-list | ucf'), "Rbs/Media/Image/"]
		]);
		MainMenu.loadModuleMenu('Rbs_Media');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_Media_Image_ListController', ListController);

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
			[i18n.trans('m.rbs.media.admin.js.module-name | ucf'), "Rbs/Media"],
			[i18n.trans('m.rbs.media.admin.js.image-list | ucf'), "Rbs/Media/Image/"]
		]);
		FormsManager.initResource($scope, 'Rbs_Media_Image');
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n'];
	app.controller('Rbs_Media_Image_FormController', FormController);
})();