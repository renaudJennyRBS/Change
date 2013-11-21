(function () {

	"use strict";

	function editorRbsThemePageTemplate(ArrayUtils, REST)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Theme/PageTemplate/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.websiteId = null;

				scope.blockList = [];

				scope.block = null;

				scope.blockParameters = null;

				scope.$on('blockSelected', function(event, args) {
					angular.forEach(scope.blockList, function(value, key) {
						if (value.block && value.block.name === args.name && value.name != args.name) {
							// If there is a block, load default parameters and open parameterize panel.
							if (value.block.name) {
								REST.blockInfo(args.name).then(function (blockInfo) {
									scope.blockList[key].name = args.name;
									var parameters = {};
									angular.forEach(blockInfo.parameters, function(parameter) {
										if (parameter.hasOwnProperty('defaultValue')) {
											parameters[parameter.name] = parameter.defaultValue;
										}
									});
									var block = scope.getBlockById(value.id);
									block.parameters = parameters;
									if (scope.canParametrizeBlock(value)) {
										scope.parametrizeBlock(key);
									}
								});
							}
						}
					});
				});

				scope.onLoad = function () {
					if (!angular.isObject(scope.document.editableContent) || angular.isArray(scope.document.editableContent)) {
						scope.document.editableContent = {}
					}
					if (!angular.isObject(scope.document.contentByWebsite) || angular.isArray(scope.document.contentByWebsite)) {
						scope.document.contentByWebsite = {}
					}
				};

				scope.onReady = function() {
					scope.buildBlockList();
				};

				scope.buildBlockList = function() {

					var editableContent = scope.document.editableContent,
						contentByWebsite = scope.document.contentByWebsite,
						blockList = [], webBlocks = {}, byWebsite, row;

					byWebsite = scope.websiteId != null;

					if (byWebsite && contentByWebsite.hasOwnProperty(scope.websiteId)) {
						webBlocks = contentByWebsite[scope.websiteId];
					}

					angular.forEach(editableContent, function(value, key) {
						if (value.type == 'block') {
							row = {id: value.id, name: value.name, override: false, block: {name: ''}};
							if (webBlocks.hasOwnProperty(key)) {
								row.name = webBlocks[key].name;
								row.override = true
							}
							blockList.push(row);
						}
					});
					scope.blockList = blockList;
				};

				scope.$watch('websiteId', function(newValue) {
					scope.buildBlockList();
				});

				editorCtrl.init('Rbs_Theme_PageTemplate');

				scope.isEditorRow = function(row) {
					return row.parameters;
				};

				scope.inEditMode = function() {
					return scope.block !== null;
				};

				scope.canChangeBlocName = function(row) {
					return !(scope.inEditMode() || (scope.websiteId && !row.override));
				};

				scope.closeBlock = function(index) {
					scope.block = null;
					scope.blockParameters = null;
					scope.blockList.splice(index, 1);
				};

				scope.getBlockById = function(id) {
					var blockList;
					if (scope.websiteId) {
						if (scope.document.contentByWebsite.hasOwnProperty(scope.websiteId)) {
							blockList = scope.document.contentByWebsite[scope.websiteId];
						}
					}
					else {
						blockList = scope.document.editableContent;
					}
					if (blockList && blockList.hasOwnProperty(id)) {
						return blockList[id];
					}
					return null;
				};

				scope.canParametrizeBlock = function(row) {
					return row.block && row.block.template && scope.getBlockById(row.id) != null;
				};

				scope.parametrizeBlock = function(index) {
					var row = scope.blockList[index];
					scope.block = scope.getBlockById(row.id);
					if (row.block && row.block.hasOwnProperty('name')) {
						if (row.name != row.block.name) {
							row.name = row.block.name;
							scope.block.parameters = {};
						}
					}

					scope.block.name = row.name;
					if (!angular.isObject(scope.block.parameters) || angular.isArray(scope.block.parameters)) {
						scope.block.parameters = {};
					}

					scope.blockParameters = scope.block.parameters;
					if (!scope.blockParameters.hasOwnProperty('TTL')) {
						scope.blockParameters.TTL = 60;
					}
					scope.blockList.splice(index + 1, 0, {parameters: row.id, template: row.block.template});
				};

				scope.canEmptyBlock = function(row) {
					return row.name != '' && scope.getBlockById(row.id) != null;
				};

				scope.emptyBlock = function(row) {
					var block =  scope.getBlockById(row.id);
					block.name = '';
					block.parameters = {};
					row.name = '';
					row.block = {};
				};

				scope.canOverrideBlock = function(row) {
					if (scope.websiteId) {
						return !row.override;
					}
					return false;
				};

				scope.addBlockOverride = function(row) {
					var block = {};
					angular.copy(scope.document.editableContent[row.id], block);
					var contentByWebsite = scope.document.contentByWebsite;
					if (!contentByWebsite.hasOwnProperty(scope.websiteId)) {
						contentByWebsite[scope.websiteId] = {};
					}
					contentByWebsite[scope.websiteId][row.id] = block;
					row.override = true;
				};

				scope.canRemoveOverrideBlock = function(row) {
					if (scope.websiteId) {
						return row.override;
					}
					return false;
				};

				scope.removeBlockOverride = function(row) {
					var contentByWebsite = scope.document.contentByWebsite;
					delete contentByWebsite[scope.websiteId][row.id];
					var block = scope.document.editableContent[row.id];
					row.block = {};
					row.name = block.name;
					row.override = false;
				};

				scope.hasTTL = function(seconds)
				{
					return scope.blockParameters.TTL == seconds;
				};

				scope.setTTL = function(seconds)
				{
					scope.blockParameters.TTL = seconds;
				};

				scope.isVisibleFor = function (device) {
					if (device == 'raw') {
						return scope.block.visibility == 'raw';
					} else if (scope.block.visibility == 'raw') {
						return false;
					}
					return (!scope.block.visibility || scope.block.visibility.indexOf(device) !== -1);
				};

				scope.toggleVisibility = function (device) {
					var value = !scope.isVisibleFor(device), splat;

					if (device == 'raw') {
						if (value) {
							scope.block.visibility = device;
						} else {
							delete scope.block.visibility;
						}
						return;
					}
					else if (scope.block.visibility == 'raw') {
						delete scope.block.visibility;
					}

					if (scope.block.visibility) {
						splat = scope.block.visibility.split('');
						if (ArrayUtils.inArray(device, splat) !== -1 && ! value) {
							ArrayUtils.removeValue(splat, device);
						} else if (ArrayUtils.inArray(device, splat) === -1 && value) {
							splat.push(device);
						}
						splat.sort();
						if (splat.join('') == '') {
							delete scope.block.visibility;
						} else {
							scope.block.visibility = splat.join('');
						}
					} else {
						if (value) {
							scope.block.visibility = device;
						} else {
							switch (device) {
								case 'X' :
									scope.block.visibility = 'SML';
									break;
								case 'S' :
									scope.block.visibility = 'XML';
									break;
								case 'M' :
									scope.block.visibility = 'XSL';
									break;
								case 'L' :
									scope.block.visibility = 'XSM';
									break;
							}
						}
					}
				};
			}
		};
	}

	editorRbsThemePageTemplate.$inject = ['RbsChange.ArrayUtils', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsThemePageTemplate', editorRbsThemePageTemplate);
})();