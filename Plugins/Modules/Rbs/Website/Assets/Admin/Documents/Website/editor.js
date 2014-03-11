(function () {

	"use strict";

	function changeEditorWebsiteWebsite (REST, User)
	{
		return {
			restrict    : 'A',
			require     : '^rbsDocumentEditorBase',

			link : function (scope)
			{
				scope.data = {};
				scope.pendingSitemapCreations = false;
				scope.onReady = function (){
					scope.$watch('document.sitemaps', function (sitemaps){
						angular.forEach(sitemaps, function (sitemap){
							scope.pendingSitemapCreations = angular.isDefined(sitemap.jobId) && angular.isUndefined(sitemap.url);
						});
					});

					//take the first sitemap as reference for time interval and other stuff
					if (angular.isArray(scope.document.sitemaps) && scope.document.sitemaps.length > 0) {
						scope.data.timeInterval = scope.document.sitemaps[0].timeInterval;
					}
					scope.$watch('data.timeInterval', function (value){
						angular.forEach(scope.document.sitemaps, function (sitemap){
							if (sitemap.timeInterval !== value && angular.isDefined(value))
							{
								sitemap.timeInterval = value;
							}
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
			}
		};

	}

	changeEditorWebsiteWebsite.$inject = ['RbsChange.REST', 'RbsChange.User'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteWebsite', changeEditorWebsiteWebsite);

})();