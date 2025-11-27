<!DOCTYPE html>
<html>
<head>
    <title>Test Supplier Selector</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body style="padding: 20px;">
    <div class="container">
        <h1>Test Supplier Selector</h1>
        <p>This page loads the supplier selector inline component:</p>
        
        <div id="selector-container">
            <?php
                require_once 'config.php';
                require_once 'includes/warranty_functions.php';
                
                // Simulate having a product
                $product = [
                    'id' => 1,
                    'warranty_supplier_id' => null
                ];
                
                include 'warranty_supplier_selector_inline.php';
            ?>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
