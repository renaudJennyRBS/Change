(function() {
	"use strict";

	function rbsCollectionCollectionItemEditor(ArrayUtils) {
		return {
			restrict: 'A',
			link: function(scope, element, attrs) {
				scope.$watch('document.label', function (label, oldLabel) {
					if (scope.document) {
						var d = scope.document;
						if (d.value == oldLabel && !d.locked) {
							d.value = label;
						}
						if (scope.localization.LCID) {
							var LCID = scope.localization.LCID;
							if (d.LCID && d.LCID[LCID] && d.LCID[LCID].title == oldLabel) {
								d.LCID[LCID].title = label;
							}
						}
					}
				});

				//When loading item
				scope.$watch('document.refLCID', function (refLCID) {
					scope.localization.refLCID = refLCID;
					scope.localization.LCID = refLCID;
				});

				scope.$watch('localization.LCID', function (LCID) {
					scope.defineLocalization(LCID);
					if (LCID && scope.document) {
						var d = scope.document;
						if (d['LCID'] && d['LCID'][LCID] && !d.LCID[LCID].title) {
							d.LCID[LCID].title = d.label;
						}
					}
				})
			}
		};
	}

	rbsCollectionCollectionItemEditor.$inject = ['RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsCollectionCollectionItemEditor', rbsCollectionCollectionItemEditor);
})();