(function ()
{
	"use strict";

	function Editor(REST, $routeParams, Settings)
	{
		return {
			restrict: 'EA',
			templateUrl: 'Document/Rbs/Price/Price/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',


			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onLoad = function(){
					if (angular.isArray(scope.document.taxCategories) || !angular.isObject(scope.document.taxCategories))
					{
						scope.document.taxCategories = {};
					}

					if (scope.document.isNew()) {
						scope.document.priority = 25;
					}
				};

				scope.onReady = function(){

					if (!scope.document.product && $routeParams.productId)
					{
						REST.resource('Rbs_Catalog_Product', $routeParams.productId).then(function(product){
							scope.document.sku = product.sku;
						});
					}

					scope.discount = false;

					if (scope.document.isNew() && $routeParams.basePriceId)
					{
						REST.resource('Rbs_Price_Price', $routeParams.basePriceId).then(function(price){

							scope.document.sku = price.sku;
							scope.document.webStore = price.webStore;
							scope.document.billingArea = price.billingArea;
							scope.document.taxCategories = price.taxCategories;
							scope.document.basePrice = price;
							scope.discount = true;

							REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id:price.billingArea.id}).then(function(res){
								scope.taxInfo = res;
							});

						});
					}

					if (scope.document.basePrice)
					{
						scope.discount = true;
					}

					if (scope.document.webStore)
					{
						if (angular.isObject(scope.document.webStore))
						{
							scope.webStoreId = scope.document.webStore.id;
						}
						else
						{
							scope.webStoreId = scope.document.webStore;
						}
					}

					if (scope.document.startActivation && scope.document.endActivation)
					{
						var startAct = moment(scope.document.startActivation);
						var endAct = moment(scope.document.endActivation);

						if (endAct.diff(startAct, 'weeks', true) == 1)
						{
							scope.activationOffsetClass = {"1w": "active", "2w" : null, "1M": null};
						}
						else if (endAct.diff(startAct, 'weeks', true) == 2)
						{
							scope.activationOffsetClass = {"1w": null, "2w" : "active", "1M": null};

						}
						else if (endAct.diff(startAct, 'months', true) == 1)
						{
							scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": "active"};
						}
						else
						{
							scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": null};
						}
					}
					else
					{
						scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": null};
					}

				};

				scope.webStoreId = null;
				scope.billingArea = null;
				scope.taxInfo = null;

				scope.$watch('document.webStore', function(newValue, oldValue){
					if (newValue)
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							scope.webStoreId = newValue.id;
						}
						else
						{
							scope.webStoreId = newValue;
						}
					}
					else
					{
						scope.webStoreId = null;
					}

				});

				scope.$watch('document.billingArea', function(newValue, oldValue){
					if (newValue)
					{
						if (!scope.document.taxCategories)
						{
							scope.document.taxCategories = {};
						}

						var billingAreaId = (angular.isObject(newValue)) ? newValue.id : newValue;
						REST.resource('Rbs_Price_BillingArea', billingAreaId).then(function(res){
							scope.billingArea = res;
						});
					}
					else
					{
						scope.billingArea = null;
						scope.taxInfo = null;
					}
				});

				scope.$watch('billingArea', function(newValue) {
					if (newValue)
					{
						REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id:newValue.id}).then(function(res){
							scope.taxInfo = res;
						});
					}
				});

				editorCtrl.init('Rbs_Price_Price');

				var _timeZone = Settings.get('TimeZone');

				function now () {
					return moment.utc().tz(_timeZone);
				}

				scope.$on('Change:TimeZoneChanged', function (event, tz) {
					_timeZone = tz;
				});


				scope.activationNow = function(){
					scope.document.startActivation = now().toDate();
				};

				scope.activationTomorrow = function(){
					scope.document.startActivation = now().startOf('d').add('d', 1).toDate();
				};

				scope.activationNextMonday = function(){
					scope.document.startActivation = now().add('w', 1).startOf('w').startOf('d').toDate();
				};

				scope.activationNextMonth = function(){
					scope.document.startActivation = now().add('M', 1).startOf('M').startOf('d').toDate();
				};

				scope.$watch('document.startActivation', function(newValue, oldValue){
					if (newValue != oldValue && angular.isObject(scope.activationOffsetClass))
					{
						if (scope.activationOffsetClass['1w'])
						{
							scope.endActivationOneWeek();
						}
						else if (scope.activationOffsetClass['2w'])
						{
							scope.endActivationTwoWeeks();
						}
						else if (scope.activationOffsetClass['1M'])
						{
							scope.endActivationOneMonth();
						}
					}
				});

				scope.endActivationOneWeek = function(toggle){
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['1w'])
					{
						scope.activationOffsetClass['1w'] = null;
						return;
					}
					scope.document.endActivation = moment(scope.document.startActivation).add('w', 1).toDate();
					scope.activationOffsetClass = {"1w":"active", "2w" : null, "1M": null};
				};

				scope.endActivationTwoWeeks = function(toggle){
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['2w'])
					{
						scope.activationOffsetClass['2w'] = null;
						return;
					}
					scope.document.endActivation = moment(scope.document.startActivation).add('w', 2).toDate();
					scope.activationOffsetClass = {"1w":null, "2w" : "active", "1M": null};
				};

				scope.endActivationOneMonth = function(toggle){
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['1M'])
					{
						scope.activationOffsetClass['1M'] = null;
						return;
					}
					scope.document.endActivation = moment(scope.document.startActivation).add('M', 1).toDate();
					scope.activationOffsetClass = {"1w":null, "2w" : null, "1M": "active"};
				};

				scope.endActivationTomorrow = function(){
					scope.document.endActivation = moment().endOf('d').toDate();
				};

				scope.endActivationEndOfWeek = function(){
					scope.document.endActivation = moment().endOf('w').toDate();
				};

				scope.endActivationEndOfMonth = function(){
					scope.document.endActivation = moment().endOf('M').toDate();
				};

			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams', 'RbsChange.Settings'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsPricePrice', Editor);

})();