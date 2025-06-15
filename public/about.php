<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = "About Us";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .about-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 3rem;
        }
        .team-member {
            margin-bottom: 2rem;
        }
        .team-member img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="about-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4">About <?php echo SITE_NAME; ?></h1>
                    <p class="lead">Discover the story behind our elegant jewelry collection</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="index.php" class="btn btn-primary">Go to Shop</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4">Our Story</h2>
                <p class="mb-4">
                    <?php echo SITE_NAME; ?> was founded with a simple yet powerful vision: to bring elegant, high-quality jewelry to discerning customers who appreciate both beauty and craftsmanship. Our journey began when a group of passionate jewelry enthusiasts came together with a shared dream of creating a brand that would redefine luxury accessories in Kenya.
                </p>
                <p class="mb-4">
                    Today, <?php echo SITE_NAME; ?> has grown into a trusted name in the jewelry industry, offering a carefully curated collection of pieces that blend contemporary design with timeless elegance. Each item in our collection is selected with our customers in mind, ensuring that every piece tells a unique story and adds a touch of sophistication to any occasion.
                </p>
                
                <h2 class="mb-4 mt-5">Our Vision</h2>
                <p class="mb-4">
                    At <?php echo SITE_NAME; ?>, we envision a world where everyone can access beautifully crafted jewelry that enhances their personal style and confidence. We believe that jewelry is more than just an accessory—it's a form of self-expression, a way to celebrate life's special moments, and a means to create lasting memories.
                </p>
                <p class="mb-4">
                    Our vision is to become the leading jewelry brand in Kenya, known not only for our exquisite collections but also for our commitment to customer satisfaction, ethical sourcing, and sustainable practices. We strive to create pieces that will be cherished for generations to come.
                </p>
                
                <h2 class="mb-4 mt-5">Why Choose <?php echo SITE_NAME; ?>?</h2>
                <p class="mb-4">
                    We understand that choosing jewelry is a personal decision, and we're committed to making that experience as enjoyable as possible. Here's why our customers trust us:
                </p>
                <ul class="mb-4">
                    <li class="mb-2"><strong>Quality Craftsmanship:</strong> Every piece in our collection is crafted with attention to detail and quality materials.</li>
                    <li class="mb-2"><strong>Curated Collections:</strong> We carefully select each item to ensure it meets our high standards for design and quality.</li>
                    <li class="mb-2"><strong>Customer Satisfaction:</strong> Your happiness is our priority. We're dedicated to providing exceptional service and support.</li>
                    <li class="mb-2"><strong>Ethical Practices:</strong> We're committed to ethical sourcing and sustainable business practices.</li>
                    <li class="mb-2"><strong>Expert Guidance:</strong> Our team of jewelry experts is always ready to help you find the perfect piece.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Team</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="team-member text-center">
                        <img src="/Glamour-shopv1.3/assets/images/team/antony.jpg" alt="Antony Njangiru" class="img-fluid">
                        <h4>Antony Njangiru</h4>
                        <p class="text-muted">Executive Director</p>
                        <p>Leading our company with vision and expertise, Antony brings years of industry experience to <?php echo SITE_NAME; ?>.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-member text-center">
                        <img src="/Glamour-shopv1.3/assets/images/team/dennis.jpg" alt="Dennis Maina" class="img-fluid">
                        <h4>Dennis Maina</h4>
                        <p class="text-muted">Sales Manager</p>
                        <p>Dennis oversees our sales operations, ensuring that our customers receive the best service and products.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-member text-center">
                        <img src="/Glamour-shopv1.3/assets/images/team/brian.jpg" alt="Brian Ndung'u" class="img-fluid">
                        <h4>Brian Ndung'u</h4>
                        <p class="text-muted">Marketing Adviser</p>
                        <p>Brian drives our marketing strategies, helping us connect with our customers and share our story.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 