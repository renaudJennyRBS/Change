(function ()
{
	"use strict";

	/**
	 * @param $http
	 * @param REST
	 * @param Utils
	 * @constructor
	 */
	function Editor($http, REST, Utils)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Seo/ModelConfiguration/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.addMetaVariable = function (meta, variable)
				{
					if (scope.document[meta])
					{
						scope.document[meta] += '{' + variable + '}';
					}
					else
					{
						scope.document[meta] = '{' + variable + '}';
					}
				};

				scope.$watch('document.modelName', function (modelName){
					if (modelName)
					{
						var url = Utils.makeUrl('Rbs/Seo/GetMetaVariables', { 'modelName': modelName });
						$http.get(REST.getBaseUrl(url)).success(function (data){
							scope.metaVariables = data;
							scope.variableCount = Object.keys(data).length;
						})
					}
				});

				editorCtrl.init('Rbs_Seo_ModelConfiguration');
			}
		};
	}

	Editor.$inject = ['$http', 'RbsChange.REST', 'RbsChange.Utils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoModelConfiguration', Editor);
})();