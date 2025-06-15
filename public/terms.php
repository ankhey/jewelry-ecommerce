<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = "Terms & Conditions";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .terms-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 3rem;
        }
        .terms-content {
            line-height: 1.8;
        }
        .terms-content h2 {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .terms-content p {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="terms-header">
        <div class="container">
            <h1 class="display-4">Terms & Conditions</h1>
            <p class="lead">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="terms-content">
                    <h2>1. Agreement to Terms</h2>
                    <p>By accessing and using Glamour Shop's website, you agree to be bound by these Terms and Conditions. If you disagree with any part of these terms, you may not access our website or use our services.</p>

                    <h2>2. Use License</h2>
                    <p>Permission is granted to temporarily access the materials (information or software) on Glamour Shop's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                    <ul>
                        <li>Modify or copy the materials</li>
                        <li>Use the materials for any commercial purpose</li>
                        <li>Attempt to decompile or reverse engineer any software contained on the website</li>
                        <li>Remove any copyright or other proprietary notations from the materials</li>
                        <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
                    </ul>

                    <h2>3. Product Information</h2>
                    <p>We strive to display our products as accurately as possible. However, we cannot guarantee that your computer monitor's display of any color will be accurate. We reserve the right to discontinue any product at any time.</p>

                    <h2>4. Pricing and Payment</h2>
                    <p>All prices are subject to change without notice. We reserve the right to modify or discontinue any product without notice. We shall not be liable to you or any third party for any modification, price change, or discontinuance of the product.</p>

                    <h2>5. Order Acceptance</h2>
                    <p>Your receipt of an electronic or other form of order confirmation does not signify our acceptance of your order, nor does it constitute confirmation of our offer to sell. We reserve the right to limit the quantity of items purchased and to refuse service to anyone.</p>

                    <h2>6. Shipping and Delivery</h2>
                    <p>We will make every effort to deliver products in a timely manner. However, we are not responsible for delivery delays beyond our control. All delivery times are estimates only.</p>

                    <h2>7. Returns and Refunds</h2>
                    <p>We accept returns within 30 days of purchase. Items must be unused and in their original packaging. Refunds will be processed within 14 business days of receiving the returned item.</p>

                    <h2>8. Disclaimer</h2>
                    <p>The materials on Glamour Shop's website are provided on an 'as is' basis. Glamour Shop makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

                    <h2>9. Limitations</h2>
                    <p>In no event shall Glamour Shop or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Glamour Shop's website.</p>

                    <h2>10. Revisions and Errata</h2>
                    <p>The materials appearing on Glamour Shop's website could include technical, typographical, or photographic errors. Glamour Shop does not warrant that any of the materials on its website are accurate, complete, or current.</p>

                    <h2>11. Links</h2>
                    <p>Glamour Shop has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Glamour Shop of the site.</p>

                    <h2>12. Site Terms of Use Modifications</h2>
                    <p>Glamour Shop may revise these terms of service for its website at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>

                    <h2>13. Governing Law</h2>
                    <p>These terms and conditions are governed by and construed in accordance with the laws of Kenya and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>

                    <h2>14. Contact Information</h2>
                    <p>If you have any questions about these Terms & Conditions, please contact us at:</p>
                    <ul>
                        <li>Email: info@glamourshop.com</li>
                        <li>Phone: +254 114 595 589</li>
                        <li>Address: 123 Jewelry Street, Nairobi, Kenya</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 