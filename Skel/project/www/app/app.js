if (typeof angular !== 'undefined') {

  var app = angular.module('app', []);

  app.config(function ($routeProvider) {
    if (typeof AddUserRoutes !== 'undefined') {
      AddUserRoutes($routeProvider);
    }

    $routeProvider.when('/', {
      templateUrl: cf_options.rest_path + '/home'
    });

    $routeProvider.otherwise({
      redirectTo: '/',
    });
  });

}
