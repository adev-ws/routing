<?php
/**
 * ---------------------------
 * Router helper class
 * ---------------------------
 *
 * This class is added so calls can be made statically like SimpleRouter::get() making the code look pretty.
 * It also adds some extra functionality like default-namespace etc.
 */

namespace adevws\Routing;

use Closure;
use Exception;
use adevws\Routing\Exceptions\InvalidArgumentException;
use adevws\Routing\Http\Middleware\BaseCsrfVerifier;
use adevws\Routing\Http\Request;
use adevws\Routing\Http\Response;
use adevws\Routing\Http\Url;
use adevws\Routing\ClassLoader\IClassLoader;
use adevws\Routing\Exceptions\HttpException;
use adevws\Routing\Handlers\CallbackExceptionHandler;
use adevws\Routing\Handlers\IEventHandler;
use adevws\Routing\Route\IGroupRoute;
use adevws\Routing\Route\ILoadableRoute;
use adevws\Routing\Route\IPartialGroupRoute;
use adevws\Routing\Route\IRoute;
use adevws\Routing\Route\RouteController;
use adevws\Routing\Route\RouteGroup;
use adevws\Routing\Route\RoutePartialGroup;
use adevws\Routing\Route\RouteResource;
use adevws\Routing\Route\RouteUrl;

class SRouter extends Manager
{
    /**
     * Default namespace added to all routes
     * @var string|null
     */
    protected $defaultNamespace;

    /**
     * The response object
     * @var Response
     */
    protected $response;

    /**
     * Start routing
     *
     * @throws \adevws\Routing\Exceptions\NotFoundHttpException
     * @throws \adevws\Routing\Http\Middleware\Exceptions\TokenMismatchException
     * @throws HttpException
     * @throws Exception
     */
    public function run(): void
    {
        foreach ($this->getRoutes() as $route) {
            $this->addDefaultNamespace($route);
        }

        echo $this->start();
    }

    /**
     * Start the routing an return array with debugging-information
     *
     * @return array
     */
    public function runDebug(): array
    {
        $routerOutput = null;

        try {
            ob_start();
            $this->setDebugEnabled(true)->start();
            $routerOutput = ob_get_clean();
        } catch (Exception $e) {

        }

        // Try to parse library version
        $composerFile = dirname(__DIR__, 3) . '/composer.lock';
        $version = false;

        if (is_file($composerFile) === true) {
            $composerInfo = json_decode(file_get_contents($composerFile), true);

            if (isset($composerInfo['packages']) === true && is_array($composerInfo['packages']) === true) {
                foreach ($composerInfo['packages'] as $package) {
                    if (isset($package['name']) === true && strtolower($package['name']) === 'adevws\Routing/simple-router') {
                        $version = $package['version'];
                        break;
                    }
                }
            }
        }

        $request = $this->getRequest();
        $router = $this;

        return [
            'url'             => $request->getUrl(),
            'method'          => $request->getMethod(),
            'host'            => $request->getHost(),
            'loaded_routes'   => $request->getLoadedRoutes(),
            'all_routes'      => $router->getRoutes(),
            'boot_managers'   => $router->getBootManagers(),
            'csrf_verifier'   => $router->getCsrfVerifier(),
            'log'             => $router->getDebugLog(),
            'event_handlers'  => $router->getEventHandlers(),
            'router_output'   => $routerOutput,
            'library_version' => $version,
            'php_version'     => PHP_VERSION,
            'server_params'   => $request->getHeaders(),
        ];
    }

    /**
     * Set default namespace which will be prepended to all routes.
     *
     * @param string $defaultNamespace
     */
    public function setDefaultNamespace(string $defaultNamespace): void
    {
        $this->defaultNamespace = $defaultNamespace;
    }

    /**
     * Base CSRF verifier
     *
     * @param BaseCsrfVerifier $baseCsrfVerifier
     */
    public function csrfVerifier(BaseCsrfVerifier $baseCsrfVerifier): void
    {
        $this->setCsrfVerifier($baseCsrfVerifier);
    }

    /**
     * Redirect to when route matches.
     *
     * @param string $where
     * @param string $to
     * @param int $httpCode
     * @return IRoute
     */
    public function redirect(string $where, string $to, int $httpCode = 301): IRoute
    {
        return $this->get($where, static function () use ($to, $httpCode): void {
            $this->response()->redirect($to, $httpCode);
        });
    }

    /**
     * Route the given url to your callback on GET request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     *
     * @return IRoute
     */
    public function get(string $url, $callback, array $settings = null): IRoute
    {
        return $this->match([Request::REQUEST_TYPE_GET], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on POST request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public function post(string $url, $callback, array $settings = null): IRoute
    {
        return $this->match([Request::REQUEST_TYPE_POST], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PUT request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public function put(string $url, $callback, array $settings = null): IRoute
    {
        return $this->match([Request::REQUEST_TYPE_PUT], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PATCH request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public function patch(string $url, $callback, array $settings = null): IRoute
    {
        return $this->match([Request::REQUEST_TYPE_PATCH], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on OPTIONS request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public function options(string $url, $callback, array $settings = null): IRoute
    {
        return $this->match([Request::REQUEST_TYPE_OPTIONS], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on DELETE request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public function delete(string $url, $callback, array $settings = null): IRoute
    {
        return $this->match([Request::REQUEST_TYPE_DELETE], $url, $callback, $settings);
    }

    /**
     * Groups allows for encapsulating routes with special settings.
     *
     * @param array $settings
     * @param Closure $callback
     * @return RouteGroup|IGroupRoute
     * @throws InvalidArgumentException
     */
    public function group(array $settings, Closure $callback): IGroupRoute
    {
        $group = new RouteGroup();
        $group->setCallback($callback);
        $group->setSettings($settings);

        $this->addRoute($group);

        return $group;
    }

    /**
     * Special group that has the same benefits as group but supports
     * parameters and which are only rendered when the url matches.
     *
     * @param string $url
     * @param Closure $callback
     * @param array $settings
     * @return RoutePartialGroup|IPartialGroupRoute
     * @throws InvalidArgumentException
     */
    public function partialGroup(string $url, Closure $callback, array $settings = []): IPartialGroupRoute
    {
        $settings['prefix'] = $url;

        $group = new RoutePartialGroup();
        $group->setSettings($settings);
        $group->setCallback($callback);

        $this->addRoute($group);

        return $group;
    }

    /**
     * Alias for the form method
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     * @see SRouter::form
     */
    public function basic(string $url, $callback, array $settings = null): IRoute
    {
        return $this->form($url, $callback, $settings);
    }

    /**
     * This type will route the given url to your callback on the provided request methods.
     * Route the given url to your callback on POST and GET request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     * @see SRouter::form
     */
    public function form(string $url, $callback, array $settings = null): IRoute
    {
        return $this->match([
            Request::REQUEST_TYPE_GET,
            Request::REQUEST_TYPE_POST,
        ], $url, $callback, $settings);
    }

    /**
     * This type will route the given url to your callback on the provided request methods.
     *
     * @param array $requestMethods
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public function match(array $requestMethods, string $url, $callback, array $settings = null): IRoute
    {
        $route = new RouteUrl($url, $callback);
        $route->setRequestMethods($requestMethods);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return $this->addRoute($route);
    }

    /**
     * This type will route the given url to your callback and allow any type of request method
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|IRoute
     */
    public function all(string $url, $callback, array $settings = null): IRoute
    {
        $route = new RouteUrl($url, $callback);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return $this->addRoute($route);
    }

    /**
     * This route will route request from the given url to the controller.
     *
     * @param string $url
     * @param string $controller
     * @param array|null $settings
     * @return RouteController|IRoute
     */
    public function controller(string $url, string $controller, array $settings = null): IRoute
    {
        $route = new RouteController($url, $controller);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return $this->addRoute($route);
    }

    /**
     * This type will route all REST-supported requests to different methods in the provided controller.
     *
     * @param string $url
     * @param string $controller
     * @param array|null $settings
     * @return RouteResource|IRoute
     */
    public function resource(string $url, string $controller, array $settings = null): IRoute
    {
        $route = new RouteResource($url, $controller);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return $this->addRoute($route);
    }

    /**
     * Add exception callback handler.
     *
     * @param Closure $callback
     * @return CallbackExceptionHandler $callbackHandler
     */
    public function error(Closure $callback): CallbackExceptionHandler
    {
        $callbackHandler = new CallbackExceptionHandler($callback);

        $this->addExceptionHandler($callbackHandler);

        return $callbackHandler;
    }

    /**
     * Get url for a route by using either name/alias, class or method name.
     *
     * The name parameter supports the following values:
     * - Route name
     * - Controller/resource name (with or without method)
     * - Controller class name
     *
     * When searching for controller/resource by name, you can use this syntax "route.name@method".
     * You can also use the same syntax when searching for a specific controller-class "MyController@home".
     * If no arguments is specified, it will return the url for the current loaded route.
     *
     * @param string|null $name
     * @param string|array|null $parameters
     * @param array|null $getParams
     * @return Url
     */
    public function getUrl(?string $name = null, $parameters = null, ?array $getParams = null): Url
    {
        try {
            return $this->getUrl($name, $parameters, $getParams);
        } catch (Exception $e) {
            return new Url('/');
        }
    }

    /**
     * Get the response object
     *
     * @return Response
     */
    public function response(): Response
    {
        if ($this->response === null) {
            $this->response = new Response($this->getRequest());
        }

        return $this->response;
    }

    /**
     * Prepends the default namespace to all new routes added.
     *
     * @param IRoute $route
     * @return IRoute
     */
    public function addDefaultNamespace(IRoute $route): IRoute
    {
        if ($this->defaultNamespace !== null) {
            $route->setNamespace($this->defaultNamespace);
        }

        return $route;
    }

    /**
     * Changes the rendering behavior of the router.
     * When enabled the router will render all routes that matches.
     * When disabled the router will stop rendering at the first route that matches.
     *
     * @param bool $bool
     */
    public function enableMultiRouteRendering(bool $bool): void
    {
        $this->setRenderMultipleRoutes($bool);
    }

    /**
     * Set custom class-loader class used.
     * @param IClassLoader $classLoader
     */
    public function setCustomClassLoader(IClassLoader $classLoader): void
    {
        $this->setClassLoader($classLoader);
    }

}