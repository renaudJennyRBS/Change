(function () {

	"use strict";

	var cfgLocalStorageKeyName = 'ClipboardData';


	function ClipboardService (ArrayUtils, REST, localStorageService) {


		function saveToLocalStorage (values, status) {
			localStorageService.add(cfgLocalStorageKeyName, JSON.stringify({
				'values' : values,
				'status' : status
			}));
		}

		var localData = localStorageService.get(cfgLocalStorageKeyName);
		if (localData) {
			try {
				localData = JSON.parse(localData);
				angular.forEach(localData.values, function (doc, index) {
					localData.values[index] = REST.transformObjectToChangeDocument(doc);
				});
			} catch (e) {
				localData = null;
			}
		}

		this.values = localData ? localData.values : [];
		this.status = localData ? localData.status : 'empty';


		this.clear = function () {
			ArrayUtils.clear(this.values);
			this.status = 'empty';
			saveToLocalStorage(this.values, this.status);
		};


		this.isEmpty = function () {
			return this.values.length === 0;
		};


		this.append = function (value) {
			if (angular.isArray(value)) {
				for (var i=0 ; i<value.length ; i++) {
					if (this.values.indexOf(value[i]) === -1) {
						this.values.push(value[i]);
					}
				}
			} else {
				this.values.push(value);
			}
			this.status = 'unused';
			saveToLocalStorage(this.values, this.status);
		};


		this.replace = function (value) {
			this.clear();
			this.append(value);
		};


		this.remove = function (item) {
			var p = this.values.indexOf(item);
			if (p !== -1) {
				this.values.splice(p, 1);
				saveToLocalStorage(this.values, this.status);
			}
		};


		this.getItems = function (markAsUsed) {
			if (markAsUsed) {
				this.markAsUsed();
			}
			return this.values;
		};


		this.markAsUsed = function (used) {
			this.status = 'used';
			saveToLocalStorage(this.values, this.status);
		};

	}

	ClipboardService.$inject = ['RbsChange.ArrayUtils', 'RbsChange.REST', 'localStorageService'];

	angular.module('RbsChange').service('RbsChange.Clipboard', ClipboardService);

})();