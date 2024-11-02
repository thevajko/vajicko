<?php

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\Response;
use App\Core\Responses\ViewResponse;
use Exception;

/**
 * Class ControllerBase
 *
 * This abstract class serves as the foundation for all controller classes within the application.
 * It provides a set of common methods and properties that all controllers can leverage, ensuring
 * a consistent interface and reducing code duplication.
 *
 * Provides access to the application instance, allowing controllers to interact with core functionalities such as
 * request handling, routing, and session management.
 *
 * Contains utility methods for generating responses in various
 * formats (HTML, JSON, redirects), streamlining the response construction process.
 *
 * Facilitates the retrieval of controller and action names, aiding in dynamic view rendering and URL generation.
 *
 * Implements a default authorization method for controller actions, which can be overridden in derived classes to
 * enforce specific access control rules.
 *
 * Each controller extending this class is required to implement the `index` method, establishing
 * a standard entry point for handling requests. This ensures that every controller has a
 * well-defined default action.
 *
 * By using this base class, derived controllers benefit from a robust and organized structure,
 * simplifying the development of new features and the maintenance of existing code.
 *
 * @package App\Core
 */
abstract class ControllerBase
{
    /**
     * Reference to the application instance.
     *
     * This property allows access to the application's core components and services, enabling controllers to perform
     * actions such as managing sessions, routing, and handling requests.
     *
     * @var App
     */
    protected App $app;

    /**
     * Returns the name of the controller, excluding the "Controller" suffix.
     *
     * This method is useful for dynamically determining the controller's name, which can be helpful in view rendering
     * and URL generation.
     *
     * @return string The name of the controller.
     */
    public function getName(): string
    {
        return str_replace("Controller", "", $this->getClassName());
    }

    /**
     * Retrieves the full class name of the controller.
     *
     * This method provides the complete namespace-qualified name of the controller, which may be useful for debugging
     * or logging purposes.
     *
     * @return string The fully qualified class name.
     */
    public function getClassName(): string
    {
        $arr = explode("\\", get_class($this));
        return end($arr);
    }

    /**
     * Injects the application instance into the controller.
     *
     * This method sets the `app` property to the provided instance, allowing the controller to access application-level
     * functionalities and services.
     *
     * @param App $app The application instance to inject.
     */
    public function setApp(App $app): void
    {
        $this->app = $app;
    }

    /**
     * Authorizes an action for the controller.
     *
     * This method determines whether the specified action is allowed to be executed. The default implementation
     * returns true, but this method can be overridden in derived classes to enforce custom authorization logic.
     *
     * @param string $action The name of the action to authorize.
     * @return bool True if the action is authorized; false otherwise.
     */
    public function authorize(string $action): bool
    {
        return true;
    }

    /**
     * Handles the default index action for the controller.
     *
     * Each controller must implement this method to define its default action. It should return a `Response` object
     * representing the result of the action.
     *
     * @return Response The response generated by the index action.
     */
    abstract public function index(): Response;

    /**
     * Creates a ViewResponse for rendering a view.
     *
     * This helper method constructs a `ViewResponse` object, allowing the controller to return a view with associated
     * data. If a view name is not provided, the method infers it from the current controller and action.
     *
     * @param array $data Associative array containing data to be passed to the view.
     * @param string|null $viewName The name of the view to render, or null to infer from context.
     * @return ViewResponse The constructed ViewResponse object.
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
     * Creates a JsonResponse for returning JSON data.
     *
     * This helper method constructs a `JsonResponse` object, allowing the controller to send JSON formatted data back
     * to the client.
     *
     * @param mixed $data The data to be returned as JSON.
     * @return JsonResponse The constructed JsonResponse object.
     */
    protected function json($data): JsonResponse
    {
        return new JsonResponse($data);
    }

    /**
     * Generates a redirect response to a specified URL.
     *
     * This helper method constructs a `RedirectResponse` object, which instructs the client to navigate to
     * a different URL.
     *
     * @param string $redirectUrl The URL to redirect to.
     * @return RedirectResponse The constructed RedirectResponse object.
     */
    protected function redirect(string $redirectUrl): RedirectResponse
    {
        return new RedirectResponse($redirectUrl);
    }

    /**
     * Retrieves the current HTTP request instance.
     *
     * This helper method allows controllers to access request data, such as query parameters and form submissions.
     *
     * @return Request The current Request object.
     */
    protected function getRequest(): Request
    {
        return $this->app->getRequest();
    }

    /**
     * Generates a URL based on routing parameters.
     *
     * This helper method utilizes the LinkGenerator to create a URL based on the specified destination and parameters.
     * It offers options for generating absolute URLs and appending existing request parameters.
     *
     * @param string|array $destination The target controller and action or parameters.
     * @param array $parameters Additional parameters to include in the URL query string.
     * @param bool $absolute Whether to return an absolute URL (including domain).
     * @param bool $appendParameters Whether to append existing parameters from the current request.
     * @return string The generated URL.
     * @throws Exception If the parameters are invalid for the specified destination.
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
