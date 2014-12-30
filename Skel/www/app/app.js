if (typeof angular != 'undefined') {

  var app = angular.module('app', ['ngRoute', 'ngAnimate', 'mgcrea.ngStrap']);

  app.config(function ($routeProvider) {
    AddUserRoutes($routeProvider);
    
    $routeProvider.otherwise({
      redirectTo: '/'
    });
  });

}
