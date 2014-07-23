/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ($) {

	"use strict";
	var app = angular.module('RbsChange');

	app.directive('rbsInlineArray', ['RbsChange.ArrayUtils', 'RbsChange.i18n', function (ArrayUtils, i18n) {
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/js/directives/inline-array.twig',
			scope : {
				'inlineArray' : '=',
				'canDelete' : '=',
				'refLCID' : '=', 'LCID' : '='
			},

			link: function(scope, element, attrs) {

				scope.editionInfos = [];
				scope.newModel = attrs['newModel'];

				if (!angular.isFunction(scope.canDelete)) {
					scope.canDelete = function (inline) {
						return true;
					}
				}

				scope.getIndex = function(inline) {
					if (angular.isArray(scope.inlineArray)) {
						for (var i = 0; i < scope.inlineArray.length; i++) {
							if (scope.inlineArray[i] === inline) {
								return i;
							}
						}
					}
					return -1;
				};

				scope.getEditionInfo = function(inline) {
					for (var i = 0; i < scope.editionInfos.length; i++) {
						if (scope.editionInfos[i].inline === inline) {
							return scope.editionInfos[i];
						}
					}
					return null;
				};

				scope.closeEditor = function(inline) {
					for (var i = 0; i < scope.editionInfos.length; i++) {
						if (scope.editionInfos[i].inline === inline) {
							scope.editionInfos.splice(i, 1);
							return;
						}
					}
				};

				scope.editItem = function(inline) {
					var index = scope.getIndex(inline);
					if (index != -1) {
						var editionInfo = scope.getEditionInfo(inline);
						if (!editionInfo) {
							editionInfo = {"inline" : inline, "edit": false};
							scope.editionInfos.push(editionInfo);
						}
						editionInfo.edit = true;
					}
				};

				scope.isEditorRow = function(inline) {
					var editionInfo = scope.getEditionInfo(inline);
					return editionInfo && editionInfo.edit;
				};

				scope.rowLabel = function(inline) {
					if (inline && inline.label) {
						return inline.label;
					}
					return i18n.trans('m.rbs.admin.admin.edit|ucf');
				};

				scope.deleteItem = function(inline) {
					var index = scope.getIndex(inline);
					if (index != -1) {
						scope.remove(index);
					}
				};

				scope.canAdd = function() {
					return (scope.newModel)
				};

				scope.addItem = function() {
					if (scope.newModel) {
						var inline = {"model": scope.newModel};
						var index = scope.inlineArray.length;
						scope.inlineArray.push(inline);
						scope.editItem(inline);
					}
				};

				scope.moveTop = function(index) {
					if (angular.isArray(scope.inlineArray)) {
						ArrayUtils.move(scope.inlineArray, index, 0);
					}
				};

				scope.moveUp = function(index) {
					if (angular.isArray(scope.inlineArray)) {
						ArrayUtils.move(scope.inlineArray, index, index - 1);
					}
				};

				scope.moveBottom = function(index) {
					if (angular.isArray(scope.inlineArray)) {
						ArrayUtils.move(scope.inlineArray, index, scope.inlineArray.length - 1);
					}
				};

				scope.moveDown = function(index) {
					if (angular.isArray(scope.inlineArray)) {
						ArrayUtils.move(scope.inlineArray, index, index + 1);
					}
				};

				scope.remove = function(index) {
					if (angular.isArray(scope.inlineArray)) {
						scope.inlineArray.splice(index, 1);
					}
				};
			}
		};
	}]);

	var LCIDArray = null;

	function loadLCID(REST) {
		var params = {"code": "Rbs_Generic_Collection_Languages"};
		REST.action('collectionItems', params).then(function (data) {
			LCIDArray = data.items;
			angular.forEach(LCIDArray, function (item) {
				if (item.label && item.label.length > 0) {
					item.label = angular.uppercase(item.label.substr(0, 1)) + item.label.substr(1);
				}
			});
		});
	}

	app.directive('rbsInlineDocument', ['$http', '$templateCache', '$compile', 'RbsChange.REST',
		function ($http, $templateCache, $compile, REST) {
		return {
			restrict : 'E',
			scope : {
				'document' : '=',
				'refLCID' : '=', 'LCID' : '='
			},
			link: function(scope, element, attrs) {
				scope.localization = {"refLCID": scope.refLCID, "LCID": scope.LCID};
				scope.modelInfo = null;

				scope.LCIDArray = function() {
					if (LCIDArray === null) {
						LCIDArray = {};
						loadLCID(REST)
					}
					return LCIDArray;
				};

				function onRenderEditor() {
					if (!scope.document || !scope.document.model) {
						return;
					}
					var templateParts = scope.document.model.split('_');
					var templateURL = 'Document/' + templateParts[0] + '/' + templateParts[1] + '/' + templateParts[2] + '/editor.twig';

					element.html('');
					$http.get(templateURL, {cache: $templateCache}).success(function (html) {
						html = $(html);
						$compile(html)(scope, function (clone) {
							element.append(clone);
						});
					});
				}

				scope.$watch('document.model', function (model) {
					if (model) {
						if (scope.modelInfo && scope.modelInfo.metas && scope.modelInfo.metas.name != model) {
							scope.modelInfo = null;
						}

						if (!scope.modelInfo) {
							REST.modelInfo(model).then(function (modelInfo) {
								scope.modelInfo = modelInfo;
								if (modelInfo.metas.localized)
								{
									scope.LCIDArray();
								}
							});
						}
						onRenderEditor();
					} else {
						scope.modelInfo = null;
					}
				});

				scope.$watch('document.refLCID', function (refLCID) {
					scope.localization.refLCID = refLCID;
					scope.localization.LCID = refLCID;
				});

				scope.$watch('localization.LCID', function (LCID) {
					if (LCID) {
						if (scope.document) {
							if (!angular.isObject(scope.document['LCID'])) {
								scope.document['LCID'] = {};
							}
							if (!angular.isObject(scope.document['LCID'][LCID])) {
								scope.document['LCID'][LCID] = {"LCID": LCID};
							}
						}
					}
				})
			}
		};
	}]);
})(window.jQuery);