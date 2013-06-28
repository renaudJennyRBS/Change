(function ()
{
	"use strict";

	function Editor(Editor, REST)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/Price/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				scope.metadata = {};

				var callback = function () {
					var document = scope.document;
					REST.resource(document.billingArea).then(function (area) {
							REST.resource(area.currency).then(function (currency) {
									scope.metadata.currencySymbol = currency.symbol;
								}
							);

							scope.metadata.editWithTax = area.boEditWithTax;
							scope.metadata.taxCategories = area.taxesData[0].categories;
						}
					);
				}

				Editor.initScope(scope, elm, callback);
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.REST'];
	angular.module('RbsChange').directive('editorRbsCatalogPrice', Editor);
})();