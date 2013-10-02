(function ()
{
	"use strict";

	/**
	 * @param $timeout
	 * @param $http
	 * @param Loading
	 * @param REST
	 * @constructor
	 */
	function Editor($timeout, $http, Loading, REST, NotificationCenter)
	{
		return {
			restrict : 'C',
			templateUrl : 'Rbs/Seo/DocumentSeo/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				/** @namespace scope.document.locations */
				scope.displayConfig = {};
				scope.newRedirect = {};
				scope.onReady = function() {
					for (var i = 0; i < scope.document.locations.length; i++)
					{
						scope.displayConfig[i] = {'showDetails': false, 'showRedirects': false, 'urls': [], 'redirects': []};
						for (var j = 0; j < scope.document.locations[i].urls.length; j++)
						{
							scope.displayConfig[i].urls.push({ 'edit': false });
						}
						for (j = 0; j < scope.document.locations[i].redirects.length; j++)
						{
							scope.displayConfig[i].redirects.push({ 'edit': false });
						}
						scope.newRedirect[i] = {'relativePath': '', 'permanent': true, 'query': ''};
					}
				};
				scope.oldValues = {};
				scope.newRuleLastId = -1;

				// Private functions.
				var showRedirects = function(locationIndex)
				{
					scope.displayConfig[locationIndex].showRedirects = true;
				};

				var isModified = function(locationIndex, type, index)
				{
					var key = locationIndex + type + index;
					if (scope.document.locations[locationIndex][type][index].relativePath != scope.oldValues[key].relativePath)
					{
						return true;
					}
					if (scope.document.locations[locationIndex][type][index].query != scope.oldValues[key].query)
					{
						return true;
					}
					if (type != 'redirects')
					{
						return false;
					}
					return scope.document.locations[locationIndex][type][index].permanent != scope.oldValues[key].permanent;
				};

				var saveOldValue = function(locationIndex, type, index)
				{
					var key = locationIndex + type + index;
					scope.oldValues[key] = {};
					for (var name in scope.document.locations[locationIndex][type][index])
					{
						if (scope.document.locations[locationIndex][type][index].hasOwnProperty(name))
						{
							scope.oldValues[key][name] = scope.document.locations[locationIndex][type][index][name];
						}
					}
				};

				var restoreOldValue = function(locationIndex, type, index)
				{
					var key = locationIndex + type + index;
					for (var name in scope.oldValues[key])
					{
						if (scope.oldValues[key].hasOwnProperty(name))
						{
							scope.document.locations[locationIndex][type][index][name] = scope.oldValues[key][name];
						}
					}
					scope.oldValues[key] = null;
				};

				var pushItem = function(locationIndex, type, item)
				{
					scope.document.locations[locationIndex][type].push(item);
					scope.displayConfig[locationIndex][type].push({ 'edit': false });
				};

				var deleteItem = function(locationIndex, type, index)
				{
					scope.document.locations[locationIndex][type].splice(index, 1);
					scope.displayConfig[locationIndex][type].splice(index, 1);
				};

				var removeRedirectByRelativePath = function(locationIndex, relativePath)
				{
					var length = scope.document.locations[locationIndex].redirects.length;
					for (var i = 0; i < length; i++)
					{
						if (scope.document.locations[locationIndex].redirects[i].relativePath == relativePath)
						{
							showRedirects(locationIndex);
							scope.deleteRedirect(locationIndex, i);
						}
					}
				};

				var addRedirectFromOldValues = function(locationIndex, index)
				{
					showRedirects(locationIndex);
					scope.document.locations[locationIndex].redirects.push({
						'id': scope.oldValues[locationIndex + 'urls' + index].id,
						'relativePath': scope.oldValues[locationIndex + 'urls' + index].relativePath,
						'query': scope.oldValues[locationIndex + 'urls' + index].query,
						'permanent': true
					});
					scope.displayConfig[locationIndex].redirects.push({ 'edit': false });
					updateRule(locationIndex, 'redirects', scope.document.locations[locationIndex].redirects.length-1);
				};

				var deleteRule = function(locationIndex, type, index)
				{
					var ruleId = scope.document.locations[locationIndex][type][index].id;
					for (var i = 0; i < scope.document.rules.length; i++)
					{
						if (scope.document.rules[i].rule_id == ruleId)
						{
							if (ruleId > 0)
							{
								scope.document.rules[i] = {
									'rule_id': scope.document.rules[i].rule_id,
									'http_status': 404,
									'updated': true
								};
							}
							else
							{
								scope.document.rules.splice(i, 1);
							}
						}
					}
				};

				var isDeletedRule = function(ruleId)
				{
					for (var i = 0; i < scope.document.rules.length; i++)
					{
						if (scope.document.rules[i].rule_id == ruleId && scope.document.rules[i].http_status == 404)
						{
							return true;
						}
					}
				};

				var addRule = function(locationIndex, type, index)
				{
					var item = scope.document.locations[locationIndex][type][index];
					scope.document.rules.push({
						'rule_id': item.id,
						'website_id': scope.document.locations[locationIndex]['websiteId'],
						'section_id': scope.document.locations[locationIndex]['sectionId'],
						'http_status': (type == 'urls' ? 200 : (item.permanent ? 301 : 302)),
						'lcid': scope.document.locations[locationIndex]['LCID'],
						'relative_path': item.relativePath,
						'query': item.query
					});
				};

				var updateRule = function(locationIndex, type, index)
				{
					var item = scope.document.locations[locationIndex][type][index];
					for (var i = 0; i < scope.document.rules.length; i++)
					{
						if (scope.document.rules[i].rule_id == item.id)
						{
							scope.document.rules[i].relative_path = item.relativePath;
							scope.document.rules[i].query = item.query;
							scope.document.rules[i].http_status = (type == 'urls' ? 200 : (item.permanent ? 301 : 302));
							scope.document.rules[i].updated = true;
							break;
						}
					}
				};

				// URLs handling.
				scope.getHref = function (location, url)
				{
					return location.baseUrl + location.defaultUrl.defaultRelativePath + (url.query ? ('?' + url.query) : '');
				};

				scope.startEditUrl = function (locationIndex, index)
				{
					saveOldValue(locationIndex, 'urls', index);
					scope.displayConfig[locationIndex].urls[index].edit = true;
				};

				scope.undoEditUrl = function (locationIndex, index)
				{
					NotificationCenter.clear();
					restoreOldValue(locationIndex, 'urls', index);
					scope.displayConfig[locationIndex].urls[index].edit = false;
				};

				scope.updateUrl = function (locationIndex, index)
				{
					NotificationCenter.clear();
					if (isModified(locationIndex, 'urls', index))
					{
						var url = REST.getBaseUrl('resources/Rbs/Seo/DocumentSeo/' + scope.document.id + '/CheckRelativePath');
						$http.get(url).success(function (data){
							var ruleId = scope.document.locations[locationIndex].urls[index].id;
							if (!data.hasOwnProperty('rule') || ruleId == data.rule.rule_id || isDeletedRule(data.rule.rule_id))
							{
								// Remove potential redirect with the same relative path.
								var newRelativePath = scope.document.locations[locationIndex].urls[index].relativePath;
								removeRedirectByRelativePath(locationIndex, newRelativePath);

								// Add the redirect to the old URL.
								if (ruleId !== 'auto')
								{
									addRedirectFromOldValues(locationIndex, index);
								}
								scope.document.locations[locationIndex].urls[index].id = scope.newRuleLastId--;
								addRule(locationIndex, 'urls', index);

								scope.oldValues[locationIndex + 'urls' + index] = null;
								scope.displayConfig[locationIndex].urls[index].edit = false;
							}
							else
							{
								NotificationCenter.error('Invalid URL', {message: 'This URL is already used!', code: 999999});
							}
						});
					}
				};

				scope.restoreDefaultUrl = function (locationIndex, index)
				{
					// Set the default URL.
					saveOldValue(locationIndex, 'urls', index);
					deleteRule(locationIndex, 'urls', index);
					var defaultUrl = scope.document.locations[locationIndex].defaultUrl;
					scope.document.locations[locationIndex].urls[index].id = defaultUrl.id;
					scope.document.locations[locationIndex].urls[index].relativePath = defaultUrl.relativePath;

					// Remove potential redirect with the same relative path.
					removeRedirectByRelativePath(locationIndex, defaultUrl.relativePath);

					// Add the redirect to the old URL.
					addRedirectFromOldValues(locationIndex, index);
					showRedirects(locationIndex);

					scope.oldValues[locationIndex + 'urls' + index] = null;
				};

				// Redirects handling.
				scope.startEditRedirect = function (locationIndex, index)
				{
					saveOldValue(locationIndex, 'redirects', index);
					scope.displayConfig[locationIndex].redirects[index].edit = true;
				};

				scope.undoEditRedirect = function (locationIndex, index)
				{
					NotificationCenter.clear();
					restoreOldValue(locationIndex, 'redirects', index);
					scope.displayConfig[locationIndex].redirects[index].edit = false;
				};

				scope.updateRedirect = function (locationIndex, index)
				{
					NotificationCenter.clear();
					var url = REST.getBaseUrl('resources/Rbs/Seo/DocumentSeo/' + scope.document.id + '/CheckRelativePath');
					$http.get(url).success(function (data){
						var ruleId = scope.document.locations[locationIndex].redirects[index].id;
						if (data === null || ruleId == data.rule_id || isDeletedRule(data.rule_id))
						{
							if (ruleId > 0)
							{
								deleteRule(locationIndex, 'redirects', index);
								scope.document.locations[locationIndex].redirects[index].id = scope.newRuleLastId--;
								addRule(locationIndex, 'redirects', index);
							}
							else
							{
								updateRule(locationIndex, 'redirects', index);
							}
							scope.oldValues[locationIndex + 'redirects' + index] = null;
							scope.displayConfig[locationIndex].redirects[index].edit = false;
						}
						else
						{
							NotificationCenter.error('Invalid URL', {message: 'This URL is already used!', code: 999999});
						}
					});
				};

				scope.deleteRedirect = function (locationIndex, index)
				{
					deleteRule(locationIndex, 'redirects', index);
					deleteItem(locationIndex, 'redirects', index);
				};

				scope.makeCurrentUrl = function (locationIndex, index)
				{
					var redirect = scope.document.locations[locationIndex].redirects[index];
					deleteItem(locationIndex, 'redirects', index);

					var length = scope.document.locations[locationIndex].urls.length;
					for (var i = 0; i < length; i++)
					{
						var url = scope.document.locations[locationIndex].urls[i];
						if (url.query == redirect.query)
						{
							deleteItem(locationIndex, 'urls', i);
							url.http_status = 301;
							pushItem(locationIndex, 'redirects', url);
							updateRule(locationIndex, 'redirects', scope.document.locations[locationIndex].redirects.length-1);
							break;
						}
					}

					redirect.http_status = 200;
					pushItem(locationIndex, 'urls', redirect);
					updateRule(locationIndex, 'urls', scope.document.locations[locationIndex].urls.length-1);
				};

				scope.addNewRedirect = function (locationIndex)
				{
					NotificationCenter.clear();
					var url = REST.getBaseUrl('resources/Rbs/Seo/DocumentSeo/' + scope.document.id + '/CheckRelativePath');
					$http.get(url).success(function (data){
						if (data === null || isDeletedRule(data.rule_id))
						{
							scope.document.locations[locationIndex].redirects.push({
								'id': scope.newRuleLastId--,
								'relativePath': scope.newRedirect[locationIndex].relativePath,
								'query': scope.newRedirect[locationIndex].query,
								'permanent': scope.newRedirect[locationIndex].permanent
							});

							addRule(locationIndex, 'redirects', scope.document.locations[locationIndex].redirects.length-1);
							scope.displayConfig[locationIndex].redirects.push({ 'edit': false });
							scope.clearNewRedirect(locationIndex);
						}
						else
						{
							NotificationCenter.error('Invalid URL', {message: 'This URL is already used!', code: 999999});
						}
					});
				};

				scope.clearNewRedirect = function (locationIndex)
				{
					NotificationCenter.clear();
					scope.newRedirect[locationIndex] = {'relativePath': '', 'permanent': true, 'query': ''};
				};

				editorCtrl.init('Rbs_Seo_DocumentSeo');
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.NotificationCenter'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoDocumentSeo', Editor);
})();