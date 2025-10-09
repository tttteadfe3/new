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
        // Preserve asset sections (css, js) before clearing for the new render.
        $css = self::$sections['css'] ?? null;
        $js = self::$sections['js'] ?? null;

        // Reset sections for the new view rendering.
        self::$sections = [];
        self::$currentSection = null;

        // Restore asset sections so they are available to the layout.
        if ($css) {
            self::$sections['css'] = $css;
        }
        if ($js) {
            self::$sections['js'] = $js;
        }

        self::$layout = $layout;
        self::$layoutData = $data;

        $viewPath = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$viewPath}");
        }

        extract($data);

        ob_start();
        require $viewPath;
        $viewContent = ob_get_clean();

        // If a layout is being used and the 'content' section was not explicitly defined
        // by the view (using startSection), then we assume the entire view's
        // output is the content.
        if (self::$layout && !isset(self::$sections['content'])) {
            self::$sections['content'] = $viewContent;
        }

        // If a layout is specified, render it.
        if (self::$layout) {
            return self::renderWithLayout(self::$layout, self::$layoutData);
        }

        // Otherwise, just return the content. If sections were used without a layout,
        // this will correctly return the main content.
        return self::$sections['content'] ?? $viewContent;
    }

    /**
     * Render a view with a layout.
     *
     * @param string $layout The layout file name.
     * @param array $data The data to pass to the layout.
     */
    private static function renderWithLayout(string $layout, array $data = []): string
    {
        // Path should be relative to the /app/Views/ directory, same as regular views.
        $layoutPath = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $layout) . '.php';

        if (!file_exists($layoutPath)) {
            throw new \Exception("Layout not found: {$layoutPath}");
        }

        // The content is now consistently available via `yieldSection('content')`.
        // We just need to make sure any other data is available to the layout.
        $data['sections'] = self::$sections;
        extract($data);

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