(function ()
{
	"use strict";

	function Editor(Editor)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Stock/Sku/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				Editor.initScope(scope, elm);
				scope.data = {lengthUnit:'m'};
				scope.$watch('document.physicalProperties', function(newValue){
					console.log(newValue);
				}, true);
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor'];
	angular.module('RbsChange').directive('editorRbsStockSku', Editor);
})();