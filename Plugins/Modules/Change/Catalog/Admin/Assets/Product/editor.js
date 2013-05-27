(function ()
{
	"use strict";

	function Editor(Editor, DocumentList, Loading, REST)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Change/Catalog/Product/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm);

				// Configure prices DataTable...
				var PricesSection = DocumentList.initScope(scope, null, 'PricesSection');
				PricesSection.columns[1].label = "Valeur";
				PricesSection.columns.push(new DocumentList.Column('discount', "Promotion", true, 'center', '90px'));
				PricesSection.columns.push(new DocumentList.Column('beginDate', "Début de validité", true));
				PricesSection.columns.push(new DocumentList.Column('endDate', "Fin de validité", true));
				PricesSection.columns.push(new DocumentList.Column('activated', "Activé", true, 'center', '90px'));

				PricesSection.shopsLoading = true;
				Loading.start("Chargement de la liste des boutiques...");
				REST.collection('Change_Catalog_Shop').then(function (shops)
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
						Loading.start("Chargement de la liste des zones de tarification...");
						REST.resource('Change_Catalog_Shop', PricesSection.selectedShop.id)
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
						Loading.start("Chargement de la liste des prix...");
						REST.collection('Change_Catalog_BillingArea',
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

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.DocumentList', 'RbsChange.Loading', 'RbsChange.REST'];
	angular.module('RbsChange').directive('editorChangeCatalogProduct', Editor);
})();