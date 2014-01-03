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
	 * @param Dialog
	 * @param $timeout
	 * @param $http
	 * @constructor
	 */
	function Editor(REST, ArrayUtils, $q, Query, i18n, NotificationCenter, ErrorFormatter, Dialog, $timeout, $http)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Order/Shipment/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.data = {};
				scope.orderRemainderAlreadyDefined = false;
				var refreshOrderRemainderLocked = false;

				//In remainLines, we mean lines to remain from the order
				scope.data.remainLines = [];
				//In shipmentLines, we mean lines to add to this shipment
				scope.data.shipmentLines = [];
				//In asideLines, we mean lines from remain will be not added to this shipment
				scope.data.asideLines = [];
				//In preparedLines, we mean lines already prepared (you can't modify this lines)
				scope.data.preparedLines = [];

				scope.onReady = function ()
				{
					if (scope.isNew()){
						//pre fill fields if there is data in query url
						var query = window.location.search;
						var queryRegExp = /\?orderId=([0-9]*)\&shippingModeId=([0-9]*)/;
						if (queryRegExp.test(query)){
							var test = queryRegExp.exec(query);
							var orderId = test[1];
							var shippingModeId = test[2];
							setOrderByOrderId(orderId);
							REST.resource('Rbs_Shipping_Mode', shippingModeId).then(function (data){
								scope.data.carrier = data;
							}, function (error){
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							});
						}
					}
					//load shipping mode if the document is not new and if a shipping mode code is defined and not null
					if (!scope.isNew() && scope.document.shippingModeCode) {
						REST.query(Query.simpleQuery('Rbs_Shipping_Mode', 'code', scope.document.shippingModeCode))
							.then(function (data){
								if (data.resources.length === 1) {
									scope.data.carrier = data.resources[0];
								}
								else {
									NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_shipping_mode_not_found_title | ucf'),
										i18n.trans('m.rbs.order.adminjs.shipment_shipping_mode_not_found | ucf', {CODE: scope.document.shippingModeCode}));
								}
							}, function (error){
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							});
					}
					//load order if the document is not new and if an order id is defined and not null
					if (!scope.isNew() && scope.document.orderId){
						setOrderByOrderId(scope.document.orderId);
					}
				};

				scope.preSave = function(document){
					var q = $q.defer();
					if (angular.isObject(document.address.address)) {
						$http.post(REST.getBaseUrl('Rbs/Geo/AddressLines'),{
							address: document.address.address,
							addressFieldsId: document.address.addressFields
						}).success(function (data){
								document.address.lines = data;
								q.resolve(data);
							}).error(function (dataError){
								q.reject(dataError);
							});
					}
					else {
						q.resolve();
					}
					return q.promise;
				};

				function setOrderByOrderId(orderId){
					REST.resource('Rbs_Order_Order', orderId).then(function (data){
						scope.data.order = data;
					}, function (error){
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_order | ucf'),
							ErrorFormatter.format(error));
						console.error(error);
					});
				}

				scope.$watch('data.carrier', function (carrier){
					if (angular.isDefined(carrier)){
						REST.resource(carrier.model, carrier.id, carrier.LCID).then(function (data){
							scope.document.shippingModeCode = data.code;
							if (scope.document.orderId){
								refreshCode();
								refreshOrderRemainder();
							}
						}, function (error){
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
					}
				});

				scope.$watch('data.order', function (order){
					if (angular.isDefined(order)){
						scope.data.order = order;
						scope.document.orderId = order.id;
						if (angular.isDefined(scope.data.carrier) && scope.data.carrier.id){
							refreshOrderRemainder();
							refreshCode();
						}
					}
				});

				function refreshCode(){
					if (!scope.document.code && scope.data.order && scope.document.shippingModeCode){
						scope.document.code = 'E-' + scope.data.order.label + '-' + scope.document.shippingModeCode + '-?';
					}
				}

				function refreshOrderRemainder(){
					//because this is an asynchronous function containing another asynchronous function,
					//it needs a lock to prevent another call during processing
					if (!refreshOrderRemainderLocked && !scope.document.prepared)
					{
						refreshOrderRemainderLocked = true;
						scope.data.remainLines = [];
						scope.data.shipmentLines = [];
						scope.data.asideLines = [];
						//if we already have orderId and shipping mode code, load the order remainder
						REST.call(REST.getBaseUrl('rbs/order/orderRemainder'),{
							orderId: scope.document.orderId,
							shippingModeId: scope.data.carrier.id
						}).then(function (data){
								scope.data.remainLines = data.remainLines;
								if (angular.isUndefined(scope.document.data) || scope.document.data.length === 0){
									angular.forEach(data.remainLines, function (remainderLine){
										scope.data.shipmentLines.push({
											designation: remainderLine.designation,
											quantity: remainderLine.quantity,
											codeSKU: remainderLine.codeSKU,
											allowQuantitySplit: remainderLine.allowQuantitySplit,
											SKU: remainderLine.SKU
										});
									});
									refreshOrderRemainderLocked = false;
								}
								else {
									sortShipmentLinesFromDocumentData();
								}
								if (!scope.document.address || !scope.document.address.address || !scope.document.address.addressFields)
								{
									scope.document.address = {
										address: data.address.address,
										addressFields: data.address.addressFields
									}
								}
							}, function (error){
								refreshOrderRemainderLocked = false;
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_request_remainder | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							});
					}
				}

				function sortShipmentLinesFromDocumentData(){
					//get shipment lines from document data
					//sort them in three arrays :
					//  shipment lines : line already in shipment and present in order remainder
					//  aside lines : line not in shipment but in order remainder
					//  new lines : line in shipment but not in order remainder (that mean new lines)

					//first take them from order lines
					angular.forEach(scope.data.remainLines, function (remainderLine){
						var remainderLineInDocumentData = false;
						angular.forEach(scope.document.data, function (documentLine){
							if (documentLine.SKU == remainderLine.SKU){
								remainderLineInDocumentData = true;
								remainderLine.quantityToShip = documentLine.quantity;
								//push it in shipment lines
								scope.data.shipmentLines.push(remainderLine);
							}
						});
						if (!remainderLineInDocumentData){
							//push it in aside lines
							scope.data.asideLines.push(remainderLine);
						}
					});
					//and find new shipment lines
					if (scope.data.shipmentLines.length < scope.document.data.length) {
						angular.forEach(scope.document.data, function (documentLine){
							if (!lineIsInArray(documentLine, scope.data.remainLines)){
								//get product from SKU
								REST.query(getProductFromSKUId(documentLine.SKU), {column: ['code']})
									.then(function (data){
										if (data.resources.length === 1) {
											//push new line in shipment lines
											scope.data.shipmentLines.push({
												designation: data.resources[0].label,
												codeSKU: data.resources[0].code,
												quantity: '',
												allowQuantitySplit: true,
												quantityToShip: documentLine.quantity,
												SKU: documentLine.SKU,
												newLine: true
											});
											refreshOrderRemainderLocked = false;
										}
										else {
											refreshOrderRemainderLocked = false;
											NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_sku_not_found_title | ucf'),
												i18n.trans('m.rbs.order.adminjs.shipment_sku_not_found | ucf', {SKU:  documentLine.codeSKU}));
										}
									}, function (error){
										refreshOrderRemainderLocked = false;
										NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_sku | ucf'),
											ErrorFormatter.format(error));
									})
							}
						});
					}
					else {
						refreshOrderRemainderLocked = false;
					}

				}

				scope.$watch('document.prepared', function (prepared){
					if (prepared){
						//load shipment lines but just for display
						angular.forEach(scope.document.data, function (shipmentLine){
							REST.query(Query.simpleQuery('Rbs_Stock_Sku', 'id', shipmentLine.SKU)).then(function (data){
								if (data.resources.length === 1) {
									scope.data.preparedLines.push({
										label: shipmentLine.label,
										quantity: shipmentLine.quantity,
										codeSKU: data.resources[0].code
									});
								}
								else {
									NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_sku_not_found_title | ucf'),
										i18n.trans('m.rbs.order.adminjs.shipment_sku_not_found | ucf', {SKU: shipmentLine.SKU}));
								}
							}, function (error){
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_sku | ucf'),
									ErrorFormatter.format(error));
								console.error(error)
							});
						});
					}
				});

				scope.moveToShipmentLines = function (asideLine){
					scope.data.shipmentLines.push(asideLine);
					ArrayUtils.removeValue(scope.data.asideLines, asideLine);
				};

				scope.addNewLines = function (){
					angular.forEach(scope.data.newLines, function (article){
						//a query to get SKU from product
						REST.query(getSKUFromProductIdQuery(article.id)).then(function (data){
							if (data.resources.length === 1) {
								//check first if the article is not already in order or shipment lines,
								//in this cases, don't add the article
								//because article is not properly a line, we have to made it (the compare function only need SKU id)
								var line = {SKU: data.resources[0].id};
								if (!lineIsInArray(line, scope.data.remainLines) && !lineIsInArray(line, scope.data.shipmentLines)){
									scope.data.shipmentLines.push({
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
								NotificationCenter.warning(i18n.trans('m.rbs.order.adminjs.shipment_product_not_found_title | ucf'),
									i18n.trans('m.rbs.order.adminjs.shipment_product_not_found | ucf', {PRODUCTID:  article.id}));
							}
						}, function (error){
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_sku_from_product_id | ucf'),
								ErrorFormatter.format(error));
							console.error(error);
						});
					});
				};

				scope.$watch('selectAllShipmentLines', function (value){
					angular.forEach(scope.data.shipmentLines, function (shipmentLine){
						shipmentLine.selected = value;
					});
				});

				scope.removeLines = function (){
					var shipmentLinesToRemove = [];
					angular.forEach(scope.data.shipmentLines, function (shipmentLine){
						if (shipmentLine.selected){
							shipmentLinesToRemove.push(shipmentLine);
							if (!shipmentLine.newLine){
								scope.data.asideLines.push(shipmentLine);
							}
						}
					});
					ArrayUtils.removeArray(scope.data.shipmentLines, shipmentLinesToRemove);
				};

				scope.$watch('data.shipmentLines', function (shipmentLines){
					scope.data.removeLinesDisabled = true;
					scope.data.quantityToShipExceeded = false;
					scope.data.hasNewLines = false;
					scope.document.data = [];
					var itemCount = 0;

					angular.forEach(shipmentLines, function (shipmentLine){
						if (shipmentLine.selected){
							scope.data.removeLinesDisabled = false;
						}
						//find if a shipping quantity exceeds the remain quantity
						if (shipmentLine.quantity && shipmentLine.quantityToShip > shipmentLine.quantity){
							scope.data.quantityToShipExceeded = true;
						}
						if (shipmentLine.newLine){
							scope.data.hasNewLines = true;
						}
						scope.document.data.push({
							label: shipmentLine.designation,
							quantity: shipmentLine.quantityToShip,
							SKU: shipmentLine.SKU
						});
						if (shipmentLine.selected){
							scope.removeLinesDisabled = false;
						}
						else {
							scope.selectAllShipmentLines = false;
						}
						itemCount += shipmentLine.quantityToShip;
					});
					scope.document.itemCount = itemCount;
				}, true);

				scope.validatePreparation = function ($event){
					var message = i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation | ucf');
					if (!angular.isObject(scope.document.address.address)){
						message += '<br><strong>' +
							i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation_empty_address | ucf') +
							'</strong>';
					}
					Dialog.confirmLocal($event.target,
						i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation_title | ucf'),
						message,
						{placement: 'top'}
					).then(function (){
							//OK
							scope.document.prepared = true;
							//wait for angular to check changes
							$timeout(function (){
								scope.submit();
							});
					}, function (){
							//NOK
					});
				};

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
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_no_sku_to_compare | ucf'));
							}
						});
					}
					else {
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_no_sku_to_compare | ucf'));
					}
					return lineIsInArray;
				}

				editorCtrl.init('Rbs_Order_Shipment');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', 'RbsChange.ArrayUtils', '$q', 'RbsChange.Query', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Dialog', '$timeout', '$http'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderShipment', Editor);
})();