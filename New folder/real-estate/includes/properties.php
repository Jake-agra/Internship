<?php
// Get all properties using the new schema
$featured_properties = [];
$stmt = $conn->prepare("SELECT id, propertiesname, price, currency, property_type, bedrooms, bathrooms, area_sqft, city, region, country, images FROM property_details ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $featured_properties[] = $row;
}
$stmt->close();
?>

<style>
    .properties-section {
        background: white !important;
        padding: 80px 0;
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
        width: 80px;
        height: 4px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border-radius: 2px;
    }
    
    .section-subtitle {
        color: #6c757d;
        font-size: 1.2rem;
        text-align: center;
        margin-bottom: 60px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .property-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: none;
        margin-bottom: 40px;
        position: relative;
    }
    
    .property-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }
    
    .property-image {
        height: 280px;
        background-size: cover;
        background-position: center;
        background-color: #f8f9fa;
        position: relative;
        overflow: hidden;
    }
    
    .property-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 86, 179, 0.1) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .property-card:hover .property-image::before {
        opacity: 1;
    }
    
    .property-type-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(255, 255, 255, 0.95);
        color: #007bff;
        padding: 8px 16px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.85rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .property-card-body {
        padding: 30px;
    }
    
    .property-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.4rem;
        margin-bottom: 15px;
        line-height: 1.3;
    }
    
    .price-badge {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 12px 20px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 1.3rem;
        display: inline-block;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    
    .property-features {
        display: flex;
        gap: 20px;
        margin: 25px 0;
        flex-wrap: wrap;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #495057;
        font-weight: 500;
        font-size: 0.95rem;
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 20px;
        border: 1px solid #e9ecef;
    }
    
    .feature-item i {
        color: #007bff;
        font-size: 1.1rem;
    }
    
    .property-location {
        color: #6c757d;
        font-size: 1rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .property-location i {
        color: #dc3545;
    }
    
    .view-details-btn {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        width: 100%;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }
    
    .view-details-btn:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
    }
    
    .no-properties {
        text-align: center;
        padding: 100px 20px;
        background: #f8f9fa;
        border-radius: 20px;
        margin: 40px 0;
    }
    
    .no-properties i {
        color: #6c757d;
        margin-bottom: 30px;
    }
    
    .no-properties h4 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .no-properties p {
        color: #6c757d;
        font-size: 1.1rem;
    }
    
    .properties-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 40px;
        margin-top: 60px;
    }
    
    @media (max-width: 768px) {
        .properties-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .section-title {
            font-size: 2.2rem;
        }
        
        .property-features {
            gap: 10px;
        }
        
        .feature-item {
            font-size: 0.85rem;
            padding: 6px 10px;
        }
    }
</style>

<!-- Properties Section -->
<section class="properties-section" id="properties">
    <div class="container">
        <h2 class="section-title">Our Premium Properties</h2>
        <p class="section-subtitle">
            Discover exceptional properties that match your lifestyle and investment goals. 
            Each property is carefully selected to offer the best value and quality.
        </p>
        
        <?php if (!empty($featured_properties)): ?>
            <div class="properties-grid">
                <?php foreach ($featured_properties as $property): ?>
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
                <i class="fas fa-home fa-4x"></i>
                <h4>No Properties Available</h4>
                <p>We're currently updating our property listings. Please check back soon for new opportunities!</p>
            </div>
        <?php endif; ?>
    </div>
</section>
