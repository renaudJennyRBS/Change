(function ()
{
	"use strict";

	function Editor(Editor)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Price/Tax/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm, function(){
					if (!scope.document.data)
					{
						scope.document.data = {c:[], z:[], r:[]};
					}
				});

				scope.makeDefaultZone = function(defaultIndex){
					// Order the zone list
					var spliced = scope.document.data.z.splice(defaultIndex, 1);
					scope.document.data.z.unshift(spliced[0]);

					// Order the rates
					angular.forEach(scope.document.data.r, function(value){
						var spliced = value.splice(defaultIndex, 1);
						value.unshift(spliced[0]);
					});
				};

				scope.deleteZone = function(defaultIndex){
					scope.document.data.z.splice(defaultIndex, 1);
					angular.forEach(scope.document.data.r, function(value){
						value.splice(defaultIndex, 1);
					});
				};

				scope.deleteCategory = function(catIndex){
					scope.document.data.c.splice(catIndex, 1);
					scope.document.data.r.splice(catIndex, 1);
				};

				scope.addCategory = function(catName){
					var emptyRates = Array.apply(null, new Array(scope.document.data.z.length)).map(Number.prototype.valueOf,0);
					scope.document.data.c.push(catName);
					scope.document.data.r.push(emptyRates);
					scope.newCategoryName = null;
				};

				scope.addZone = function(zoneName){
					var emptyRates = Array.apply(null, new Array(scope.document.data.z.length)).map(Number.prototype.valueOf,0);
					scope.document.data.z.push(zoneName);
					angular.forEach(scope.document.data.r, function(value){
						value.push(0);
					});
					scope.newZoneName = null;
				}

				scope.$watch('newCategoryName', function(newValue, oldValue){
					if (newValue !== oldValue){
						if (scope.document.data.c.indexOf(newValue) != -1){
							scope.form.newCategoryName.$setValidity("categoryExists", false);
						} else {
							scope.form.newCategoryName.$setValidity("categoryExists", true);
						}
					}
				}, true);

				scope.$watch('newZoneName', function(newValue, oldValue){
					if (newValue !== oldValue){
						if (scope.document.data.z.indexOf(newValue) != -1){
							scope.form.newZoneName.$setValidity("zoneExists", false);
						} else {
							scope.form.newZoneName.$setValidity("zoneExists", true);
						}
					}
				}, true);
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor'];
	angular.module('RbsChange').directive('editorRbsPriceTax', Editor);
})();