(function ()
{
	angular.module('RbsChange').directive('taxesSection', ['RbsChange.i18n', 'RbsChange.REST', function (i18n, REST)
	{
		return {
			restrict    : 'A',
			templateUrl: 'Rbs/Catalog/BillingArea/taxes-section.twig',
			replace     : false,

			link : function (scope, elm, attrs) {
				// TODO better way to ignore this property...
				delete scope.document.taxes;

				scope.TaxesSection = {
					// Tax management.
					newTaxCode: '',
					addTax: function() {
						var code = this.newTaxCode;
						if (!code)
						{
							return;
						}
						for (var i in scope.document.taxesData)
						{
							if (scope.document.taxesData[i].code == code)
							{
								alert(i18n.trans('m.rbs.catalog.admin.js.tax-zone-exists'));
								return;
							}
						}
						scope.document.taxesData.push({code: code, categories: [], ratesByZone: []});
						this.newTaxCode = '';
					},
					removeTax: function(index) {
						scope.document.taxesData.splice(index, 1);
					},
					moveTaxUp: function(index) {
						if (index > 0)
						{
							scope.document.taxesData.splice(index-1, 0, scope.document.taxesData.splice(index, 1)[0]);
						}
					},
					moveTaxDown: function(index) {
						if (index+1 < scope.document.taxesData.length)
						{
							scope.document.taxesData.splice(index+1, 0, scope.document.taxesData.splice(index, 1)[0]);
						}
					},

					// Categories management.
					newCategoryCode: '',
					addCategory: function(tax) {
						var code = this.newCategoryCode;
						if (!code)
						{
							return;
						}
						for (var i in tax.categories)
						{
							if (tax.categories[i].code == code)
							{
								alert(i18n.trans('m.rbs.catalog.admin.js.tax-zone-exists'));
								return;
							}
						}
						tax.categories.push({code: code});

						for (var j in tax.ratesByZone)
						{
							tax.ratesByZone[j].rates.push({category: code, value: null});
						}
						this.newCategoryCode = '';
					},
					removeCategory: function(tax, index) {
						tax.categories.splice(index, 1);
						for (var j in tax.ratesByZone)
						{
							tax.ratesByZone[j].rates.splice(index, 1);
						}
					},

					// Zones management.
					newZoneCode: '',
					addZone: function(tax) {
						var code = this.newZoneCode;
						if (!code)
						{
							return;
						}
						for (var i in tax.ratesByZone)
						{
							if (tax.ratesByZone[i].zone.code == code)
							{
								alert(i18n.trans('m.rbs.catalog.admin.js.tax-category-exists'));
								return;
							}
						}

						var line = { zone: {code: code}, rates: []};
						for (var j in tax.categories)
						{
							line.rates.push({category: tax.categories[j].code, value: null});
						}
						tax.ratesByZone.push(line);
						this.newZoneCode = '';
					},
					removeZone: function(index) {
						tax.ratesByZone.splice(index, 1);
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

				// TODO: dynamic list of Rbs_Geo_Zone.
				/*REST.collection('Rbs_Geo_Zone').then(function (zones)
				{
					scope.TaxesSection.zones = zones.resources;
				});*/
				scope.TaxesSection.zones = [
					{label: 'France continentale', code: 'FR-CONT' },
					{label: 'Corse', code: 'FR-CORSE' },
					{label: 'Belgique', code: 'BE' }
				];
			}
		};
	}]);
})();