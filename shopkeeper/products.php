<?php
/**
 * shopkeeper/products.php
 * =======================
 * PRODUCT MANAGEMENT PAGE
 *
 * Shopkeeper can:
 *   - View all their listed cakes in a table
 *   - Toggle a cake's availability (In Stock / Sold Out)
 *   - Edit a cake's details (inline, in the right panel)
 *   - Delete a cake
 *   - Add new cakes (via a separate add_product.php page)
 *
 * HOW IT WORKS:
 *   1. Reads the $shopId from session to scope ALL queries to just this shop
 *   2. Handles POST actions: 'save' (edit), 'delete', 'toggle' availability
 *   3. Fetches all products belonging to this shop for the table
 *   4. If ?edit=ID is in the URL, loads that product into the edit form
 */

// ─── Access Control ────────────────────────────────────────────────────────────
$required_role = 'shopkeeper';
require_once '../includes/auth_check.php'; // Redirects non-shopkeepers away
require_once '../config/db.php';           // Opens MySQL connection ($conn)

// Get the shopkeeper's shop ID from their session
// Without this, we wouldn't know which shop's products to show
$shopId = $_SESSION['shop_id'] ?? 0;
if (!$shopId) { header("Location: dashboard.php"); exit(); } // No shop = redirect home

$message     = '';   // Holds 'success:...' or 'error:...' after form actions
$editProduct = null; // Holds the product data when the edit form is open

// Check if we were redirected here FROM add_product.php with a flash message
// e.g. products.php?status=success&msg=Item+added!
if (isset($_GET['status'], $_GET['msg'])) {
    $message = $_GET['status'] . ':' . $_GET['msg'];
}

// ─── Handle Form Submissions ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the hidden 'action' input to decide what to do
    $action = $_POST['action'] ?? '';

    // ── SAVE: Add a new product OR update an existing one ────────────────────
    if ($action === 'save') {
        $pid         = (int)($_POST['product_id'] ?? 0); // 0 = adding new, >0 = editing
        $name        = trim($_POST['name']        ?? '');
        $flavor      = trim($_POST['flavor']      ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price']    ?? 0);
        $imageUrl    = trim($_POST['image_url']   ?? '');
        $available   = isset($_POST['is_available']) ? 1 : 0;
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $newCategory = trim($_POST['new_category'] ?? '');

        // ── Handle New Category Creation ──────────────────────────────────────
        // If the shopkeeper typed a new category name, create it first,
        // then use its new ID as the category for this product.
        if (!empty($newCategory)) {
            // INSERT IGNORE skips insertion if the category name already exists for this shop
            $catSql = mysqli_prepare($conn, "INSERT IGNORE INTO categories (shop_id, name) VALUES (?, ?)");
            mysqli_stmt_bind_param($catSql, 'is', $shopId, $newCategory);
            mysqli_stmt_execute($catSql);

            // Fetch the ID of the just-created (or already-existing) category
            $catRes = mysqli_query($conn, "SELECT id FROM categories WHERE shop_id=$shopId AND name='" . mysqli_real_escape_string($conn, $newCategory) . "'");
            if ($r = mysqli_fetch_assoc($catRes)) {
                $categoryId = $r['id']; // Use this ID when inserting/updating the product
            }
        }
        
        // If no category was selected (value=0), store NULL in the DB instead of 0
        $finalCatId = $categoryId > 0 ? $categoryId : null;

        // Check if the weight-based variant pricing checkbox is ticked
        $hasVariants = isset($_POST['has_variants']) ? 1 : 0;
        if ($hasVariants) {
            // When variants are enabled, use the 1kg price as the base price in the products table
            // Fall back to 500g if 1kg is missing
            $price = (float)($_POST['variants']['1kg']['price'] ?? ($_POST['variants']['500g']['price'] ?? 0));
        }

        if (empty($name) || $price <= 0) {
            $message = 'error:Name and valid base price are required.';
        } elseif ($pid === 0) {
            $s = mysqli_prepare($conn,"INSERT INTO products (shop_id,name,flavor,description,price,image_url,is_available,category_id,has_variants) VALUES (?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($s,'isssdsiii',$shopId,$name,$flavor,$description,$price,$imageUrl,$available,$finalCatId,$hasVariants);
            mysqli_stmt_execute($s) ? $message='success:Item added!' : $message='error:Failed to add.';
        } else {
            $s = mysqli_prepare($conn,"UPDATE products SET name=?,flavor=?,description=?,price=?,image_url=?,is_available=?,category_id=?,has_variants=? WHERE id=? AND shop_id=?");
            mysqli_stmt_bind_param($s,'sssdsiiiii',$name,$flavor,$description,$price,$imageUrl,$available,$finalCatId,$hasVariants,$pid,$shopId);
            
            if (mysqli_stmt_execute($s)) {
                $message='success:Item updated!';
                
                // Update variants
                // Simplest approach: delete all existing variants and recreate
                mysqli_query($conn, "DELETE FROM product_variants WHERE product_id = $pid");
                
                if ($hasVariants && isset($_POST['variants'])) {
                    $varStmt = mysqli_prepare($conn, "INSERT INTO product_variants (product_id, weight_label, price) VALUES (?, ?, ?)");
                    foreach ($_POST['variants'] as $weight => $data) {
                        if (!empty($data['enabled']) && !empty($data['price'])) {
                            $vPrice = (float)$data['price'];
                            mysqli_stmt_bind_param($varStmt, 'isd', $pid, $weight, $vPrice);
                            mysqli_stmt_execute($varStmt);
                        }
                    }
                    mysqli_stmt_close($varStmt);
                }
            } else {
                $message='error:Update failed.';
            }
        }
    // ── DELETE: Remove a product permanently ────────────────────────────────
    } elseif ($action === 'delete') {
        $pid = (int)($_POST['product_id'] ?? 0);
        // 'AND shop_id=?' is a security check — prevents deleting another shop's product
        $s = mysqli_prepare($conn,"DELETE FROM products WHERE id=? AND shop_id=?");
        mysqli_stmt_bind_param($s,'ii',$pid,$shopId);
        mysqli_stmt_execute($s) ? $message='success:Deleted.' : $message='error:Delete failed.';

    // ── TOGGLE: Flip is_available between 1 (in stock) and 0 (sold out) ────
    } elseif ($action === 'toggle') {
        $pid = (int)($_POST['product_id'] ?? 0);
        // NOT is_available flips the boolean: 1 becomes 0, 0 becomes 1
        mysqli_query($conn,"UPDATE products SET is_available=NOT is_available WHERE id=$pid AND shop_id=$shopId");
        $message = 'success:Availability updated.';
    }
}

// ─── Load Product for Editing ─────────────────────────────────────────────────
// When the admin clicks the ✏️ Edit button, the URL becomes: products.php?edit=5
// We load that product's data so the form is pre-filled with existing values.
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    // Security: 'AND shop_id=$shopId' ensures shopkeeper can only edit their OWN products
    $res = mysqli_query($conn,"SELECT * FROM products WHERE id=$editId AND shop_id=$shopId");
    $editProduct = mysqli_fetch_assoc($res);

    // If this product has weight-based variants (500g, 1kg, etc.), load them too
    $existingVariants = []; // Will be ['500g' => 450.00, '1kg' => 850.00, ...]
    if ($editProduct && $editProduct['has_variants']) {
        $vRes = mysqli_query($conn, "SELECT weight_label, price FROM product_variants WHERE product_id=$editId");
        while ($v = mysqli_fetch_assoc($vRes)) {
            // Build an associative array: ['500g' => price, '1kg' => price, ...]
            $existingVariants[$v['weight_label']] = $v['price'];
        }
    }
}

// ─── Load Categories for the Dropdown ────────────────────────────────────────
// Used to populate the "Category" dropdown in the add/edit form
$categoriesList = mysqli_query($conn, "SELECT * FROM categories WHERE shop_id=$shopId ORDER BY name ASC");

// ─── Load All Products for the Table ─────────────────────────────────────────
// LEFT JOIN with categories so we also get the category name (c.name AS category_name)
// LEFT JOIN means: even products WITHOUT a category are still shown (they get NULL)
// ORDER BY category first, then product name — groups products by category
$products = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.shop_id=$shopId
     ORDER BY c.name ASC, p.name ASC"
);

// Parse the message: 'success:Item added!' → $msgType='success', $msgText='Item added!'
[$msgType, $msgText] = $message ? explode(':', $message, 2) : ['',''];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Products - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
.page-grid{display:grid;grid-template-columns:1fr 360px;gap:24px;}
.table-card { min-width: 0; overflow-x: auto; } /* Prevent grid blowout */
.img-preview{width:100%;height:110px;object-fit:cover;border-radius:8px;margin-top:8px;display:none;}
@media(max-width:900px){.page-grid{grid-template-columns:1fr;}}
</style>
</head><body class="dashboard-body">

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
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
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            <div class="topbar-title"><h1>🎂 My Products</h1><p>Manage your cake listings</p></div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Shopkeeper</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-body">
        <?php if($msgText):?><div class="alert alert-<?=$msgType?>"><?=$msgType==='success'?'✅':'❌'?> <?=htmlspecialchars($msgText)?></div><?php endif;?>
        <div class="page-grid">
            <!-- Products Table -->
            <div class="table-card">
                <div class="table-card-header">
                    <h2>All Items (<?= mysqli_num_rows($products) ?>)</h2>
                        <a href="add_product.php" class="btn btn-primary btn-sm">+ Add New</a>
                </div>
                <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Img</th><th>Name</th><th>Flavor</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php mysqli_data_seek($products,0); while($p=mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><img src="<?=htmlspecialchars($p['image_url']?:'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=80&q=60')?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;" onerror="this.src='https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=80&q=60'"></td>
                        <td><strong><?=htmlspecialchars($p['name'])?></strong><div style="font-size:.73rem;color:var(--text-muted)"><?=htmlspecialchars(substr($p['description'],0,40))?>...</div></td>
                        <td><?= htmlspecialchars($p['flavor'] ?: '—') ?></td>
                        <td><span style="font-size:.8rem;background:#eee;padding:2px 6px;border-radius:4px;"><?=htmlspecialchars($p['category_name']??'Uncategorized')?></span></td>
                        <td><strong>₹<?=number_format($p['price'],2)?></strong></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="product_id" value="<?=$p['id']?>">
                                <button type="submit" class="badge <?=$p['is_available']?'badge-active':'badge-inactive'?>" style="cursor:pointer;border:none;"><?=$p['is_available']?'✅ In Stock':'❌ Sold Out'?></button>
                            </form>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="products.php?edit=<?=$p['id']?>" class="btn btn-outline btn-sm">✏️</a>
                                <form method="POST" onsubmit="return confirm('Delete this cake?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?=$p['id']?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile;?>
                    </tbody>
                </table>
                </div>
            </div>
            <!-- Add/Edit Panel -->
            <div class="form-card">
                <?php if ($editProduct): ?>
                    <h2>✏️ Edit Item</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="product_id" value="<?=$editProduct['id']?>">
                        <div class="form-group"><label>Item Name *</label><input type="text" name="name" value="<?=htmlspecialchars($editProduct['name'])?>" placeholder="e.g. Chocolate Truffle" required></div>

                        <div class="form-group"><label>Flavor</label><input type="text" name="flavor" value="<?=htmlspecialchars($editProduct['flavor'] ?? '')?>" placeholder="e.g. Chocolate, Mango, Vanilla"></div>

                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" id="catSelect" onchange="toggleNewCat(this.value)">
                                <option value="0">-- Select Category --</option>
                                <?php 
                                mysqli_data_seek($categoriesList, 0);
                                while($cat = mysqli_fetch_assoc($categoriesList)): 
                                    $selected = ($editProduct['category_id'] == $cat['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?=$cat['id']?>" <?=$selected?>><?=htmlspecialchars($cat['name'])?></option>
                                <?php endwhile; ?>
                                <option value="new">+ Create New Category</option>
                            </select>
                            <input type="text" name="new_category" id="newCatInput" placeholder="Enter new category name..." style="display:none; margin-top:8px;">
                        </div>

                        <div class="form-group"><label>Description</label><textarea name="description" rows="3" placeholder="Describe the item..." style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;"><?=htmlspecialchars($editProduct['description'])?></textarea></div>
                        
                        <div class="form-group" id="basePriceGroup" style="<?= $editProduct['has_variants'] ? 'display:none;' : '' ?>"><label>Price (₹) *</label><input type="number" name="price" id="basePrice" step="0.01" min="0" value="<?=$editProduct['price']?>" placeholder="550.00" <?= !$editProduct['has_variants'] ? 'required' : '' ?>></div>

                        <div class="form-group" style="display:flex;align-items:center;gap:10px; margin-top:20px; padding:12px; background:var(--body-bg); border-radius:8px; border:1px solid var(--border);">
                            <input type="checkbox" name="has_variants" id="hasVariants" onchange="toggleVariants()" <?= $editProduct['has_variants'] ? 'checked' : '' ?>>
                            <label for="hasVariants" style="text-transform:none;font-weight:bold;margin:0;">Enable Variants (Weight-based Pricing)</label>
                        </div>

                        <div id="variantsSection" style="<?= $editProduct['has_variants'] ? 'display:block;' : 'display:none;' ?> background:var(--body-bg); padding:16px; border-radius:12px; margin-bottom:16px; border:1px solid var(--border);">
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">Select variants to offer. 500g and 1kg prices drive the auto-calculation for others.</p>
                            
                            <?php
                                $weights = ['500g', '1kg', '2kg', '3kg', '4kg', '5kg', '6kg'];
                                foreach($weights as $w):
                                    $hasThisVar = isset($existingVariants[$w]);
                                    $vPrice = $hasThisVar ? $existingVariants[$w] : '';
                                    // if it's 500g or 1kg it's checked by default in UI but let's reflect DB if variants enabled
                                    $isChecked = $editProduct['has_variants'] ? $hasThisVar : in_array($w, ['500g','1kg']);
                            ?>
                            <div style="display:grid; grid-template-columns: 80px 1fr; gap:10px; align-items:center; margin-bottom:12px;">
                                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                                    <input type="checkbox" name="variants[<?= $w ?>][enabled]" value="1" <?= $isChecked ? 'checked' : '' ?> onchange="toggleVarInput(this, '<?= $w ?>')"> <?= $w ?>
                                </label>
                                <input type="number" step="0.01" name="variants[<?= $w ?>][price]" id="price_<?= $w ?>" value="<?= htmlspecialchars($vPrice) ?>" placeholder="Price for <?= $w ?>" style="padding:8px 12px; border:1px solid var(--border); border-radius:6px;" <?= ($isChecked && in_array($w, ['500g','1kg'])) ? 'required' : ($isChecked ? '' : 'disabled') ?> oninput="autoCalculateVariants()" <?= $vPrice ? 'data-manual-override="true"' : '' ?>>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="url" name="image_url" id="imgUrl" value="<?=htmlspecialchars($editProduct['image_url'])?>" placeholder="https://..." oninput="previewImg(this.value)">
                            <img id="imgPreview" class="img-preview" src="<?=htmlspecialchars($editProduct['image_url'])?>" style="<?=$editProduct['image_url']?'display:block':'display:none'?>">
                        </div>
                        <div class="form-group" style="display:flex;align-items:center;gap:10px;">
                            <input type="checkbox" name="is_available" id="avail" <?=($editProduct['is_available'])?'checked':''?>>
                            <label for="avail" style="text-transform:none;font-size:.9rem;">Available / In Stock</label>
                        </div>
                        <div style="display:flex;gap:10px;">
                            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                            <a href="products.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                <?php else: ?>
                    <h2>➕ Add New Item</h2>
                    <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">
                        Open the dedicated add product page to create a new cake listing.
                    </p>
                    <a href="add_product.php" class="btn btn-primary" style="margin-top:16px;">+ Go to Add Product</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
/**
 * previewImg(url)
 * ===============
 * Shows a live preview of the cake image as the shopkeeper types the URL.
 * If the URL is invalid/broken, the onerror handler hides the image.
 */
function previewImg(url) {
    const img = document.getElementById('imgPreview');
    if (url) {
        img.src = url;
        img.style.display = 'block';
        img.onerror = () => img.style.display = 'none'; // Hide if image fails to load
    } else {
        img.style.display = 'none'; // Hide when field is empty
    }
}

/**
 * toggleNewCat(val)
 * =================
 * Shows or hides the "new category name" text input.
 * Called when the category dropdown changes.
 * If the user selects "+ Create New Category" (value='new'),
 * the input appears. Otherwise it's hidden and cleared.
 */
function toggleNewCat(val) {
    const input = document.getElementById('newCatInput');
    if (val === 'new') {
        input.style.display = 'block'; // Show the text input
        input.required = true;          // Make it required so form won't submit empty
    } else {
        input.style.display = 'none';  // Hide it
        input.value = '';               // Clear any previously typed text
        input.required = false;
    }
}

/**
 * toggleVariants()
 * ================
 * Called when the "Enable Variants" checkbox is toggled.
 * When variants ARE enabled:
 *   - Hides the single base price field (not needed)
 *   - Shows the variant grid (500g, 1kg, 2kg, etc.)
 *   - Makes 500g and 1kg prices required
 * When variants are NOT enabled:
 *   - Shows the single base price field
 *   - Hides the variant grid
 */
function toggleVariants() {
    const hasVar = document.getElementById('hasVariants').checked;
    // Show/hide the variants grid section
    document.getElementById('variantsSection').style.display = hasVar ? 'block' : 'none';
    const basePriceInput = document.getElementById('basePrice');

    if (hasVar) {
        // Hide the single base price — not needed when variants define prices
        document.getElementById('basePriceGroup').style.display = 'none';
        basePriceInput.required = false; // Remove required so form can submit
        // 500g and 1kg are the anchor prices — make them required
        document.getElementById('price_500g').required = true;
        document.getElementById('price_1kg').required = true;
    } else {
        // Show single price field and make it required again
        document.getElementById('basePriceGroup').style.display = 'block';
        basePriceInput.required = true;
        // Remove required from variant fields
        document.getElementById('price_500g').required = false;
        document.getElementById('price_1kg').required = false;
    }
}

/**
 * toggleVarInput(checkbox, weight)
 * =================================
 * Called when a variant weight checkbox (e.g. "2kg") is checked/unchecked.
 * Enables/disables the corresponding price input field.
 * If unchecked, clears and disables the price field so it won't submit.
 */
function toggleVarInput(checkbox, weight) {
    const input = document.getElementById('price_' + weight);
    if (checkbox.checked) {
        input.disabled = false; // Enable the price field
        if (weight === '500g' || weight === '1kg') input.required = true; // These must be filled
        autoCalculateVariants(); // Recalculate larger sizes when a new size is enabled
    } else {
        input.disabled = true;  // Disable so it doesn't submit
        input.required = false;
        input.value = '';        // Clear the price
    }
}

/**
 * autoCalculateVariants()
 * =======================
 * Automatically fills in prices for 2kg, 3kg, 4kg, 5kg, 6kg
 * based on the 1kg price. This is a convenience shortcut.
 *
 * Logic: price_Xkg = price_1kg × X
 *   (e.g. if 1kg = ₹800, then 2kg = ₹1600, 3kg = ₹2400, etc.)
 *
 * Skips any weight that:
 *   - Has been manually typed by the shopkeeper (data-manual-override='true')
 *   - Is currently disabled (checkbox unchecked)
 */
function autoCalculateVariants() {
    const p500 = parseFloat(document.getElementById('price_500g').value);
    const p1kg = parseFloat(document.getElementById('price_1kg').value);

    // Need both values to calculate — exit early if either is missing
    if (isNaN(p500) || isNaN(p1kg)) return;

    // Multiplier: how many times the 1kg price each size costs
    const multipliers = {'2kg': 2, '3kg': 3, '4kg': 4, '5kg': 5, '6kg': 6};

    for (const [w, mult] of Object.entries(multipliers)) {
        const input = document.getElementById('price_' + w);
        // Only auto-fill if: the shopkeeper hasn't manually overridden it AND it's enabled
        if (!input.dataset.manualOverride && !input.disabled) {
            input.value = (p1kg * mult).toFixed(2); // Round to 2 decimal places
        }
    }
}

/**
 * DOMContentLoaded listener
 * =========================
 * Marks a price field as "manually overridden" when the shopkeeper types in it.
 * This prevents autoCalculateVariants() from overwriting their custom price.
 * Uses the HTML data attribute: data-manual-override="true"
 */
document.addEventListener('DOMContentLoaded', () => {
    const multipliers = ['2kg', '3kg', '4kg', '5kg', '6kg'];
    multipliers.forEach(w => {
        const input = document.getElementById('price_' + w);
        if(input) {
            // When the user manually types, flag it so auto-calc won't overwrite
            input.addEventListener('input', () => {
                input.dataset.manualOverride = 'true';
            });
        }
    });
});
</script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body></html>
