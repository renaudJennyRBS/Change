(function () {

	"use strict";

	function rbsOrderOrderEditor (Utils, REST)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Order/Order/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				var extend = {

					articleCount : 0,
					showNewLineUI : false,
					loadingProductInfo : false,
					removedLines : [],

					addNewLines : function ()
					{
						extend.loadingProductInfo = true;
						REST.call(
							REST.getBaseUrl('rbs/order/productPriceInfo'),
							{
								'products' : Utils.toIds(scope.document.newLineProducts),
								'webStore' : scope.document.webStoreId,
								'billingArea' : scope.document.billingAreaId,
								'zone' : scope.document.zone
							}
						).then(function (products) {
								extend.loadingProductInfo = false;
								angular.forEach(products, function (product) {
									scope.document.linesData.push(makeOrderLine(
										scope.document.linesData.length + 1,
										product,
										1
									));
								});
								scope.document.newLineProducts = undefined;
							});
					},

					removeLines : function (lines)
					{
						angular.forEach(lines, function (line) {
							scope.document.linesData.splice(line.number-1, 1);
							line.selected = false;
							extend.removedLines.push(line);
							updateLines();
						});
					},

					restoreRemovedLine : function (lineIndex)
					{
						scope.document.linesData.push(extend.removedLines[lineIndex]);
						extend.removedLines.splice(lineIndex, 1);
						updateLines();
					},

					trashRemovedLine : function (lineIndex)
					{
						extend.removedLines.splice(lineIndex, 1);
					}

				};

				scope.getProductsBySku = function (query)
				{
					console.log(query);
					return ['toto'];
				};


				function makeOrderLine (number, product, quantity)
				{
					return {
						designation : product.label,
						quantity : quantity || 1,
						items : [{
							codeSKU : product.boInfo.sku.code,
							quantity : quantity || 1,
							priceValue : product.boInfo.price.boValue
						}],
						options : {
							lineNumber : number,
							visual : product.adminthumbnail
						}
					};
				}


				function updateLines ()
				{
					extend.articleCount = 0;
					for (var i=0 ; i < scope.document.linesData.length ; i++) {
						scope.document.linesData[i].number = i + 1;
						extend.articleCount += scope.document.linesData[i].quantity;
					}
				}

				scope.extend = extend;


				scope.onReady = function ()
				{
					extend.showNewLineUI = scope.document.isNew();
					if (! scope.document.linesData) {
						scope.document.linesData = [];
					}
				};


				// This watches for modifications in the lines, made by the user, such as quantity for each line.
				scope.$watch('document.linesData', function (lines, old) {
					if (scope.document && lines !== old) {
						scope.document.amountWithTax = 0;
						extend.articleCount = 0;
						for (var i=0 ; i < lines.length ; i++) {
							extend.articleCount += lines[i].quantity;
							scope.document.amountWithTax += lines[i].quantity * lines[i].items[0].priceValue;
						}
					}
				}, true);

				scope.address = {};

				// This watches for modifications in the address doc in order to fill the address form
				scope.$watch('address.doc', function (addressDoc, old) {
					if(angular.isObject(addressDoc)){
						// must reset addressField in order to trigger fieldValues generation
						if(angular.isObject(scope.document.contextData)){
							scope.document.contextData.addressFields = null;
						}
						REST.resource(addressDoc.model, addressDoc.id).then(scope.populateAddressFields);
					}
				}, true);

				scope.populateAddressFields = function(addressDoc) {
					if(angular.isObject(addressDoc)){
						var addressFields = addressDoc.addressFields;
						if(angular.isObject(addressFields)){
							if(!angular.isObject(scope.document.contextData)){
								scope.document.contextData = {};
							}
							scope.document.contextData.addressFields = addressFields.id;
							scope.document.addressData = addressDoc.fieldValues;
						}
					}
				};

				editorCtrl.init('Rbs_Order_Order');
			}
		};
	}

	rbsOrderOrderEditor.$inject = [ 'RbsChange.Utils', 'RbsChange.REST' ];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderOrder', rbsOrderOrderEditor);

})();