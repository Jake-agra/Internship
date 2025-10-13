<?php
session_start();
include('./includes/header.php');
include('./Database/connection.php');
include('./includes/route.php');

// Get property statistics using new schema
$stats = [];
$stats_query = $conn->query("SELECT 
    COUNT(*) as total_properties,
    COUNT(CASE WHEN status = 'available' THEN 1 END) as available_properties,
    COUNT(CASE WHEN status = 'sold' THEN 1 END) as sold_properties,
    AVG(pr.amount) as avg_price
    FROM properties p 
    JOIN prices pr ON p.price_id = pr.id");
$stats = $stats_query->fetch_assoc();
?>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h1 class="display-4 fw-bold mb-4">Find Your Dream Home</h1>
                    <p class="lead mb-4">Discover the perfect property that matches your lifestyle and budget with our expert guidance and extensive property listings.</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="#properties" class="btn btn-light btn-lg px-4 py-3">
                            <i class="fas fa-search me-2"></i>Browse Properties
                        </a>
                        <a href="#about" class="btn btn-outline-light btn-lg px-4 py-3">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section" id="stats">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="section-title">Our Achievements</h2>
                    <p class="lead text-muted">Trusted by thousands of clients nationwide</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <i class="fas fa-home text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h3 class="fw-bold"><?php echo number_format($stats['total_properties'] ?? 0); ?></h3>
                        <p class="mb-0">Total Properties</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <i class="fas fa-key text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h3 class="fw-bold text-success"><?php echo number_format($stats['available_properties'] ?? 0); ?></h3>
                        <p class="mb-0">Available Now</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <i class="fas fa-handshake text-warning mb-3" style="font-size: 2.5rem;"></i>
                        <h3 class="fw-bold text-warning"><?php echo number_format($stats['sold_properties'] ?? 0); ?></h3>
                        <p class="mb-0">Successfully Sold</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <i class="fas fa-dollar-sign text-info mb-3" style="font-size: 2.5rem;"></i>
                        <h3 class="fw-bold text-info">$<?php echo number_format($stats['avg_price'] ?? 0); ?></h3>
                        <p class="mb-0">Average Price</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Properties Section -->
    <section class="py-5 bg-light" id="featured-properties">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="section-title">Featured Properties</h2>
                    <p class="lead text-muted">Handpicked properties that offer exceptional value</p>
                </div>
            </div>
            <div class="row g-4">
                <?php
                // Get featured properties (uncommented for display)
                $featured_query = $conn->query("SELECT * FROM property_details WHERE is_featured = 1 LIMIT 6");
                if ($featured_query->num_rows > 0) {
                    while($property = $featured_query->fetch_assoc()) {
                        $image = !empty($property['images']) ? explode(',', $property['images'])[0] : 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                        $price = number_format($property['price']);
                        $beds = $property['bedrooms'] ?? 'N/A';
                        $baths = $property['bathrooms'] ?? 'N/A';
                        $area = $property['area_sqft'] ? number_format($property['area_sqft']) . ' sqft' : 'N/A';
                        $location = trim(implode(', ', array_filter([$property['city'], $property['region'], $property['country']])), ', ');
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card property-card h-100 border-0 shadow-sm">
                                <div class="position-relative overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($property['propertiesname']); ?>">
                                    <div class="position-absolute top-0 end-0 p-3">
                                        <span class="badge bg-success fs-6">Featured</span>
                                    </div>
                                    <div class="position-absolute bottom-0 start-0 p-3">
                                        <span class="badge bg-primary fs-6"><?php echo ucfirst($property['status'] ?? 'Available'); ?></span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($property['propertiesname']); ?></h5>
                                    <p class="text-primary fw-bold mb-3 fs-4">$<?php echo $price; ?></p>
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <div class="feature-item">
                                                <i class="fas fa-bed text-primary"></i>
                                                <div class="mt-1">
                                                    <small class="text-muted d-block"><?php echo $beds; ?></small>
                                                    <small class="text-muted">Beds</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="feature-item">
                                                <i class="fas fa-bath text-primary"></i>
                                                <div class="mt-1">
                                                    <small class="text-muted d-block"><?php echo $baths; ?></small>
                                                    <small class="text-muted">Baths</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="feature-item">
                                                <i class="fas fa-ruler-combined text-primary"></i>
                                                <div class="mt-1">
                                                    <small class="text-muted d-block"><?php echo $area; ?></small>
                                                    <small class="text-muted">Area</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <?php echo htmlspecialchars($location); ?>
                                    </p>
                                    <div class="mt-auto">
                                        <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary w-100">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-home text-muted mb-3" style="font-size: 4rem;"></i>
                            <h4 class="text-muted">No Featured Properties</h4>
                            <p class="text-muted">Check back soon for new featured listings!</p>
                            <a href="properties.php" class="btn btn-primary">Browse All Properties</a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php if ($featured_query->num_rows > 0) { ?>
            <div class="text-center mt-5">
                <a href="properties.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-th-large me-2"></i>View All Properties
                </a>
            </div>
            <?php } ?>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section py-5" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="section-title">About Real Estate </h2>
                    <p class="lead mb-4">We are dedicated to helping you find the perfect property that matches your dreams and budget. With years of experience and a commitment to excellence, we make your real estate journey seamless.</p>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-primary me-2"></i>
                                    <span>Expert real estate guidance</span>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-primary me-2"></i>
                                    <span>Wide range of properties</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-primary me-2"></i>
                                    <span>Competitive pricing</span>
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-primary me-2"></i>
                                    <span>Professional service</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <a href="contact.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-phone me-2"></i>Contact Us Today
                        </a>
                        <a href="about.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative">
                        <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" 
                             alt="Real estate fessional" class="img-fluid rounded shadow-lg">
                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient-primary opacity-10 rounded"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-5 bg-light" id="services">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="section-title">Our Services</h2>
                    <p class="lead text-muted">Comprehensive real estate solutions for all your needs</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="text-center p-4">
                        <div class="bg-primary bg-gradient rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-search text-white fa-2x"></i>
                        </div>
                        <h4>Property Search</h4>
                        <p class="text-muted">Find your perfect home with our advanced search tools and expert recommendations.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center p-4">
                        <div class="bg-success bg-gradient rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-handshake text-white fa-2x"></i>
                        </div>
                        <h4>Property Sales</h4>
                        <p class="text-muted">Sell your property quickly and at the best price with our proven marketing strategies.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center p-4">
                        <div class="bg-info bg-gradient rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-chart-line text-white fa-2x"></i>
                        </div>
                        <h4>Market Analysis</h4>
                        <p class="text-muted">Get detailed market insights and property valuations from our expert analysts.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include('./includes/footer.php'); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Smooth scrolling for navigation links and the cards  on the apoge as well, take note yeah
        document.querySelectorAll('.stats-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroSection = document.querySelector('.hero-section');
            if (heroSection) {
                heroSection.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });

        // Counter animation for stats
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 50);
        }

        // Trigger counter animation when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stats-card h3');
                    counters.forEach(counter => {
                        const target = parseInt(counter.textContent.replace(/[,$]/g, ''));
                        if (target > 0) {
                            animateCounter(counter, target);
                        }
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }
    </script>
</body>