<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = "Privacy Policy";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .privacy-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 3rem;
        }
        .privacy-content {
            line-height: 1.8;
        }
        .privacy-content h2 {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .privacy-content p {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="privacy-header">
        <div class="container">
            <h1 class="display-4">Privacy Policy</h1>
            <p class="lead">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="privacy-content">
                    <h2>Introduction</h2>
                    <p>At Glamour Shop, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or make a purchase. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.</p>

                    <h2>Information We Collect</h2>
                    <p>We collect information that you provide directly to us when you:</p>
                    <ul>
                        <li>Create an account</li>
                        <li>Make a purchase</li>
                        <li>Sign up for our newsletter</li>
                        <li>Contact us for support</li>
                        <li>Participate in promotions or surveys</li>
                    </ul>

                    <h2>How We Use Your Information</h2>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Process your orders and payments</li>
                        <li>Communicate with you about orders, products, services, and promotional offers</li>
                        <li>Improve our website and customer service</li>
                        <li>Send you marketing communications (with your consent)</li>
                        <li>Comply with legal obligations</li>
                    </ul>

                    <h2>Information Sharing</h2>
                    <p>We do not sell or rent your personal information to third parties. We may share your information with:</p>
                    <ul>
                        <li>Service providers who assist in our operations</li>
                        <li>Payment processors to handle transactions</li>
                        <li>Law enforcement when required by law</li>
                    </ul>

                    <h2>Data Security</h2>
                    <p>We implement appropriate security measures to protect your personal information. However, no method of transmission over the Internet is 100% secure, and we cannot guarantee absolute security.</p>

                    <h2>Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal information</li>
                        <li>Correct inaccurate information</li>
                        <li>Request deletion of your information</li>
                        <li>Opt-out of marketing communications</li>
                        <li>Lodge a complaint with supervisory authorities</li>
                    </ul>

                    <h2>Cookies</h2>
                    <p>We use cookies and similar tracking technologies to track activity on our website and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>

                    <h2>Children's Privacy</h2>
                    <p>Our website is not intended for children under 13. We do not knowingly collect personal information from children under 13.</p>

                    <h2>Changes to This Policy</h2>
                    <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>

                    <h2>Contact Us</h2>
                    <p>If you have any questions about this Privacy Policy, please contact us at:</p>
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