#!/bin/bash
# Comprehensive Test Suite for Warranty Supplier Selector

echo "=== WARRANTY SUPPLIER SELECTOR - TEST SUITE ==="
echo ""

# Test 1: PHP Syntax
echo "Test 1: PHP Syntax Validation"
echo "  - Checking edit_warranty.php..."
php -l /c/xampp/htdocs/sistema4/edit_warranty.php
echo "  - Checking warranty_supplier_selector_inline.php..."
php -l /c/xampp/htdocs/sistema4/warranty_supplier_selector_inline.php
echo ""

# Test 2: Check database connection
echo "Test 2: Database Connection"
cat > /tmp/db_test.php << 'EOF'
<?php
require_once '/c/xampp/htdocs/sistema4/config.php';
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranty_suppliers WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "✓ Database connected. Active suppliers: " . $result['total'] . "\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
?>
EOF
php /tmp/db_test.php
echo ""

# Test 3: Check API endpoint
echo "Test 3: API Endpoint (get_warranty_suppliers.php)"
echo "  URL: http://localhost/sistema4/get_warranty_suppliers.php"
curl -s http://localhost/sistema4/get_warranty_suppliers.php | head -20
echo ""
echo ""

# Test 4: Check files exist
echo "Test 4: File Existence Check"
files=( 
    "edit_warranty.php"
    "warranty_supplier_selector_inline.php"
    "get_warranty_suppliers.php"
    "warranty_template_selector_inline.php"
)

for file in "${files[@]}"; do
    if [ -f "/c/xampp/htdocs/sistema4/$file" ]; then
        echo "  ✓ $file exists"
    else
        echo "  ✗ $file NOT FOUND"
    fi
done
echo ""

echo "=== TEST SUITE COMPLETE ==="
