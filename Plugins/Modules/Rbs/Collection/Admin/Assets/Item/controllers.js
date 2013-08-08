(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.collection.admin.js.module-name | ucf'), "Rbs/Collection/Item"]
		]);
		FormsManager.initResource($scope, 'Rbs_Collection_Item');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_Collection_Item_FormController', FormController);
})();