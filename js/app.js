(function() {
	var app = angular.module('bills', []);

	app.controller('GroceriesController', ['$http', function($http) {
		var ctrl = this;
		ctrl.sortField = ['date', 'weekStart', 'month'];
		ctrl.sortReverse = [true, true, true];

		ctrl.loadBills = function() {
			//load bills
			$http.get('groceries.php')
			.success(function (data, status, headers, config) {
				ctrl.responseBills = {message: 'success', data: data, status: status, headers: headers, config: config};
				ctrl.bills = data;		
			})
			.error(function (data, status, headers, config) {
				ctrl.responseBills = {message: 'error', data: data, status: status, headers: headers, config: config};
			});

			//load weeks, calculate weeks summaries
			$http.get('groceries.php?weeks')
			.success(function (data, status, headers, config) {
				ctrl.responseWeeks = {message: 'success', data: data, status: status, headers: headers, config: config};
				ctrl.weeks = data;
				ctrl.weeksSummary = {};

				var nWeeks = 0, nBills = 0, amount = 0;
				for (var key in ctrl.weeks) {
					nWeeks++;
					nBills += +ctrl.weeks[key].count;
					amount += +ctrl.weeks[key].amount;
				}
				ctrl.weeksSummary.totalWeeks = nWeeks;
				ctrl.weeksSummary.totalBills = nBills;
				ctrl.weeksSummary.totalAmount = amount;
				ctrl.weeksSummary.averageBills = nBills / nWeeks;
				ctrl.weeksSummary.averageAmount = amount / nWeeks;
			})
			.error(function (data, status, headers, config) {
				ctrl.responseWeeks = {message: 'error', data: data, status: status, headers: headers, config: config};
			});

			//load months, calculate months summaries
			$http.get('groceries.php?months')
			.success(function (data, status, headers, config) {
				ctrl.responseMonths = {message: 'success', data: data, status: status, headers: headers, config: config};
				ctrl.months = data;
				ctrl.monthsSummary = {};

				var nMonths = 0, nBills = 0, amount = 0;
				for (var key in ctrl.months) {
					nMonths++;
					nBills += +ctrl.months[key].count;
					amount += +ctrl.months[key].amount;
				}
				ctrl.monthsSummary.totalMonths = nMonths;
				ctrl.monthsSummary.totalBills = nBills;
				ctrl.monthsSummary.totalAmount = amount;
				ctrl.monthsSummary.averageBills = nBills / nMonths;
				ctrl.monthsSummary.averageAmount = amount / nMonths;
			})
			.error(function (data, status, headers, config) {
				ctrl.responseMonths = {message: 'error', data: data, status: status, headers: headers, config: config};
			});
		};

		ctrl.addBill = function(newBill) {
			var billToAdd = JSON.parse(JSON.stringify(ctrl.newBill));
			billToAdd.date = billToAdd.date ? moment(billToAdd.date).format('YYYY-MM-DD') : null;
			$http({
				method: 'POST',
				url: 'groceries.php',
				data: billToAdd,
				header: {'Content-Type': 'application/c-www-form-urlencoded'}
			})
			.success(function (data, status, headers, config) {
				ctrl.responseAdd = 'SUCCESS - DATA: ' + data + '|STATUS: ' + status + '|HEADERS: ' + headers + '|CONFIG: ' + config;
				ctrl.newBill = null;
				ctrl.loadBills();
			})
			.error(function (data, status, headers, config) {
				ctrl.responseAdd = 'ERROR - DATA: ' + data + '|STATUS: ' + status + '|HEADERS: ' + headers + '|CONFIG: ' + config;
			});
		};

		ctrl.changeSortField = function(tableNumber, field) {
			// if field is already selected, toggle the sort direction
			if(ctrl.sortField[tableNumber] == field) {
				ctrl.sortReverse[tableNumber] = !ctrl.sortReverse[tableNumber];
			} else {
				ctrl.sortField[tableNumber] = field;
				ctrl.sortReverse[tableNumber] = false;
			}
		};
		ctrl.isSortField = function(tableNumber, field) {
			return ctrl.sortField[tableNumber] == field;
		};

		ctrl.loadBills();
	}]);
})();
