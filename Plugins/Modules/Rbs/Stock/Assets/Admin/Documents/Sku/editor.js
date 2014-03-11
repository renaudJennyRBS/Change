(function ()
{
	"use strict";

	function Editor ()
	{
		return {
			restrict : 'A',
			require : '^rbsDocumentEditorBase',

			link : function (scope)
			{
				// FIXME Is this used? I could not find it in the template...
				scope.data = {
					lengthUnit : 'm'
				};
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsStockSku', Editor);

})();