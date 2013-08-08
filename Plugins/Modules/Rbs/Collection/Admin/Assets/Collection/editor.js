(function () {

	function editorChangeCollectionCollection (Editor, FormsManager) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Collection/Collection/editor.twig',

			replace: true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link : function (scope, elm, attrs) {
				Editor.initScope(scope, elm, function(){
					if (!angular.isArray(scope.document.items))
					{
						scope.document.items = [];
					}
				});
				scope.moveTop = function(index){
					if (angular.isArray(scope.document.items))
					{
						item = scope.document.items.splice(index, 1);
						scope.document.items.unshift(item[0]);
					}
				};

				scope.moveUp = function(index){
					if (angular.isArray(scope.document.items))
					{
						d = scope.document.items[index];
						u = scope.document.items[index-1];
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
						d = scope.document.items[index+1];
						u = scope.document.items[index];
						scope.document.items[index + 1] = u;
						scope.document.items[index] = d;
					}
				};

				scope.remove = function(index){
					if (angular.isArray(scope.document.items))
					{
						item = scope.document.items.splice(index, 1);
					}
				};

				scope.cascadeCreateItem = function(){
					FormsManager.cascade('Rbs/Collection/Item/form.twig', null, function(doc){scope.document.items.push(doc);}, scope.document.label);
				};

				scope.cascadeEditItem = function(index){
					FormsManager.cascade('Rbs/Collection/Item/form.twig', {id:scope.document.items[index].id}, function(doc){scope.document.items[index] = doc;}, scope.document.label);
				};

			}
		};

	}

	editorChangeCollectionCollection.$inject = ['RbsChange.Editor', 'RbsChange.FormsManager'];

	angular.module('RbsChange').directive('editorChangeCollectionCollection', editorChangeCollectionCollection);

})();