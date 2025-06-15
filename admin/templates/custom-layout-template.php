<?php
// This is a template file showing how to use the custom layout structure
// Copy this structure to your admin pages where you want more flexible positioning

// Start output buffering
ob_start();
?>

<!-- Custom Page Layout -->
<div class="custom-page-layout">
    <!-- Sidebar Content - This appears next to the main sidebar -->
    <div class="sidebar-content">
        <h5>Sidebar Content</h5>
        <p>This content appears next to the main sidebar.</p>
        <ul class="list-group">
            <li class="list-group-item">Item 1</li>
            <li class="list-group-item">Item 2</li>
            <li class="list-group-item">Item 3</li>
        </ul>
    </div>
    
    <!-- Main Area - This is the primary content area -->
    <div class="main-area">
        <h5>Main Content Area</h5>
        <p>This is the main content area that can be positioned closer to the sidebar.</p>
        
        <!-- Your page-specific content goes here -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Card Title</h5>
            </div>
            <div class="card-body">
                <p class="card-text">This is an example card that you can use for your content.</p>
            </div>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout file
require_once __DIR__ . '/../layout.php';
?> 