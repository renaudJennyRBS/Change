/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	/**
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-document-activation-section
	 * @restrict A
	 *
	 * @description
	 * Used to display the <em>Activation</em> section in Document editors.
	 *
	 * @example
	 * <pre>
	 *     <fieldset data-rbs-editor-section="activation"
	 *        data-editor-section-label="{{ i18nAttr('m.rbs.admin.admin.activation_properties', ['ucf']) }}"
	 *        data-rbs-document-activation-section="">
	 *     </fieldset>
	 * </pre>
	 */
	angular.module('RbsChange').directive('rbsDocumentActivationSection', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Admin/js/directives/document-activation-section.twig',
			replace: false,
			scope: true
		};
	}]);
})();