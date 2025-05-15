<?php

namespace App\Routes;

class Router {
    private $routes = [];

    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['REQUEST_URI'];
        $path = parse_url($path, PHP_URL_PATH);
        
        // Remove base path from URL
        $basePath = '/b/public';
        $path = str_replace($basePath, '', $path);
        $path = $path ?: '/';

        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            if (is_array($callback)) {
                $controller = new $callback[0]();
                $method = $callback[1];
                echo $controller->$method();
            } else {
                echo $callback();
            }
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }
}