<?php

namespace App\Core;

class View
{
    private static array $sections = [];
    private static ?string $currentSection = null;
    private static ?string $layout = null;
    private static array $layoutData = [];

    /**
     * Render a view file.
     *
     * @param string $view The view file to render.
     * @param array $data The data to extract into variables for the view.
     * @param string|null $layout The layout to use for rendering.
     */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        // Reset sections for each render
        self::$sections = [];
        self::$currentSection = null;
        self::$layout = $layout;
        self::$layoutData = $data;

        // Construct the full path to the view file
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$viewPath}");
        }

        // Extract the data array into individual variables
        extract($data);

        // Buffer the output
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // If a layout is specified, render with layout
        if (self::$layout) {
            return self::renderWithLayout($content, self::$layout, self::$layoutData);
        }

        return $content;
    }

    /**
     * Render a view with a layout.
     *
     * @param string $content The main content to render.
     * @param string $layout The layout file name.
     * @param array $data The data to pass to the layout.
     */
    private static function renderWithLayout(string $content, string $layout, array $data = []): string
    {
        $layoutPath = __DIR__ . '/../Views/layouts/' . str_replace('.', '/', $layout) . '.php';

        if (!file_exists($layoutPath)) {
            throw new \Exception("Layout not found: {$layoutPath}");
        }

        // Make content and sections available to layout
        $data['content'] = $content;
        $data['sections'] = self::$sections;

        // Extract the data array into individual variables
        extract($data);

        // Buffer the output
        ob_start();
        require $layoutPath;
        return ob_get_clean();
    }

    /**
     * Start a section.
     *
     * @param string $name The section name.
     */
    public static function startSection(string $name): void
    {
        if (self::$currentSection !== null) {
            throw new \Exception("Cannot start section '{$name}' while section '" . self::$currentSection . "' is still open.");
        }

        self::$currentSection = $name;
        ob_start();
    }

    /**
     * End the current section.
     */
    public static function endSection(): void
    {
        if (self::$currentSection === null) {
            throw new \Exception("No section to end.");
        }

        $content = ob_get_clean();
        self::$sections[self::$currentSection] = $content;
        self::$currentSection = null;
    }

    /**
     * Yield a section's content.
     *
     * @param string $name The section name.
     * @param string $default Default content if section doesn't exist.
     */
    public static function yieldSection(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    /**
     * Check if a section exists.
     *
     * @param string $name The section name.
     */
    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]);
    }

    /**
     * Add CSS file to the page.
     *
     * @param string $path The CSS file path.
     */
    public static function addCss(string $path): void
    {
        $currentCss = self::$sections['css'] ?? '';
        $cssTag = '<link href="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '" rel="stylesheet" type="text/css" />' . "\n";
        self::$sections['css'] = $currentCss . $cssTag;
    }

    /**
     * Add JS file to the page.
     *
     * @param string $path The JS file path.
     */
    public static function addJs(string $path): void
    {
        $currentJs = self::$sections['js'] ?? '';
        $jsTag = '<script src="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        self::$sections['js'] = $currentJs . $jsTag;
    }

    /**
     * Set the layout for the current view.
     *
     * @param string $layout The layout name.
     */
    public static function extends(string $layout): void
    {
        self::$layout = $layout;
    }
}