<?php
/**
 * Request Class
 *
 * This is Express PHP Request class. The Request class is used to create the Request Object
 * which is used to handle/process user's request. Request class contains methods and properties
 * that are used to process a user's request.
 *
 * @copyright Copyright (c) Victor Aremu <victor.olorunbunmi@gmail.com>
 * @license MIT
 */

namespace Express;

class Request
{
    /**
     *@var array Array to store route parameters
     */
    public $params = [];

    /**
     * Setup Request Object
     * @author Victor Aremu
     */
    public function __construct()
    {
    }

    /**
     * Set @var header the HTTP HEADERS in the following format:
     *     array('HEADER_NAME' => 'VALUE');
     */
    public function getHttpHeader()
    {
        /**
         * headers_list() @return HTTP HEADERS in the following format:
         *     array('HEADER_NAME:VALUE');
         */
        $headers = headers_list();
        $headers_key_value = [];

        /**
         * Loop through $headers
         */
        for ($i = 0; $i < count($headers); $i++) {
            /**
             * Explode ':' in the current index's value
             */
            $chunks = explode(':', $headers[$i]);

            /**
             * Set $chunks[0] as key and $chunks[1] value of element in @var header
             */
            $headers_key_value[$chunks[0]] = $chunks[1];
        }

        return $headers_key_value;
    }

    /**
     * Magic Getter Useful for getting the value of undeclared class properties
     * on the fly. Useful my getting request entities on the fly
     * @return mixed
     * @param string $name The name of the undeclared property called
     * @author Victor Aremu
     */
    public function __get($name)
    {
        switch ($name) {
            case 'query':
                return $_GET;
                break;
            case 'body':
                return $_POST;
                break;
            case 'header':
                return $this->getHttpHeader();
                break;
            case 'cookies':
                return $_COOKIE;
                break;
            case 'route':
                return $_SERVER['REQUEST_URI'];
                break;
            case 'session':
                return $_SESSION;
                break;
            case 'method':
                return $_SERVER['REQUEST_METHOD'];
                break;
            default:
                return 'Class property ' . $name . ' not declared';
        }
    }

    /**
     * Adds a new element to @var params in the following format
     *     array('KEY', 'VALUE');
     */
    public function setParams($key, $value)
    {
        $this->params[$key] = $value;
    }
}
