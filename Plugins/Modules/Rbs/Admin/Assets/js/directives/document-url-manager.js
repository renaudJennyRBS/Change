(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsDocumentUrlManager', ['$http', 'RbsChange.REST', 'RbsChange.NotificationCenter', 'RbsChange.Utils', 'RbsChange.ErrorFormatter',
		function ($http, REST, NotificationCenter, Utils, ErrorFormatter)
	{
		return {
			restrict : 'E',
			scope : {
				targetDocument : '=document'
			},
			templateUrl : 'Rbs/Admin/js/directives/document-url-manager.twig',

			link : function (scope)
			{
				scope.rules = [];
				scope.locations = [];
				scope.displayConfig = {};
				scope.newRedirect = {};
				scope.oldValues = {};
				scope.newRuleLastId = -1;
				scope.saveProgress = {success:false, error:false, running:false};
				var unchanged = true;

				var initPathRule = function (result) {
					scope.rules = result.rules;
					unchanged = true;
					scope.locations = result.locations;

					for (var i = 0; i < scope.locations.length; i++) {
						scope.displayConfig[i] = {'showDetails': false, 'showRedirects': false, 'urls': [], 'redirects': []};

						for (var j = 0; j < scope.locations[i].urls.length; j++) {
							scope.displayConfig[i].urls.push({ 'edit': false });
						}
						for (j = 0; j < scope.locations[i].redirects.length; j++) {
							scope.displayConfig[i].redirects.push({ 'edit': false });
						}
						scope.newRedirect[i] = {'relativePath': '', 'permanent': true, 'query': ''};
					}

					if (scope.saveProgress.running) {
						scope.saveProgress.running = scope.saveProgress.error = false;
						scope.saveProgress.success = true;
					}
				};

				scope.$watch('targetDocument', function (tgtDoc)
				{
					// Wait for document to become ready.
					if (!tgtDoc) {
						return;
					}

					// Load DocumentSeo document related to the target document.
					var pathRulesURL = tgtDoc.getLink('pathRules');
					if (pathRulesURL) {
						scope.pathRulesURL = pathRulesURL;
						REST.call(scope.pathRulesURL, null, REST.resourceTransformer()).then(initPathRule);
					}
				});

				REST.getAvailableLanguages().then(function (langs) {
					scope.availableLanguages = langs.items;
				});

				scope.isUnchanged = function() {
					return unchanged;
				};

				scope.savePathRules = function() {
					if (scope.rules.length && !scope.saveProgress.running) {
						scope.saveProgress.running = true;
						scope.saveProgress.success = scope.saveProgress.error = false;
						var url = scope.pathRulesURL, rules = scope.rules;
						$http.post(url, {rules: rules})
							.success(initPathRule)
							.error(function(error) {
								scope.saveProgress.running = scope.saveProgress.success = false;
								scope.saveProgress.error = true;
						});
					}
				};

				scope.revert = function() {
					REST.call(scope.pathRulesURL, null, REST.resourceTransformer()).then(initPathRule);
				};

				// Private functions.
				var showRedirects = function(locationIndex) {
					scope.displayConfig[locationIndex].showRedirects = true;
				};

				var isModified = function(locationIndex, type, index) {
					var key = locationIndex + type + index;
					if (scope.locations[locationIndex][type][index].relativePath != scope.oldValues[key].relativePath) {
						return true;
					}
					if (scope.locations[locationIndex][type][index].query != scope.oldValues[key].query) {
						return true;
					}
					if (type != 'redirects') {
						return false;
					}
					return scope.locations[locationIndex][type][index].permanent != scope.oldValues[key].permanent;
				};

				var saveOldValue = function(locationIndex, type, index) {
					var key = locationIndex + type + index;
					scope.oldValues[key] = {};
					for (var name in scope.locations[locationIndex][type][index]) {
						if (scope.locations[locationIndex][type][index].hasOwnProperty(name)) {
							scope.oldValues[key][name] = scope.locations[locationIndex][type][index][name];
						}
					}
				};

				var restoreOldValue = function(locationIndex, type, index) {
					var key = locationIndex + type + index;
					for (var name in scope.oldValues[key]) {
						if (scope.oldValues[key].hasOwnProperty(name))
						{
							scope.locations[locationIndex][type][index][name] = scope.oldValues[key][name];
						}
					}
					scope.oldValues[key] = null;
				};

				var pushItem = function(locationIndex, type, item) {
					scope.locations[locationIndex][type].push(item);
					scope.displayConfig[locationIndex][type].push({ 'edit': false });
				};

				var deleteItem = function(locationIndex, type, index)
				{
					scope.locations[locationIndex][type].splice(index, 1);
					scope.displayConfig[locationIndex][type].splice(index, 1);
				};

				var removeRedirectByRelativePath = function(locationIndex, relativePath) {
					var length = scope.locations[locationIndex].redirects.length;
					for (var i = 0; i < length; i++) {
						if (scope.locations[locationIndex].redirects[i].relativePath == relativePath) {
							showRedirects(locationIndex);
							scope.deleteRedirect(locationIndex, i);
							return;
						}
					}
				};

				var addRedirectFromOldValues = function(locationIndex, index) {
					showRedirects(locationIndex);
					scope.locations[locationIndex].redirects.push({
						id: scope.oldValues[locationIndex + 'urls' + index].id,
						relativePath: scope.oldValues[locationIndex + 'urls' + index].relativePath,
						query: scope.oldValues[locationIndex + 'urls' + index].query,
						sectionId: scope.oldValues[locationIndex + 'urls' + index].sectionId,
						permanent: true
					});
					scope.displayConfig[locationIndex].redirects.push({ 'edit': false });
					updateRule(locationIndex, 'redirects', scope.locations[locationIndex].redirects.length-1);
				};

				var deleteRule = function(locationIndex, type, index) {
					unchanged = scope.saveProgress.success = false;

					var rules = scope.rules, rule, i, ruleId = scope.locations[locationIndex][type][index].id;
					for (i = 0; i < rules.length; i++) {
						rule = rules[i];
						if (rule.id == ruleId) {
							if (ruleId > 0) {
								rules[i] = {id: ruleId, httpStatus: 404, updated: true};
							} else {
								rules.splice(i, 1);
							}
						}
					}
				};

				var isDeletedRule = function(ruleId) {
					var rule, i, rules = scope.rules;
					for (i = 0; i < rules.length; i++) {
						rule = rules[i];
						if (rule.id == ruleId && rule.httpStatus == 404) {
							return true;
						}
					}
					return false
				};

				var addRule = function(locationIndex, type, index) {
					unchanged = scope.saveProgress.success = false;
					var item = scope.locations[locationIndex][type][index];
					scope.rules.push({
						id: item.id,
						websiteId: scope.locations[locationIndex]['websiteId'],
						sectionId: item.sectionId,
						httpStatus: (type == 'urls' ? 200 : (item.permanent ? 301 : 302)),
						LCID: scope.locations[locationIndex]['LCID'],
						relativePath: item.relativePath,
						query: item.query
					});
				};

				var updateRule = function(locationIndex, type, index) {
					unchanged = scope.saveProgress.success = false;
					var rules = scope.rules, rule, i, item = scope.locations[locationIndex][type][index];
					for (i = 0; i < rules.length; i++) {
						rule = rules[i];
						if (rule.id == item.id) {
							rule.relativePath = item.relativePath;
							rule.query = item.query;
							rule.httpStatus = (type == 'urls' ? 200 : (item.permanent ? 301 : 302));
							rule.updated = true;
							break;
						}
					}
				};

				// URLs handling.
				scope.getHref = function (location, url) {
					if (url === undefined) {
						return location.baseUrl;
					}
					return location.baseUrl + url.relativePath + (url.query ? ('?' + url.query) : '');
				};

				scope.startEditUrl = function (locationIndex, index) {
					saveOldValue(locationIndex, 'urls', index);
					scope.displayConfig[locationIndex].urls[index].edit = true;
				};

				scope.undoEditUrl = function (locationIndex, index) {
					NotificationCenter.clear();
					restoreOldValue(locationIndex, 'urls', index);
					scope.displayConfig[locationIndex].urls[index].edit = false;
				};

				scope.updateUrl = function (locationIndex, index) {
					NotificationCenter.clear();
					if (isModified(locationIndex, 'urls', index)) {
						var url = scope.pathRulesURL,
							location = scope.locations[locationIndex],
							checkRule = {websiteId: location.websiteId, LCID: location.LCID, relativePath: location.urls[index].relativePath};

						$http.post(url,{checkRule: checkRule}).success(function (data) {
							var ruleId = scope.locations[locationIndex].urls[index].id;

							if (data.checked || ruleId == data.ruleId || isDeletedRule(data.ruleId)) {
								// Remove potential redirect with the same relative path.
								var newRelativePath = scope.locations[locationIndex].urls[index].relativePath;

								removeRedirectByRelativePath(locationIndex, newRelativePath);

								addRedirectFromOldValues(locationIndex, index);

								scope.locations[locationIndex].urls[index].id = scope.newRuleLastId--;
								addRule(locationIndex, 'urls', index);

								scope.oldValues[locationIndex + 'urls' + index] = null;
								scope.displayConfig[locationIndex].urls[index].edit = false;
							} else {
								NotificationCenter.error('Invalid URL', ErrorFormatter.format({message: 'This URL is already used!', code: 999999}));
							}
						});
					}
				};

				scope.getLocationDefaultRelativePath = function (locationIndex, index) {
					var location = scope.locations[locationIndex];
					if (location.canonical && !location.urls[index].sectionId) {
						return location.defaultCanonicalUrl.relativePath;
					} else {
						return location.defaultUrl.relativePath;
					}
				};

				scope.restoreDefaultUrl = function (locationIndex, index) {
					// Set the default URL.
					saveOldValue(locationIndex, 'urls', index);
					deleteRule(locationIndex, 'urls', index);

					var defaultUrl, location = scope.locations[locationIndex];
					if (location.canonical && !location.urls[index].sectionId) {
						defaultUrl = location.defaultCanonicalUrl;
					} else {
						defaultUrl = location.defaultUrl;
					}
					location.urls[index].id = scope.newRuleLastId--;
					location.urls[index].relativePath = defaultUrl.relativePath;

					// Remove potential redirect with the same relative path.
					removeRedirectByRelativePath(locationIndex, defaultUrl.relativePath);

					// Add the redirect to the old URL.
					addRedirectFromOldValues(locationIndex, index);
					showRedirects(locationIndex);

					scope.oldValues[locationIndex + 'urls' + index] = null;

					addRule(locationIndex, 'urls', index);
				};

				// Redirects handling.
				scope.startEditRedirect = function (locationIndex, index) {
					saveOldValue(locationIndex, 'redirects', index);
					scope.displayConfig[locationIndex].redirects[index].edit = true;
				};

				scope.undoEditRedirect = function (locationIndex, index) {
					NotificationCenter.clear();
					restoreOldValue(locationIndex, 'redirects', index);
					scope.displayConfig[locationIndex].redirects[index].edit = false;
				};

				scope.updateRedirect = function (locationIndex, index) {
					NotificationCenter.clear();
					var url = scope.pathRulesURL,
						location = scope.locations[locationIndex],
						checkRule = {websiteId: location.websiteId, LCID: location.LCID, relativePath: location.redirects[index].relativePath};

					$http.post(url,{checkRule: checkRule}).success(function (data) {
						var ruleId = scope.locations[locationIndex].redirects[index].id;
						if (data.checked || ruleId == data.ruleId || isDeletedRule(data.ruleId)) {
							if (ruleId > 0) {
								deleteRule(locationIndex, 'redirects', index);
								scope.locations[locationIndex].redirects[index].id = scope.newRuleLastId--;
								addRule(locationIndex, 'redirects', index);
							} else {
								updateRule(locationIndex, 'redirects', index);
							}
							scope.oldValues[locationIndex + 'redirects' + index] = null;
							scope.displayConfig[locationIndex].redirects[index].edit = false;
						} else {
							NotificationCenter.error('Invalid URL', ErrorFormatter.format({message: 'This URL is already used!', code: 999999}));
						}
					});
				};

				scope.deleteRedirect = function (locationIndex, index) {
					deleteRule(locationIndex, 'redirects', index);
					deleteItem(locationIndex, 'redirects', index);
				};

				scope.makeCurrentUrl = function (locationIndex, index) {
					var redirect = scope.locations[locationIndex].redirects[index];
					deleteItem(locationIndex, 'redirects', index);

					var length = scope.locations[locationIndex].urls.length;
					for (var i = 0; i < length; i++) {
						var url = scope.locations[locationIndex].urls[i];
						if (url.query == redirect.query && url.sectionId == redirect.sectionId) {
							deleteItem(locationIndex, 'urls', i);
							url.httpStatus = 301;
							url.permanent = true;
							pushItem(locationIndex, 'redirects', url);
							updateRule(locationIndex, 'redirects', scope.locations[locationIndex].redirects.length-1);
							break;
						}
					}

					redirect.httpStatus = 200;
					pushItem(locationIndex, 'urls', redirect);
					updateRule(locationIndex, 'urls', scope.locations[locationIndex].urls.length-1);
				};

				scope.addNewRedirect = function (locationIndex) {
					NotificationCenter.clear();
					var url = scope.pathRulesURL,
						location = scope.locations[locationIndex],
						checkRule = {websiteId: location.websiteId, LCID: location.LCID, relativePath: scope.newRedirect[locationIndex].relativePath};
					$http.post(url,{checkRule: checkRule}).success(function (data) {
						if (data.checked ||isDeletedRule(data.ruleId)) {
							scope.locations[locationIndex].redirects.push({
								id: scope.newRuleLastId--,
								relativePath: scope.newRedirect[locationIndex].relativePath,
								query: scope.newRedirect[locationIndex].query,
								permanent: scope.newRedirect[locationIndex].permanent
							});
							addRule(locationIndex, 'redirects', scope.locations[locationIndex].redirects.length-1);
							scope.displayConfig[locationIndex].redirects.push({'edit': false });
							scope.clearNewRedirect(locationIndex);
						} else {
							NotificationCenter.error('Invalid URL', ErrorFormatter.format({message: 'This URL is already used!', code: 999999}));
						}
					});
				};

				scope.clearNewRedirect = function (locationIndex) {
					NotificationCenter.clear();
					scope.newRedirect[locationIndex] = {'relativePath': '', 'permanent': true, 'query': ''};
				};
			}
		};
	}]);

})();