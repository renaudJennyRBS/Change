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

				scope.cascadeCreateItem = function(){
					EditorManager.cascade(REST.newResource('Rbs_Elasticsearch_Facet'), scope.document.label, function(doc){scope.document.facets.push(doc);});
				};

				scope.cascadeEditItem = function(index){
					REST.resource(scope.document.facets[index]).then(
						function(doc) {
							scope.cascadeEdit(doc, scope.document.label, function(doc){scope.document.facets[index] = doc;});
						}
					)
				};
			}
		};

	}

	editorRbsElasticsearchStoreIndex.$inject = ['RbsChange.EditorManager', 'RbsChange.REST', 'RbsChange.ArrayUtils'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsElasticsearchStoreIndex', editorRbsElasticsearchStoreIndex);

})();