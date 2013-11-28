(function () {

	"use strict";

	function editorRbsElasticsearchStoreIndex (EditorManager, REST, ArrayUtils) {
		return {
			restrict : 'EC',
			templateUrl : 'Document/Rbs/Elasticsearch/StoreIndex/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {
				scope.onReady = function(){
					if (!angular.isArray(scope.document.facets))
					{
						scope.document.facets = [];
					}
				};

				editorCtrl.init('Rbs_Elasticsearch_StoreIndex');

				scope.remove = function(index){
					if (angular.isArray(scope.document.facets))
					{
						scope.document.facets.splice(index, 1);
					}
				};

				scope.cascadeCreateItem = editorCtrl.registerCreateCascade('facets', 'Rbs_Elasticsearch_Facet');
				scope.cascadeEditItem = editorCtrl.registerEditCascade('facets');
			}
		};

	}

	editorRbsElasticsearchStoreIndex.$inject = ['RbsChange.EditorManager', 'RbsChange.REST', 'RbsChange.ArrayUtils'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsElasticsearchStoreIndex', editorRbsElasticsearchStoreIndex);

})();