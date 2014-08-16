(function() {
	"use strict";

	function rbsDocumentEditorRbsGeoAddressFieldsEdit() {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl)  {
				scope.onReady = function() {
					if (!angular.isArray(scope.document.fields)) {
						scope.document.fields = [];
					}
					if (!angular.isArray(scope.document.fieldsLayout)) {
						scope.document.fieldsLayout = [];
					}
				};

				scope.canDeleteItem = function(field) {
					return field && !field.locked;
				}
			}
		};
	}

	angular.module('RbsChange').directive('rbsDocumentEditorRbsGeoAddressFieldsEdit', rbsDocumentEditorRbsGeoAddressFieldsEdit);

	function rbsAddressComposer($rootScope, ArrayUtils) {
		return {
			restrict: 'E',
			templateUrl: 'Rbs/Geo/Documents/AddressFields/address-composer.twig',
			scope : {
				'fields' : "=",
				'fieldsLayout': "="
			},
			link: function(scope, element, attrs) {

				scope.draggedElement = {name : null};
				scope.$watchCollection('fields', function (fields) {
					buildTemplateLayout();
				});

				scope.$watch('fieldsLayout', function (fieldsLayout) {
					buildTemplateLayout();
				}, true);

				function buildTemplateLayout() {
					scope.templateLayout = [];
					if (angular.isArray(scope.fields) && angular.isArray(scope.fieldsLayout)) {
						for(var i = 0; i < scope.fieldsLayout.length; i++) {
							scope.templateLayout.push([]);
							var row = scope.fieldsLayout[i];
							for (var y = 0; y < row.length; y++) {
								var name = row[y], field = {name: name, label: name, row: i, col: y};
								for (var z = 0; z < scope.fields.length; z++) {
									if (name == scope.fields[z].code || (name == 'country' && scope.fields[z].code == 'countryCode')) {
										field.label = scope.fields[z].label;
									}
								}
								scope.templateLayout[i].push(field);
							}
						}
					}
				}
				buildTemplateLayout();

				scope.onDrop = function(name, fromRow, fromCol, rowIndex, colIndex) {
					var s1 = [], i, added = false;
					if (name == 'countryCode') {name = 'country';}

					if (fromRow != -1) {
						//Remove
						ArrayUtils.remove(scope.fieldsLayout[fromRow], fromCol, fromCol);
						if (fromRow == rowIndex && colIndex > fromCol) {
							colIndex--;
						}
					}

					if (rowIndex != -1) {
						if (colIndex == -1) {
							for (i = 0; i < scope.fieldsLayout.length; i++) {
								if (rowIndex == i) {
									s1.push([name]);
									added = true;
								}
								s1.push(scope.fieldsLayout[i]);
							}
							if (!added){s1.push([name]);}
							scope.fieldsLayout = s1;
						} else {
							for (i = 0; i < scope.fieldsLayout[rowIndex].length; i++) {
								if (colIndex == i) {
									s1.push(name);
									added = true;
								}
								s1.push(scope.fieldsLayout[rowIndex][i]);
							}
							if (!added){s1.push(name);}
							scope.fieldsLayout[rowIndex] = s1;
						}
					}
					s1 = [];
					for (i = 0; i < scope.fieldsLayout.length; i++) {
						if (scope.fieldsLayout[i].length) {
							s1.push(scope.fieldsLayout[i]);
						}
					}
					scope.fieldsLayout = s1;

					$rootScope.$digest();
				}
			}
		};
	}
	rbsAddressComposer.$inject = ['$rootScope', 'RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsAddressComposer', rbsAddressComposer);

	function addressField($rootScope) {
		return {
			restrict: 'A',
			link: function(scope, element, attrs) {
				var id = attrs.addressField;
				angular.element(element).attr('draggable', 'true');

				element.bind('dragstart', function(event) {
					scope.draggedElement.name = id;
					if (attrs.sandBox) {
						scope.draggedElement.mode = 'copy';
						scope.draggedElement.row = -1;
						scope.draggedElement.col = -1;
					} else {
						scope.draggedElement.mode = 'move';
						scope.draggedElement.row = parseInt(attrs.fieldRow, 10);
						scope.draggedElement.col = parseInt(attrs.fieldCol, 10);
					}

					event.originalEvent.dataTransfer.setData('text', id);
					$rootScope.$emit('ADDR-DRAG-START');
				});

				element.bind("dragend", function(event) {
					$rootScope.$emit('ADDR-DRAG-END');
					scope.draggedElement.name = null;
					scope.draggedElement.mode = null;
				});
			}
		};
	}
	addressField.$inject = ['$rootScope'];
	angular.module('RbsChange').directive('addressField', addressField);

	function fieldRowDrop($rootScope, ArrayUtils) {
		return {
			restrict: 'A',
			link: function(scope, element, attrs) {
				var rowIndex = parseInt(attrs.fieldRow, 10);
				var colIndex = parseInt(attrs.fieldCol, 10);
				
				element.bind('dragenter', function(event) {
					event.preventDefault();
					event.stopPropagation();
					angular.element(element).addClass('drop-over');
				});

				element.bind('dragleave', function(event) {
					angular.element(element).removeClass('drop-over');
				});

				element.bind('dragover', function(event) {
					if (scope.draggedElement.name)
					{
						if (scope.draggedElement.row != rowIndex) {
							event.preventDefault();
							event.originalEvent.dataTransfer.dropEffect = scope.draggedElement.mode;
							angular.element(element).addClass('drop-over');
							return;
						} else if (scope.draggedElement.col != colIndex && scope.draggedElement.col + 1 != colIndex) {
							event.preventDefault();
							event.originalEvent.dataTransfer.dropEffect = scope.draggedElement.mode;
							angular.element(element).addClass('drop-over');
							return;
						}
					}
					angular.element(element).removeClass('drop-over');
				});

				element.bind("drop", function(event) {
					event.preventDefault();
					angular.element(element).removeClass('drop-over');
					scope.onDrop(scope.draggedElement.name, scope.draggedElement.row, scope.draggedElement.col , rowIndex, colIndex);
				});

				$rootScope.$on('ADDR-DRAG-START', function() {
				});

				$rootScope.$on('ADDR-DRAG-END', function() {
					angular.element(element).removeClass("drop-over");
				});
			}
		};
	}
	fieldRowDrop.$inject = ['$rootScope', 'RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('fieldRowDrop', fieldRowDrop);
})();