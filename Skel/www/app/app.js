if (typeof angular != 'undefined') {

  var app = angular.module('app', ['ngRoute', 'ngAnimate', 'mgcrea.ngStrap']);

  app.config(function ($routeProvider) {
    $routeProvider.when('/user', {
      controller: 'UserController',
      templateUrl: cf_options.rest_path + '/user/list',
      menu: 'user',
      title: 'Users'
    }).when('/user/:id', {
      controller: 'UserDetailController',
      templateUrl: cf_options.rest_path + '/user/detail',
      menu: 'users',
    }).otherwise({
      redirectTo: '/user'
    });
  });

}
