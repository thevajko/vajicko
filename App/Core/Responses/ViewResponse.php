<?php

namespace App\Core\Responses;

use App\Config\Configuration;
use App\Core\App;

/**
 * Class ViewResponse
 *
 * Represents an HTTP response that renders a view, typically in the context of a web application. This class is
 * responsible for generating the appropriate HTML output by combining a view file with optional layout and data.
 *
 * @package App\Core\Responses
 */
class ViewResponse extends Response
{
    /**
     * Instance of the application, used for accessing application-level services.
     *
     * @var App
     */
    private App $app;

    /**
     * The name of the view file to render.
     *
     * @var string
     */
    private string $viewName;

    /**
     * The data to be passed to the view for rendering.
     *
     * @var array
     */
    private array $data;

    /**
     * ViewResponse constructor.
     *
     * Initializes a new instance of ViewResponse with the specified application instance, view name, and data to be
     * passed to the view.
     *
     * @param App $app The application instance.
     * @param string $viewName The name of the view to render.
     * @param array $data The data to be passed to the view.
     */
    public function __construct(App $app, string $viewName, array $data)
    {
        $this->app = $app;
        $this->viewName = $viewName;
        $this->data = $data;
    }

    /**
     * Generates and outputs the rendered view.
     *
     * This method captures the output of the view rendering process and handles the inclusion of layout files if
     * specified. It creates view helpers based on the application instance, which can be used within the views.
     *
     * @return void
     */
    protected function generate(): void
    {
        // Retrieve the layout configuration.
        $layout = Configuration::ROOT_LAYOUT;

        // Create view helpers to be passed to the view.
        $viewHelpers = [
            'auth' => $this->app->getAuth(),
            'link' => $this->app->getLinkGenerator(),
        ];

        // Start output buffering to capture view rendering.
        ob_start();

        // Render the main view file.
        $this->renderView($layout, $viewHelpers + $this->data, $this->viewName . ".view.php");

        // If a layout is specified, render it with the captured view content.
        if ($layout != null) {
            $contentHTML = ob_get_clean();
            $layoutData = $viewHelpers + ['contentHTML' => $contentHTML];
            $this->renderView($layout, $layoutData, $this->getLayoutFullName($layout));
        } else {
            // If no layout, output the buffered content directly.
            ob_end_flush();
        }
    }

    /**
     * Renders the specified view file with the given data.
     *
     * This method extracts the provided data as variables for use within the view and includes the view file, making
     * it part of the current output.
     *
     * @param string &$layout The layout being used (if any).
     * @param array $data The data to be made available in the view.
     * @param string $viewPath The path to the view file to render.
     *
     * @return void
     */
    private function renderView(string &$layout, array $data, string $viewPath): void
    {
        // Extract variables from the provided data array.
        extract($data, EXTR_SKIP);

        // Include the specified view file, which will be rendered.
        require "App" . DIRECTORY_SEPARATOR . "Views" . DIRECTORY_SEPARATOR . $viewPath;
    }

    /**
     * Determines the full path of the specified layout file.
     *
     * This method checks if the layout name ends with the expected extension, and appends it if necessary to ensure
     * the correct layout file is used.
     *
     * @param string $layoutName The base name of the layout.
     * @return string The full path of the layout file.
     */
    private function getLayoutFullName(string $layoutName): string
    {
        return str_ends_with($layoutName, '.layout.view.php') ? $layoutName : $layoutName . '.layout.view.php';
    }
}