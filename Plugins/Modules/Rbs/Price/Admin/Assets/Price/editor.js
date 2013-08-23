(function ()
{
	"use strict";

	function Editor(REST, $routeParams, Settings)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Price/Price/editor.twig',
			replace: true,
			require : 'rbsDocumentEditor',


			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function(){
					if (!scope.document.product && $routeParams.productId)
					{
						REST.resource('Rbs_Catalog_AbstractProduct', $routeParams.productId).then(function(product){
							scope.document.sku = product.sku;
						});
					}
					if (!scope.document.taxCategories)
					{
						scope.document.taxCategories = {};

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

				editorCtrl.init('Rbs_Price_Price');

				scope.$watch('document.webStore', function(newValue, oldValue){
					if (!angular.isUndefined(newValue))
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							scope.document.webStore = newValue.id;
						}
						if (!newValue)
						{
							scope.document.billingArea = null;
						}
					}
				});

				scope.$watch('document.billingArea', function(newValue, oldValue){
					if (newValue)
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							scope.document.billingArea = newValue.id;
							scope.billingArea = newValue;
						}
						else if (!angular.isObject(newValue))
						{
							REST.resource('Rbs_Price_BillingArea', newValue).then(function(res){
								scope.billingArea = res;
							});
						}

						if (!angular.isObject(scope.document.billingArea))
						{
							REST.call(REST.getBaseUrl('rbs/price/taxInfo'), {id:scope.document.billingArea}).then(function(res){
								scope.taxInfo = res;
							});
						}

					}
					else
					{
						scope.billingArea = null;
						scope.taxInfo = null;
					}
				});



				var _timeZone = Settings.get('TimeZone');

				function now () {
					console.log("using tz: ", _timeZone);
					console.log("now 1=", moment.utc());
					console.log("now 2=", moment.utc().tz(_timeZone));
					console.log("now 3=", moment.utc().tz(_timeZone).toDate());
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