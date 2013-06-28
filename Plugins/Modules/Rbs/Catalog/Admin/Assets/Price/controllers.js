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
	 * @param REST
	 * @param $location
	 * @constructor
	 */
	function FormController($scope, Breadcrumb, FormsManager, i18n, REST, $location)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.catalog.admin.js.module-name | ucf'), "Rbs/Catalog"],
			[i18n.trans('m.rbs.catalog.admin.js.product-list | ucf'), "Rbs/Catalog/Product"]
		]);
		FormsManager.initResource($scope, 'Rbs_Catalog_Price').then(function (price) {
			var productId = (!price.isNew()) ? price.product.id : $location.search().productId;
			REST.resource(productId).then(function (product) {
				Breadcrumb.setPath([product]);
			});
		});
	}

	FormController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.FormsManager', 'RbsChange.i18n', 'RbsChange.REST', '$location'];
	app.controller('Rbs_Catalog_Price_FormController', FormController);
})();