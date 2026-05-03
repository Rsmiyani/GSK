<?php
/**
 * shopkeeper/products.php - PRODUCT MANAGEMENT
 * Shopkeeper can add/edit/delete/toggle cakes for their shop.
 */

$required_role = 'shopkeeper';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$shopId = $_SESSION['shop_id'] ?? 0;
if (!$shopId) { header("Location: dashboard.php"); exit(); }

$message = '';
$editProduct = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $pid         = (int)($_POST['product_id'] ?? 0);
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price']    ?? 0);
        $imageUrl    = trim($_POST['image_url']   ?? '');
        $available   = isset($_POST['is_available']) ? 1 : 0;
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $newCategory = trim($_POST['new_category'] ?? '');

        // Handle new category creation
        if (!empty($newCategory)) {
            $catSql = mysqli_prepare($conn, "INSERT IGNORE INTO categories (shop_id, name) VALUES (?, ?)");
            mysqli_stmt_bind_param($catSql, 'is', $shopId, $newCategory);
            mysqli_stmt_execute($catSql);
            
            $catRes = mysqli_query($conn, "SELECT id FROM categories WHERE shop_id=$shopId AND name='" . mysqli_real_escape_string($conn, $newCategory) . "'");
            if ($r = mysqli_fetch_assoc($catRes)) {
                $categoryId = $r['id'];
            }
        }
        
        // Nullify if 0
        $finalCatId = $categoryId > 0 ? $categoryId : null;

        if (empty($name) || $price <= 0) {
            $message = 'error:Name and valid price are required.';
        } elseif ($pid === 0) {
            $s = mysqli_prepare($conn,"INSERT INTO products (shop_id,name,description,price,image_url,is_available,category_id) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($s,'issdsii',$shopId,$name,$description,$price,$imageUrl,$available,$finalCatId);
            mysqli_stmt_execute($s) ? $message='success:Item added!' : $message='error:Failed to add.';
        } else {
            $s = mysqli_prepare($conn,"UPDATE products SET name=?,description=?,price=?,image_url=?,is_available=?,category_id=? WHERE id=? AND shop_id=?");
            mysqli_stmt_bind_param($s,'ssdsiiii',$name,$description,$price,$imageUrl,$available,$finalCatId,$pid,$shopId);
            mysqli_stmt_execute($s) ? $message='success:Item updated!' : $message='error:Update failed.';
        }
    } elseif ($action === 'delete') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $s = mysqli_prepare($conn,"DELETE FROM products WHERE id=? AND shop_id=?");
        mysqli_stmt_bind_param($s,'ii',$pid,$shopId);
        mysqli_stmt_execute($s) ? $message='success:Deleted.' : $message='error:Delete failed.';
    } elseif ($action === 'toggle') {
        $pid = (int)($_POST['product_id'] ?? 0);
        mysqli_query($conn,"UPDATE products SET is_available=NOT is_available WHERE id=$pid AND shop_id=$shopId");
        $message = 'success:Availability updated.';
    }
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $res = mysqli_query($conn,"SELECT * FROM products WHERE id=$editId AND shop_id=$shopId");
    $editProduct = mysqli_fetch_assoc($res);
}

$categoriesList = mysqli_query($conn, "SELECT * FROM categories WHERE shop_id=$shopId ORDER BY name ASC");

$products = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.shop_id=$shopId 
     ORDER BY c.name ASC, p.name ASC"
);
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
.img-preview{width:100%;height:110px;object-fit:cover;border-radius:8px;margin-top:8px;display:none;}
@media(max-width:900px){.page-grid{grid-template-columns:1fr;}}
</style>
</head><body class="dashboard-body">

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
        <div class="topbar-title"><h1>🎂 My Products</h1><p>Manage your cake listings</p></div>
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
                    <a href="products.php" class="btn btn-primary btn-sm">+ Add New</a>
                </div>
                <table class="data-table">
                    <thead><tr><th>Img</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php mysqli_data_seek($products,0); while($p=mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><img src="<?=htmlspecialchars($p['image_url']?:'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=80&q=60')?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;" onerror="this.src='https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=80&q=60'"></td>
                        <td><strong><?=htmlspecialchars($p['name'])?></strong><div style="font-size:.73rem;color:var(--text-muted)"><?=htmlspecialchars(substr($p['description'],0,40))?>...</div></td>
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
            <!-- Add/Edit Form -->
            <div class="form-card">
                <h2><?=$editProduct?'✏️ Edit Item':'+ Add New Item'?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="product_id" value="<?=$editProduct['id']??0?>">
                    <div class="form-group"><label>Item Name *</label><input type="text" name="name" value="<?=htmlspecialchars($editProduct['name']??'')?>" placeholder="e.g. Chocolate Truffle" required></div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" id="catSelect" onchange="toggleNewCat(this.value)">
                            <option value="0">-- Select Category --</option>
                            <?php 
                            mysqli_data_seek($categoriesList, 0);
                            while($cat = mysqli_fetch_assoc($categoriesList)): 
                                $selected = ($editProduct && $editProduct['category_id'] == $cat['id']) ? 'selected' : '';
                            ?>
                                <option value="<?=$cat['id']?>" <?=$selected?>><?=htmlspecialchars($cat['name'])?></option>
                            <?php endwhile; ?>
                            <option value="new">+ Create New Category</option>
                        </select>
                        <input type="text" name="new_category" id="newCatInput" placeholder="Enter new category name..." style="display:none; margin-top:8px;">
                    </div>

                    <div class="form-group"><label>Description</label><textarea name="description" rows="3" placeholder="Describe the item..." style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;"><?=htmlspecialchars($editProduct['description']??'')?></textarea></div>
                    <div class="form-group"><label>Price (₹) *</label><input type="number" name="price" step="0.01" min="0" value="<?=$editProduct['price']??''?>" placeholder="550.00" required></div>
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="url" name="image_url" id="imgUrl" value="<?=htmlspecialchars($editProduct['image_url']??'')?>" placeholder="https://..." oninput="previewImg(this.value)">
                        <img id="imgPreview" class="img-preview" src="<?=htmlspecialchars($editProduct['image_url']??'')?>" style="<?=$editProduct&&$editProduct['image_url']?'display:block':'display:none'?>">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox" name="is_available" id="avail" <?=(!$editProduct||$editProduct['is_available'])?'checked':''?>>
                        <label for="avail" style="text-transform:none;font-size:.9rem;">Available / In Stock</label>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="btn btn-primary"><?=$editProduct?'💾 Save Changes':'+ Add Item'?></button>
                        <?php if($editProduct):?><a href="products.php" class="btn btn-outline">Cancel</a><?php endif;?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function previewImg(url){const img=document.getElementById('imgPreview');if(url){img.src=url;img.style.display='block';img.onerror=()=>img.style.display='none';}else{img.style.display='none';}}

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
</script>
</body></html>
