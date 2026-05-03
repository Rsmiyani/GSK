<?php
/**
 * shopkeeper/add_product.php
 * ==========================
 * ADD PRODUCT PAGE
 */

$required_role = 'shopkeeper';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$shopId = $_SESSION['shop_id'] ?? 0;
if (!$shopId) {
    header("Location: dashboard.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $imageUrl    = trim($_POST['image_url']   ?? '');
    $available   = isset($_POST['is_available']) ? 1 : 0;
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $newCategory = trim($_POST['new_category'] ?? '');

    if (!empty($newCategory)) {
        $catSql = mysqli_prepare($conn, "INSERT IGNORE INTO categories (shop_id, name) VALUES (?, ?)");
        mysqli_stmt_bind_param($catSql, 'is', $shopId, $newCategory);
        mysqli_stmt_execute($catSql);

        $catRes = mysqli_query($conn, "SELECT id FROM categories WHERE shop_id=$shopId AND name='" . mysqli_real_escape_string($conn, $newCategory) . "'");
        if ($r = mysqli_fetch_assoc($catRes)) {
            $categoryId = (int)$r['id'];
        }
    }

    $hasVariants = isset($_POST['has_variants']) ? 1 : 0;
    
    // In has_variants mode, standard price is the 1kg price or 500g price if 1kg missing, or 0.
    if ($hasVariants) {
        $price = (float)($_POST['variants']['1kg']['price'] ?? ($_POST['variants']['500g']['price'] ?? 0));
    }

    if (empty($name) || $price <= 0) {
        $message = 'error:Name and valid base price are required.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO products (shop_id, name, description, price, image_url, is_available, category_id, has_variants) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issdsiii', $shopId, $name, $description, $price, $imageUrl, $available, $finalCatId, $hasVariants);

        if (mysqli_stmt_execute($stmt)) {
            $newProductId = mysqli_insert_id($conn);
            
            // Insert variants
            if ($hasVariants && isset($_POST['variants'])) {
                $varStmt = mysqli_prepare($conn, "INSERT INTO product_variants (product_id, weight_label, price) VALUES (?, ?, ?)");
                foreach ($_POST['variants'] as $weight => $data) {
                    if (!empty($data['enabled']) && !empty($data['price'])) {
                        $vPrice = (float)$data['price'];
                        mysqli_stmt_bind_param($varStmt, 'isd', $newProductId, $weight, $vPrice);
                        mysqli_stmt_execute($varStmt);
                    }
                }
                mysqli_stmt_close($varStmt);
            }
            
            header('Location: products.php?status=success&msg=' . urlencode('Item added!'));
            exit();
        }

        $message = 'error:Failed to add item.';
        mysqli_stmt_close($stmt);
    }
}

$categoriesList = mysqli_query($conn, "SELECT * FROM categories WHERE shop_id=$shopId ORDER BY name ASC");
[$msgType, $msgText] = $message ? explode(':', $message, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
.form-shell{display:grid;grid-template-columns:1fr minmax(320px,420px);gap:24px;align-items:start;}
.img-preview{width:100%;height:120px;object-fit:cover;border-radius:8px;margin-top:8px;display:none;}
@media(max-width:900px){.form-shell{grid-template-columns:1fr;}}
</style>
</head>
<body class="dashboard-body">
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="logo">
        <div><h2>My Shop</h2><span>Shopkeeper Portal</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Management</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="products.php" class="active"><span class="nav-icon">🎂</span> My Products</a>
        <a href="orders.php"><span class="nav-icon">📦</span> Orders</a>
        <a href="analytics.php"><span class="nav-icon">📊</span> Analytics</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title"><h1>➕ Add Product</h1><p>Create a new cake listing for your shop</p></div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Shopkeeper</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">
        <?php if($msgText): ?><div class="alert alert-<?=$msgType?>"><?=$msgType==='success'?'✅':'❌'?> <?=htmlspecialchars($msgText)?></div><?php endif; ?>

        <div class="form-shell">
            <div class="form-card" style="max-width:none;">
                <h2>Product Details</h2>
                <form method="POST">
                    <div class="form-group"><label>Item Name *</label><input type="text" name="name" placeholder="e.g. Chocolate Truffle" required></div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" id="catSelect" onchange="toggleNewCat(this.value)">
                            <option value="0">-- Select Category --</option>
                            <?php while($cat = mysqli_fetch_assoc($categoriesList)): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endwhile; ?>
                            <option value="new">+ Create New Category</option>
                        </select>
                        <input type="text" name="new_category" id="newCatInput" placeholder="Enter new category name..." style="display:none; margin-top:8px;">
                    </div>

                    <div class="form-group"><label>Description</label><textarea name="description" rows="3" placeholder="Describe the item..." style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;"></textarea></div>
                    
                    <div class="form-group" id="basePriceGroup"><label>Price (₹) *</label><input type="number" name="price" id="basePrice" step="0.01" min="0" placeholder="550.00"></div>

                    <div class="form-group" style="display:flex;align-items:center;gap:10px; margin-top:20px; padding:12px; background:var(--body-bg); border-radius:8px; border:1px solid var(--border);">
                        <input type="checkbox" name="has_variants" id="hasVariants" onchange="toggleVariants()">
                        <label for="hasVariants" style="text-transform:none;font-weight:bold;margin:0;">Enable Variants (Weight-based Pricing)</label>
                    </div>

                    <div id="variantsSection" style="display:none; background:var(--body-bg); padding:16px; border-radius:12px; margin-bottom:16px; border:1px solid var(--border);">
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">Select variants to offer. 500g and 1kg prices drive the auto-calculation for others.</p>
                        
                        <?php
                            $weights = ['500g', '1kg', '2kg', '3kg', '4kg', '5kg', '6kg'];
                            foreach($weights as $w):
                        ?>
                        <div style="display:grid; grid-template-columns: 80px 1fr; gap:10px; align-items:center; margin-bottom:12px;">
                            <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                                <input type="checkbox" name="variants[<?= $w ?>][enabled]" value="1" <?= in_array($w, ['500g','1kg']) ? 'checked' : '' ?> onchange="toggleVarInput(this, '<?= $w ?>')"> <?= $w ?>
                            </label>
                            <input type="number" step="0.01" name="variants[<?= $w ?>][price]" id="price_<?= $w ?>" placeholder="Price for <?= $w ?>" style="padding:8px 12px; border:1px solid var(--border); border-radius:6px;" <?= in_array($w, ['500g','1kg']) ? 'required' : 'disabled' ?> oninput="autoCalculateVariants()">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="url" name="image_url" id="imgUrl" placeholder="https://..." oninput="previewImg(this.value)">
                        <img id="imgPreview" class="img-preview" src="">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox" name="is_available" id="avail" checked>
                        <label for="avail" style="text-transform:none;font-size:.9rem;">Available / In Stock</label>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="btn btn-primary">+ Add Product</button>
                        <a href="products.php" class="btn btn-outline">Back to Products</a>
                    </div>
                </form>
            </div>

            <div class="table-card" style="margin-bottom:0;">
                <div class="table-card-header">
                    <h2>Quick Notes</h2>
                </div>
                <p style="color:var(--text-muted);line-height:1.7;">
                    Add a clear name, price, and image so the product shows well in the customer dashboard.
                </p>
                <div style="margin-top:18px;padding:16px;border:1px solid var(--border);border-radius:12px;background:var(--body-bg);">
                    <strong style="display:block;margin-bottom:8px;">Tip</strong>
                    <span style="color:var(--text-muted);font-size:.92rem;">If you create a new category, it will be saved for this shop automatically.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImg(url) {
    const img = document.getElementById('imgPreview');
    if (url) {
        img.src = url;
        img.style.display = 'block';
        img.onerror = () => img.style.display = 'none';
    } else {
        img.style.display = 'none';
    }
}

function toggleNewCat(val) {
    const input = document.getElementById('newCatInput');
    if (val === 'new') {
        input.style.display = 'block';
        input.required = true;
    } else {
        input.style.display = 'none';
        input.value = '';
        input.required = false;
    }
}

function toggleVariants() {
    const hasVar = document.getElementById('hasVariants').checked;
    document.getElementById('variantsSection').style.display = hasVar ? 'block' : 'none';
    const basePriceInput = document.getElementById('basePrice');
    
    if (hasVar) {
        document.getElementById('basePriceGroup').style.display = 'none';
        basePriceInput.required = false;
        document.getElementById('price_500g').required = true;
        document.getElementById('price_1kg').required = true;
    } else {
        document.getElementById('basePriceGroup').style.display = 'block';
        basePriceInput.required = true;
        document.getElementById('price_500g').required = false;
        document.getElementById('price_1kg').required = false;
    }
}

function toggleVarInput(checkbox, weight) {
    const input = document.getElementById('price_' + weight);
    if (checkbox.checked) {
        input.disabled = false;
        if (weight === '500g' || weight === '1kg') input.required = true;
        autoCalculateVariants(); // Recalculate if newly enabled
    } else {
        input.disabled = true;
        input.required = false;
        input.value = '';
    }
}

function autoCalculateVariants() {
    const p500 = parseFloat(document.getElementById('price_500g').value);
    const p1kg = parseFloat(document.getElementById('price_1kg').value);
    
    if (isNaN(p500) || isNaN(p1kg)) return; // Need both to safely assume calculation is intended
    
    const multipliers = {'2kg': 2, '3kg': 3, '4kg': 4, '5kg': 5, '6kg': 6};
    
    for (const [w, mult] of Object.entries(multipliers)) {
        const input = document.getElementById('price_' + w);
        // Only auto-calculate if it's currently empty, or if we want to overwrite.
        // We'll just overwrite it to keep it simple, but allow manual edit later.
        // Wait, if we overwrite on every keystroke, user can't manually edit easily!
        // To allow override, only calculate if it's empty or perfectly matches the OLD calculated price.
        // Actually, simplest is to just overwrite if it's currently disabled, OR if we force it.
        // Let's just set it if it's not manually overridden. 
        if (!input.dataset.manualOverride && !input.disabled) {
            input.value = (p1kg * mult).toFixed(2);
        }
    }
}

// Mark as manually overridden if user types in it
document.addEventListener('DOMContentLoaded', () => {
    const multipliers = ['2kg', '3kg', '4kg', '5kg', '6kg'];
    multipliers.forEach(w => {
        const input = document.getElementById('price_' + w);
        if(input) {
            input.addEventListener('input', () => {
                input.dataset.manualOverride = 'true';
            });
        }
    });
});

</script>
</body>
</html>
