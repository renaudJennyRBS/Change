(function () {

	function editorChangeCollectionCollection (EditorManager, REST) {

		return {

			restrict : 'EC',
			templateUrl : 'Rbs/Collection/Collection/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {
				scope.onReady = function(){
					if (!angular.isArray(scope.document.items))
					{
						scope.document.items = [];
					}
				};

				editorCtrl.init('Rbs_Collection_Collection');

				scope.moveTop = function(index){
					if (angular.isArray(scope.document.items))
					{
						var item = scope.document.items.splice(index, 1);
						scope.document.items.unshift(item[0]);
					}
				};

				scope.moveUp = function(index){
					if (angular.isArray(scope.document.items))
					{
						var d = scope.document.items[index];
						var u = scope.document.items[index-1];
						scope.document.items[index] = u;
						scope.document.items[index-1] = d;
					}
				};

				scope.moveBottom = function(index){
					if (angular.isArray(scope.document.items))
					{
						item = scope.document.items.splice(index, 1);
						scope.document.items.push(item[0]);
					}
				};

				scope.moveDown = function(index){
					if (angular.isArray(scope.document.items))
					{
						var d = scope.document.items[index+1];
						var u = scope.document.items[index];
						scope.document.items[index + 1] = u;
						scope.document.items[index] = d;
					}
				};

				scope.remove = function(index){
					if (angular.isArray(scope.document.items))
					{
						scope.document.items.splice(index, 1);
					}
				};

				scope.cascadeCreateItem = function(){
					//EditorManager.cascade('Rbs/Collection/Item/form.twig', null, function(doc){scope.document.items.push(doc);}, scope.document.label);
					EditorManager.cascade(REST.newResource('Rbs_Collection_Item'), scope.document.label, function(doc){scope.document.items.push(doc);});
				};

				scope.cascadeEditItem = function(index){
					//EditorManager.cascade('Rbs/Collection/Item/form.twig', {id:scope.document.items[index].id}, function(doc){scope.document.items[index] = doc;}, scope.document.label);
					EditorManager.cascade(scope.document.items[index], scope.document.label, function(doc){scope.document.items[index] = doc;});
				};

			}
		};

	}

	editorChangeCollectionCollection.$inject = ['RbsChange.EditorManager', 'RbsChange.REST'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsCollectionCollection', editorChangeCollectionCollection);

})();