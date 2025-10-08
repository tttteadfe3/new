<?php
use App\Core\View;

// Add page-specific CSS
View::startSection('css');
?>
<link href="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<style>
    .demo-section {
        margin-bottom: 2rem;
        padding: 1.5rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
    }
</style>
<?php
View::endSection();

// Add page-specific JavaScript
View::startSection('js');
?>
<script src="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Layout example page loaded with dynamic JS!');
        
        // Example of page-specific functionality
        const demoButton = document.getElementById('demo-button');
        if (demoButton) {
            demoButton.addEventListener('click', function() {
                alert('Dynamic JavaScript is working!');
            });
        }
    });
</script>
<?php
View::endSection();
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Layout System Demo</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Pages</a></li>
                    <li class="breadcrumb-item active">Layout Demo</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">New View System Features</h5>
            </div>
            <div class="card-body">
                <div class="demo-section">
                    <h6>1. Layout Inheritance</h6>
                    <p>This page uses the main 'app' layout which includes header, sidebar, and footer components.</p>
                    <p>The layout is specified in the controller: <code>$this->render('pages/demo/layout-example', $data, 'app')</code></p>
                </div>

                <div class="demo-section">
                    <h6>2. Dynamic CSS Loading</h6>
                    <p>Page-specific CSS is added using <code>View::startSection('css')</code> and <code>View::endSection()</code>.</p>
                    <p>This allows each page to include its own stylesheets without modifying the layout.</p>
                </div>

                <div class="demo-section">
                    <h6>3. Dynamic JavaScript Loading</h6>
                    <p>Page-specific JavaScript is added using the same section system.</p>
                    <button type="button" class="btn btn-primary" id="demo-button">Test Dynamic JS</button>
                </div>

                <div class="demo-section">
                    <h6>4. Helper Methods</h6>
                    <p>The View class also provides helper methods:</p>
                    <ul>
                        <li><code>View::addCss($path)</code> - Add CSS files programmatically</li>
                        <li><code>View::addJs($path)</code> - Add JS files programmatically</li>
                        <li><code>View::hasSection($name)</code> - Check if a section exists</li>
                        <li><code>View::yieldSection($name, $default)</code> - Output section content</li>
                    </ul>
                </div>

                <div class="demo-section">
                    <h6>5. Organized Directory Structure</h6>
                    <p>Views are now organized in a logical structure:</p>
                    <ul>
                        <li><code>app/Views/layouts/</code> - Layout templates</li>
                        <li><code>app/Views/pages/</code> - Page-specific views organized by feature</li>
                        <li><code>app/Views/auth/</code> - Authentication related views</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>