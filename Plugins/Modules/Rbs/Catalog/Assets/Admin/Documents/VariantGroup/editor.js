(function () {

	"use strict";

	/**
	 * Editor for Rbs_Catalog_VariantGroup Documents.
	 */
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogVariantGroup', ['RbsChange.REST', '$timeout', '$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.UrlManager', function Editor (REST, $timeout, $routeParams, Breadcrumb, i18n, UrlManager)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Catalog/VariantGroup/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			controller : function () {},

			link : function (scope, elm, attrs, editorCtrl)
			{
				var	axesCount = 0,
					nextProductId = 0;

				scope.path = [];
				scope.navigationEnd = false;
				scope.editMode = {};
				scope.axisValueToAdd = {};


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

				scope.postSave = function()
				{
					scope.$broadcast('Change:DocumentList:DLRbsCatalogVariantList:call', { 'method' : 'reload' });
				}

				scope.onReady = function(){
					if ($routeParams.productId)
					{
						//Creation : get Product
						REST.resource('Rbs_Catalog_Product', $routeParams.productId).then(function(product){
							scope.document.rootProduct = product;
						});
					}

					if (scope.document.rootProduct)
					{
						Breadcrumb.setLocation([
							[i18n.trans('m.rbs.catalog.adminjs.module_name | ucf'), "Rbs/Catalog"],
							[i18n.trans('m.rbs.catalog.adminjs.product_list | ucf'), UrlManager.getUrl(scope.document.rootProduct, 'list')],
							[scope.document.rootProduct.label, UrlManager.getUrl(scope.document.rootProduct, 'form') ],
							[i18n.trans('m.rbs.catalog.adminjs.variant_group | ucf'), "Rbs/Catalog/VariantGroup"]]
						);
					}
				};

				scope.toggleEditMode = function (axisIndex)
				{
					elm.find('.axis-column .axis-values .indicator').hide();
					scope.editMode[axisIndex] = scope.editMode[axisIndex] ? false : true;
					$timeout(updateIndicators);
				};


				scope.inEditMode = function (axisIndex)
				{
					return scope.editMode[axisIndex] === true;
				};


				scope.getColumnWidthStyle = function ()
				{
					return { 'width': (100.0 / (scope.navigationEnd ? (axesCount+1) : axesCount)) + '%' };
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
					updateIndicators();

					scope.navigationEnd = (scope.path.length === axesCount);
					loadFinalProduct(product);
				};


				scope.inNavPath = function (axisIndex, value)
				{
					return scope.path[axisIndex] && scope.path[axisIndex].value === value;
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
					if (scope.document.productMatrixInfo)
					{
						for (i=0 ; i<scope.document.productMatrixInfo.length ; i++) {
							item = scope.document.productMatrixInfo[i];
							if (item.axisId === axis.id && item.axisValue === value && item.parentId === parentId && ! item.removed) {
								return 'Y';
							}
						}
					}

					return 'N';
				};


				scope.selectAllVariants = function ()
				{
					cleanUp();
					selectAllValuesInAxis(0, scope.document.rootProduct);
				};


				scope.removeAllVariants = function ()
				{
					var i, item;
					if (scope.document.productMatrixInfo)
					{
						for (i=0 ; i<scope.document.productMatrixInfo.length ; i++) {
							item = scope.document.productMatrixInfo[i];
							item.removed = true;
						}
					}
					//scope.document.productMatrixInfo.length = 0;
				};


				scope.removeAxisValue = function (axisIndex, valueIndex)
				{
					var axis = scope.document.axesInfo[axisIndex];
					removeProductsInAxis(axis, axis.dv[valueIndex].value);
					axis.dv.splice(valueIndex, 1);
				};


				scope.addAxisValue = function (axisIndex)
				{
					var value = scope.axisValueToAdd[axisIndex];
					scope.document.axesInfo[axisIndex].dv.push(makeValueObject(value));
					selectAxisValue(axisIndex, value, getParentProductInNav(axisIndex));
					scope.axisValueToAdd[axisIndex] = null;
				};


				scope.addValueOnEnter = function (axisIndex, $event)
				{
					$event.stopPropagation();
					var form = angular.element($event.target).controller('form');
					if ($event.keyCode === 13 && form.$valid) {
						scope.addAxisValue(axisIndex);
					}
				};


				scope.valueAlreadyExists = function (index)
				{
					var form = angular.element(elm.find('.axis-column:nth-child(' + (index+1) + ') [ng-form]')).controller('form');
					return form.axisValueToAdd.$error.valueExists;
				};


				scope.isInvalid = function (index)
				{
					var form = angular.element(elm.find('.axis-column:nth-child(' + (index+1) + ') [ng-form]')).controller('form');
					return form.$invalid;
				};


				scope.finalProductSaved = function ()
				{
					return (getFinalProduct().id > 0);
				};


				scope.getFinalProductUrl = function ()
				{
					var doc = REST.newResource('Rbs_Catalog_Product');
					doc.id = getFinalProduct().id;
					return doc.url();
				};


				// `axesInfo` should be recompiled when `document.axesInfo` changes.
				scope.$watch('document.axesInfo', function (axesInfo, old) {
					if (axesInfo && axesInfo !== old) {
						compileAxesInfo();
					}
				}, true);


				function updateIndicators ()
				{
					var i, $lv, $rv, $lCol, $rCol, lTop, rTop, top, height, offset;

					elm.find('.axis-column .axis-values .indicator').hide();
					for (i=1 ; i < scope.path.length ; i++)
					{
						$lCol = elm.find('.axis-column:nth-child(' + (i) + ') .axis-values');
						$rCol = elm.find('.axis-column:nth-child(' + (i+1) + ') .axis-values');

						$lv = $lCol.find('.axis-value:nth-child(' + (scope.path[i-1].index+1) + ')');
						$rv = $rCol.find('.axis-value:nth-child(' + (scope.path[i].index+1) + ')');

						lTop = $lv.position().top;
						rTop = $rv.position().top;

						if (scope.editMode[i]) {
							offset = $rCol.parent().find('.axis-header').outerHeight() - $lCol.parent().find('.axis-header').outerHeight();
							lTop -= offset;
						}

						if (scope.editMode[i-1]) {
							offset = $lCol.parent().find('.axis-header').outerHeight() - $rCol.parent().find('.axis-header').outerHeight();
							lTop += offset;
						}

						if (lTop < rTop) {
							top = lTop;
							height = rTop - lTop + $rv.outerHeight();
						}
						else {
							top = rTop;
							height = lTop - rTop + $lv.outerHeight();
						}

						elm.find('.axis-column:nth-child(' + (i+1) + ') .axis-values .indicator')
							.css({ height : height+'px', top : top+'px' })
							.show();
					}
				}


				function removeProductsInAxis (axis, value)
				{
					angular.forEach(scope.document.productMatrixInfo, function (product) {
						if (product.axisId === axis.id && product.axisValue === value) {
							product.removed = true;
							removeChildProducts(product);
						}
					});
				}


				function removeChildProducts (parentProduct)
				{
					angular.forEach(scope.document.productMatrixInfo, function (product) {
						if (product.parentId === parentProduct.id) {
							product.removed = true;
							removeChildProducts(product);
						}
					});
				}


				function getFinalProduct ()
				{
					return scope.path[scope.path.length-1].product;
				}


				function makeValueObject (value)
				{
					return {
						value : value,
						title : value,
						label : value
					};
				}


				function loadFinalProduct (product)
				{
					if (scope.navigationEnd) {
						scope.loadingFinalProduct = true;
						REST.resource('Rbs_Catalog_Product', product.id).then(function (product) {
							scope.loadingFinalProduct = false;
							scope.finalProduct = product;
						});
					}
					else {
						scope.finalProduct = null;
					}
				}


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
					if (! parentProduct) {
						return;
					}

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
					if (scope.document.productMatrixInfo.length) {
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
						} while (i < scope.document.productMatrixInfo.length);
					}
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

					for (vi=0 ; vi < axis.dv.length ; vi++)
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

					for (i=0 ; i < scope.document.productMatrixInfo.length ; i++) {
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
				function getNextProductId ()
				{
					nextProductId--;
					return nextProductId;
				}


				/**
				 * Compiles different pieces of information about axes.
				 */
				function compileAxesInfo ()
				{
					var axesInfo = [];
					axesCount = 0;
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


				/**
				 * Computes the number of possible variants.
				 *
				 * @returns {number}
				 */
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

	}]);


	angular.module('RbsChange').filter('rbsVariantGroupEditorValidProductsCount', function () {
		return function (input) {
			var count = 0;
			angular.forEach(input, function (product) {
				if (! product.removed) {
					count++;
				}
			});
			return count;
		};
	});


	/**
	 * This Directive validates the input for a new value in an axis:
	 * it checks if the value already exists in the given axis.
	 *
	 * @attribute axis-index="number"
	 */
	angular.module('RbsChange').directive('rbsVariantGroupEditorNewAxisValueValidator', ['RbsChange.Utils', function (Utils)
	{
		return {
			require : [ 'ngModel', '^rbsDocumentEditorRbsCatalogVariantGroup' ],
			scope : false,

			link : function (scope, iElement, iAttr, ctrls)
			{
				var	axisIndex = iAttr.axisIndex,
					ngModel = ctrls[0];

				function validate (value) {
					var	i,
						axis = scope.axesInfo[axisIndex],
						valid = true;
					for (i=0 ; i<axis.dv.length && valid ; i++) {
						valid = ! Utils.equalsIgnoreCase(value, axis.dv[i].value);
					}
					ngModel.$setValidity('valueExists', valid);
					return valid ? value : undefined;
				}

				// For DOM -> model validation
				ngModel.$parsers.unshift(function(value) {
					return validate(value);
				});

				// For model -> DOM validation
				ngModel.$formatters.unshift(function(value) {
					validate(value);
					return value;
				});
			}
		};
	}]);

})();