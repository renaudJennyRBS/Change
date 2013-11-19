(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsLinesEditor', ['RbsChange.REST', 'RbsChange.Utils', '$timeout', rbsLinesEditorDirective]);

	function rbsLinesEditorDirective (REST, Utils, $timeout) {
		return {
			restrict : 'E',
			scope : {linesData: "="},
			templateUrl : "Document/Rbs/Order/Order/linesEditor.twig",

			link : function (scope, elm, attrs) {

				scope.newQuantity = 1;

				scope.$watch('linesData', function (value) {
					if (angular.isArray(value))
					{
						scope.lines = value;
					}
					else
					{
						scope.lines  = []
					}
				}, true);

				scope.addProduct = function(newProduct, newQuantity) {
					console.log(newProduct, newQuantity);
				};

				scope.removeLine = function(index){
					var old = scope.lines;
					var newLines = [];

					for (var i = 0; i < old.length; i++)
					{
						if (index != i)
						{
							newLines.push(old[i]);
						}
					}
					scope.linesData = newLines;
				};


			}
		};
	}
})();