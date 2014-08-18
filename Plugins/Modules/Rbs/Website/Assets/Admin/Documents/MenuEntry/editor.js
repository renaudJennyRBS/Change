(function() {
	"use strict";

	function rbsWebsiteMenuEntryEditor(REST) {
		return {
			restrict: 'A',
			link: function(scope, elm, attrs) {
				scope.$watch('document.label', function (label, oldLabel) {
					var d = scope.document, LCID = scope.localization.currentLCID();
					if (d && LCID) {
						if (d.LCID && d.LCID[LCID] && d.LCID[LCID].title == oldLabel) {
							d.LCID[LCID].title = label;
						}
					}
				});

				scope.$watch('document.targetDocument', function(doc) {
					refreshLabelAndTitle(doc);
				});

				scope.$watch('document.subMenu', function(doc) {
					refreshLabelAndTitle(doc);
				});

				var refreshLabelAndTitle = function(doc) {
					var d = scope.document, LCID = scope.localization.currentLCID();
					if (doc && !d.label || (d.LCID && d.LCID[LCID] && !d.LCID[LCID].title)) {
						REST.ensureLoaded(doc).then(function (document) {
							if (!d.label) {
								d.label = document.label;
							}
							if (d.LCID && d.LCID[LCID] && !d.LCID[LCID].title) {
								d.LCID[LCID].title = document.title;
							}
						});
					}
				}
			}
		};
	}

	rbsWebsiteMenuEntryEditor.$inject = ['RbsChange.REST'];
	angular.module('RbsChange').directive('rbsWebsiteMenuEntryEditor', rbsWebsiteMenuEntryEditor);
})();