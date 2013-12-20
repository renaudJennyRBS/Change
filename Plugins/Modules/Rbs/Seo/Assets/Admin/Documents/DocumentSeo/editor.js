(function ()
{
	"use strict";

	/**
	 * @param $http
	 * @param REST
	 * @param NotificationCenter
	 * @param Utils
	 * @constructor
	 */
	function Editor($http, REST, NotificationCenter, Utils, ErrorFormatter)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Seo/DocumentSeo/editor.twig',
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

				scope.$watch('document.target', function (target){
					if (target)
					{
						var url = Utils.makeUrl('Rbs/Seo/GetMetaVariables', { 'modelName': target.model });
						$http.get(REST.getBaseUrl(url)).success(function (data){
							scope.metaVariables = data;
							scope.variableCount = Object.keys(data).length;
						});
					}
				});

				editorCtrl.init('Rbs_Seo_DocumentSeo');
			}
		};
	}

	Editor.$inject = ['$http', 'RbsChange.REST', 'RbsChange.NotificationCenter', 'RbsChange.Utils', 'RbsChange.ErrorFormatter'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoDocumentSeo', Editor);
})();