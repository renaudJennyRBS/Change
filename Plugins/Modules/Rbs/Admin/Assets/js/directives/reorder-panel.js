(function () {

	"use strict";

	function compareItems (item1, item2) {
		var	l1 = angular.lowercase(item1.label),
			l2 = angular.lowercase(item2.label);
		if (l1 < l2) {
			return -1;
		} else if (item1.label > item2.label) {
			return 1;
		}
		return 0;
	}

	function reorderPanelDirectiveFn () {

		return {
			"restrict"    : 'E',
			"templateUrl" : 'Rbs/Admin/js/directives/reorder-panel.twig',

			"scope" : {
				"originals" : "=documents"
			},

			"link" : function reorderPanelDirectiveLinkFn (scope) {

				scope.revert = function () {
					scope.documents = angular.copy(scope.originals);
				};

				scope.revert();

				scope.isUnchanged = function () {
					return angular.equals(scope.documents, scope.originals);
				};

				scope.save = function () {
					// TODO
					//scope.originals = scope.documents;
				};

				scope.useAlphabeticalSort = function () {
					scope.documents.sort(compareItems);
				};

				scope.reverseSort = function () {
					scope.documents.reverse();
				};
			}

		};

	}

	angular.module('RbsChange').directive(
		'reorderPanel',
		[
			reorderPanelDirectiveFn
		]
	);

})();