(function() {
	'use strict';

	var app = angular.module('RbsChangeApp');

	app.directive('rbsSocialButtons', ['$location', function($location) {
		return {
			restrict: 'A',
			templateUrl: '/rbsSocialButtons.tpl',
			scope: {},
			link: function(scope, elem, attrs) {
				var sites = ['twitter', 'facebook', 'google-plus', 'pinterest'];

				scope.shareUrl = attrs.shareUrl ? attrs.shareUrl : encodeURIComponent($location.absUrl());
				scope.shareTitle = attrs.shareTitle ? attrs.shareTitle : document.title;
				scope.shareTitleUri = encodeURIComponent(scope.shareTitle);

				// Init button data for each network.
				scope.items = [];
				var networkNames = attrs['networks'] ? attrs['networks'].toLowerCase().split(',') : sites;
				for (var i = 0; i < networkNames.length; i++) {
					var key = networkNames[i].trim();
					if (sites.indexOf(key) < 0) {
						continue;
					}

					var item = {};
					switch (key) {
						case 'twitter':
							item.type = 'link';
							item.title = 'Twitter';
							item.iconSrc = 'Theme/Rbs/Base/Rbs_Social/img/icons/twitter.png';
							item.href = 'http://twitter.com/intent/tweet?text=' + scope.shareTitleUri + '%20' + scope.shareUrl;
							break;
						case 'facebook':
							item.type = 'link';
							item.title = 'Facebook';
							item.iconSrc = 'Theme/Rbs/Base/Rbs_Social/img/icons/facebook.png';
							item.href = 'http://facebook.com/sharer.php?u=' + scope.shareUrl;
							break;
						case 'google-plus':
							item.type = 'link';
							item.title = 'Google+';
							item.iconSrc = 'Theme/Rbs/Base/Rbs_Social/img/icons/google.png';
							item.href = 'https://plus.google.com/share?url=' + scope.shareUrl;
							break;
						case 'pinterest':
							item.type = 'button';
							item.title = 'Pinterest';
							item.iconSrc = 'Theme/Rbs/Base/Rbs_Social/img/icons/pinterest.png';
							item.ngClick = function() {
								var e = document.createElement('script');
								e.setAttribute('type','text/javascript');
								e.setAttribute('charset','UTF-8');
								e.setAttribute('src','//assets.pinterest.com/js/pinmarklet.js?r=' + Math.random()*99999999);
								document.body.appendChild(e);
							};
							break;
					}
					scope.items.push(item);
				}
			}
		};
	}]);
})();