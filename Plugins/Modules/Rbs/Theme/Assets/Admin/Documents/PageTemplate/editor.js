(function () {

	"use strict";

	function editorRbsThemePageTemplate(Breadcrumb)
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

				scope.onLoad = function () {
					if (!angular.isObject(scope.document.editableContent) || angular.isArray(scope.document.editableContent))
					{
						scope.document.editableContent = {}
					}
					if (!angular.isObject(scope.document.contentByWebsite) || angular.isArray(scope.document.contentByWebsite))
					{
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
						if (value.type == 'block')
						{
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
					var row = scope.blockList[index];
					scope.block = null;
					scope.blockParameters = null;
					scope.blockList.splice(index, 1);
				};

				scope.getBlockById = function(id) {
					var blockList;
					if (scope.websiteId)
					{
						if (scope.document.contentByWebsite.hasOwnProperty(scope.websiteId))
						{
							blockList = scope.document.contentByWebsite[scope.websiteId];
						}
					}
					else
					{
						blockList = scope.document.editableContent;
					}
					if (blockList && blockList.hasOwnProperty(id))
					{
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

					if (row.block && row.block.hasOwnProperty('name'))
					{
						if (row.name != row.block.name)
						{
							row.name = row.block.name;
							scope.block.parameters = {};
						}
					}

					scope.block.name = row.name;
					if (!angular.isObject(scope.block.parameters) || angular.isArray(scope.block.parameters))
					{
						scope.block.parameters = {};
					}

					scope.blockParameters = scope.block.parameters;
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
					if (scope.websiteId)
					{
						return !row.override;
					}
					return false;
				};

				scope.addBlockOverride = function(row) {
					var block = {};
					angular.copy(scope.document.editableContent[row.id], block);
					var contentByWebsite = scope.document.contentByWebsite;
					if (!contentByWebsite.hasOwnProperty(scope.websiteId))
					{
						contentByWebsite[scope.websiteId] = {};
					}
					contentByWebsite[scope.websiteId][row.id] = block;
					row.override = true;
				};

				scope.canRemoveOverrideBlock = function(row) {
					if (scope.websiteId)
					{
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
				}
			}
		};
	}

	editorRbsThemePageTemplate.$inject = ['RbsChange.Breadcrumb'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsThemePageTemplate', editorRbsThemePageTemplate);
})();