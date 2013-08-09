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
			[i18n.trans('m.rbs.collection.admin.js.module-name | ucf'), "Rbs/Catalog/ProductCategorization"]
		]);
		FormsManager.initResource($scope, 'Rbs_Catalog_ProductCategorization');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_Catalog_ProductCategorization_FormController', FormController);
})();