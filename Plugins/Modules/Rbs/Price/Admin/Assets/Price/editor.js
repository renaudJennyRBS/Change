(function ()
{
	"use strict";

	function Editor(Editor, REST)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Price/Price/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm, function(){
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


				});

				scope.$watch('document.shop', function(newValue, oldValue){
					if (!angular.isUndefined(newValue))
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							scope.document.shop = newValue.id;
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
							})
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

				scope.activationNow = function(){
					scope.document.startActivation = moment().toDate();
				};

				scope.activationTomorrow = function(){
					scope.document.startActivation = moment().startOf('d').add('d', 1).toDate();
				};

				scope.activationNextMonday = function(){
					scope.document.startActivation = moment().add('w', 1).startOf('w').startOf('d').toDate();
				};

				scope.activationNextMonth = function(){
					scope.document.startActivation = moment().add('M', 1).startOf('M').startOf('d').toDate();
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

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.REST'];
	angular.module('RbsChange').directive('editorRbsPricePrice', Editor);
})();