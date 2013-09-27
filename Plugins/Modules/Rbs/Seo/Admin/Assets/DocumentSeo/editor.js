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
	function Editor($timeout, $http, Loading, REST)
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

				var deleteItem = function(locationIndex, type, index)
				{
					deleteRule(locationIndex, type, index);
					scope.document.locations[locationIndex][type].splice(index, 1);
					scope.displayConfig[locationIndex][type].splice(index, 1);
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
				}

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
				}

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
				}

				// URLs handling.
				scope.startEditUrl = function (locationIndex, index)
				{
					saveOldValue(locationIndex, 'urls', index);
					scope.displayConfig[locationIndex].urls[index].edit = true;
				};

				scope.undoEditUrl = function (locationIndex, index)
				{
					restoreOldValue(locationIndex, 'urls', index);
					scope.displayConfig[locationIndex].urls[index].edit = false;
				};

				scope.updateUrl = function (locationIndex, index)
				{
					if (isModified(locationIndex, 'urls', index))
					{
						// Remove potential redirect with the same relative path.
						var newRelativePath = scope.document.locations[locationIndex].urls[index].relativePath;
						var length = scope.document.locations[locationIndex].redirects.length;
						for (var i = 0; i < length; i++)
						{
							if (scope.document.locations[locationIndex].redirects[i].relativePath == newRelativePath)
							{
								showRedirects(locationIndex);
								scope.deleteRedirect(locationIndex, i);
							}
						}

						// Add the redirect to the old URL.
						if (scope.document.locations[locationIndex].urls[index].id !== 'auto')
						{
							showRedirects(locationIndex);
							scope.document.locations[locationIndex].redirects.push({
								'id': scope.document.locations[locationIndex].urls[index].id,
								'relativePath': scope.oldValues[locationIndex + 'urls' + index].relativePath,
								'query': scope.oldValues[locationIndex + 'urls' + index].query,
								'permanent': true
							});
							scope.displayConfig[locationIndex].redirects.push({ 'edit': false });

							scope.document.locations[locationIndex].urls[index].id = scope.newRuleLastId--;
							addRule(locationIndex, 'urls', index);

							updateRule(locationIndex, 'redirects', scope.document.locations[locationIndex].redirects.length-1);
						}
						else
						{
							scope.document.locations[locationIndex].urls[index].id = scope.newRuleLastId--;
							addRule(locationIndex, 'urls', index);
						}
					}

					scope.oldValues[locationIndex + 'urls' + index] = null;
					scope.displayConfig[locationIndex].urls[index].edit = false;
				};

				scope.deleteUrl = function (locationIndex, index)
				{
					deleteItem(locationIndex, 'urls', index);
				};

				// Redirects handling.
				scope.startEditRedirect = function (locationIndex, index)
				{
					saveOldValue(locationIndex, 'redirects', index);
					scope.displayConfig[locationIndex].redirects[index].edit = true;
				};

				scope.undoEditRedirect = function (locationIndex, index)
				{
					restoreOldValue(locationIndex, 'redirects', index);
					scope.displayConfig[locationIndex].redirects[index].edit = false;
				};

				scope.updateRedirect = function (locationIndex, index)
				{
					if (scope.document.locations[locationIndex].redirects[index].id > 0)
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
				};

				scope.deleteRedirect = function (locationIndex, index)
				{
					deleteItem(locationIndex, 'redirects', index);
				};

				scope.addNewRedirect = function (locationIndex)
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
				};

				scope.clearNewRedirect = function (locationIndex)
				{
					scope.newRedirect[locationIndex] = {'relativePath': '', 'permanent': true, 'query': ''};
				};

				editorCtrl.init('Rbs_Seo_DocumentSeo');
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSeoDocumentSeo', Editor);
})();