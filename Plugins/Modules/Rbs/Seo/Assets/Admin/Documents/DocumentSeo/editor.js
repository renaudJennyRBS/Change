(function() {
	"use strict";

	function rbsDocumentEditorRbsSeoDocumentSeo($http, REST, Utils) {
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

				scope.onLoad = function() {
					var target = scope.document.target;
					if (target) {
						var url = Utils.makeUrl('Rbs/Seo/GetVariables', { 'modelName': target.model });
						$http.get(REST.getBaseUrl(url)).success(function(data) {
							scope.metaVariables = data.metaVariables;
							scope.pathVariables = data.pathVariables;
						});
					}
				};
			}
		};
	}

	rbsDocumentEditorRbsSeoDocumentSeo.$inject = ['$http', 'RbsChange.REST', 'RbsChange.Utils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoDocumentSeoEdit', rbsDocumentEditorRbsSeoDocumentSeo);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoDocumentSeoTranslate', rbsDocumentEditorRbsSeoDocumentSeo);
})();