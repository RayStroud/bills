(function() {
	angular.module('bills', ['ngRoute'])

	//* ONLINE */ .constant('backend', {domain:'http://23.17.122.45:7290/bills/data/'})
	/* LOCAL */ .constant('backend', {domain:'./data/'})

	.config(function ($routeProvider) {
		$routeProvider
		.when('/bills', 			{templateUrl: 'app/pages/bills.html'})
		.when('/groceries', 		{templateUrl: 'app/pages/groceries.html'})
		.when('/gas', 				{templateUrl: 'app/pages/gas.html'})
		.when('/monthly', 			{templateUrl: 'app/pages/monthly.html'})
		.when('/bills/add', 		{templateUrl: 'app/pages/bill-add.html'})
		.when('/', 					{templateUrl: 'app/pages/home.html'})
		.otherwise(					{redirectTo: '/'});
	})
})();
