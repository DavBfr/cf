if (typeof angular != 'undefined') {

  var app = angular.module('app', ['ngRoute', 'ngAnimate', 'mgcrea.ngStrap']);

  app.config(function ($routeProvider) {
    $routeProvider.otherwise({
      redirectTo: '/'
    });
  });

}
