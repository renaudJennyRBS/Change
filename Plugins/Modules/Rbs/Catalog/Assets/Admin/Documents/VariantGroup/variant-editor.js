(function () {

	"use strict";

	/**
	 * Editor for Rbs_Catalog_VariantGroup Documents.
	 */
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogVariantGroupEditor', ['RbsChange.REST', '$timeout', '$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.UrlManager', function Editor (REST, $timeout, $routeParams, Breadcrumb, i18n, UrlManager)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Catalog/VariantGroup/variant-editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			controller : function () {},

			link : function (scope, elm, attrs, editorCtrl)
			{
				var axesCount = 0, nextProductId = 0;

				scope.path = [];
				scope.navigationEnd = false;
				scope.editMode = {};
				scope.axisValueToAdd = {};
				scope.axesInfo = [];
				scope.possibleVariantsCount = 0;
				scope.hasJobs = false;

				scope.onLoad = function() {

					scope.hasJobs = (angular.isArray(scope.document.jobs) && scope.document.jobs.length > 0);

					if (scope.document.variantConfiguration === null) {
						scope.document.variantConfiguration = {axesValues: [], products: []};
					}
					if (scope.document.axesConfiguration === null) {
						scope.document.axesConfiguration = [];
					} else {
						compileAxesInfo();
					}
				};

				scope.onReload = function() {
					scope.hasJobs = (angular.isArray(scope.document.jobs) && scope.document.jobs.length > 0);

					if (scope.hasJobs)
					{
						scope.path = [];
						scope.navigationEnd = false;
						scope.editMode = {};
						scope.axisValueToAdd = {};
					}
				}

				scope.toggleEditMode = function (axisIndex) {
					elm.find('.axis-column .axis-values .indicator').hide();
					scope.editMode[axisIndex] = scope.editMode[axisIndex] ? false : true;
					$timeout(updateIndicators);
				};


				scope.inEditMode = function (axisIndex) {
					return scope.editMode[axisIndex] === true;
				};


				scope.getColumnWidthStyle = function () {
					return { 'width': (100.0 / (scope.navigationEnd ? (axesCount+1) : axesCount)) + '%' };
				};

				scope.isVariantSelected = function (axisIndex, value) {
					var i, item, parentId, axesValues, axis = scope.axesInfo[axisIndex];
					if (scope.path.length < axisIndex) {
						return 'U';
					} else if (!axis.url) {
						axesValues = getEmptyAxesValues(axisIndex);
						for (i = 0; i < axisIndex; i++) {
							axesValues[i].value = scope.path[i].value.value;
						}
						axesValues[axisIndex].value = value;
						return (findProductId(axesValues) === null) ? 'S': 'C';
					}

					axesValues = getEmptyAxesValues();
					for (i = 0; i < axisIndex; i++) {
						axesValues[i].value = scope.path[i].value.value;
					}
					axesValues[axisIndex].value = value;
					return (findProductId(axesValues) !== null) ? 'Y' : 'N';
				};

				scope.navigate = function (axisIndex, value, valueIndex) {
					var axis = scope.axesInfo[axisIndex],
						product = {id:0}, productId, i,
						axesValues = getEmptyAxesValues();

					if (scope.path.length < axisIndex) {
						return;
					}

					if (axis.url) {
						for (i = 0; i < axisIndex; i++) {
							axesValues[i].value = scope.path[i].value.value;
						}
						axesValues[axisIndex].value = value.value;
						productId = findProductId(axesValues);
						if (productId === null) {
							return;
						} else {
							product.id = productId;
						}
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
					if (scope.navigationEnd) {
						scope.loadingFinalProduct = true;
						if (product.id > 0) {
							REST.resource('Rbs_Catalog_Product', product.id).then(function (product) {
								scope.loadingFinalProduct = false;
								scope.path[axisIndex].product = product;
							});
						}
					}
				};

				scope.inNavPath = function (axisIndex, value) {
					return scope.path[axisIndex] && scope.path[axisIndex].value === value;
				};


				scope.selectVariant = function (axisIndex, value, valueIndex, $event) {
					$event.stopPropagation();
					var axesValues = getEmptyAxesValues(), i;
					for (i = 0; i < axisIndex; i++) {
						axesValues[i].value = scope.path[i].value.value;
					}
					axesValues[axisIndex].value = value.value;
					selectAxisValue(axisIndex, axesValues);
					scope.navigate(axisIndex, value, valueIndex);
				};

				scope.unselectVariant = function (axisIndex, value, valueIndex, $event) {
					$event.stopPropagation();
					var axesValues = getEmptyAxesValues(axisIndex), i;
					for (i = 0; i < axisIndex; i++) {
						axesValues[i].value = scope.path[i].value.value;
					}
					axesValues[axisIndex].value = value.value;
					removeProductsInAxis(axesValues);
					if (scope.path[axisIndex].value.value == value.value) {
						scope.path.length = axisIndex;
						scope.navigationEnd = false;
						updateIndicators();
					}
				};

				scope.selectAllVariants = function () {
					selectAllValuesInAxis(0, getEmptyAxesValues());
				};

				scope.removeAllVariants = function () {
					scope.document.variantConfiguration.products = [];
					scope.path = [];
					scope.navigationEnd = false;
					scope.editMode = {};
					scope.axisValueToAdd = {};
					updateIndicators();
				};

				scope.removeAxisValue = function (axisIndex, valueIndex) {
					var axis = scope.axesInfo[axisIndex];
					removeProductsInAxis([{id:axis.id, value:axis.values[valueIndex].value}]);
					axis.values.splice(valueIndex, 1);
					scope.possibleVariantsCount = getPossibleVariantsCount();
				};


				scope.addAxisValue = function (axisIndex) {
					var value = scope.axisValueToAdd[axisIndex];
					scope.axesInfo[axisIndex].values.push(makeValueObject(value));
					scope.axisValueToAdd[axisIndex] = null;
					scope.possibleVariantsCount = getPossibleVariantsCount();
				};


				scope.addValueOnEnter = function (axisIndex, $event) {
					$event.stopPropagation();
					var form = angular.element($event.target).controller('form');
					if ($event.keyCode === 13 && form.$valid) {
						scope.addAxisValue(axisIndex);
					}
				};


				scope.valueAlreadyExists = function (index) {
					var form = angular.element(elm.find('.axis-column:nth-child(' + (index+1) + ') [ng-form]')).controller('form');
					return form.axisValueToAdd.$error.valueExists;
				};


				scope.isInvalid = function (index) {
					var form = angular.element(elm.find('.axis-column:nth-child(' + (index+1) + ') [ng-form]')).controller('form');
					return form.$invalid;
				};


				scope.finalProductSaved = function () {
					return (getFinalProduct().id > 0);
				};

				scope.getFinalProductUrl = function () {
					if (scope.navigationEnd) {
						var doc = getFinalProduct();
						return doc.url();
					}
					return null;
				};

				function getFinalProduct() {
					if (scope.navigationEnd)
					{
						return scope.path[scope.path.length-1].product;
					}
					return {id:0};
				}

				function updateIndicators() {
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
						} else {
							top = rTop;
							height = lTop - rTop + $lv.outerHeight();
						}

						elm.find('.axis-column:nth-child(' + (i+1) + ') .axis-values .indicator')
							.css({ height : height+'px', top : top+'px' })
							.show();
					}
				}

				function makeValueObject(value) {
					return {value : value, title : value, label : value};
				}

				/**
				 * @param axisIndex
				 * @param axesValues
				 */
				function selectAxisValue(axisIndex, axesValues) {
					var axis = scope.axesInfo[axisIndex], productId, product;
					if (axis.url) {
						productId = findProductId(axesValues);
						if (productId === null) {
							var values = angular.copy(axesValues);
							product = {id: getNextProductId(), values: values};
							scope.document.variantConfiguration.products.push(product);
						}
					}
				}

				function selectAllValuesInAxis(axisIndex, axesValues) {
					var axis = scope.axesInfo[axisIndex];
					angular.forEach(axis.values, function(valueObject) {
						axesValues[axisIndex].value = valueObject.value;
						if (axis.url) {
							selectAxisValue(axisIndex, axesValues);
						}
						if (axisIndex + 1 < axesCount)
						{
							selectAllValuesInAxis(axisIndex + 1, axesValues)
						}
					});
					axesValues[axisIndex].value = null;
				}

				/**
				 * Returns the next temporary product ID (temporary IDs are < 0).
				 * @returns {number}
				 */
				function getNextProductId() {
					nextProductId--;
					return nextProductId;
				}


				function getEmptyAxesValues(toIndex) {
					if (toIndex === undefined) {
						toIndex = axesCount - 1;
					}
					var values = [], i, axis;
					for (i = 0; i <= toIndex; i++) {
						axis = scope.axesInfo[i];
						values.push({id:axis.id, value:null});
					}
					return values;
				}

				function findProductId(axesValues) {
					var products = scope.document.variantConfiguration.products, i, product;
					for (i = 0; i < products.length; i++) {
						product = products[i];
						if (eqAxesValues(axesValues, product.values))
						{
							return product.id;
						}
					}
					return null;
				}

				function eqAxesValues(expected, actual)
				{
					var e, eav, a, aav;
					for (e = 0; e < expected.length; e++) {
						eav = expected[e];
						for (a = 0; a < actual.length; a++) {
							aav = actual[a];
							if (aav.id == eav.id) {
								if (aav.value !== eav.value)
								{
									return false;
								}
							}
						}
					}
					return true;
				}

				function removeProductsInAxis(axesValues) {
					var products = scope.document.variantConfiguration.products, i, product, match;

					if (products.length) {
						i = 0;
						do {
							product = products[i];
							match = true;
							angular.forEach(axesValues, function(axisValue) {
								angular.forEach(product.values, function(productAxisValue) {
									if (match && productAxisValue.id == axisValue.id) {
										match = (productAxisValue.value === axisValue.value);
									}
								});
							});
							if (match) {
								products.splice(i, 1);
							} else {
								i++;
							}
						} while (i < products.length);
					}
				}

				/**
				 * Compiles different pieces of information about axes.
				 */
				function compileAxesInfo() {
					var axesInfo = [], axisInfo, axeConf, defExist;
					axesCount = 0;
					angular.forEach(scope.document.axesConfiguration, function (def, index) {
						axisInfo = angular.extend({
							index : index,
							label : scope.document.axesAttributes[index].label
						}, def);
						axeConf = scope.document.variantConfiguration.axesValues[index];
						axisInfo.values = axeConf.defaultValues;
						angular.forEach(axeConf.values, function (usedValue) {
							defExist = false;
							angular.forEach(axisInfo.values, function (objectValue) {
								if (objectValue.value == usedValue) {
									defExist = true;
								}
							});
							if (!defExist) {
								axisInfo.values.push(makeValueObject(usedValue));
							}
						});
						axesInfo.push(axisInfo);
						axesCount++;
					});
					scope.axesInfo = axesInfo;
					scope.possibleVariantsCount = getPossibleVariantsCount();
				}


				/**
				 * Computes the number of possible variants.
				 * @returns {number}
				 */
				function getPossibleVariantsCount() {
					var axesInfo = scope.axesInfo, p = [], i, count = 0, v,
						mul = function (a, b) { return a * b; };

					if (axesInfo.length == 0) {
						return count;
					}
					v = 1;

					for (i=0 ; i < axesInfo.length ; i++) {
						v = v * axesInfo[i].values.length;
						if (axesInfo[i].url)
						{
							p.push(v);
							v = 1;
						}
					}

					while (p.length > 0) {
						count += p.reduce(mul);
						p.length = p.length - 1;
					}
					return count;
				}



				editorCtrl.init('Rbs_Catalog_VariantGroup');
			}
		};
	}]);

	/**
	 * This Directive validates the input for a new value in an axis:
	 * it checks if the value already exists in the given axis.
	 *
	 * @attribute axis-index="number"
	 */
	angular.module('RbsChange').directive('rbsVariantGroupEditorNewAxisValueValidator', ['RbsChange.Utils', function (Utils)
	{
		return {
			require : [ 'ngModel', '^rbsDocumentEditorRbsCatalogVariantGroupEditor' ],
			scope : false,

			link : function (scope, iElement, iAttr, ctrls) {
				var	axisIndex = iAttr.axisIndex, ngModel = ctrls[0];

				function validate (value) {
					var i, axis = scope.axesInfo[axisIndex], valid = true;
					for (i=0 ; i<axis.values.length && valid ; i++) {
						valid = ! Utils.equalsIgnoreCase(value, axis.values[i].value);
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