(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	//-------------------------------------------------------------------------
	// rbs-row
	// Row.
	//
	//-------------------------------------------------------------------------
	app.directive('rbsRow', ['structureEditorService', function (structureEditorService) {
		return {
			"restrict"   : 'A',
			"require"    : "^rbsStructureEditor",
			"scope"      : {}, // isolated scope is required

			"link" : function seRowLinkFn (scope, elm, attrs, ctrl) {
				var item = ctrl.getItemById(elm.data('id'));

				elm.addClass('row');

				elm.click(function (event) {
					if (event.target === this || event.target.parentNode === this) {
						ctrl.selectBlock(elm);
					}
				});

				structureEditorService.initChildItems(scope, elm, item, ctrl.isReadOnly());
			}
		};
	}]);

	//-------------------------------------------------------------------------
	// rbs-cell
	// Column.
	//
	//-------------------------------------------------------------------------
	app.directive('rbsCell', ['structureEditorService', function (structureEditorService) {
		return {
			"restrict"   : 'A',
			"template"   : '<div class="{{span}} {{offset}} block-container"></div>',
			"replace"    : true,
			"scope"      : {}, // isolated scope is required
			"require"    : '^rbsStructureEditor',

			"link" : function seRowLinkFn (scope, elm, attrs, ctrl) {
				var item = ctrl.getItemById(elm.data('id'));

				scope.span = 'col-md-' + item.size;
				if (item.offset) {
					scope.offset = 'col-md-offset-' + item.offset;
				}

				scope.saveItem = function (item) {
					if (item) {
						item.size = parseInt(elm.attr('data-size'), 10);
						item.offset = parseInt(elm.attr('data-offset'), 10) || 0;
					}
				};

				// On click on a column, select the row.
				elm.click(function (event) {
					if (event.target === this) {
						ctrl.selectBlock(jQuery(this.parentNode));
					}
				});

				structureEditorService.initChildItems(scope, elm, item, ctrl.isReadOnly());
			}
		};
	}]);

	//-------------------------------------------------------------------------
	// rbs-block-chooser
	// Block chooser.
	//
	//-------------------------------------------------------------------------
	app.directive('rbsBlockChooser', [ function () {
		return {
			"restrict": 'A',
			"scope": true,
			"require": '^rbsStructureEditor',
			"replace": true,
			"templateUrl": 'Rbs/Admin/js/directives/structure-editor-block-chooser.twig',

			"link": function seBlockChooserLinkFn(scope, element, attrs, ctrl) {
				scope.selectBlock = function ($event) {
					ctrl.selectBlock(element);
				};
			}
		};
	}]);

	//-------------------------------------------------------------------------
	// rbs-block-template
	// Block template.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsBlockTemplate', [ function () {
		return {
			"restrict": 'A',
			"scope": {}, // isolated scope is required
			"require": '^rbsStructureEditor',
			"replace": true,
			"template": '<div draggable="true" class="block btn btn-block btn-settings break-word block-draggable block-handle" ng-click="selectBlock($event)">' +
				'<i class="icon-th-large"></i> <span ng-bind-html="item.label"></span><div><small>(= item.parameters | json =)</small></div>' +
				'</div>',

			"link": function seBlockTemplateLinkFn(scope, element, attrs, ctrl) {
				scope.item = ctrl.getItemById(element.data('id'));

				scope.selectBlock = function ($event) {
					ctrl.selectBlock(element);
				};
			}
		};
	}]);

	//-------------------------------------------------------------------------
	// rbs-block-markdown-text
	// Markdown text.
	//
	//-------------------------------------------------------------------------

	app.directive('rbsBlockMarkdownText', [ function () {
		return {
			"restrict": 'A',
			"scope": {
				// isolated scope is required
				readonly: '@'
			},
			"require": '^rbsStructureEditor',
			"transclude": true,
			"replace": true,
			"template": '<div class="block block-draggable" ng-click="selectBlock($event)"><rbs-rich-text-input data-draggable="true" ng-readonly="readonly" use-tabs="false" ng-model="input.text" profile="(= profile =)" substitution-variables="(= substitutionVariables =)"></rbs-rich-text-input></div>',

			"link": function seRichTextLinkFn(scope, element, attrs, ctrl) {
				element.attr('block-label', "Markdown");
				element.attr('block-type', "rich-text");

				scope.initItem = function (item) {
					item.parameters = {
						contentType: 'Markdown',
						content: ''
					};
				};

				var item = ctrl.getItemById(element.data('id'));
				if (!item.parameters) {
					scope.initItem(item);
				}
				scope.input = {text: item.parameters.content};
				scope.profile = item.name === 'Rbs_Mail_Richtext' ? 'Mail' : 'Website';
				scope.substitutionVariables = item.substitutionVariables ? JSON.parse(item.substitutionVariables) : [];

				scope.$watch('input.text', function (text, old) {
					if (text !== old) {
						scope.saveItem(item);
					}
				});

				scope.selectBlock = function ($event) {
					ctrl.selectBlock(element);
				};

				scope.saveItem = function (item) {
					if (item) {
						angular.extend(item.parameters, {content: scope.input.text});
					}
				};
			}
		};
	}]);

})(window.jQuery);