<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('./Database/connection.php');


// build the base query for property search
$query = "SELECT p.id, p.propertiesname,p.description,p.price_id,p.user_id,p.property_type_id,p.location_id,p.status,p.bedrooms,p.bathrooms,p.area_sqft,p.year_built,p.images,pr.currency, pr.price_type,pr.amount as price, pt.type_name as property_type, l.city,l.country,l.region from properties p
JOIN prices pr ON p.price_id=pr.id
JOIN locations l ON p.location_id=l.id
JOIN property_types pt ON p.property_type_id=pt.id
WHERE p.status='available' ";
?>

<!-- adding a search condition -->

$params = array();

<div class="search-filter-section">
    <div class="container">.
        <div class="row gap-3">

        <!-- search property -->
        <div class="col-md-4">
            <label for="search" class="form-label fw-500 mb-1">Search Property</label>
            <input type="text" name="search" class="form-control" id="search" placeholder="Search by property name, location, etc.">
        </div>

        <!-- property type -->
         <div class="col-md-3">
            <label for="property_type" class="form-label fw-500 mb-1">Property Type</label> 
            <input type="text" name="property_type" class="form-control" id="property_type" placeholder="Enter property type">
            <option value="property_type">All Type</option>
            <?php

            ?>
         </div>
        </div>
    </div>
</div>

