(function ()
{
	"use strict";

	/**
	 * @param $timeout
	 * @param $http
	 * @param Loading
	 * @param REST
	 * @constructor
	 */
	function Editor($timeout, $http, Loading, REST)
	{
		return {
			restrict : 'C',
			templateUrl : 'Rbs/Catalog/VariantGroup/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				var axesCount = 0;
				scope.path = [];
				scope.navigationEnd = false;

				function compileAxesInfo ()
				{
					var axesInfo = [], index = 0;
					angular.forEach(scope.document.axesDefinition, function (def) {
						axesInfo.push(angular.extend({index: index++}, def));
						axesCount++;
					});
					scope.axesInfo = axesInfo;
				}

				scope.onLoad = function() {
					if (scope.document.isNew())
					{
						scope.document.newSkuOnCreation = true;
					}

					if (scope.document.productMatrixInfo == null)
					{
						scope.document.productMatrixInfo = [];
					}

					compileAxesInfo();
				};

				// TODO
				scope.addAxisValue = function (axis, value)
				{

				};

				// TODO
				scope.removeAxisValue = function (axis, value)
				{

				};

				scope.navigate = function (axisIndex, value, valueIndex)
				{
					// This will remove all the values after 'axisIndex' in 'path' Array.
					scope.path.length = axisIndex;
					scope.path[axisIndex] = {
						value : value,
						index : valueIndex
					};

					scope.navigationEnd = (scope.path.length === axesCount);
				};

				scope.inPath = function (axisIndex, value)
				{
					return scope.path[axisIndex] && scope.path[axisIndex].value === value;
				};


				scope.isBetween = function (axisIndex, $index) {
					if (scope.path.length <= (axisIndex+1)) {
						return false;
					}
					return $index >= Math.min(scope.path[axisIndex+1].index, scope.path[axisIndex].index)
					    && $index <= Math.max(scope.path[axisIndex+1].index, scope.path[axisIndex].index);
				};



/*
				scope.onReady = function() {
					if (!scope.document.isNew())
					{
						scope.selectVariantId(scope.document.rootProduct.id);
						var c = scope.document.productMatrixInfo;
						for (var i = 0; i < c.length; i++)
						{
							if (c[i].id < scope.newProductId)
							{
								scope.newProductId = c[i].id;
							}
						}
						scope.loadProductList();
					}
				};

				scope.onReload = function() {
					scope.loadProductList();
					scope.buildMatrix();
				};

				scope.newProductId = 0;
				scope.axisDefaultValue = {};

				scope.currentAxisIndex = null;
				scope.currentAxis = null;
				scope.variantPath = null;

				scope.matrix = [];
				scope.productList = [];

				scope.loadProductList = function() {
					scope.productList = [];

					REST.call(scope.document.META$.links.self.href + '/Products').then(function(data) {
						var ct = REST.transformObjectToChangeDocument;
						for (var i = 0; i < data.resources.length; i++)
						{
							var doc = ct(data.resources[i]);
							if (doc.sku)
							{
								doc.sku = ct(doc.sku)
							}
							scope.productList.push(doc);
						}
					}, function(response) {
						console.log(response);
					});
				};

				scope.selectAxis = function(axisIndex) {
					var axesInfo = scope.document.axesInfo[axisIndex];
					var c = scope.document.axesDefinition;
					for (var i = 0; i < c.length; i++)
					{
						if (c[i].id == axesInfo.id)
						{
							scope.currentAxisIndex = axisIndex;
							scope.currentAxis = {"id": axesInfo.id, "info" : axesInfo, "def" :c[i]};
							return;
						}
					}
				};

				scope.removeAxisDefaultValue = function(axesInfo, value) {
					var dv = axesInfo.dv;
					for (var i = 0; i < dv.length; i++)
					{
						if (dv[i] === value)
						{
							dv.splice(i, 1);
							scope.buildMatrix();
							return;
						}
					}
				};

				scope.addAxisDefaultValue = function(axesInfo) {
					if (angular.isString(scope.axisDefaultValue[axesInfo.id]))
					{
						var value = scope.axisDefaultValue[axesInfo.id];
						if (value != "")
						{
							var dv = axesInfo.dv;
							for (var i = 0; i < dv.length; i++)
							{
								if (dv[i].value == value)
								{
									return;
								}
							}
							var title = scope.getAxisValueTitle(axesInfo.id, value);
							axesInfo.dv.push({value:value, label: title, title: title});
							scope.axisDefaultValue[axesInfo.id] = "";
							scope.buildMatrix();
						}
					}
				};

				scope.getAxisDefinition = function(axisId) {
					var c = scope.document.axesDefinition;
					for (var i = 0; i < c.length; i++)
					{
						if (c[i].id == axisId)
						{
							return c[i];
						}
					}
					return null;
				};

				scope.getAxisTitle = function(axisId) {
					if (scope.document)
					{
						var c = scope.document.axesDefinition;
						for (var i = 0; i < c.length; i++)
						{
							if (c[i].id == axisId)
							{
								return c[i].label;
							}
						}
					}
					return null;
				};

				scope.getAxisValueTitle = function(axisId, value) {
					if (scope.document)
					{
						var c = scope.document.axesDefinition;
						for (var i = 0; i < c.length; i++)
						{
							if (c[i].id == axisId)
							{
								if (angular.isArray(c[i].values))
								{
									var values = c[i].values;
									for (var j = 0; j < values.length; j++)
									{
										if (values[j].value == value)
										{
											return values[j].title;
										}
									}
								}
								break;
							}
						}
					}
					return value;
				};

				scope.selectVariantId = function(variantId) {
					var productMatrix = scope.findProductInfo(variantId);
					var variantPath = [];
					if (productMatrix != null)
					{
						do {
							variantPath.push(productMatrix);
							productMatrix = scope.findProductInfo(productMatrix.parentId)
						}	while (productMatrix != null);
						variantPath.reverse();
					}
					scope.variantPath = variantPath;
					scope.selectAxis(variantPath.length);
					scope.buildMatrix();
				};

				scope.findProductInfo = function(productId) {
					var c = scope.document.productMatrixInfo;
					for (var i = 0; i < c.length; i++)
					{
						if (c[i].id == productId)
						{
							return c[i];
						}
					}
					return null;
				};

				scope.findProductEntry = function(productId, axisId, axisValue) {
					var c = scope.document.productMatrixInfo;
					var entry = null;
					for (var i = 0; i < c.length; i++)
					{
						entry = c[i];
						if (entry.parentId == productId && entry.axisId == axisId && entry.axisValue == axisValue)
						{
							return entry;
						}
					}
					return null;
				};

				scope.isVariantMatrix = function() {
					if (scope.document)
					{
						return (scope.currentAxisIndex + 1) < scope.document.axesInfo.length;
					}
					return false;
				};

				scope.nextVariantAxis = function() {
					if (scope.isVariantMatrix())
					{
						var axesInfo = scope.document.axesInfo[scope.currentAxisIndex + 1];
						return scope.getAxisDefinition(axesInfo.id);
					}
					return null;
				};

				scope.addVariant = function(entry) {
					if (entry.hasOwnProperty('removed'))
					{
						delete entry.removed;
					}
					else
					{
						scope.newProductId = scope.newProductId -1;
						entry.id = scope.newProductId;
						scope.document.productMatrixInfo.push(entry);
					}
				};

				scope.deleteVariant = function(entry) {
					if (entry.id != 0)
					{
						entry.removed = true;
					}
				};

				scope.editProduct = function(entry) {
					if (entry.id > 0)
					{
						REST.resource(entry.id).then(
							function(doc) {
								scope.cascadeEdit(doc, scope.document.label, function(doc) {
									console.log('product edited', entry.id);
								});
							}
						)
					}
				};

				scope.addAllProduct = function() {
					var axesInfo = scope.document.axesInfo;
					var parentId = scope.document.rootProduct.id;
					scope.addAllAxisProduct(0, parentId, axesInfo);
					scope.buildMatrix();
				};

				scope.addAllAxisProduct = function(axisLevel, parentId, axesInfo)
				{
					var ai = axesInfo[axisLevel];
					var currentAxisId = ai.id;
					var variant = (axisLevel + 1) < axesInfo.length;

					for (var j = 0; j < ai.dv.length; j++)
					{
						var value = ai.dv[j].value;
						var entry = scope.findProductEntry(parentId, currentAxisId, value);
						if (entry == null)
						{
							entry = {id: 0, parentId: parentId, axisId:currentAxisId, axisValue: value, variant: variant};
							scope.addVariant(entry);
						}
						else if (entry.hasOwnProperty('removed'))
						{
							delete entry.removed;
						}

						if (variant)
						{
							scope.addAllAxisProduct((axisLevel + 1), entry.id, axesInfo);
						}
					}
				};

				scope.buildMatrix = function() {
					var m = [];
					var r, dv ;
					var axisDefaultValues =  scope.currentAxis.info.dv;
					var currentAxisId =  scope.currentAxis.id;

					var parentId = scope.variantPath.length ?
						scope.variantPath[scope.variantPath.length - 1].id : scope.document.rootProduct.id;
					var variant = scope.isVariantMatrix();

					for (var i = 0; i < axisDefaultValues.length; i++)
					{
						dv = axisDefaultValues[i];
						r = [dv];
						var entry = scope.findProductEntry(parentId, currentAxisId, dv.value);
						if (entry == null)
						{
							entry = {id: 0, parentId: parentId, axisId:currentAxisId, axisValue: dv.value, variant: variant}
						}
						r.push(entry);
						m.push(r);
					}

					scope.matrix = m;
				};
*/
				editorCtrl.init('Rbs_Catalog_VariantGroup');
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogVariantGroup', Editor);
})();