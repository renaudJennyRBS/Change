(function ()
{
	"use strict";

	/**
	 * @param Editor
	 * @param DocumentList
	 * @param Loading
	 * @param REST
	 * @param i18n
	 * @param Breadcrumb
	 * @constructor
	 */
	function Editor(Editor, DocumentList, Loading, REST, i18n, Breadcrumb)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/Product/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm, function () {
					if (scope.document.isNew()) {
						scope.document.category = [Breadcrumb.getCurrentNode()];
					}
				});

				scope.createActions = [
					{ 'label': i18n.trans('m.rbs.catalog.admin.js.price | ucf'), 'url': 'Rbs/Catalog/Price/new', 'icon': 'file' }
				];

				// Configure prices DataTable...
				var PricesSection = DocumentList.initScope(scope, null, 'PricesSection');
				PricesSection.columns[1].label = "Valeur";
				PricesSection.columns.push(new DocumentList.Column('discount', "Promotion", true, 'center', '90px'));
				PricesSection.columns.push(new DocumentList.Column('beginDate', "Début de validité", true));
				PricesSection.columns.push(new DocumentList.Column('endDate', "Fin de validité", true));
				PricesSection.columns.push(new DocumentList.Column('activated', "Activé", true, 'center', '90px'));

				PricesSection.shopsLoading = true;
				Loading.start(i18n.trans('m.rbs.catalog.admin.js.shop-list-loading'));
				REST.collection('Rbs_Catalog_Shop').then(function (shops)
				{
					PricesSection.shops = shops.resources;
					Loading.stop();
					PricesSection.shopsLoading = false;
				});

				scope.$watch('PricesSection.selectedShop', function ()
				{
					PricesSection.documents = [];
					PricesSection.billingAreas = [];
					if (PricesSection.selectedShop)
					{
						PricesSection.billingAreasLoading = true;
						Loading.start(i18n.trans('m.rbs.catalog.admin.js.billingarea-list-loading'));
						REST.resource('Rbs_Catalog_Shop', PricesSection.selectedShop.id)
							.then(function (shop)
							{
								PricesSection.billingAreas = shop.billingArea;
								Loading.stop();
								PricesSection.billingAreasLoading = false;
							});
					}
				});

				scope.$watch('PricesSection.selectedBillingArea', function ()
				{
					if (PricesSection.selectedShop && PricesSection.selectedBillingArea)
					{
						Loading.start(i18n.trans('m.rbs.catalog.admin.js.price-list-loading'));
						REST.collection('Rbs_Catalog_BillingArea',
							{'shopId': PricesSection.selectedShop.id, 'billingAreaId': PricesSection.selectedBillingArea.id})
							.then(function (prices)
							{
								PricesSection.prices = prices.resources;
								Loading.stop();
							});
					}
				});
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.DocumentList', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.Breadcrumb'];
	angular.module('RbsChange').directive('editorRbsCatalogProduct', Editor);
})();