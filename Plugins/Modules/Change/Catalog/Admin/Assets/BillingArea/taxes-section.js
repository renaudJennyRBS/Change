(function ()
{
	angular.module('RbsChange').directive('taxesSection', ['RbsChange.i18n', function (i18n)
	{
		return {
			restrict    : 'A',
			templateUrl: 'Change/Catalog/BillingArea/taxes-section.twig',
			replace     : false,

			link : function (scope, elm, attrs) {
				scope.TaxesSection = {
					// Data.
					// TODO dynamic data (scope.document.id)
					categories: [{code: '1'}, {code: '2'}],
					lines: [
						{ zone: {code: 'FR'}, rates: [{category: '1', value: 10.5}, {category: '2', value: 4.2}] },
						{ zone: {code: 'BE'}, rates: [{category: '1', value: 75.5}, {category: '2', value: null}] }
					],

					// Categories management.
					newCategoryCode: '',
					addCategory: function() {
						var code = this.newCategoryCode;
						if (!code)
						{
							return;
						}
						for (var i in this.categories)
						{
							if (this.categories[i].code == code)
							{
								alert(i18n.trans('m.change.catalog.admin.js.tax-zone-exists'));
								return;
							}
						}
						this.categories.push({code: code});

						for (var j in this.lines)
						{
							this.lines[j].rates.push({category: code, value: null});
						}
						this.newCategoryCode = '';
					},
					removeCategory: function(index) {
						this.categories.splice(index, 1);
						for (var j in this.lines)
						{
							this.lines[j].rates.splice(index, 1);
						}
					},

					// Zones management.
					newZoneCode: '',
					addZone: function() {
						var code = this.newZoneCode;
						if (!code)
						{
							return;
						}
						for (var i in this.lines)
						{
							if (this.lines[i].zone.code == code)
							{
								alert(i18n.trans('m.change.catalog.admin.js.tax-category-exists'));
								return;
							}
						}

						var line = { zone: {code: code}, rates: []};
						for (var j in this.categories)
						{
							line.rates.push({category: this.categories[j].code, value: null});
						}
						this.lines.push(line);
						this.newZoneCode = '';
					},
					removeZone: function(index) {
						this.lines.splice(index, 1);
					},

					// Deletion mode.
					deletionMode: false,
					enableDeletion: function() {
						this.deletionMode = true;
					},
					disableDeletion: function() {
						this.deletionMode = false;
					}
				}
			}
		};
	}]);
})();