<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <!-- Company Info - Hidden on small screens -->
            <div class="col-md-4 mb-4 mb-md-0 d-none d-md-block">
                <h5 class="mb-3"><?php echo SITE_NAME; ?></h5>
                <p class="text-muted mb-0">
                    Your one-stop shop for beautiful and elegant jewelry.
                    Find the perfect piece to complement your style.
                </p>
            </div>
            <!-- Quick Links - Collapsible on small screens -->
            <div class="col-6 col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#footerLinks" role="button" aria-expanded="false" aria-controls="footerLinks">
                    Quick Links
                    <i class="bi bi-chevron-down d-md-none"></i>
                </h5>
                <div class="collapse d-md-block" id="footerLinks">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="/Glamour-shopv1.3/public/about.php" class="text-muted text-decoration-none">About Us</a>
                        </li>
                        <li class="mb-2">
                            <a href="/Glamour-shopv1.3/public/contact.php" class="text-muted text-decoration-none">Contact Us</a>
                        </li>
                        <li class="mb-2">
                            <a href="/Glamour-shopv1.3/public/privacy-policy.php" class="text-muted text-decoration-none">Privacy Policy</a>
                        </li>
                        <li class="mb-2">
                            <a href="/Glamour-shopv1.3/public/terms.php" class="text-muted text-decoration-none">Terms & Conditions</a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Contact Info - Collapsible on small screens -->
            <div class="col-6 col-md-4">
                <h5 class="mb-3 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#footerContact" role="button" aria-expanded="false" aria-controls="footerContact">
                    Contact
                    <i class="bi bi-chevron-down d-md-none"></i>
                </h5>
                <div class="collapse d-md-block" id="footerContact">
                    <div class="d-flex gap-3 mb-3">
                        <a href="https://facebook.com/glamourshop" target="_blank" class="text-muted text-decoration-none fs-5">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://twitter.com/glamourshop" target="_blank" class="text-muted text-decoration-none fs-5">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="https://instagram.com/glamourshop" target="_blank" class="text-muted text-decoration-none fs-5">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://pinterest.com/glamourshop" target="_blank" class="text-muted text-decoration-none fs-5">
                            <i class="bi bi-pinterest"></i>
                        </a>
                    </div>
                    <p class="text-muted mb-0">
                        <i class="bi bi-envelope me-2"></i> info@glamourshop.com<br>
                        <i class="bi bi-telephone me-2"></i> +254 114 595 589
                    </p>
                </div>
            </div>
        </div>
        <hr class="my-4 bg-secondary">
        <div class="text-center text-muted">
            <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</small>
        </div>
    </div>
</footer>

<style>
/* Footer responsive styles */
@media (max-width: 767.98px) {
    footer h5 {
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }
    footer .bi-chevron-down {
        transition: transform 0.2s;
    }
    footer h5[aria-expanded="true"] .bi-chevron-down {
        transform: rotate(180deg);
    }
    footer .collapse {
        margin-top: 0.5rem;
    }
    footer .list-unstyled li {
        margin-bottom: 0.5rem;
    }
    footer .d-flex.gap-3 {
        gap: 1rem !important;
    }
}
</style> 