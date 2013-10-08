(function () {

	"use strict";

	/**
	 * @constructor
	 */
	function Editor ()
	{
		return {
			restrict : 'C',
			templateUrl : 'Rbs/Catalog/VariantGroup/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				var	axesCount = 0,
					nextProductId = 0;

				scope.path = [];
				scope.navigationEnd = false;
				scope.editMode = {};


				scope.onLoad = function()
				{
					if (scope.document.isNew())
					{
						scope.document.newSkuOnCreation = true;
					}

					if (scope.document.productMatrixInfo === null)
					{
						scope.document.productMatrixInfo = [];
					}

					compileAxesInfo();
				};


				scope.toggleEditMode = function (axisIndex)
				{
					scope.editMode[axisIndex] = scope.editMode[axisIndex] ? false : true;
				};


				scope.inEditMode = function (axisIndex)
				{
					return scope.editMode[axisIndex] === true;
				};


				scope.getColumnWidthStyle = function ()
				{
					var cols = axesCount;
					if (scope.navigationEnd) {
						cols++;
					}
					return {'width': (100.0 / cols)+'%'};
				};


				scope.navigate = function (axisIndex, value, valueIndex)
				{
					var product = findProduct(axisIndex, value.value, getParentProductInNav(axisIndex));
					// Product must be selected in order to navigate to next axes.
					if (! product) {
						return;
					}

					// This will remove all the values after 'axisIndex' in 'path' Array.
					scope.path.length = axisIndex;
					scope.path[axisIndex] = {
						value : value,
						index : valueIndex,
						product : product
					};

					scope.navigationEnd = (scope.path.length === axesCount);
				};


				scope.inNavPath = function (axisIndex, value)
				{
					return scope.path[axisIndex] && scope.path[axisIndex].value === value;
				};


				scope.isBetween = function (axisIndex, $index)
				{
					if (scope.path.length <= (axisIndex+1)) {
						return false;
					}
					var	l = scope.path[axisIndex].index,
						r = scope.path[axisIndex+1].index;
					return $index >= Math.min(l, r) && $index <= Math.max(l, r);
				};


				scope.selectVariant = function (axisIndex, value, $event)
				{
					$event.stopPropagation();
					selectAxisValue(axisIndex, value, getParentProductInNav(axisIndex));
				};


				scope.unselectVariant = function (axisIndex, value, $event)
				{
					$event.stopPropagation();
					unselectAxisValue(axisIndex, value, getParentProductInNav(axisIndex));
				};


				scope.isVariantSelected = function (axisIndex, value)
				{
					var i, item, parentId, axis;

					if (axisIndex === 0) {
						parentId = scope.document.rootProduct.id;
					} else if (scope.path.length >= axisIndex) {
						parentId = scope.path[axisIndex-1].product.id;
					} else {
						return 'U';
					}

					axis = scope.axesInfo[axisIndex];

					for (i=0 ; i<scope.document.productMatrixInfo.length ; i++) {
						item = scope.document.productMatrixInfo[i];
						if (item.axisId === axis.id && item.axisValue === value && item.parentId === parentId && ! item.removed) {
							return 'Y';
						}
					}

					return 'N';
				};


				scope.selectAllVariants = function ()
				{
					cleanUp();
					selectAllValuesInAxis(0, scope.document.rootProduct);
				};


				scope.removeAxisValue = function (axisIndex, valueIndex)
				{
					scope.document.axesInfo[axisIndex].dv.splice(valueIndex, 1);
				};


				/**
				 * Returns the product associated to the current path for the given `axisIndex`.
				 *
				 * @param axisIndex
				 * @returns {*}
				 */
				function getParentProductInNav (axisIndex)
				{
					if (axisIndex === 0) {
						return scope.document.rootProduct;
					}
					else if (scope.path.length >= axisIndex) {
						return scope.path[axisIndex-1].product;
					}
					return null;
				}


				/**
				 * Selects the given `value` in the given `axisIndex` for the given `parentProduct`.
				 *
				 * @param axisIndex
				 * @param value
				 * @param parentProduct
				 */
				function selectAxisValue (axisIndex, value, parentProduct)
				{
					var product = findProduct(axisIndex, value, parentProduct);

					if (product) {
						delete product.removed;
					}
					else {
						product = {
							id : getNextProductId(),
							parentId : parentProduct.id,
							axisId : scope.axesInfo[axisIndex].id,
							axisValue : value,
							variant : ((axisIndex + 1) < scope.axesInfo.length)
						};
						scope.document.productMatrixInfo.push(product);
					}

					return product;
				}


				/**
				 * Unselects the given `value` in the given `axisIndex` for the given `parentProduct`.
				 *
				 * @param axisIndex
				 * @param value
				 * @param parentProduct
				 */
				function unselectAxisValue (axisIndex, value, parentProduct)
				{
					var product = findProduct(axisIndex, value, parentProduct);
					if (product) {
						product.removed = true;
					}
				}


				/**
				 * Removes all the temporary products (ID < 0).
				 */
				function cleanUp ()
				{
					var i = 0, item;
					do {
						item = scope.document.productMatrixInfo[i];
						// Temporary items have negative IDs.
						if (item.id < 0) {
							console.log("removing temp product: ", item.id, " at ", i);
							scope.document.productMatrixInfo.splice(i, 1);
						}
						else {
							i++;
						}
					} while (i < scope.document.productMatrixInfo);
				}


				/**
				 * Selects all the variants of the axis `axisIndex`.
				 *
				 * @param axisIndex
				 * @param parentProduct
				 */
				function selectAllValuesInAxis (axisIndex, parentProduct)
				{
					var	vi,
						axis = scope.axesInfo[axisIndex],
						product;

					for (vi=0 ; vi<axis.dv.length ; vi++)
					{
						product = selectAxisValue(axisIndex, axis.dv[vi].value, parentProduct);
						if (axisIndex < (scope.axesInfo.length - 1))
						{
							selectAllValuesInAxis(axisIndex+1, product);
						}
					}
				}


				/**
				 * Finds a product in the `productMatrixInfo` for the given axisIndex, value and parentProduct.
				 *
				 * @param axisIndex
				 * @param value
				 * @param parentProduct
				 * @returns {*}
				 */
				function findProduct (axisIndex, value, parentProduct)
				{
					var	i,
						product,
						axis = scope.axesInfo[axisIndex];

					for (i=0 ; i<scope.document.productMatrixInfo.length ; i++) {
						product = scope.document.productMatrixInfo[i];
						if (product.axisId === axis.id && product.axisValue === value && product.parentId === parentProduct.id) {
							return product;
						}
					}

					return null;
				}


				/**
				 * Returns the next temporary product ID (temporary IDs are < 0).
				 *
				 * @returns {number}
				 */
				function getNextProductId () {
					nextProductId--;
					return nextProductId;
				}


				function compileAxesInfo ()
				{
					var axesInfo = [];
					angular.forEach(scope.document.axesInfo, function (def, index) {
						axesInfo.push(angular.extend({
							index : index,
							label : scope.document.axesDefinition[index].label
						}, def));
						axesCount++;
					});
					scope.axesInfo = axesInfo;
					scope.possibleVariantsCount = getPossibleVariantsCount();
				}


				// axesInfo should be recompiled when document.axesInfo changes.
				scope.$watch('document.axesInfo', function (axesInfo, old) {
					if (axesInfo && axesInfo !== old) {
						compileAxesInfo();
					}
				}, true);


				function getPossibleVariantsCount ()
				{
					if (! scope.axesInfo) {
						return 0;
					}

					var	i,
						p = [],
						count = 0,
						mul = function (a, b) { return a * b; };

					for (i=0 ; i<scope.axesInfo.length ; i++) {
						p.push(scope.axesInfo[i].dv.length);
					}
					while (p.length > 0)
					{
						count += p.reduce(mul);
						p.length = p.length - 1;
					}

					return count;
				}


				editorCtrl.init('Rbs_Catalog_VariantGroup');
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogVariantGroup', Editor);

})();