/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsDocumentPreview
	 * @name Document preview
	 * @restrict E
	 *
	 * @param {Document} document The Document to preview.
	 *
	 * @description
	 * Displays a summary (or preview) of a Document.
	 *
	 * This Directive looks for another Directive named like this:<br/>
	 * `rbs-document-preview-&lt;vendor&gt;-&lt;plugin&gt;-&lt;document&gt;`
	 *
	 * - Directive exists: it is inserted into the DOM.
	 * - Directive does NOT exist: the `label` property of the Document is displayed.
	 *
	 * ### Declaring the Directive for the preview of a Document ###
	 *
	 * The Directive must have the following properties:
	 *
	 * - displayed as an element: <code>restrict: E</code>
	 * - with an <strong>isolated scope</strong> and a `document` attribute:  <code>scope:{document:'='}</code>
	 *
	 * <pre>
	 * angular.module('RbsChange').directive('rbsDocumentPreviewVendorPluginDocument', function ()
	 * {
	 *    return {
	 *       restrict : 'E',
	 *       scope : {
	 *          document : '='
	 *       },
	 *       template : '...',
	 *       templateUrl : '... .twig'
	 *       link : function (scope, iElement, iAttrs)
	 *       {
	 *          ...
	 *       }
	 *    };
	 * });
	 * </pre>
	 */
	app.directive('rbsDocumentPreview', ['RbsChange.REST', '$injector', '$compile', function(REST, $injector, $compile)
	{
		return {
			restrict : 'E',
			scope : {
				document : '='
			},

			link : function(scope, iElement)
			{
				scope.$watch('document', function(doc)
				{
					var html, tag, directiveName;
					if (doc && doc.model) {
						directiveName = 'rbsDocumentPreview' + doc.model.replace(/_/g, '');
						if ($injector.has(directiveName + 'Directive')) {
							tag = snakeCase(directiveName);
							html = '<' + tag + ' document="document"></' + tag + '>';
						}
						else {
							html = '<div data-ng-bind="document.label"></div>';
						}

						$compile(html)(scope, function(el) {
							iElement.empty().append(el);
						});

						REST.ensureLoaded(doc).then(function(doc) {
							angular.extend(scope.document, doc);
						});
					}
				});
			}
		};
	}]);


	function snakeCase(name, separator)
	{
		separator = separator || '-';
		return name.replace(/[A-Z]/g, function(letter, pos) {
			return (pos ? separator : '') + letter.toLowerCase();
		});
	}

})();
