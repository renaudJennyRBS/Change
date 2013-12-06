(function ()
{
	"use strict";

	var app = angular.module('RbsChange');


	app.directive('rbsDocumentPreviewRbsCatalogProduct', function ()
	{
		return {
			restrict : 'E',
			scope : {
				document : '='
			},
			template :
				'<p><a href="(= document | rbsURL =)"><strong>(= document.label =)</strong><br/><small>(= document.sku.code =)</small></a></p>' +
				'<p><img rbs-storage-image="document.visuals[0].id" thumbnail="M"/></p>' +
				'<p><img ng-repeat="v in document.visuals" ng-if="! $first" rbs-storage-image="v.id" thumbnail="S"/></p>'
		};
	});

})();