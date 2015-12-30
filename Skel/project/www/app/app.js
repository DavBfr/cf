if (typeof angular != 'undefined') {

  var app = angular.module('app', ['ngRoute', 'ngAnimate', 'mgcrea.ngStrap', 'ui.select']);

  app.config(function ($routeProvider) {
    AddUserRoutes($routeProvider);
    
    $routeProvider.otherwise({
      redirectTo: '/'
    });
  });

}
