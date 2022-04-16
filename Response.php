<?php

/**
 * Response Class
 *
 * This is Express PHP Response class. The Response class is used to create the Response Object
 * which is used to handle/process user's response. Request class contains methods and properties
 * that are used to process a user's response.
 *
 * @copyright Copyright (c) Victor Aremu <victor.olorunbunmi@gmail.com>
 * @license MIT
 */

namespace Express;

class Response
{
    /**
     *@var string Path to Express view directory
     */
    private $views;

    /**
     *@var string Path to Express view directory
     */
    private $basePath;

    /**
     *@var boolean Enables or disables Express template caching
     */
    private $template_caching;

    /**
     *@var string Path to Express view cache directory
     */
    private $template_cache_dir;

    /**
     *@var string Name of view engine used by Express
     */
    private $view_engine;

    /**
     * Set Response view properties
     * @param string $engine
     * @param string $views
     * @param string $caching
     * @param string $cache_dir
     */
    public function configTemplate($basePath, $engine, $views, $caching, $cache_dir)
    {
        $this->basePath = $basePath;
        $this->view_engine = $engine;
        $this->views = $views;
        $this->template_cache_dir = $cache_dir;
        $this->template_caching = $caching;
    }

    /**
     * Sends a response(view) without using view engine
     * @param string $content
     * @param array $http_headers
     */
    public function send($content, $http_headers)
    {
        foreach ($http_headers as $key => $value) {
            header($key . ':' . $value);
        }
        echo $content;

        ob_end_flush();
    }

    /**
     * Renders a response(view) using view engine
     * @param string $template
     * @param array $data
     */
    public function render($template, $data)
    {
        /**
         * Assign the shared global variables
         */

        foreach (Express::$sharedData as $key => $value) {
            $data[$key] = $value;
        }

        /**
         * Switch between template engines
         */
        switch ($this->view_engine) {
            case 'default':
                /**
                 * Case default, no view engine is used, we serve PHP view files
                 */
                include '../' . $this->views . '/' . $template . '.php';
                break;
            case 'smarty':
                /**
                 * Case smarty, set up the view engine and configure it
                 */
                require 'express_modules/Smarty/Smarty.class.php';
                $smarty = new Smarty();

                # Configure smarty
                $smarty->template_dir = __DIR__ . '/' . $this->views;
                /**
                 * Enable caching if set
                 */
                if ($this->template_caching == true) {
                    echo $this->template_cache_dir . '_______-';
                    $smarty->cache_dir = __DIR__ . '/' . $this->template_cache_dir;
                    $smarty->caching = true;
                }

                /**
                 * Assign values
                 */
                foreach ($data as $key => $value) {
                    $smarty->assign($key, $value);
                }

                /**
                 * Load the view
                 */
                $smarty->display($template . '.tpl');
                break;
        }
    }

    /**
     * Sets HTTP Header
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        # Add HTTP header
        header($key . ':' . $value);
    }

    /**
     * Sets COOKIE
     * @param string $name
     * @param string $value
     * @param string Time $expire
     */
    public function setCookie($name, $value, $expire)
    {
        # Add a new cookie
        setcookie($name, $value, $expire);
    }

    /**
     * Sets a SESSION data
     * @param string $key
     * @param string $value
     */
    public function setSession($key, $value)
    {
        # Add a new element to $_SESSION
        $session_status = session_status();
        if ($session_status == 2) {
            # Implies that session as been started, append element
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Redirects user to another route
     * @param string $route
     */
    public function redirect($route)
    {
        header('location:' . $this->basePath . $route);
    }

    /**
     * Sets HTTP status
     * @param string $route
     */
    public function status($code)
    {
        http_response_code($code);
    }
}
