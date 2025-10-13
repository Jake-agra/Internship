<?php
session_start();
include('./includes/header.php');
include('./Database/connection.php');

// Get all properties using the new schema
$properties = [];
$stmt = $conn->prepare("SELECT pd.id, pd.propertiesname, pd.price, pd.currency, pd.property_type, pd.bedrooms, pd.bathrooms, pd.area_sqft, pd.city, pd.region, pd.country, p.images 
FROM property_details pd
JOIN properties p ON pd.id = p.id
 ORDER BY pd.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $properties[] = $row;
}
$stmt->close();

// Get property statistics
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

<style>
    body {
        background: white !important;
        padding-top: 80px;
    }
    
    .properties-hero {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: #007bff;
        padding: 80px 0;
        text-align: center;
        margin-bottom: 60px;
    }
    
    .properties-hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 20px;
    }
    
    .property-price {
        color: #007bff;
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .properties-hero p {
        font-size: 1.3rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .stats-section {
        background: white;
        padding: 40px 0;
        margin-bottom: 60px;
        position: relative;
        z-index: 1;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border-radius: 15px;
        padding: 25px 20px;
        text-align: center;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-10px);
    }
    
    .stat-card h3 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 10px;
    }
    
    .stat-card p {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.9;
    }
    
    .properties-section {
        background: white;
        padding: 40px 0 80px;
        position: relative;
        z-index: 2;
    }
    
    .section-title {
        color: #2c3e50;
        font-weight: 800;
        font-size: 3rem;
        margin-bottom: 20px;
        text-align: center;
        position: relative;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border-radius: 2px;
    }
    
    .section-subtitle {
        color: #6c757d;
        font-size: 1.2rem;
        text-align: center;
        margin-bottom: 60px;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .property-card {
        border: 1px solid #eee;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 30px;
        height: 100%;
        background: white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .property-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: #007bff;
    }
    
    .property-img {
        height: 220px;
        object-fit: cover;
        width: 100%;
        transition: transform 0.3s ease;
    }
    
    .property-card:hover .property-img {
        transform: scale(1.03);
    }
    
    .property-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 50px;
        z-index: 1;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .property-card .card-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        height: calc(100% - 220px);
    }
    
    .property-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 20px;
        line-height: 1.3;
    }
    
    .property-features {
        display: flex;
        justify-content: space-between;
        margin: 15px 0;
        color: #6c757d;
        padding: 10px 0;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #495057;
        font-weight: 600;
        font-size: 1rem;
        background: #f8f9fa;
        padding: 12px 18px;
        border-radius: 25px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .feature-item:hover {
        background: #e3f2fd;
        border-color: #007bff;
    }
    
    .feature-item i {
        color: #007bff;
        font-size: 1.2rem;
    }
    
    .property-location {
        color: #6c757d;
        font-size: 1rem;
        margin-bottom: 20px;
        display: flex;
        flex-grow: 1;
        align-items: flex-start;
    }
    
    .property-location i {
        color: #dc3545;
        font-size: 1.2rem;
    }
    
    .btn-view-property {
        background: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 600;
        transition: all 0.3s ease;
        width: 100%;
        text-align: center;
        margin-top: auto;
    }
    
    .btn-view-property:hover {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
    }
    
    /* Fix for property grid */
    .properties-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    
    @media (max-width: 768px) {
        .properties-grid {
            grid-template-columns: 1fr;
        }
        
        .stat-card {
            padding: 20px 15px;
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
        }
    }
    
    .no-properties {
        text-align: center;
        padding: 120px 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 25px;
        margin: 60px 0;
    }
    
    .no-properties i {
        color: #6c757d;
        margin-bottom: 30px;
    }
    
    .no-properties h4 {
        color: #495057;
        font-weight: 700;
        margin-bottom: 20px;
        font-size: 1.8rem;
    }
    
    .no-properties p {
        color: #6c757d;
        font-size: 1.2rem;
    }
    
    .properties-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 50px;
        margin-top: 60px;
    }
    
    @media (max-width: 768px) {
        .properties-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        
        .properties-hero h1 {
            font-size: 2.5rem;
        }
        
        .section-title {
            font-size: 2.2rem;
        }
        
        .property-features {
            gap: 10px;
        }
        
        .feature-item {
            font-size: 0.9rem;
            padding: 10px 15px;
        }
    }
</style>

<!-- Properties Hero Section -->
<section class="properties-hero">
    <div class="container">
        <h1>Our Properties</h1>
        <p>Discover exceptional properties that match your lifestyle and investment goals. Browse our carefully curated collection of premium real estate.</p>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <h3><?php echo number_format($stats['total_properties'] ?? 0); ?></h3>
                    <p>Total Properties</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <h3><?php echo number_format($stats['available_properties'] ?? 0); ?></h3>
                    <p>Available Now</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <h3><?php echo number_format($stats['sold_properties'] ?? 0); ?></h3>
                    <p>Successfully Sold</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <h3>$<?php echo number_format($stats['avg_price'] ?? 0); ?></h3>
                    <p>Average Price</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Properties Listing Section -->
<section class="properties-section">
    <div class="container">
        <h2 class="section-title">Available Properties</h2>
        <p class="section-subtitle">
            Each property in our portfolio is carefully selected to offer exceptional value, quality, and potential for our clients.
        </p>
        
        <?php if (!empty($properties)): ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image" style="background-image: url('<?php echo !empty($property['images']) ? htmlspecialchars($property['images']) : 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'; ?>');">
                            <div class="property-type-badge">
                                <?php echo htmlspecialchars($property['property_type']); ?>
                            </div>
                        </div>
                        
                        <div class="property-card-body">
                            <h5 class="property-title"><?php echo htmlspecialchars($property['propertiesname']); ?></h5>
                            
                            <div class="price-badge">
                                <?php echo htmlspecialchars($property['currency']); ?> <?php echo number_format($property['price']); ?>
                            </div>
                            
                            <div class="property-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($property['city'] . ', ' . $property['region'] . ', ' . $property['country']); ?></span>
                            </div>
                            
                            <div class="property-features">
                                <?php if ($property['bedrooms'] > 0): ?>
                                    <div class="feature-item">
                                        <i class="fas fa-bed"></i>
                                        <span><?php echo $property['bedrooms']; ?> Bedroom<?php echo $property['bedrooms'] > 1 ? 's' : ''; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($property['bathrooms'] > 0): ?>
                                    <div class="feature-item">
                                        <i class="fas fa-bath"></i>
                                        <span><?php echo $property['bathrooms']; ?> Bathroom<?php echo $property['bathrooms'] > 1 ? 's' : ''; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($property['area_sqft'] > 0): ?>
                                    <div class="feature-item">
                                        <i class="fas fa-ruler-combined"></i>
                                        <span><?php echo number_format($property['area_sqft']); ?> sqft</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <a href="property-details.php?id=<?php echo $property['id']; ?>" class="view-details-btn">
                                <i class="fas fa-eye me-2"></i>View Property Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-properties">
                <i class="fas fa-home fa-5x"></i>
                <h4>No Properties Available</h4>
                <p>We're currently updating our property listings. Please check back soon for new opportunities!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include('./includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
