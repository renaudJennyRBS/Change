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
			templateUrl : 'Rbs/Catalog/DeclinationGroup/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.onLoad = function() {
					if (scope.document.isNew())
					{
						scope.document.newSkuOnCreation = true;
					}

					if (scope.document.productMatrixInfo == null)
					{
						scope.document.productMatrixInfo = [];
					}
				};

				scope.onReady = function() {
					if (!scope.document.isNew())
					{
						scope.selectDeclinationId(scope.document.declinedProduct.id);
						var c = scope.document.productMatrixInfo;
						for (var i = 0; i < c.length; i++)
						{
							if (c[i].id < scope.newProductId)
							{
								scope.newProductId = c[i].id;
							}
						}
					}
				};

				scope.onReload = function() {
					scope.buildMatrix();
				}

				scope.newProductId = 0;
				scope.axeDefaultValue = {};
				
				scope.currentAxeIndex = null;
				scope.currentAxe = null;
				scope.declinationPath = null;
				
				scope.matrix = [];

				scope.selectAxe = function(axeIndex) {
					var axeInfo = scope.document.axesInfo[axeIndex];
					var c = scope.document.axesDefinition;
					for (var i = 0; i < c.length; i++)
					{
						if (c[i].id == axeInfo.id)
						{
							scope.currentAxeIndex = axeIndex;
							scope.currentAxe = {"id": axeInfo.id, "info" : axeInfo, "def" :c[i]};
							return;
						}
					}
				};

				scope.removeAxeDefaultValue = function(axeInfo, value) {
					var dv = axeInfo.dv;
					for (var i = 0; i < dv.length; i++)
					{
						if (dv[i] === value)
						{
							dv.splice(i, 1);
							scope.buildMatrix();
							return;
						}
					}
				}

				scope.addAxeDefaultValue = function(axeInfo) {
					if (angular.isString(scope.axeDefaultValue[axeInfo.id]))
					{
						var value = scope.axeDefaultValue[axeInfo.id];
						if (value != "")
						{
							var dv = axeInfo.dv;
							for (var i = 0; i < dv.length; i++)
							{
								if (dv[i].value == value)
								{
									return;
								}
							}
							var title = scope.getAxeValueTitle(axeInfo.id, value);
							axeInfo.dv.push({value:value, label: title, title: title});
							scope.axeDefaultValue[axeInfo.id] = "";
							scope.buildMatrix();
						}
					}
				}

				scope.getAxeDefinition = function(axeId) {
					var c = scope.document.axesDefinition;
					for (var i = 0; i < c.length; i++)
					{
						if (c[i].id == axeId)
						{
							return c[i];
						}
					}
				};

				scope.getAxeTitle = function(axeId) {
					if (scope.document)
					{
						var c = scope.document.axesDefinition;
						for (var i = 0; i < c.length; i++)
						{
							if (c[i].id == axeId)
							{
								return c[i].label;
							}
						}
					}
					return null;
				};

				scope.getAxeValueTitle = function(axeId, value) {
					if (scope.document)
					{
						var c = scope.document.axesDefinition;
						for (var i = 0; i < c.length; i++)
						{
							if (c[i].id == axeId)
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

				scope.selectDeclinationId = function(declinationId) {
					var productMatrix = scope.findProductInfo(declinationId);
					var declinationPath = [];
					if (productMatrix != null)
					{
						do {
							declinationPath.push(productMatrix);
							productMatrix = scope.findProductInfo(productMatrix.parentId)
						}	while (productMatrix != null);
						declinationPath.reverse();
					}
					scope.declinationPath = declinationPath;
					scope.selectAxe(declinationPath.length);
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

				scope.findChildrenProductInfo = function(productId, axeId) {
					var r = [];
					var c = scope.document.productMatrixInfo;
					for (var i = 0; i < c.length; i++)
					{
						if (c[i].parentId == productId && c[i].axeId == axeId)
						{
							r.push(c[i]);
						}
					}
					return r;
				};

				scope.findProductEntry = function(productId, axeId, axeValue) {
					var c = scope.document.productMatrixInfo;
					for (var i = 0; i < c.length; i++)
					{
						if (c[i].parentId == productId && c[i].axeId == axeId && c[i].axeValue == axeValue)
						{
							return c[i];
						}
					}
					return null
				};

				scope.isDeclinationMatrix = function() {
					if (scope.document)
					{
						return (scope.currentAxeIndex + 1) < scope.document.axesInfo.length;
					}
					return false;
				};

				scope.nextDeclinationAxe = function() {
					if (scope.isDeclinationMatrix())
					{
						var axeInfo = scope.document.axesInfo[scope.currentAxeIndex + 1];
						return scope.getAxeDefinition(axeInfo.id);
					}
					return null;
				};

				scope.addDeclination = function(entry) {
					if (entry.hasOwnProperty('removed'))
					{
						entry.id = entry.removed;
						delete entry.removed;
					}
					else
					{
						entry.id = --scope.newProductId;
					}
					scope.document.productMatrixInfo.push(entry);
				}

				scope.deleteDeclination = function(entry) {
					if (entry.id != 0)
					{
						entry.removed = entry.id
					}
					entry.id = 0;
				}

				scope.editProduct = function(entry) {
					entry.id = --scope.newProductId;
					scope.document.productMatrixInfo.push(entry);
				}

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
				}

				scope.addAllProduct = function() {
					var axesInfo = scope.document.axesInfo;
					var parentId = scope.document.declinedProduct.id;
					scope.addAllAxeProduct(0, parentId, axesInfo);
					scope.buildMatrix();
				};

				scope.addAllAxeProduct = function(axeLevel, parentId, axesInfo)
				{
					var ai = axesInfo[axeLevel];
					var currentAxeId = ai.id;
					var declination = (axeLevel + 1) < axesInfo.length;

					for (var j = 0; j < ai.dv.length; j++)
					{
						var value = ai.dv[j].value;
						var entry = scope.findProductEntry(parentId, ai.id, value);
						if (entry == null)
						{
							entry = {id: 0, parentId: parentId, axeId:currentAxeId, axeValue: value, declination: declination};
							scope.addDeclination(entry);
						}

						if (declination)
						{
							scope.addAllAxeProduct((axeLevel + 1), entry.id, axesInfo);
						}
					}
				}

				scope.buildMatrix = function() {
					var m = [];
					var r, dv ;
					var axeDefaultValues =  scope.currentAxe.info.dv;
					var currentAxeId =  scope.currentAxe.id;

					var parentId = scope.declinationPath.length ?
						scope.declinationPath[scope.declinationPath.length - 1].id : scope.document.declinedProduct.id;
					var declination = scope.isDeclinationMatrix();

					for (var i = 0; i < axeDefaultValues.length; i++)
					{
						dv = axeDefaultValues[i];
						r = [dv];
						var entry = scope.findProductEntry(parentId, currentAxeId, dv.value);
						if (entry == null)
						{
							entry = {id: 0, parentId: parentId, axeId:currentAxeId, axeValue: dv.value, declination: declination}
						}
						r.push(entry);
						m.push(r);
					}

					scope.matrix = m;
				};

				editorCtrl.init('Rbs_Catalog_DeclinationGroup');
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogDeclinationGroup', Editor);
})();