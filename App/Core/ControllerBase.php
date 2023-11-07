<?php

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\Response;
use App\Core\Responses\ViewResponse;

/**
 * Class AControllerBase
 * Basic controller class, predecessor of all controllers
 * @package App\Core
 */
abstract class ControllerBase
{
    /**
     * Reference to APP object instance
     * @var App
     */
    protected App $app;

    /**
     * Returns controller name (without Controller prefix)
     * @return string
     */
    public function getName(): string
    {
        return str_replace("Controller", "", $this->getClassName());
    }

    /**
     * Return full class name
     * @return string
     */
    public function getClassName(): string
    {
        $arr = explode("\\", get_class($this));
        return end($arr);
    }

    /**
     * Method for injecting App object
     * @param App $app
     */
    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * Authorize controller action
     * @param string $action action name
     * @return bool
     */
    public function authorize(string $action): bool
    {
        return true;
    }

    /**
     * Every controller should implement the method for index action at least
     * @return Response
     */
    abstract public function index(): Response;

    /**
     * Helper method for returning response type ViewResponse
     * @param array $data Associative array with view data
     * @param string|null $viewName
     * @return ViewResponse
     */
    protected function html(array $data = [], string $viewName = null): ViewResponse
    {
        if ($viewName == null) {
            $viewName = $this->app->getRouter()->getControllerName() . DIRECTORY_SEPARATOR .
                $this->app->getRouter()->getAction();
        } else {
            $viewName = is_string($viewName) ?
                ($this->app->getRouter()->getControllerName() . DIRECTORY_SEPARATOR . $viewName) :
                ($viewName['0'] . DIRECTORY_SEPARATOR . $viewName['1']);
        }
        return new ViewResponse($this->app, $viewName, $data);
    }

    /**
     * Helper method for returning response type JsonResponse
     * @param $data
     * @return JsonResponse
     */
    protected function json($data): JsonResponse
    {
        return new JsonResponse($data);
    }

    /**
     * Helper method for redirect request to another URL
     * @param string $redirectUrl
     * @return RedirectResponse
     */
    protected function redirect(string $redirectUrl): RedirectResponse
    {
        return new RedirectResponse($redirectUrl);
    }

    /**
     * Helper method for request
     * @return Request
     */
    protected function getRequest(): Request
    {
        return $this->app->getRequest();
    }

    /**
     * @throws \Exception
     * @see LinkGenerator::url()
     */
    protected function url(
        string|array $destination,
        array $parameters = [],
        bool $absolute = false,
        bool $appendParameters = false
    ): string {
        return $this->app->getLinkGenerator()->url($destination, $parameters, $absolute, $appendParameters);
    }
}
