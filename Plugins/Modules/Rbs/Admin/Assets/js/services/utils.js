(function () {

	"use strict";

	var forEach = angular.forEach;

	/**
	 * Global utility methods.
	 */
	angular.module('RbsChange').constant('RbsChange.Utils', {

		/**
		 * Indicates whether the given `doc` has a status among the ones given as arguments.
		 *
		 * @param doc Document
		 * @param Statuses...
		 *
		 * @returns Boolean
		 */
		hasStatus : function (doc) {
			var s, statuses;
			statuses = arguments;
			for (s=1 ; s<statuses.length ; s++) {
				if (doc.publicationStatus === statuses[s]) {
					return true;
				}
			}
			return false;
		},


		/**
		 * Indicates whether the given `doc` has a correction or not.
		 *
		 * @param doc Document
		 *
		 * @returns Boolean
		 */
		hasCorrection : function (doc) {
			return this.isDocument(doc) && angular.isObject(doc.META$) && angular.isObject(doc.META$.correction);
		},


		/**
		 *
		 * @param doc
		 * @param propertiesNames
		 * @returns {boolean}
		 */
		removeCorrection : function (doc, propertiesNames) {
			if (! this.hasCorrection(doc)) {
				return false;
			}
			if (angular.isString(propertiesNames)) {
				propertiesNames = [ propertiesNames ];
			}
			else if (! angular.isArray(propertiesNames) || propertiesNames.length === 0) {
				propertiesNames = doc.META$.correction.propertiesNames;
			}
			angular.forEach(propertiesNames, function (property) {
				doc[property] = doc.META$.correction.original[property];
			});
			delete doc.META$.correction;
			return true;
		},


		/**
		 * @param doc
		 * @param correctionData
		 * @returns {*}
		 */
		applyCorrection : function (doc, correctionData) {
			// Copy current values to make them available as 'doc.correction.original'.
			var original = angular.copy(doc);
			delete original.META$;
			// Create Correction object with original values available as 'doc.correction.original'.
			doc.META$.correction = angular.extend({'original': original}, correctionData.correction);
			// Replace corrected values in the Document.
			return angular.extend(doc, correctionData.properties);
		},


		/**
		 * Indicates whether the given `doc` is localized or not.
		 */
		isLocalized : function (doc) {
			return this.isDocument(doc) && doc.refLCID && doc.LCID;
		},


		isValidLCID : function (lcid) {
			return angular.isString(lcid) && (/^[a-z]{2}(_[a-zA-Z]{2})?$/).test(lcid);
		},


		/**
		 * Indicates whether the given `doc` is a Tree Node or not.
		 */
		isTreeNode : function (doc) {
			return this.isDocument(doc) && angular.isObject(doc.META$.treeNode);
		},


		/**
		 * Indicates whether the given `doc` is a model among the ones given as arguments.
		 *
		 * @param {Document} doc
		 * @param {String...} Model names
		 *
		 * @returns {Boolean}
		 */
		isModel : function (doc) {
			var m, models;
			models = arguments;
			for (m=1 ; m<models.length ; m++) {
				if (models[m] === '*' || doc.model === models[m]) {
					return true;
				}
			}
			return false;
		},


		/**
		 * Indicates whether the given `doc` is NOT a model among the ones given as arguments.
		 *
		 * @param {Document} doc
		 * @param {String...} Model names
		 *
		 * @returns {Boolean}
		 */
		isNotModel : function () {
			return ! this.isModel.apply(this, arguments);
		},


		/**
		 * Indicates whether the given `obj` is a Document or not.
		 * A Document is an object with the `model` and 'ìd` properties.
		 */
		isDocument : function (obj) {
			return angular.isObject(obj) && angular.isDefined(obj.model) && angular.isDefined(obj.id);
		},


		/**
		 * Indicates whether the given `string` represents a Model name or not.
		 */
		isModelName : function (string) {
			return angular.isString(string) && (/^\w+_\w+_\w+$/).test(string);
		},


		simpleRepresentation : function (doc) {
			if (this.isDocument(doc)) {
				var out = {
					'id' : doc.id,
					'model' : doc.model,
					'label' : doc.label || (doc.model+'/'+doc.id)
				};
				if (doc.LCID) {
					out.LCID = doc.LCID;
				}
				return out;
			}
			return doc;
		},


		/**
		 * Indicates whether the given `string` represents a Model name or not.
		 */
		isModuleName : function (string) {
			return angular.isString(string) && (/^\w+_\w+$/).test(string);
		},


		/**
		 * Tells whether the given Resource is new or not.
		 * Newly created resources have a negative ID.
		 */
		isNew : function (resource) {
			return this.isDocument(resource) && resource.id < 0;
		},


		/**
		 * Duplicates the given `doc` and returns a new copy ready to be saved.
		 * @param doc
		 */
		duplicateDocument : function (doc) {
			doc = angular.copy(doc);
			doc.id = this.getTemporaryId();
			delete doc.modificationDate;
			delete doc.creationDate;
			delete doc.documentVersion;
			return doc;
		},


		/**
		 * Tells whether a Document has a local copy or not.
		 * @param doc
		 * @returns {*}
		 */
		hasLocalCopy : function (doc) {
			return doc.META$ && doc.META$.localCopy;
		},


		/**
		 * Returns information about the given model name: vendor, module and document.
		 *
		 * @param {String} A fully qualified model name, such as `Change_Website_Page`.
		 * @returns {Object} {'vendor', 'module', 'document', 'change':(true|false)}
		 */
		modelInfo : function (modelName) {
			var splat = modelName.split(/[\/_]/);
			if (splat.length !== 3) {
				throw new Error("Could not parse model name '" + modelName + "'. Model names are composed of three parts: '<vendor>_<module>_<document>'.");
			}
			return {
				'vendor'   : splat[0],
				'module'   : splat[1],
				'document' : splat[2],
				'change'   : splat[0] === 'Rbs'
			};
		},


		/**
		 * Returns the ID of the given Document or an Array of IDs of the given Array of Documents.
		 * @param value
		 * @returns {*}
		 */
		toIds : function (value)
		{
			var i, newVal;
			if (angular.isArray(value)) {
				newVal = [];
				for (i=0 ; i < value.length ; i++) {
					newVal[i] = this.toIds(value[i]);
				}
			} else if (angular.isObject(value) && value.hasOwnProperty('id')) {
				newVal = value.id;
			} else {
				newVal = value;
			}
			return newVal;
		},


		/**
		 * Makes a URL from the given one (`url`) and a parameters object (`params`).
		 * If `params` contains parameters that are present in `url`, they will be replaced.
		 * All parameters of `params` that are not in `url` are, of course, appended.
		 *
		 * @param url The base URL to use.
		 * @param params Parameters to append or replace in the base url.
		 *
		 * @returns {String}
		 */
		makeUrl : function (url, params) {
			var baseUrl = url,
			    queryString = '',
			    hash = '',
			    urlArgs = {},
			    p;

			p = url.lastIndexOf('#');
			if (p > -1) {
				baseUrl = url.substring(0, p);
				hash = url.substring(p, url.length);
			}

			p = baseUrl.indexOf('?');
			if (p > -1) {
				queryString = baseUrl.substring(p + 1, baseUrl.length);
				baseUrl = url.substring(0, p);
				forEach(queryString.split('&'), function (token) {
					var param = token.split('=');
					urlArgs[param[0]] = param[1];
				});
			}

			queryString = '';
			angular.extend(urlArgs, params);
			forEach(urlArgs, function (value, key) {
				if (angular.isDefined(value) && value !== null) {
					if (queryString.length > 0) {
						queryString += '&';
					}
					if (angular.isArray(value)) {
						for (p=0 ; p<value.length ; p++) {
							if (p > 0) {
								queryString += '&';
							}
							if (angular.isDate(value[p])) {
								value[p] = moment(value[p]).format();
							}
							queryString += key + '[]=' + encodeURIComponent(value[p]);
						}
					} else if (angular.isObject(value)) {
						p=0;
						angular.forEach(value, function(v, i) {
							if (p > 0) {
								queryString += '&';
							}
							if (angular.isDate(v)) {
								v = moment(v).format();
							}
							queryString += key + '['+i+']=' + encodeURIComponent(v);
							p=1;
						});
					} else {
						if (angular.isDate(value)) {
							value = moment(value).format();
						}
						queryString += key + '=' + encodeURIComponent(value);
					}
				}
			});

			if (queryString) {
				return baseUrl + '?' + queryString + hash;
			}

			return baseUrl + hash;
		},


		// String manipulation methods.


		/**
		 * Indicates whether the String `haystack` starts with the String `needle`.
		 * The comparison is case-sensitive. For a case-insensitive comparison, use `startsWithIgnoreCase()`.
		 *
		 * @see startsWithIgnoreCase()
		 *
		 * @param {String} The String to search in.
		 * @param {String} The String to search for.
		 */
		startsWith : function (haystack, needle) {
			return haystack.slice(0, needle.length) === needle;
		},


		/**
		 * Indicates whether the String `haystack` starts with the String `needle`.
		 * The comparison is case-INsensitive. For a case-sensitive comparison, use `startsWith()`.
		 *
		 * @see startsWith()
		 *
		 * @param {String} The String to search in.
		 * @param {String} The String to search for.
		 */
		startsWithIgnoreCase : function (haystack, needle) {
			return this.startsWith(angular.lowercase(haystack), angular.lowercase(needle));
		},


		/**
		 * Indicates whether the String `haystack` ends with the String `needle`.
		 * The comparison is case-sensitive. For a case-insensitive comparison, use `endsWithIgnoreCase()`.
		 *
		 * @see endsWithIgnoreCase()
		 *
		 * @param {String} The String to search in.
		 * @param {String} The String to search for.
		 */
		endsWith : function (haystack, needle) {
			return haystack.slice(-needle.length) === needle;
		},


		/**
		 * Indicates whether the String `haystack` ends with the String `needle`.
		 * The comparison is case-INsensitive. For a case-sensitive comparison, use `endsWith()`.
		 *
		 * @see endsWith()
		 *
		 * @param {String} The String to search in.
		 * @param {String} The String to search for.
		 */
		endsWithIgnoreCase : function (haystack, needle) {
			return this.endsWith(angular.lowercase(haystack), angular.lowercase(needle));
		},


		equalsIgnoreCase : function (s1, s2) {
			return angular.lowercase(s1) === angular.lowercase(s2);
		},

		containsIgnoreCase : function (haystack, needle) {
			return angular.lowercase(haystack).indexOf(angular.lowercase(needle)) !== -1;
		},


		/**
		 * Converts camel case name to HTML attribute name.
		 *
		 * @param str
		 * @returns {XML}
		 */
		normalizeAttrName : function (str) {
			return str.
				replace(/[^a-z0-9]/ig, '-').
				replace(/([A-Z])/g, function (_, letter, offset) {
					return (offset ? '-' : '')+letter.toLowerCase();
				});
		},


		getByProperty : function (collection, propertyName, value) {
			var results = [];
			angular.forEach(collection, function (item) {
				if (item && item[propertyName] === value) {
					results.push(item);
				}
			});
			return results;
		},


		getById : function (collection, id) {
			for (var i=0 ; i<collection.length ; i++) {
				if (collection[i] && collection[i].id === id) {
					return collection[i];
				}
			}
			return null;
		},


		// Various methods...
		// These methods are not (yet?) documented, but their use is NOT encouraged
		// as they are used for internal purposes only.


		// Used by RbsChange.Actions
		getFunctionParamNames : function (func) {
			var funStr = func.toString();
			return funStr.slice(funStr.indexOf('(')+1, funStr.indexOf(')')).match(/([^\s,]+)/g);
		},


		// Used by RbsChange.Actions
		objectValues : function (obj, order) {
			var out = [];

			if (angular.isObject(obj)) {
				if (angular.isArray(order)) {
					forEach(order, function (name) {
						if (obj && name in obj) {
							out.push(obj[name]);
						} else {
							out.push(null);
						}
					});
				} else {
					forEach(obj, function (value) {
						out.push(value);
					});
				}
			}
			return out;
		},


		// Used by RbsChange.Actions
		extractFunctionArgsFromObject : function (fn, obj) {
			return this.objectValues(obj, this.getFunctionParamNames(fn));
		}

	});

})();