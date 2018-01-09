'use strict';

angular
    .module('continuousPipeRiver', [
        'config',
        'ngAnimate',
        'ngMessages',
        'ngSanitize',
        'angular-loading-bar',
        'ngResource',
        'ui.router',
        'ngMaterial',
        'ncy-angular-breadcrumb',
        'angular-jwt',
        'angular-md5',
        'angular-google-analytics',
        'slugifier',
        'ui.ace',
        'yaru22.angular-timeago',
        'firebase',
        'RecursionHelper',
        'angularResizable',
        'googlechart',
        'kubeStatusDashboard'
    ])
    .constant('KUBE_STATUS_TEMPLATE_URI_ROOT', 'bower_components/kube-status/ui/app/')
    .config(function ($urlRouterProvider, $breadcrumbProvider, $locationProvider, $mdThemingProvider, AnalyticsProvider) {
        $urlRouterProvider.otherwise('/');
        $locationProvider.html5Mode(true);
        $breadcrumbProvider.setOptions({
            includeAbstract: true
        });

        AnalyticsProvider
            .setAccount('UA-71216332-2')
            .setPageEvent('$stateChangeSuccess')
            ;

        //$mdThemingProvider.theme('blue');

        firebase.initializeApp({
            apiKey: "AIzaSyDIK_08syPHkRxcf2n8zJ48XAVPHWpTsp0",
            authDomain: "continuous-pipe.firebaseapp.com",
            databaseURL: "https://continuous-pipe.firebaseio.com",
        });
    })
    .factory('$exceptionHandler', function ($window, $log, SENTRY_DSN) {
        if (SENTRY_DSN) {
            Raven.config(SENTRY_DSN).install();
        }

        return function (exception, cause) {
            $log.error.apply($log, arguments);

            if (SENTRY_DSN) {
                Raven.captureException(exception);
            }
        };
    })
    // We need to inject it at least once to have automatic tracking
    .run(['$rootScope', '$state', '$http', '$firebaseApplicationResolver', '$intercom', function ($rootScope, $state, $http, $firebaseApplicationResolver, $intercom) {
        function capitalizeFirstLetter(word) {
            return word.charAt(0).toUpperCase() + word.slice(1);
        }

        function titleCase(text) {
            return text.replace(/[\.\-\_]/g, ' ').split(' ').map(capitalizeFirstLetter).join(' ');
        }

        function formatTitle(text) {
            var titleCasedText = titleCase(text);
            return titleCasedText ? titleCasedText + ' - ' : '';
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) $rootScope.$emit('visibility-changed');
        });

        $rootScope.$on('$stateChangeStart', function (event, current, params) {
            $rootScope.title = formatTitle(current.name);

            if (current.redirectTo) {
                event.preventDefault();
                $state.go(current.redirectTo, params, { location: 'replace' });
            }
        });

        $rootScope.$on('user_context.user_updated', function (event, user) {
            $intercom.configure(user);

            window.satismeter({
                writeKey: "MAY39UHqizidGBSa",
                userId: user.username,
                traits: {
                    email: user.email
                }
            });
        });

        $http.getError = function (error) {
            var response = error || {};
            var body = response.data || {};
            var message = body.message || body.error;

            if (!message && response.status == 400) {
                // We are seeing a constraint violation list here, let's return the first one
                message = body[0] && body[0].message;
            }

            if (typeof message == 'object') {
                message = message.message;
            }

            return message;
        };

        $firebaseApplicationResolver.init('continuouspipe-watch-logs', {
            apiKey: "AIzaSyBRRw-vWdMbylnupsE8OVZNp3d6t5hl7tE",
            authDomain: "continuouspipe-watch-logs.firebaseapp.com",
            databaseURL: "https://continuouspipe-watch-logs.firebaseio.com"
        });
    }])
    ;
