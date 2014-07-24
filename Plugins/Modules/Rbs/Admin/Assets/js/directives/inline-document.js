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

	app.directive('rbsInlineArray', ['RbsChange.ArrayUtils', 'RbsChange.i18n', 'RbsChange.Navigation', function (ArrayUtils, i18n, Navigation) {
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/js/directives/inline-array.twig',
			scope : {
				'inlineArray' : '=',
				'checkDelete' : '=',
				'refLcid' : '=', 'lcid' : '='
			},

			link: function(scope, element, attrs) {

				scope.editionInfos = [];
				scope.newModel = attrs['newModel'];

				scope.$on('Navigation.saveContext', function(event, args) {
					args.context.savedData(attrs.inlineArray, {editionInfos: scope.editionInfos});
				});

				var currentContext = Navigation.getCurrentContext();
				if (currentContext) {
					var data = currentContext.savedData(attrs.inlineArray);
					if (data) {
						scope.editionInfos = data.editionInfos;
					}
				}

				if (angular.isFunction(scope.checkDelete)) {
					scope.canDelete = scope.checkDelete;

				} else {
					scope.canDelete = function (inline) {
						return true;
					}
				}

				scope.getContextKey = function(index) {
					return scope.newModel + '_' + index;
				};

				scope.inTranslation = function() {
					return scope.lcid != null && scope.lcid != scope.refLcid;
				};

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

	app.directive('rbsInlineSingle', ['RbsChange.i18n', 'RbsChange.Navigation', function (i18n, Navigation) {
		return {
			restrict : 'E',
			templateUrl : 'Rbs/Admin/js/directives/inline-single.twig',
			scope : {
				'inline' : '=',
				'refLcid' : '=', 'lcid' : '='
			},

			link: function(scope, element, attrs) {

				scope.edition = {"edit":false};
				scope.newModel = attrs['newModel'];

				scope.$on('Navigation.saveContext', function(event, args) {
					args.context.savedData(attrs.inline, {edition: scope.edition});
				});

				var currentContext = Navigation.getCurrentContext();
				if (currentContext) {
					var data = currentContext.savedData(attrs.inline);
					if (data) {
						scope.edition = data.edition;
					}
				}

				scope.getContextKey = function() {
					return scope.newModel;
				};

				scope.inTranslation = function() {
					return scope.lcid != null && scope.lcid != scope.refLcid;
				};

				scope.isEmpty = function() {
					return !scope.inline;
				};

				scope.inlineLabel = function() {
					if (scope.inline && scope.inline.label) {
						return scope.inline.label;
					}
					return i18n.trans('m.rbs.admin.admin.edit|ucf');
				};

				scope.inEdition = function() {
					return scope.edition.edit;
				};

				scope.addItem = function() {
					if (scope.newModel) {
						scope.inline = {"model": scope.newModel};
						scope.editInline();
					}
				};

				scope.editInline = function() {
					scope.edition.edit = true;
				};

				scope.closeEditor = function() {
					scope.edition.edit = false;
				};

				scope.deleteInline = function() {
					scope.edition.edit = false;
					scope.inline = null;
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

	app.directive('rbsInlineDocument', ['$http', '$templateCache', '$compile', 'RbsChange.REST', 'RbsChange.Navigation',
		function ($http, $templateCache, $compile, REST, Navigation) {
		return {
			restrict : 'E',
			scope : {
				'document' : '=',
				'refLcid' : '=', 'lcid' : '='
			},
			link: function(scope, element, attrs) {
				scope.modelInfo = null;
				scope.localization = {
					"refLCID": scope.refLcid,
					"LCID" : scope.lcid,
					"inTranslation": function() {
						return this.LCID != null && this.LCID != this.refLCID;
					},
					"currentLCID": function() {
						return this.inTranslation() ? this.LCID : this.refLCID;
					}
				};

				scope.getContextKey = function () {
					return attrs.contextKey || 'rbsInlineDocument';
				};

				scope.$on('Navigation.saveContext', function(event, args) {
					args.context.savedData(scope.getContextKey(), {localization: scope.localization, modelInfo: scope.modelInfo});
				});

				var currentContext = Navigation.getCurrentContext();
				if (currentContext) {
					var data = currentContext.savedData(scope.getContextKey());
					if (data) {
						scope.localization = data.localization;
						scope.modelInfo = data.modelInfo;
					}
				}

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
								if (modelInfo.metas.localized) {
									scope.LCIDArray();
								}
							});
						}
						onRenderEditor();
					} else {
						scope.modelInfo = null;
					}
				});

				scope.$watch('refLcid', function (refLCID) {
					scope.localization.refLCID = refLCID;
					scope.defineLocalization(refLCID);
				});

				scope.$watch('lcid', function (lcid) {
					scope.localization.LCID = lcid;
					scope.defineLocalization(lcid);
				});

				scope.defineLocalization = function(LCID) {
					if (LCID && scope.document) {
						var d = scope.document;
						if (!angular.isObject(d['LCID']) || angular.isArray(d['LCID']) ) {
							d['LCID'] = {};
						}
						if (!angular.isObject(d['LCID'][LCID])) {
							d['LCID'][LCID] = {"LCID": LCID};
						}
					}
				}
			}
		};
	}]);

	angular.module('RbsChange').directive('rbsInlineActivationSection', ['RbsChange.Settings', function(Settings) {
		return {
			restrict: 'E',
			templateUrl: 'Rbs/Admin/js/directives/inline-activation-section.twig',
			link : function (scope, elm, attrs) {
				var _timeZone = Settings.get('TimeZone');

				function now () {
					return moment.utc().tz(_timeZone);
				}

				scope.activableDocument = function() {
					if (scope.document && scope.document.LCID)
					{
						return scope.document.LCID[scope.localization.currentLCID()];
					}
					return scope.document;
				};

				function toIso (date) {
					return date.zone('+0000').format('YYYY-MM-DDTHH:mm:ssZZ')
				}

				function setActivationClasses() {
					var d = scope.activableDocument();
					if (d && d.startActivation && d.endActivation) {
						var startAct = moment(d.startActivation);
						var endAct = moment(d.endActivation);

						if (endAct.diff(startAct, 'weeks', true) == 1) {
							scope.activationOffsetClass = {"1w": "active", "2w" : null, "1M": null};
						} else if (endAct.diff(startAct, 'weeks', true) == 2) {
							scope.activationOffsetClass = {"1w": null, "2w" : "active", "1M": null};
						} else if (endAct.diff(startAct, 'months', true) == 1) {
							scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": "active"};
						} else {
							scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": null};
						}
					}
					else {
						scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": null};
					}
				}

				scope.$on('Change:TimeZoneChanged', function (event, tz) {
					_timeZone = tz;
				});

				//2014-04-08T07:29:09+0000
				scope.activationNow = function(){
					scope.activableDocument().startActivation = toIso(now());
				};

				scope.activationTomorrow = function(){
					scope.activableDocument().startActivation = toIso(now().startOf('d').add('d', 1));
				};

				scope.activationNextMonday = function(){
					scope.activableDocument().startActivation = toIso(now().add('w', 1).startOf('w').startOf('d'));
				};

				scope.activationNextMonth = function(){
					scope.activableDocument().startActivation = toIso(now().add('M', 1).startOf('M').startOf('d'));
				};

				scope.$watch('activableDocument().startActivation', function(newValue, oldValue){
					if (newValue != oldValue && angular.isObject(scope.activationOffsetClass)) {
						if (newValue) {
							if (scope.activationOffsetClass['1w']) {
								scope.endActivationOneWeek();
							} else if (scope.activationOffsetClass['2w']) {
								scope.endActivationTwoWeeks();
							} else if (scope.activationOffsetClass['1M']) {
								scope.endActivationOneMonth();
							}
						} else {
							setActivationClasses();
						}
					}
				});

				scope.$watch('activableDocument().endActivation', function(){
					setActivationClasses();
				});

				scope.endActivationOneWeek = function(toggle){
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['1w']) {
						scope.activationOffsetClass['1w'] = null;
						return;
					}
					var d = scope.activableDocument();
					d.endActivation = toIso(moment(d.startActivation).add('w', 1));
					scope.activationOffsetClass = {"1w":"active", "2w" : null, "1M": null};
				};

				scope.endActivationTwoWeeks = function(toggle){
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['2w']) {
						scope.activationOffsetClass['2w'] = null;
						return;
					}
					var d = scope.activableDocument();
					d.endActivation = toIso(moment(d.startActivation).add('w', 2));
					scope.activationOffsetClass = {"1w":null, "2w" : "active", "1M": null};
				};

				scope.endActivationOneMonth = function(toggle) {
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['1M']) {
						scope.activationOffsetClass['1M'] = null;
						return;
					}
					var d = scope.activableDocument();
					d.endActivation = toIso(moment(d.startActivation).add('M', 1));
					scope.activationOffsetClass = {"1w":null, "2w" : null, "1M": "active"};
				};

				scope.endActivationTomorrow = function(){
					scope.activableDocument().endActivation = toIso(moment().endOf('d'));
				};

				scope.endActivationEndOfWeek = function(){
					scope.activableDocument().endActivation = toIso(moment().endOf('w'));
				};

				scope.endActivationEndOfMonth = function(){
					scope.activableDocument().endActivation = toIso(moment().endOf('M'));
				};
			}
		};
	}]);
})(window.jQuery);