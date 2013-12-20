(function () {

	"use strict";

	function changeEditorWebsiteWebsite (REST, User) {

		return {
			restrict    : 'EA',
			templateUrl : 'Document/Rbs/Website/Website/editor.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl) {
				scope.data = {};
				scope.pendingSitemapCreations = false;
				scope.onReady = function (){
					scope.$watch('document.sitemaps', function (sitemaps){
						angular.forEach(sitemaps, function (sitemap){
							scope.pendingSitemapCreations = angular.isDefined(sitemap.jobId) && angular.isUndefined(sitemap.url);
						});
					});

					//take the first sitemap as reference for time interval and other stuff
					scope.data.timeInterval = scope.document.sitemaps[0].timeInterval;
					scope.$watch('data.timeInterval', function (value){
						angular.forEach(scope.document.sitemaps, function (sitemap){
							sitemap.timeInterval = value;
						});
					});

					scope.$watch('document.sitemapGeneration', function (value){
						var timeInterval = angular.isDefined(scope.data.timeInterval) ? scope.data.timeInterval : '';
						if (value === true)
						{
							angular.forEach(scope.document.sitemaps, function (sitemap){
								sitemap.timeInterval = timeInterval;
							});
						}
					});

				};

				scope.disableNotifyButtons = {};
				scope.notifyUrlCreation = function (sitemap){
					scope.disableNotifyButtons[sitemap.LCID] = true;
					REST.resource(scope.document.id).then(function (website){
						angular.forEach(website.sitemaps, function (websiteSitemap){
							if (sitemap.LCID === websiteSitemap.LCID)
							{
								websiteSitemap.notify = { userId: User.get().id };
								REST.save(website);
							}
						});
					});
				};

				editorCtrl.init('Rbs_Website_Website');
			}
		};

	}

	changeEditorWebsiteWebsite.$inject = ['RbsChange.REST', 'RbsChange.User'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteWebsite', changeEditorWebsiteWebsite);

})();