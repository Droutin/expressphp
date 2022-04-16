<?php
/**
 * App Class
 *
 * The App class is the main entry point to Express PHP Framework. It is used to create the Express Object
 * which constructs the Express PHP frame work, providing route methods to ignite middle wares and router,
 * imports Express PHP modules, used in setting and getting Express PHP Configurations...
 *
 * @copyright Copyright (c) Victor Aremu <victor.olorunbunmi@gmail.com>
 * @license MIT
 */

namespace ExpressPHP;

class App
{
    /**
     * @var string Express base path
     */
    public $basePath;

    /**
     * @var string Express base path
     */
    public $staticPath;

    /**
     * @var array Array of app shared variables
     */

    public static $sharedData = [];

    /**
     * @var array holding error pages handler routes
     */
    public $errorPage = [];

    /**
     * @var object Router object
     */
    public $router;

    /**
     * @var array Array Contains Injected Express modules in the format:
     *            array('_MODULE_NAME'=>OBJECT);
     */
    private $modules;

    /**
     * @var array Array Contains Reverse names(identifiers) of routes as the keys and
     *      their second callback functions as value in a format:
     *      array('route'=>'callback')
     *      i.e array('/user/[i:uid]'=>$callback(new Request, new Response));
     */
    public $route_callback = [];

    /**
     * @var string Path to Express views directory
     */
    public $views;

    /**
     * @var string Express view engine name
     */
    public $view_engine;

    /**
     * @var array Array Register that holds callbacks for route specific middle wares in format:
     *            array('route'=>array('middleware1_callback', 'middleware2_callback'))
     */
    public $route_middlewares = [];

    /**
     * @var string Express Application environment state variable
     */
    public $env = 'development';

    /**
     * @var boolean Enables or disables view template caching
     */
    public $template_caching;

    /**
     * @var string Express view  template cache directory
     */
    public $template_cache_dir;

    /**
     * Construct Express Application
     * @author Victor Aremu
     */
    public function __construct()
    {
        # Set the Error pages
        //301 error Moved Permanently
        $this->errorPage['301'] = null;

        // 401 error Unauthorized
        $this->errorPage['401'] = null;
        // 404 error Not found
        $this->errorPage['404'] = null;
        // 500 error Internal Server error
        $this->errorPage['500'] = null;

        # Set the Express base path as '/' by default...
        $this->basePath = '/';

        # Set the Express static path as '/' by default...
        $this->staticPath = '/';

        # Instance a new Router class...
        $this->router = new Router();

        # Set Express default template engine...
        $this->view_engine = 'default';

        # Disable view template caching by default...
        $this->template_caching = false;
    }

    /**
     * Import Module into Express
     * @param string $module_name
     */

    public function import($module_name)
    {
        global $rootDir;
        $path = $rootDir . '/kernel/express_modules/' . $module_name . '/index.php';
        if (file_exists($path)) {
            include $path;
        } else {
            throw new Exception('Unable to Load Module: ' . $module_name);
        }
    }

    /**
     * Magic function __set Invokes when user set a value to a undefined class property
     * Useful for Registering Express modules
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        /**
         * Check if the property name meets Express Module Naming convention format:
         *     Underscore followed by module name in pascal case
         *     _ModuleName
         */
        if (strpos($name, '_') !== false && strpos($name, '_') === 0) {
            /**
             * It Match Express Modules naming convention
             * Register the module
             */
            $this->modules[$name] = $value;
        } else {
            throw new Exception('Cannot set ' . $name . '. Not a defined class property', 1);
        }
    }

    /**
     * Magic function __get() Invokes when user calls Object properties that does not exists
     * Useful for getting Express Injected Modules
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        /**
         * Check if the user is trying to get a module
         */
        if (strpos($name, '_') !== false && strpos($name, '_') === 0) {
            /**
             * TRUE => $name Match Express Module naming convention
             * Return the appropriate module object
             */

            # Check If module exits...
            if (array_key_exists($name, $this->modules)) {
                # Module exists return module object...
                return $this->modules[$name];
            } else {
                # Module does exists return NULL...
                return null;
            }
        } else {
            throw new Exception($name . ' not a defined class property', 1);
        }
    }

    /**
     * Magic function __call Invokes when user calls Object method that does not exists
     * Useful for overloading Object methods
     * @param string $method_name
     * @param array $parameter
     */
    public function __call($method_name, $parameter)
    {
        /**
         * Overloaded methods includes:
         *      $Object->use($param1);
         *      $Object->use($param1, $param2);
         *      $Object->get($param1);
         *      $Object->get($param1, $param2);
         */
        if ($method_name == 'use') {
            # Overloaded Use method goes here...
            $count = count($parameter);

            switch ($count) {
                case '1':
                    # Global middle ware logic...

                    /**
                     * Invoke the middle ware
                     */
                    $parameter[0];
                    break;
                case '2':
                    # Route specific middle ware...

                    /**
                     * $parameter[0] => middle ware name
                     * $parameter[1] => middle ware callback function
                     */

                    # Check if the middle ware is already registered...
                    if (array_key_exists($parameter[0], $this->route_middlewares)) {
                        /**
                         * Implies that this middle ware as been registered
                         * The user is trying to append a new middle ware to that route
                         */
                        array_push($this->route_middlewares[$parameter[0]], $parameter[1]);
                    } else {
                        /**
                         * The user hasn't registered that middle ware before
                         */
                        $this->route_middlewares[$parameter[0]] = [];
                        array_push($this->route_middlewares[$parameter[0]], $parameter[1]);
                    }
                    break;
            }

            if ($count == 0 || $count > 2) {
                throw new Exception('Bad Argument');
            }
        } elseif ($method_name == 'get') {
            #Overloaded get method goes here..
            $count_get = count($parameter);
            switch ($count_get) {
                case '1':
                    # Overloaded function to get Express configuration details...
                    switch (strtolower($parameter[0])) {
                        case 'basepath':
                            return $this->basePath;
                            break;
                        case 'static':
                            return $this->staticPath;
                            break;
                        case 'views':
                            return $this->views;
                            break;
                        case 'view engine':
                            return $this->view_engine;
                            break;
                        case 'env':
                            return $this->env;
                            break;
                        case 'view cache':
                            return $this->template_caching;
                            break;
                        case 'view cache path':
                            return $this->template_cache_dir;
                            break;
                        case 'error 301':
                            return $this->errorPage['301'];
                            break;
                        case 'error 401':
                            return $this->errorPage['401'];
                            break;
                        case 'error 404':
                            return $this->errorPage['404'];
                            break;
                        case 'error 500':
                            return $this->errorPage['500'];
                            break;
                    }

                    break;
                case '2':
                    # Overloaded function to route HTTP post request...

                    /**
                     * @param string $route
                     * @param function $callback
                     */
                    $route = $this->getRoute($parameter[0]);
                    $callback = $parameter[1];

                    /**
                     * The GET HTTP request route function
                     * Set this route reverse name in format:
                     *     $route-$_SERVER['REQUEST_METHOD']
                     */
                    $route_reverse_name = $route . '-GET';

                    /**
                     * Append an  array into the @var $route_callback[] using the reverse name as a key
                     * and set the value to the callback function for this route
                     */
                    $this->route_callback[$route_reverse_name] = $callback;

                    # Map the route; inside this 1st call back function, invoke the 2nd callback...
                    $this->router->map(
                        'GET',
                        $route,
                        function ($call, $params) {
                            # Instance a Request object...
                            $req = new Request();

                            # Instance a Response object...
                            $res = new Response();

                            # Configure view engine...
                            $res->configTemplate(
                                $this->basePath,
                                $this->view_engine,
                                $this->views,
                                $this->template_caching,
                                $this->template_cache_dir,
                            );

                            # Loop through the route parameters and map the values...
                            foreach ($params as $key => $value) {
                                $req->setParams($key, $value);
                            }

                            # Get current route
                            $get_route = str_replace($this->basePath, '', $_SERVER['REQUEST_URI']);

                            /**
                             * Execute route specific middle wares
                             */

                            # loop through the @var $route_middlewares...
                            foreach ($this->route_middlewares as $key => $value) {
                                // flag here
                                // if($key==$get_route) {

                                if (strpos($get_route, $key) !== false) {
                                    # The middle ware's name match this route, execute the middle ware callback...
                                    $count_call = count($value);
                                    for ($a = 0; $a < $count_call; $a++) {
                                        # Invoke the middle ware, pass request and response object...
                                        $value[$a]($req, $res);
                                    }
                                }
                            }

                            $call($req, $res);
                        },
                        $route_reverse_name,
                    );
                    break;
            }

            if ($count_get == 0 || $count_get > 2) {
                throw new Exception('Bad Argument');
            }
        } elseif ($method_name == 'setGlobal') {
            $count = count($parameter);
            if ($count == 0 || $count > 2) {
                throw new Exception('Bad Argument');
            } else {
                Express::$sharedData[$parameter[0]] = $parameter[1];
            }
        } elseif ($method_name == 'getGlobal') {
            return Express::$sharedData[$parameter[0]];
        } else {
            throw new Exception('Function ' . $method_name . ' does not exists.');
        }
    }

    private function getRoute(string $name)
    {
        global $rootDir;

        $path = str_replace("$rootDir/routes", '', debug_backtrace()[1]['file']);
        $path = explode('/', $path);
        array_pop($path);
        $path = implode('/', $path);

        $route = "$path$name";
        if (strlen($route) > 1) {
            $route = rtrim($route, '/');
        }

        return $route;
    }

    /**
     * The POST HTTP request route method
     * @param string $route
     * @param function $callback
     */
    public function post($route, $callback)
    {
        $route = $this->getRoute($route);
        /**
         * The POST HTTP request wrapper function
         * Set this route reverse name to $route
         */
        $route_reverse_name = $route . '-POST';

        /**
         * Append an  array into the route_callback[] using the reverse name as a key
         * and set the value to the callback function for this route
         */

        $this->route_callback[$route_reverse_name] = $callback;

        # Map the route; inside this 1st call back function, invoke the 2nd callback...
        $this->router->map(
            'POST',
            $route,
            function ($call, $params) {
                # Instance a Request object...
                $req = new Request();

                #Instance a Response object...
                $res = new Response();

                # Configure view engine...
                $res->configTemplate(
                    $this->basePath,
                    $this->view_engine,
                    $this->views,
                    $this->template_caching,
                    $this->template_cache_dir,
                );

                # Loop through the URL parameters and map the values...
                foreach ($params as $key => $value) {
                    $req->setParams($key, $value);
                }

                # Get the current route...
                $get_route = str_replace($this->basePath, '', $_SERVER['REQUEST_URI']);

                /**
                 * Execute route specific middle wares
                 */

                # Loop through the route_middlewares...
                foreach ($this->route_middlewares as $key => $value) {
                    if ($key == $get_route) {
                        # The middle ware's name match this route, execute the middle ware...
                        $count_call = count($value);
                        for ($a = 0; $a < $count_call; $a++) {
                            # Invoke the middle ware, pass request and response object...
                            $value[$a]($req, $res);
                        }
                    }
                }

                $call($req, $res);
            },
            $route_reverse_name,
        );
    }

    public function put($route, $callback)
    {
        $route = $this->getRoute($route);
        // The PUT HTTP request wrapper function
        // Set this route reverse name to $route
        $route_reverse_name = $route . '-PUT';
        // Append an  array into the route_callback[] using the reverse name as a key
        // and set the value to the callback function for this route
        $this->route_callback[$route_reverse_name] = $callback;
        // Map the route; inside this 1st call back function, invoke the 2nd callback
        $this->router->map(
            'PUT',
            $route,
            function ($call, $params) {
                // Instance a Request object
                $req = new Request();
                // Instance a Response object
                $res = new Response();
                // Configure templating
                $res->configTemplate(
                    $this->basePath,
                    $this->view_engine,
                    $this->views,
                    $this->template_caching,
                    $this->template_cache_dir,
                );
                // Loop through the url parameters and map the values
                foreach ($params as $key => $value) {
                    $req->setParams($key, $value);
                }

                $get_route = str_replace($this->basePath, '', $_SERVER['REQUEST_URI']);
                ///////////////// EXECUTE ROUTE SPECIFIC MIDDLEWARES
                // loop through the route_middlewares
                foreach ($this->route_middlewares as $key => $value) {
                    if ($key == $get_route) {
                        // The middleware's name match this route, execute the middleware
                        $count_call = count($value);
                        for ($a = 0; $a < $count_call; $a++) {
                            // Invoke the middleware, pass request and response object
                            $value[$a]($req, $res);
                        }
                    }
                }

                $call($req, $res);
            },
            $route_reverse_name,
        );
    }

    public function patch($route, $callback)
    {
        $route = $this->getRoute($route);
        // The PATCH HTTP request wrapper function
        // Set this route reverse name to $route
        $route_reverse_name = $route . '-PATCH';
        // Append an  array into the route_callback[] using the reverse name as a key
        // and set the value to the callback function for this route
        $this->route_callback[$route_reverse_name] = $callback;
        // Map the route; inside this 1st call back function, invoke the 2nd callback
        $this->router->map(
            'PATCH',
            $route,
            function ($call, $params) {
                // Instance a Request object
                $req = new Request();
                // Instance a Response object
                $res = new Response();
                // Configure templating
                $res->configTemplate(
                    $this->basePath,
                    $this->view_engine,
                    $this->views,
                    $this->template_caching,
                    $this->template_cache_dir,
                );
                // Loop through the url parameters and map the values
                foreach ($params as $key => $value) {
                    $req->setParams($key, $value);
                }

                $get_route = str_replace($this->basePath, '', $_SERVER['REQUEST_URI']);
                ///////////////// EXECUTE ROUTE SPECIFIC MIDDLEWARES
                // loop through the route_middlewares
                foreach ($this->route_middlewares as $key => $value) {
                    if ($key == $get_route) {
                        // The middleware's name match this route, execute the middleware
                        $count_call = count($value);
                        for ($a = 0; $a < $count_call; $a++) {
                            // Invoke the middleware, pass request and response object
                            $value[$a]($req, $res);
                        }
                    }
                }

                $call($req, $res);
            },
            $route_reverse_name,
        );
    }

    public function delete($route, $callback)
    {
        $route = $this->getRoute($route);
        // The DELETE HTTP request wrapper function
        // Set this route reverse name to $route
        $route_reverse_name = $route . '-DELETE';
        // Append an  array into the route_callback[] using the reverse name as a key
        // and set the value to the callback function for this route
        $this->route_callback[$route_reverse_name] = $callback;
        // Map the route; inside this 1st call back function, invoke the 2nd callback
        $this->router->map(
            'DELETE',
            $route,
            function ($call, $params) {
                // Instance a Request object
                $req = new Request();
                // Instance a Response object
                $res = new Response();
                // Configure templating
                $res->configTemplate(
                    $this->basePath,
                    $this->view_engine,
                    $this->views,
                    $this->template_caching,
                    $this->template_cache_dir,
                );
                // Loop through the url parameters and map the values
                foreach ($params as $key => $value) {
                    $req->setParams($key, $value);
                }

                $get_route = str_replace($this->basePath, '', $_SERVER['REQUEST_URI']);
                ///////////////// EXECUTE ROUTE SPECIFIC MIDDLEWARES
                // loop through the route_middlewares
                foreach ($this->route_middlewares as $key => $value) {
                    if ($key == $get_route) {
                        // The middleware's name match this route, execute the middleware
                        $count_call = count($value);
                        for ($a = 0; $a < $count_call; $a++) {
                            // Invoke the middleware, pass request and response object
                            $value[$a]($req, $res);
                        }
                    }
                }

                $call($req, $res);
            },
            $route_reverse_name,
        );
    }

    public function set($name, $value)
    {
        // This function is used to set, app's configuration
        switch (strtolower($name)) {
            case 'basepath':
                $this->basePath = $value;
                $this->router->setBasePath($this->basePath);
                break;
            case 'static':
                $this->staticPath = $value;
                break;
            case 'views':
                $this->views = $value;
                break;
            case 'view engine':
                $this->view_engine = $value;
                break;
            case 'env':
                $this->env = $value;
                break;
            case 'view cache':
                $this->template_caching = $value;
                break;
            case 'view cache path':
                $this->template_cache_dir = $value;
                break;
            case 'error 301':
                $this->errorPage['301'] = $value;
                break;
            case 'error 401':
                $this->errorPage['401'] = $value;
                break;
            case 'error 404':
                $this->errorPage['404'] = $value;
                break;
            case 'error 500':
                $this->errorPage['500'] = $value;
                break;
        }
    }

    public function __destruct()
    {
        // Invoke __destruct(), let match the current request
        $match = $this->router->match();

        // If a match is found
        if ($match && is_callable($match['target'])) {
            // What I want to do here is just to let route callback be the first item in the params array
            // and request parameters should be stored as an array in two the 2nd element of the params array
            // Create a new array
            $new_params = [];

            // Make call the first item in the array
            // But before that let us get the one which was matched
            // to do this we have to loop through the route_callback, use $match['name'] to get
            // the reverse name of the route which was matched.
            // if $match['name'] is equals to our current iterator's key, Yo!
            // We have found the right callback item, set it to be the first item in the $new_params array.
            foreach ($this->route_callback as $key => $value) {
                if ($match['name'] == $key) {
                    $new_params['call'] = $this->route_callback[$key];
                }
            }
            // Get the length of $match['params'] which contains request parameters variables
            $len = count($match['params']);
            // Create an array to store request parameters variables
            $variables = [];
            // loop through $match['params'] array and the append them into $variables
            foreach ($match['params'] as $key => $value) {
                $variables[$key] = $value;
            }
            // Set 'params' as the second key in the new array
            $new_params['params'] = $variables;
            // Overwrite $match['params'], with the new one we've just prepared
            $match['params'] = $new_params;
            call_user_func_array($match['target'], $match['params']);
        } else {
            // NO route was matched
            // Set HTTP response status 404 - Page Not found
            http_response_code(404);

            // Check there is a route that is dedicated to handle 404 error
            if ($this->errorPage['404'] == null) {
                echo 'Cannot ' .
                    $_SERVER['REQUEST_METHOD'] .
                    ' ' .
                    str_replace($this->basePath, '', $_SERVER['REQUEST_URI']);
            } else {
                // Found! Then redirect to the route
                header('location:' . $this->basePath . $this->errorPage['404']);
            }
        }
    }
}
