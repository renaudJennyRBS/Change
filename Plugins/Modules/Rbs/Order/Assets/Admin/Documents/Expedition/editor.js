(function ()
{
	"use strict";

	/**
	 * @param REST
	 * @param ArrayUtils
	 * @param $q
	 * @param Query
	 * @param i18n
	 * @param NotificationCenter
	 * @param ErrorFormatter
	 * @constructor
	 */
	function Editor(REST, ArrayUtils, $q, Query, i18n, NotificationCenter, ErrorFormatter)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Order/Expedition/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.data = {};
				//In orderLines, we mean lines to remain from the order
				scope.data.orderLines = [];
				//In expeditionLines, we mean lines to add to this expedition
				scope.data.expeditionLines = [];
				//In remainderLines, we mean lines from remain will be not added to this expedition
				scope.data.remainderLines = [];

				scope.onReady = function ()
				{
					//TODO: all will come from request (orderId + shippingModeId)

					//load shipping mode if the document is not new and if a shipping mode code is defined and not null
					if (!scope.isNew() && angular.isDefined(scope.document.shippingModeCode) && scope.document.shippingModeCode)
					{
						REST.query(Query.simpleQuery('Rbs_Shipping_Mode', 'code', scope.document.shippingModeCode))
							.then(function (data){
								if (data.resources.length === 1) {
									scope.data.carrier = data.resources[0];
								}
								else {
									NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_shipping_mode_not_found_title | ucf'),
										i18n.trans('m.rbs.order.adminjs.expedition_shipping_mode_not_found | ucf', {CODE: scope.document.shippingModeCode}));
								}
							}, function (error){
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_invalid_query_shipping_mode | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							});
					}

					//load order data (order lines)
					//TODO: use remainder instead of all order (create a web service)
					if (scope.document.orderId){
						REST.resource('Rbs_Order_Order', scope.document.orderId).then(function (data){
							var promises = [];
							scope.data.order = data.label;
							var i = 1;
							angular.forEach(data.linesData, function (line){
								//search if SKU allow quantity split or not
								var codeSKU = line.items[0].codeSKU;
								var allowQuantitySplit = true;
								var p = REST.query(Query.simpleQuery('Rbs_Stock_Sku', 'code', codeSKU), {column: ['allowQuantitySplit']})
									.then(function (data){
										if (data.resources.length === 1) {
											scope.data.orderLines.push({
													lineNumber: i++,
													designation: line.designation,
													codeSKU: codeSKU,
													quantity: line.quantity,
													allowQuantitySplit: data.resources[0].allowQuantitySplit,
													SKU: data.resources[0].id
												}
											);
										}
										else {
											NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_sku_not_found_title | ucf'),
												i18n.trans('m.rbs.order.adminjs.expedition_sku_not_found | ucf', {SKU: codeSKU}));
										}
									}, function (error){
										NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_invalid_query_sku | ucf'),
											ErrorFormatter.format(error));
										console.error(error);
									});
								promises.push(p);
							});
							$q.all(promises).then(function (){
								if (scope.isNew()){
									//Do a copy from the original remainder
									scope.data.expeditionLines = angular.copy(scope.data.orderLines);
								}
								else {
									//get expedition lines from document data
									//sort them in three arrays :
									//  expedition lines : line already in expedition and present in order remainder
									//  remainder lines : line not in expedition but in order remainder
									//  new lines : line in expedition but not in order remainder (that mean new lines)

									//first take them from order lines
									angular.forEach(scope.data.orderLines, function (orderLine){
										//TODO: line number not correct
										var orderLineInDocumentData = false;
										angular.forEach(scope.document.data, function (documentLine){
											if (documentLine.SKU == orderLine.SKU){
												orderLineInDocumentData = true;
												orderLine.quantityToShip = documentLine.quantity;
												orderLine.lineNumber = documentLine.lineNumber;
												//push it in expedition lines
												scope.data.expeditionLines.push(orderLine);
											}
										});
										if (!orderLineInDocumentData){
											//push it in remainder lines
											scope.data.remainderLines.push(orderLine);
										}
									});
									//and find new expedition lines
									if (scope.data.expeditionLines.length < scope.document.data.length)
									{
										angular.forEach(scope.document.data, function (documentLine){
											if (!lineIsInArray(documentLine, scope.data.orderLines)){
												//get product from SKU
												REST.query(getProductFromSKUId(documentLine.SKU), {column: ['code']})
													.then(function (data){
														if (data.resources.length === 1) {
															//push new line in expedition lines
															scope.data.expeditionLines.push({
																lineNumber: documentLine.lineNumber,
																designation: data.resources[0].label,
																codeSKU: data.resources[0].code,
																quantity: '',
																allowQuantitySplit: true,
																quantityToShip: documentLine.quantity,
																SKU: documentLine.SKU,
																newLine: true
															});
														}
														else {
															NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_sku_not_found_title | ucf'),
																i18n.trans('m.rbs.order.adminjs.expedition_sku_not_found | ucf', {SKU:  documentLine.codeSKU}));
														}
													}, function (error){
														NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_invalid_query_sku | ucf'),
															ErrorFormatter.format(error));
													})
											}
										});
									}
								}
							}, function (error){
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_invalid_query_products | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							})
						}, function (error){
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_invalid_query_order | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
					}
				};

				scope.$watch('data.carrier', function (carrier){
					if (angular.isDefined(carrier))
					{
						REST.resource(carrier.model, carrier.id, carrier.LCID).then(function (data){
							scope.document.shippingModeCode = data.code;
						}, function (error){
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_invalid_query_shipping_mode | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
					}
				});

				scope.moveToExpeditionLines = function (remainderLine){
					remainderLine.lineNumber = scope.data.expeditionLines.length + 1;
					scope.data.expeditionLines.push(remainderLine);
					ArrayUtils.removeValue(scope.data.remainderLines, remainderLine);
				};

				scope.addNewLines = function (){
					var lineNumber = scope.data.expeditionLines.length;
					angular.forEach(scope.data.newLines, function (article){
						//a query to get SKU from product
						REST.query(getSKUFromProductIdQuery(article.id)).then(function (data){
							if (data.resources.length === 1) {
								//check first if the article is not already in order or expedition lines,
								//in this cases, don't add the article
								//because article is not properly a line, we have to made it (the compare function only need SKU id)
								var line = {SKU: data.resources[0].id};
								if (!lineIsInArray(line, scope.data.orderLines) && !lineIsInArray(line, scope.data.expeditionLines)){
									scope.data.expeditionLines.push({
										lineNumber: ++lineNumber,
										designation: article.label,
										codeSKU: data.resources[0].code,
										quantity: '',
										allowQuantitySplit: true,
										SKU: data.resources[0].id,
										newLine: true
									});
								}
							}
							else {
								NotificationCenter.warning(i18n.trans('m.rbs.order.adminjs.expedition_product_not_found_title | ucf'),
									i18n.trans('m.rbs.order.adminjs.expedition_product_not_found | ucf', {PRODUCTID:  article.id}));
							}
						}, function (error){
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_invalid_query_sku_from_product_id | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
					});
				};

				scope.$watch('selectAllExpeditionLines', function (value){
					angular.forEach(scope.data.expeditionLines, function (expeditionLine){
						expeditionLine.selected = value;
					});
				});

				scope.removeLines = function (){
					var expeditionLinesToRemove = [];
					var lineNumber = 1;
					angular.forEach(scope.data.expeditionLines, function (expeditionLine){
						if (expeditionLine.selected){
							expeditionLinesToRemove.push(expeditionLine);
							if (!expeditionLine.newLine){
								scope.data.remainderLines.push(expeditionLine);
							}
						}
						else {
							expeditionLine.lineNumber = lineNumber++;
						}
					});
					ArrayUtils.removeArray(scope.data.expeditionLines, expeditionLinesToRemove);
				};

				scope.$watch('data.expeditionLines', function (expeditionLines){
					scope.data.removeLinesDisabled = true;
					scope.data.quantityToShipExceeded = false;
					scope.data.hasNewLines = false;
					scope.document.data = [];
					var itemCount = 0;

					angular.forEach(expeditionLines, function (expeditionLine){
						if (expeditionLine.selected){
							scope.data.removeLinesDisabled = false;
						}
						//find if a shipping quantity exceeds the remain quantity
						if (expeditionLine.quantity && expeditionLine.quantityToShip > expeditionLine.quantity){
							scope.data.quantityToShipExceeded = true;
						}
						if (expeditionLine.newLine){
							scope.data.hasNewLines = true;
						}
						scope.document.data.push({
							lineNumber: expeditionLine.lineNumber,
							label: expeditionLine.designation,
							quantity: expeditionLine.quantityToShip,
							SKU: expeditionLine.SKU
						});
						if (expeditionLine.selected){
							scope.removeLinesDisabled = false;
						}
						else {
							scope.selectAllExpeditionLines = false;
						}
						itemCount += expeditionLine.quantityToShip;
					});
					scope.document.itemCount = itemCount;
				}, true);

				function getSKUFromProductIdQuery(productId){
					return {
						model: 'Rbs_Stock_Sku',
						join: [
							{
								model: 'Rbs_Catalog_Product',
								name: 'product',
								property: 'sku'
							}
						],
						where: {
							and: [{
								op: 'eq',
								lexp: {
									property: 'id',
									join: 'product'
								},
								rexp: {
									value: productId,
									join: 'product'
								}
							}]
						}
					};
				}

				function getProductFromSKUId(SKUId){
					return {
						model: 'Rbs_Catalog_Product',
						join:[{
							model: 'Rbs_Stock_Sku',
							name: 'sku',
							property: 'id',
							parentProperty: 'sku'
						}],
						where: {
							and: [{
								op: 'eq',
								lexp: {
									property: 'sku'
								},
								rexp: {
									value: SKUId
								}
							}]
						}
					};
				}

				function lineIsInArray(line, array){
					var lineIsInArray = false;
					if (angular.isDefined(line.SKU) && line.SKU){
						angular.forEach(array, function (arrayLine){
							if (angular.isDefined(line.SKU) && line.SKU){
								if (arrayLine.SKU == line.SKU){
									lineIsInArray = true;
								}
							}
							else {
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_no_sku_to_compare | ucf'));
							}
						});
					}
					else {
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.expedition_no_sku_to_compare | ucf'));
					}
					return lineIsInArray;
				}

				editorCtrl.init('Rbs_Order_Expedition');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', 'RbsChange.ArrayUtils', '$q', 'RbsChange.Query', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderExpedition', Editor);
})();