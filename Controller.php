<?php

declare(strict_types=1);

namespace yurni;

use yurni\Http\Request;
use yurni\Http\Response;
use yurni\Database\QueryBuilder;

/**
 * Base Controller Class
 * Provides direct access to the application instance and Query Builder within your controllers.
 */
class Controller {
    
    /**
     * @var Application Core application instance
     */
    public Application $app;

    /**
     * @var Request Current request object
     */
    public Request $request;

    /**
     * @var Response Response object
     */
    public Response $response;

    /**
     * @var Db Database connection instance
     */
    protected Db $db;

    /**
     * Controller constructor.
     * The application instance is automatically injected via the Dependency Injection Container.
     * 
     * @param Application $app
     */
    public function __construct(Application $app)
    {
       $this->app = $app;
       $this->db = Db::getInstance();
       $this->request = $this->app->request;
       $this->response = $this->app->response;
    }

    public function db(): Db
    {
        return $this->db;
    }

    public function query(): QueryBuilder
    {
        return $this->db->query();
    }

    public function table(string $table): QueryBuilder
    {
        return $this->db->table($table);
    }

    public function transaction(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }

    /**
     * Process and render an HTML template.
     * Merges passed data with global variables and route information before passing it to the template engine.
     * 
     * @param string $view Template name (e.g., 'home' or 'users.index')
     * @param array $args Data array to be displayed in the template
     * @return string Final HTML output
     */
    public function render(string $view, array $args = []): string
    {
        // Add current route information to the view attributes
        $this->app->setViewAttr([
            "route" => $this->request->route()
        ]);
        
        // Merge user-passed data with global attributes
        $this->app->setViewAttr($args);
  
        // Render the template and return output
        return View::render($view, $this->app->getViewAttr());
    }
}
