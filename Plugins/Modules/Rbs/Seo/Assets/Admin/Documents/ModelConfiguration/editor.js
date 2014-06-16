(function() {
	"use strict";

	function rbsDocumentEditorRbsSeoModelConfiguration($http, REST, Utils) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, elm, attrs, editorCtrl) {
				scope.addMetaVariable = function(meta, variable) {
					if (scope.document[meta]) {
						scope.document[meta] += '{' + variable + '}';
					}
					else {
						scope.document[meta] = '{' + variable + '}';
					}
				};

				scope.$watch('document.modelName', function(modelName) {
					if (modelName) {
						var url = Utils.makeUrl('Rbs/Seo/GetMetaVariables', { 'modelName': modelName });
						$http.get(REST.getBaseUrl(url)).success(function(data) {
							scope.metaVariables = data;
							scope.variableCount = Object.keys(data).length;
						})
					}
				});
			}
		};
	}

	rbsDocumentEditorRbsSeoModelConfiguration.$inject = ['$http', 'RbsChange.REST', 'RbsChange.Utils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoModelConfigurationEdit',
		rbsDocumentEditorRbsSeoModelConfiguration);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoModelConfigurationTranslate',
		rbsDocumentEditorRbsSeoModelConfiguration);
})();