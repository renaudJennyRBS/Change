/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {

	"use strict";

	var forEach = angular.forEach;

	/**
	 * @ngdoc service
	 * @name RbsChange.service:Utils
	 *
	 * @description Provides global utility methods.
	 */
	angular.module('RbsChange').constant('RbsChange.Utils',
		{
			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#hasStatus
			 *
			 * @description
			 * Indicates whether the given `doc` has a publication status among the ones given as arguments.
			 *
			 * @param {Document} doc Document.
			 * @param {...String} args Publication statuses.
			 *
			 * @returns {Boolean} True if `doc` has a status among the given ones.
			 */
			hasStatus: function(doc) {
				var s, statuses;
				statuses = arguments;
				for (s = 1; s < statuses.length; s++) {
					if (doc.publicationStatus === statuses[s]) {
						return true;
					}
				}
				return false;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#hasCorrection
			 *
			 * @description
			 * Indicates whether the given `doc` has a correction or not.
			 *
			 * @param {Document} doc Document.
			 *
			 * @returns {Boolean} true if `doc` has a correction.
			 */
			hasCorrection: function(doc) {
				return this.isDocument(doc) && angular.isObject(doc.META$) && angular.isObject(doc.META$.correction);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#removeCorrection
			 *
			 * @description
			 * Removes the correction from the given `doc`.
			 *
			 * If `propertiesNames` is provided, only the specified corrected properties are removed.
			 *
			 * @param {Document} doc Document.
			 * @param {String|Array<String>} propertiesNames Property or list of properties.
			 *
			 * @returns {Boolean} false if `doc` has no correction.
			 */
			removeCorrection: function(doc, propertiesNames) {
				if (!this.hasCorrection(doc)) {
					return false;
				}
				if (angular.isString(propertiesNames)) {
					propertiesNames = [ propertiesNames ];
				}
				else if (!angular.isArray(propertiesNames) || propertiesNames.length === 0) {
					propertiesNames = doc.META$.correction.propertiesNames;
				}
				angular.forEach(propertiesNames, function(property) {
					doc[property] = doc.META$.correction.original[property];
				});
				delete doc.META$.correction;
				return true;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#applyCorrection
			 *
			 * @description
			 * Applies a correction on the given `doc`.
			 *
			 * @param {Document} doc Document.
			 * @param {Object} correctionData Correction's data.
			 *
			 * @returns {Document} The Document with correction applied.
			 */
			applyCorrection: function(doc, correctionData) {
				// Copy current values to make them available as 'doc.correction.original'.
				var original = angular.copy(doc);
				delete original.META$;
				// Create Correction object with original values available as 'doc.correction.original'.
				doc.META$.correction = angular.extend({'original': original}, correctionData.correction);
				// Replace corrected values in the Document.
				return angular.extend(doc, correctionData.properties);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isLocalized
			 *
			 * @description
			 * Indicates whether the given `doc` is localized or not.
			 *
			 * @param {Document} doc Document.
			 *
			 * @returns {Boolean} true if `doc` is localized.
			 */
			isLocalized: function(doc) {
				return this.isDocument(doc) && doc.refLCID && doc.LCID;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isValidLCID
			 *
			 * @description
			 * Checks whether the given locale ID is valid or not.
			 *
			 * @param {String} lcid Locale ID.
			 *
			 * @returns {Boolean} true if the given `lcid` is valid.
			 */
			isValidLCID: function(lcid) {
				return angular.isString(lcid) && (/^[a-z]{2}(_[a-zA-Z]{2})?$/).test(lcid);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isTreeNode
			 *
			 * @description
			 * Indicates whether the given `doc` is a Tree Node or not.
			 *
			 * @param {Document} doc Document object.
			 *
			 * @returns {Boolean} true if the given `doc` is a tree node.
			 */
			isTreeNode: function(doc) {
				return this.isDocument(doc) && angular.isObject(doc.META$.treeNode);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isModel
			 *
			 * @description
			 * Indicates whether the given `doc` has a model among the ones given as arguments.
			 *
			 * @param {Document} doc Document object.
			 * @param {...String} models Document Model names.
			 *
			 * @returns {Boolean} true if the given `doc` has a model among the given ones.
			 */
			isModel: function(doc) {
				var m, models;
				models = arguments;
				for (m = 1; m < models.length; m++) {
					if (models[m] === '*' || doc.model === models[m]) {
						return true;
					}
				}
				return false;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isNotModel
			 *
			 * @description
			 * Indicates whether the given `doc` has a model different than the ones given as arguments.
			 *
			 * @param {Document} doc Document object.
			 * @param {...String} models Document Model names.
			 *
			 * @returns {Boolean} true if the given `doc` has a model different than the given ones.
			 */
			isNotModel: function() {
				return !this.isModel.apply(this, arguments);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isDocument
			 *
			 * @description
			 * Indicates whether the given `obj` is a Document or not.
			 *
			 * @param {Object} obj Object.
			 *
			 * @returns {Boolean} true if the given `obj` is a Document.
			 */
			isDocument: function(obj) {
				return angular.isObject(obj) && angular.isDefined(obj.model) && angular.isDefined(obj.id);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isInlineDocument
			 *
			 * @description
			 * Indicates whether the given `obj` is an inline Document or not.
			 *
			 * @param {Object} obj Object.
			 *
			 * @returns {Boolean} true if the given `obj` is an inline Document.
			 */
			isInlineDocument: function(obj) {
				return angular.isObject(obj) && angular.isDefined(obj.model) && !angular.isDefined(obj.id);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isModelName
			 *
			 * @description
			 * Indicates whether the given `string` represents a valid Document Model name or not.
			 *
			 * @param {String} string Document Model name.
			 *
			 * @returns {Boolean} true if the given `string` is a valid Document Model name.
			 */
			isModelName: function(string) {
				return angular.isString(string) && (/^\w+_\w+_\w+$/).test(string);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#simpleRepresentation
			 *
			 * @description
			 * Returns a simple representation of a Document as an poor JavaScript object with the following properties:
			 *
			 * - `id`
			 * - `model`
			 * - `label` (which is either the `label` or a concatenation of `model` + `id` if `label` is undefined)
			 *
			 * @param {Document} doc Document object.
			 *
			 * @returns {Object} Simple representation of the given `doc`.
			 */
			simpleRepresentation: function(doc) {
				if (this.isDocument(doc)) {
					var out = {
						'id': doc.id,
						'model': doc.model,
						'label': doc.label || (doc.model + '/' + doc.id)
					};
					if (doc.LCID) {
						out.LCID = doc.LCID;
					}
					return out;
				}
				return doc;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isModuleName
			 *
			 * @description
			 * Indicates whether the given `string` represents a plugin (or module) name or not.
			 *
			 * @returns {Boolean} true if the given `string` is a valid plugin name.
			 */
			isModuleName: function(string) {
				return angular.isString(string) && (/^\w+_\w+$/).test(string);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#isNew
			 *
			 * @description
			 * Tells whether the given `doc` is new or not. Newly created Documents have a negative ID.
			 *
			 * @param {Document} doc Document object.
			 *
			 * @returns {Boolean} true if the given `doc` is new (unsaved).
			 */
			isNew: function(doc) {
				return this.isDocument(doc) && doc.id < 0;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#duplicateDocument
			 *
			 * @description
			 * Duplicates the given `doc` and returns a new copy ready to be saved.
			 *
			 * @param {Document} doc The Document to duplicate.
			 *
			 * @returns {Document} Copy of given `doc`, with temporary ID.
			 */
			duplicateDocument: function(doc) {
				doc = angular.copy(doc);
				doc.id = this.getTemporaryId();
				delete doc.modificationDate;
				delete doc.creationDate;
				delete doc.documentVersion;
				return doc;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#hasLocalCopy
			 *
			 * @description
			 * Tells whether the given `doc` has a saved local copy or not.
			 *
			 * @param {Document} doc Document object.
			 *
			 * @returns {Boolean} true if `doc` has a saved local copy.
			 */
			hasLocalCopy: function(doc) {
				return doc.META$ && doc.META$.localCopy;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#modelInfo
			 *
			 * @description
			 * Returns information about the given model name as a JavaScript object with the following properties:
			 *
			 * - `vendor` (String) Vendor name (ie. <em>Rbs</em>).
			 * - `module` (String) Plugin's short name (ie. <em>Catalog</em>).
			 * - `fullModule (String) Plugin's full name (ie. <em>Rbs_Catalog</em>).
			 * - `document` (String) Document's short name (ie. <em>Product</em>).
			 * - `change` (Boolean) true if the Model is a core model (provided by RBS).
			 *
			 * @param {String} modelName A fully qualified model name, such as `Rbs_Catalog_Product`.
			 * @returns {Object} Model's information.
			 */
			modelInfo: function(modelName) {
				var splat = (modelName) ? modelName.split(/[\/_]/) : [];
				if (splat.length !== 3) {
					throw new Error("Could not parse model name '" + modelName +
						"'. Model names are composed of three parts: '<vendor>_<module>_<document>'.");
				}
				return {
					'vendor': splat[0],
					'module': splat[1],
					'fullModule': splat[0] + '_' + splat[1],
					'document': splat[2],
					'change': splat[0] === 'Rbs'
				};
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#toIds
			 *
			 * @description
			 * Returns the ID of the given Document or an Array of IDs of the given Array of Documents.
			 *
			 * @param {Document|Array<Document>} value Document or Array of Documents.
			 * @returns {Integer|Array<Integer>} Document's ID or Array of Documents IDs.
			 */
			toIds: function(value) {
				var i, newVal;
				if (angular.isArray(value)) {
					newVal = [];
					for (i = 0; i < value.length; i++) {
						newVal[i] = this.toIds(value[i]);
					}
				}
				else if (angular.isObject(value) && value.hasOwnProperty('id')) {
					newVal = value.id;
				}
				else {
					newVal = value;
				}
				return newVal;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#makeUrl
			 *
			 * @description
			 * Makes a URL from the given one (`url`) and a parameters object (`params`).
			 * If `params` contains parameters that are present in `url`, they will be replaced.
			 * All parameters of `params` that are not in `url` are, of course, appended.
			 *
			 * @param {String} url The base URL to use.
			 * @param {Object} params Hash object representing the parameters to append or replace in the base URL.
			 *
			 * @returns {String} The updated URL.
			 */
			makeUrl: function(url, params) {
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
					forEach(queryString.split('&'), function(token) {
						var param = token.split('=');
						urlArgs[param[0]] = param[1];
					});
				}

				queryString = '';
				angular.extend(urlArgs, params);
				forEach(urlArgs, function(value, key) {
					if (angular.isDefined(value) && value !== null) {
						if (queryString.length > 0) {
							queryString += '&';
						}
						if (angular.isArray(value)) {
							for (p = 0; p < value.length; p++) {
								if (p > 0) {
									queryString += '&';
								}
								if (angular.isDate(value[p])) {
									value[p] = moment(value[p]).format();
								}
								queryString += key + '[]=' + encodeURIComponent(value[p]);
							}
						}
						else if (angular.isObject(value)) {
							p = 0;
							angular.forEach(value, function(v, i) {
								if (p > 0) {
									queryString += '&';
								}
								if (angular.isDate(v)) {
									v = moment(v).format();
								}
								queryString += key + '[' + i + ']=' + encodeURIComponent(v);
								p = 1;
							});
						}
						else {
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
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#startsWith
			 *
			 * @description
			 * Indicates whether the String `haystack` starts with the String `needle`.
			 * The comparison is case-sensitive. For a case-insensitive comparison, use {@link RbsChange.service:Utils#startsWithIgnoreCase `Utils.startsWithIgnoreCase()`}.
			 *
			 * @param {String} haystack The String to search in.
			 * @param {String} needle The String to search for.
			 *
			 * @returns {Boolean} true if `haystack` starts with `needle`.
			 */
			startsWith: function(haystack, needle) {
				return haystack.slice(0, needle.length) === needle;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#startsWithIgnoreCase
			 *
			 * @description
			 * Indicates whether the String `haystack` starts with the String `needle`.
			 * The comparison is case-INsensitive. For a case-sensitive comparison, use {@link RbsChange.service:Utils#startsWith `Utils.startsWith()`}.
			 *
			 * @param {String} haystack The String to search in.
			 * @param {String} needle The String to search for.
			 *
			 * @returns {Boolean} true if `haystack` starts with `needle`.
			 */
			startsWithIgnoreCase: function(haystack, needle) {
				return this.startsWith(angular.lowercase(haystack), angular.lowercase(needle));
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#endsWithIgnoreCase
			 *
			 * @description
			 * Indicates whether the String `haystack` ends with the String `needle`.
			 * The comparison is case-sensitive. For a case-INsensitive comparison, use {@link RbsChange.service:Utils#endsWithIgnoreCase `Utils.endsWithIgnoreCase()`}.
			 *
			 * @param {String} haystack The String to search in.
			 * @param {String} needle The String to search for.
			 *
			 * @returns {Boolean} true if `haystack` ends with `needle`.
			 */
			endsWith: function(haystack, needle) {
				return haystack.slice(-needle.length) === needle;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#endsWithIgnoreCase
			 *
			 * @description
			 * Indicates whether the String `haystack` ends with the String `needle`.
			 * The comparison is case-INsensitive. For a case-sensitive comparison, use {@link RbsChange.service:Utils#endsWith `Utils.endsWith()`}.
			 *
			 * @param {String} haystack The String to search in.
			 * @param {String} needle The String to search for.
			 *
			 * @returns {Boolean} true if `haystack` ends with `needle`.
			 */
			endsWithIgnoreCase: function(haystack, needle) {
				return this.endsWith(angular.lowercase(haystack), angular.lowercase(needle));
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#equalsIgnoreCase
			 *
			 * @description
			 * Indicates whether the String `s1` equals the String `s2`. The comparison is case-INsensitive.
			 *
			 * @param {String} s1 First string.
			 * @param {String} s2 Second string.
			 *
			 * @returns {Boolean} true if `s1` equals `s2`.
			 */
			equalsIgnoreCase: function(s1, s2) {
				return angular.lowercase(s1) === angular.lowercase(s2);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#equalsIgnoreCase
			 *
			 * @description
			 * Indicates whether the String `haystack` contains the String `needle`. The comparison is case-INsensitive.
			 *
			 * @param {String} haystack The String to search in.
			 * @param {String} needle The String to search for.
			 *
			 * @returns {Boolean} true if `needle` is found in `haystack`.
			 */
			containsIgnoreCase: function(haystack, needle) {
				return angular.lowercase(haystack).indexOf(angular.lowercase(needle)) !== -1;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#normalizeAttrName
			 *
			 * @description
			 * Converts camel case name to HTML attribute name (snake-case).
			 *
			 * @param {String} str The camel-case String to transform.
			 *
			 * @returns {String} Snake-case String.
			 */
			normalizeAttrName: function(str) {
				return str.
					replace(/[^a-z0-9]/ig, '-').
					replace(/([A-Z])/g, function(_, letter, offset) {
						return (offset ? '-' : '') + letter.toLowerCase();
					});
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#getByProperty
			 *
			 * @description
			 * Returns the objects of the `collection` that have a `property` equals to `value`.
			 *
			 * Please note that the check is done with a <code>===</code>.
			 *
			 * @param {Array} collection The Array of objects.
			 * @param {String} propertyName Property name.
			 * @param {*} value Value.
			 *
			 * @returns {Array} Array of all the objects from `collection` that match the condition `property=value`.
			 */
			getByProperty: function(collection, propertyName, value) {
				var results = [];
				angular.forEach(collection, function(item) {
					if (item && item[propertyName] === value) {
						results.push(item);
					}
				});
				return results;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:Utils
			 * @name RbsChange.service:Utils#getById
			 *
			 * @description
			 * Returns the object of the `collection` that has the specified `id`.
			 *
			 * @param {Array} collection The Array of objects.
			 * @param {Integer} id The ID.
			 *
			 * @returns {Object} Object from `collection` that has the specified `id`.
			 */
			getById: function(collection, id) {
				for (var i = 0; i < collection.length; i++) {
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
			getFunctionParamNames: function(func) {
				var funStr = func.toString();
				return funStr.slice(funStr.indexOf('(') + 1, funStr.indexOf(')')).match(/([^\s,]+)/g);
			},

			// Used by RbsChange.Actions
			objectValues: function(obj, order) {
				var out = [];

				if (angular.isObject(obj)) {
					if (angular.isArray(order)) {
						forEach(order, function(name) {
							if (obj && name in obj) {
								out.push(obj[name]);
							}
							else {
								out.push(null);
							}
						});
					}
					else {
						forEach(obj, function(value) {
							out.push(value);
						});
					}
				}
				return out;
			},

			// Used by RbsChange.Actions
			extractFunctionArgsFromObject: function(fn, obj) {
				return this.objectValues(obj, this.getFunctionParamNames(fn));
			}
		});
})();