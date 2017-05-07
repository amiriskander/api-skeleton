<?php

namespace ApiApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use ApiApp\Component\Auth;

class App implements HttpKernelInterface
{
    protected $routes = array(); // Application Routes

    public static $config;

    public function __construct()
    {
        $this->routes = new RouteCollection();

        $config_reader = new \Mcustiel\Config\Drivers\Reader\yaml\Reader();
        $config_reader->read(__DIR__ . "/../../config/config.yml");
        App::$config = $config_reader->getConfig()->getFullConfigAsArray();
    }

    /**
     * getConfig
     * Get configuration file from the default configuration file
     * @param string $config_path           Path to configuration with the format ParentConfig1:ParentConfig2:Config
     * @param mixed|bool $default_value     Default value to be used if the configuration was not found
     * @return mixed
     */
    public static function getConfig($config_path, $default_value = FALSE)
    {
        $configKeys = explode(':',$config_path);
        $configsToSearch = App::$config;

        $count = 0;
        foreach($configKeys as $key){
            if(isset($configsToSearch[$key])) {
                if($count == count($configKeys) - 1){
                    return $configsToSearch[$key];
                }
                else{
                    $configsToSearch = $configsToSearch[$key];
                }
            }
            $count++;
        }
        return $default_value;
    }

    /**
     * handle
     * Implements the HttpKernelInterface handle method
     * @param Request $request      User Request
     * @param int $type             Request Type
     * @param bool $catch
     * @return bool|mixed|Response
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // create a context using the current request
        $context = new RequestContext();
        $context->fromRequest($request);

        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $attributes = $matcher->match($request->getPathInfo());

            $controller = $attributes['_controller'];
            unset($attributes['_controller']);

            $protected = $attributes['_protected'];
            unset($attributes['_protected']);

            if($protected){
                $auth = new Auth();
                $response = $auth->authorizeRequest($request);
                if($response === TRUE){
                    $response = call_user_func_array($controller, $attributes);
                }
            }
            else{
                $response = call_user_func_array($controller, $attributes);
            }

        } catch (ResourceNotFoundException $e) {
            $response = new Response('Not Found!', Response::HTTP_NOT_FOUND);
        }

        // set headers for CORS
        $response->headers->set('Access-Control-Allow-Origin','*');
        $response->headers->set('Access-Control-Allow-Methods','*');
        $response->headers->set('Content-Type','application/json');

        return $response;
    }

    // Associates a path with a callback
    /**
     * map
     * Map a route to request
     * @param string $routeName    Route name
     * @param string $path          Route path
     * @param $controller           Controller to be used
     * @param array $methods        Allowed methods
     * @param bool $protected       Whether this route is protected or not
     */
    public function map($routeName, $path, $controller, $methods, $protected=FALSE) {
        $this->routes->add($routeName, new Route(
            $path,
            array('_controller' => $controller, '_protected' => $protected),
            array(), // requirements
            array(), // options
            null, // host
            array(), // schemes
            $methods // methods
        ));
    }

}