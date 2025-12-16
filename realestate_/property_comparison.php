<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/security.php');

// Get comparison IDs from session or query
$comparison_ids = [];
if (isset($_GET['ids'])) {
    $comparison_ids = array_map('intval', array_filter(explode(',', $_GET['ids'])));
} elseif (isset($_SESSION['comparison_cart'])) {
    $comparison_ids = $_SESSION['comparison_cart'];
}

// Limit to 4 properties maximum
$comparison_ids = array_slice($comparison_ids, 0, 4);

// Fetch properties
$properties_comparison = [];
if (!empty($comparison_ids)) {
    $placeholders = implode(',', $comparison_ids);
    $query = "
        SELECT 
            p.*,
            pr.price,
            pt.type_name,
            l.city,
            l.state,
            l.zip_code,
            COUNT(DISTINCT i.id) as inquiry_count,
            COUNT(DISTINCT b.id) as bookmark_count,
            AVG(rev.rating) as avg_rating
        FROM properties p
        LEFT JOIN prices pr ON p.id = pr.property_id
        LEFT JOIN property_types pt ON p.property_type_id = pt.id
        LEFT JOIN locations l ON p.location_id = l.id
        LEFT JOIN inquiries i ON p.id = i.property_id
        LEFT JOIN bookmarks b ON p.id = b.property_id
        LEFT JOIN property_reviews rev ON p.id = rev.property_id
        WHERE p.id IN ({$placeholders})
        GROUP BY p.id
    ";
    $result = $conn->query($query);
    if ($result) {
        $properties_comparison = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get page title
$page_title = count($properties_comparison) . ' Property Comparison - Real Estate';

?>

<?php include('./includes/header.php'); ?>
<?php include('./includes/nav.php'); ?>

<div class="container-fluid mt-5 mb-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-columns me-2"></i>Property Comparison</h2>
            <p class="text-muted">Compare properties side-by-side to make informed decisions</p>
        </div>
        <div class="col-auto">
            <?php if (!empty($properties_comparison)): ?>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <a href="property.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Search
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($properties_comparison)): ?>
        <div class="alert alert-info">
            <h4><i class="fas fa-info-circle me-2"></i>No Properties to Compare</h4>
            <p>You haven't selected any properties for comparison yet. 
               <a href="property.php">Browse properties</a> and click "Add to Comparison" to get started.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover comparison-table">
                <thead>
                    <tr class="table-light">
                        <th style="width: 150px;"></th>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <th class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger float-end" 
                                onclick="removeComparison(<?= $prop['id']; ?>)" title="Remove from comparison">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="text-start">
                                <strong><?= htmlspecialchars(substr($prop['title'], 0, 25)); ?>...</strong>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($prop['city']); ?>, <?= htmlspecialchars($prop['state']); ?></small>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Price -->
                    <tr>
                        <td><strong>Price</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-end">
                            <h5 class="text-success">$<?= !empty($prop['price']) ? number_format($prop['price']) : 'TBD'; ?></h5>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Property Type -->
                    <tr class="table-light">
                        <td><strong>Property Type</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td><?= htmlspecialchars($prop['type_name'] ?? 'N/A'); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Address -->
                    <tr>
                        <td><strong>Address</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td><?= htmlspecialchars($prop['address']); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Bedrooms -->
                    <tr class="table-light">
                        <td><strong>Bedrooms</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= $prop['bedrooms'] ?? 'N/A'; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Bathrooms -->
                    <tr>
                        <td><strong>Bathrooms</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= $prop['bathrooms'] ?? 'N/A'; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Square Feet -->
                    <tr class="table-light">
                        <td><strong>Square Feet</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= !empty($prop['square_feet']) ? number_format($prop['square_feet']) : 'N/A'; ?> sq ft</td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Lot Size -->
                    <tr>
                        <td><strong>Lot Size</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= !empty($prop['lot_size']) ? number_format($prop['lot_size']) : 'N/A'; ?> sq ft</td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Year Built -->
                    <tr class="table-light">
                        <td><strong>Year Built</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= $prop['year_built'] ?? 'N/A'; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Parking -->
                    <tr>
                        <td><strong>Parking Spaces</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= $prop['parking_spaces'] ?? 'N/A'; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Featured -->
                    <tr class="table-light">
                        <td><strong>Featured</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center">
                            <?php if ($prop['featured']): ?>
                                <span class="badge bg-success"><i class="fas fa-star me-1"></i>Featured</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Rating -->
                    <tr>
                        <td><strong>Average Rating</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center">
                            <?php if (!empty($prop['avg_rating'])): ?>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i> <?= number_format($prop['avg_rating'], 1); ?>/5
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No ratings</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Inquiries -->
                    <tr class="table-light">
                        <td><strong>Inquiries</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= $prop['inquiry_count']; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Bookmarks -->
                    <tr>
                        <td><strong>Bookmarked</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center"><?= $prop['bookmark_count']; ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Description -->
                    <tr class="table-light">
                        <td><strong>Description</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td>
                            <small><?= htmlspecialchars(substr($prop['description'] ?? '', 0, 100)); ?>...</small>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Actions -->
                    <tr>
                        <td><strong>Actions</strong></td>
                        <?php foreach ($properties_comparison as $prop): ?>
                        <td class="text-center">
                            <a href="property_details.php?id=<?= $prop['id']; ?>" class="btn btn-sm btn-primary">
                                View Details
                            </a>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <style>
            @media print {
                .btn, .navbar, .footer { display: none !important; }
                .comparison-table { font-size: 12px; }
            }
        </style>

        <div class="mt-4 text-center">
            <a href="property.php" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>Find More Properties
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function removeComparison(propertyId) {
    const currentIds = new URLSearchParams(window.location.search).get('ids');
    if (currentIds) {
        const ids = currentIds.split(',').filter(id => id !== propertyId.toString());
        if (ids.length > 0) {
            window.location.href = `?ids=${ids.join(',')}`;
        } else {
            window.location.href = window.location.pathname;
        }
    }
}
</script>

<?php include('./includes/footer.php'); ?>
