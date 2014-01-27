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
				scope.userAddresses = [];
				scope.canEdit = {order: true, carrier: true};
				var refreshOrderRemainderLocked = false;

				//In remainLines, we mean lines to remain from the order
				scope.data.remainLines = [];
				//In shipmentLines, we mean lines to add to this shipment
				scope.data.shipmentLines = [];
				//In asideLines, we mean lines from remain will be not added to this shipment
				scope.data.asideLines = [];
				//In preparedLines, we mean lines already prepared (you can't modify this lines)
				scope.data.preparedLines = [];

				scope.onLoad = function (){
					setShipmentPromises();
				};

				scope.onReady = function ()
				{
					if (scope.isNew()){
						//pre fill fields if there is data in query url
						var query = window.location.search;
						var queryRegExp = /\?orderId=([0-9]*)(\&shippingModeId=([0-9]*))?/;
						//                            111111   22222222222222222333333
						if (queryRegExp.test(query)){
							var test = queryRegExp.exec(query);
							var orderId = test[1];
							var shippingModeId = test[3];
							setOrderByOrderId(orderId);
							if (shippingModeId){
								REST.resource('Rbs_Shipping_Mode', shippingModeId).then(function (data){
									scope.data.carrier = data;
									scope.canEdit.carrier = false;
								}, function (error){
									NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
										ErrorFormatter.format(error));
									console.error(error);
								});
							}
						}
					}
				};

				function setOrderWatch(){
					scope.$watch('data.order', function (order){
						if (order){
							scope.document.orderId = order.id;
						}
						else {
							scope.document.orderId = 0;
						}
						refreshOrderInformation();
					});
				}

				function setShippingModeWatch(){
					scope.$watch('data.carrier', function (carrier){
						if (angular.isObject(carrier) && carrier.id > 0) {
							REST.ensureLoaded(carrier).then(function (data){
								scope.document.shippingModeCode = data.code;
								refreshOrderInformation();
							}, function (error){
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
									ErrorFormatter.format(error));
								console.error(error);
							});
						}
						else {
							scope.document.shippingModeCode = '';
							refreshOrderInformation();
						}
					});
				}

				function refreshOrderInformation(){
					resetLinesInformation();
					dispatchDocumentDataLines().then(function (){
						if (scope.data.order && (scope.data.carrier && scope.data.carrier.id > 0)){
							refreshCode();
							refreshOrderRemainder();
						}
					});
				}

				function setShipmentPromises(){
					var promises = [];

					if (scope.document.shippingModeCode){
						promises.push(setCarrierByShippingModeCode(scope.document.shippingModeCode));
					}
					else {
						setShippingModeWatch();
					}

					if (scope.document.orderId){
						promises.push(setOrderByOrderId(scope.document.orderId));
					}
					else {
						setOrderWatch();
					}

					if (promises.length === 2){
						$q.all(promises).then(refreshOrderInformation);
					}
				}

				scope.preSave = function(document){
					var q = $q.defer();
					if (angular.isObject(document.address.address) && !isObjectEmpty(document.address.address)) {
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
						//set empty address
						document.address = {address: {}, addressFields: 0};
						q.resolve();
					}
					return q.promise;
				};

				function setCarrierByShippingModeCode(shippingModeCode){
					var p = REST.query(Query.simpleQuery('Rbs_Shipping_Mode', 'code', shippingModeCode));
					p.then(function (data){
						if (data.resources.length === 1) {
							scope.data.carrier = data.resources[0];
							scope.canEdit.carrier = false;
						}
						else {
							NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_shipping_mode_not_found_title | ucf'),
								i18n.trans('m.rbs.order.adminjs.shipment_shipping_mode_not_found | ucf', {CODE: shippingModeCode}));
						}
					}, function (error){
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_shipping_mode | ucf'),
							ErrorFormatter.format(error));
						console.error(error);
					});
				}

				function setOrderByOrderId(orderId){
					var p = REST.resource('Rbs_Order_Order', orderId);
					p.then(function (data){
						scope.data.order = data;
						populateAddressList(data.ownerId);
						scope.canEdit.order = false;
					}, function (error){
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_order | ucf'),
							ErrorFormatter.format(error));
						console.error(error);
					});
					return p;
				}

				function resetLinesInformation(){
					scope.data.remainLines = [];
					scope.data.shipmentLines = [];
					scope.data.asideLines = [];
				}

				function refreshCode(){
					if (!scope.document.code && scope.data.order && scope.data.carrier){
						scope.document.code = 'E-' + scope.data.order.label + '-' + scope.document.shippingModeCode + '-?';
					}
				}

				function refreshOrderRemainder(){
					//because this is an asynchronous function containing another asynchronous function,
					//it needs a lock to prevent another call during processing
					//TODO prevent the user from choosing another carrier or order during processing instead of using lock
					if (!refreshOrderRemainderLocked && !scope.document.prepared){
						refreshOrderRemainderLocked = true;
						getOrderRemainder();
					}
				}

				function getOrderRemainder(){
					var p = REST.call(REST.getBaseUrl('rbs/order/orderRemainder'),{
						orderId: scope.document.orderId,
						shippingModeId: scope.data.carrier.id
					});

					p.then(function (data){
						scope.data.remainLines = data.remainLines;
						if (!scope.document.data || scope.document.data.length === 0){
							resetLinesInformation();
							setShipmentLines(data.remainLines);
						}
						else {
							//sort shipmentLines from documentData;
							dispatchOrderLines();
						}
						if (!scope.document.address || !scope.document.address.address || !scope.document.address.addressFields)
						{
							scope.document.address = {
								address: data.address.address,
								addressFields: data.address.addressFields
							};
						}
					}, function (error){
						refreshOrderRemainderLocked = false;
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_request_remainder | ucf'),
							ErrorFormatter.format(error));
						console.error(error);
					});

					return p;
				}

				function setShipmentLines(remainLines){
					angular.forEach(remainLines, function (remainderLine){
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

				function dispatchOrderLines(){
					//get shipment lines
					//sort them in three arrays :
					//  shipment lines : line already in shipment and present in order remainder
					//  aside lines : line not in shipment but in order remainder
					//  new lines : line in shipment but not in order remainder (that mean new lines)

					angular.forEach(scope.data.remainLines, function (remainderLine){
						var line = getShipmentLineFromSKU(remainderLine.SKU);
						if (line){
							//FIXME += or = ?
							line.quantity = remainderLine.quantity;
							line.newLine = false;
						}
						else {
							//push it in aside lines
							scope.data.asideLines.push(remainderLine);
						}
					});
				}



				function dispatchDocumentDataLines(){
					var promises = [];

					//FIXME because it's each line is get asynchronously this function could disordering the shipment array
					//FIXME so this will mark document as modified
					angular.forEach(scope.document.data, function (documentLine){
						if (!skuIsInArray(documentLine.SKU, scope.data.remainLines)){
							//get product from SKU
							promises.push(loadProductFromSKU(documentLine.SKU, documentLine.quantity));
						}
					});

					return $q.all(promises);
				}

				function loadProductFromSKU(sku, quantity){
					var p = REST.query(getProductFromSKUId(sku), {column: ['sku']});

					p.then(function (data){
						if (!skuIsInArray(sku, scope.data.shipmentLines)){
							if (data.resources.length === 1) {
								//push new line in shipment lines
								scope.data.shipmentLines.push({
									designation: data.resources[0].label,
									codeSKU: data.resources[0].sku.code,
									allowQuantitySplit: true,
									quantityToShip: quantity,
									SKU: sku,
									newLine: true
								});
								refreshOrderRemainderLocked = false;
							}
							else {
								refreshOrderRemainderLocked = false;
								NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_sku_not_found_title | ucf'),
									i18n.trans('m.rbs.order.adminjs.shipment_sku_not_found | ucf', {SKU:  documentLine.codeSKU}));
							}
						}
					}, function (error){
						refreshOrderRemainderLocked = false;
						NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_query_sku | ucf'),
							ErrorFormatter.format(error));
					});

					return p;
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
								if (!skuIsInArray(line.SKU, scope.data.remainLines) && !skuIsInArray(line.SKU, scope.data.shipmentLines)){
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

				scope.$watch('data.selectAllShipmentLines', function (value){
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
					if (angular.isDefined(shipmentLines)){
						dispatchShipmentLinesInDocumentData(shipmentLines);
					}
				}, true);

				function dispatchShipmentLinesInDocumentData(shipmentLines){
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
						itemCount += shipmentLine.quantityToShip;
					});
					scope.document.itemCount = itemCount;
				}

				scope.validatePreparation = function ($event){
					var message = i18n.trans('m.rbs.order.adminjs.shipment_confirm_validate_preparation | ucf');
					if (!scope.document.address.address || isObjectEmpty(scope.document.address.address)){
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

				function populateAddressList(ownerId){
					if(!ownerId){
						scope.userAddresses = [];
						return;
					}

					var query = {
						'model': 'Rbs_Geo_Address',
						'where': {
							'and': [
								{
									'op': 'eq',
									'lexp': {
										'property': 'ownerId'
									},
									'rexp': {
										'value': ownerId
									}
								}
							]
						}
					};

					REST.query(query, {'column': ['label', 'addressFields', 'fieldValues']}).then(function (data){
						scope.userAddresses = data.resources;
					});
				}

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

				function skuIsInArray(sku, array){
					var lineIsInArray = false;
					if (sku){
						angular.forEach(array, function (arrayLine){
							if (arrayLine.SKU){
								if (arrayLine.SKU == sku){
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

				function getShipmentLineFromSKU(sku){
					var i;
					if (!angular.isArray(scope.data.shipmentLines)){
						return null;
					}
					for (i = 0; i < scope.data.shipmentLines.length; i++){
						if (scope.data.shipmentLines[i].SKU === sku){
							return scope.data.shipmentLines[i];
						}
					}
					return null;
				}

				function isObjectEmpty(object){
					if (angular.isArray(object)){
						return object.length === 0;
					}
					if (angular.isObject(object)){
						return Object.getOwnPropertyNames(object).length === 0;
					}
					return false;
				}

				editorCtrl.init('Rbs_Order_Shipment');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', 'RbsChange.ArrayUtils', '$q', 'RbsChange.Query', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Dialog', '$timeout', '$http'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsOrderShipment', Editor);
})();