<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('index.php');

$message = '';
$msgType = '';

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $nama    = trim($_POST['nama_barang']);
        $jumlah  = (int)$_POST['jumlah'];
        $harga   = (int)$_POST['harga'];
        $kategori = trim($_POST['kategori']);
        $stmt = $pdo->prepare("INSERT INTO belanja (nama_barang, jumlah, harga, kategori) VALUES (?,?,?,?)");
        $stmt->execute([$nama, $jumlah, $harga, $kategori]);
        $message = 'Data berhasil ditambahkan!'; $msgType = 'success';
    }
    // UPDATE
    elseif ($_POST['action'] === 'update') {
        $id      = (int)$_POST['id'];
        $nama    = trim($_POST['nama_barang']);
        $jumlah  = (int)$_POST['jumlah'];
        $harga   = (int)$_POST['harga'];
        $kategori = trim($_POST['kategori']);
        $stmt = $pdo->prepare("UPDATE belanja SET nama_barang=?, jumlah=?, harga=?, kategori=? WHERE id=?");
        $stmt->execute([$nama, $jumlah, $harga, $kategori, $id]);
        $message = 'Data berhasil diperbarui!'; $msgType = 'success';
    }
    // DELETE
    elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM belanja WHERE id=?")->execute([$id]);
        $message = 'Data berhasil dihapus.'; $msgType = 'success';
    }
}

// READ
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM belanja WHERE nama_barang LIKE ? OR kategori LIKE ?");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM belanja ORDER BY id");
}
$items = $stmt->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM belanja WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editItem = $s->fetch();
}

$total = $pdo->query("SELECT COUNT(*) FROM belanja")->fetchColumn();
$totalHarga = $pdo->query("SELECT SUM(harga * jumlah) FROM belanja")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — List Belanja</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:#f0f4ff; color:#1a1a2e; }
  .topbar {
    background:#fff; padding:16px 32px;
    display:flex; align-items:center; gap:16px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);
  }
  .topbar h1 { flex:1; font-size:20px; }
  .topbar a { font-size:13px; color:#e53935; text-decoration:none; font-weight:600; }
  .content { padding:28px 32px; }
  .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
  .stat {
    background:#fff; border-radius:12px; padding:20px 24px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
  }
  .stat-label { font-size:12px; color:#888; margin-bottom:6px; }
  .stat-val { font-size:24px; font-weight:700; color:#4f8ef7; }
  .card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden; }
  .card-header { padding:18px 24px; display:flex; align-items:center; border-bottom:1px solid #f0f0f0; }
  .card-header h2 { flex:1; font-size:16px; }
  .search-form input {
    padding:8px 14px; border:1px solid #ddd; border-radius:8px;
    font-size:13px; outline:none; margin-right:8px; width:200px;
  }
  .btn-add {
    padding:9px 18px; background:#4f8ef7; color:#fff;
    border:none; border-radius:8px; font-size:13px;
    font-weight:600; cursor:pointer; text-decoration:none;
  }
  table { width:100%; border-collapse:collapse; }
  th { padding:12px 20px; text-align:left; font-size:12px; color:#888; font-weight:600; text-transform:uppercase; background:#fafafa; }
  td { padding:13px 20px; font-size:14px; border-top:1px solid #f0f0f0; }
  .badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; background:#e8f0ff; color:#4f8ef7; }
  .btn-edit { padding:6px 12px; background:#e8f0ff; color:#4f8ef7; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; }
  .btn-del  { padding:6px 12px; background:#fff0f0; color:#e53935; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; }
  .alert { padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:14px; }
  .alert.success { background:#f0fff4; border:1px solid #c3e6cb; color:#2e7d32; }
  .alert.error   { background:#fff0f0; border:1px solid #ffcccc; color:#e53935; }
  .modal-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,0.5);
    display:flex; align-items:center; justify-content:center; z-index:100;
  }
  .modal { background:#fff; border-radius:16px; padding:32px; width:100%; max-width:420px; }
  .modal h2 { font-size:18px; margin-bottom:20px; }
  .field { margin-bottom:14px; }
  .field label { font-size:13px; color:#555; display:block; margin-bottom:6px; }
  .field input, .field select {
    width:100%; padding:10px 14px; border:1px solid #ddd;
    border-radius:8px; font-size:14px; outline:none;
  }
  .modal-btns { display:flex; gap:10px; margin-top:20px; }
  .btn-primary { flex:1; padding:12px; background:#4f8ef7; border:none; border-radius:8px; color:#fff; font-size:14px; font-weight:600; cursor:pointer; }
  .btn-cancel { padding:12px 18px; border:1px solid #ddd; background:none; border-radius:8px; font-size:14px; cursor:pointer; }
</style>
</head>
<body>

<div class="topbar">
  <h1>🛒 List Belanja</h1>
  <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
  <a href="logout.php">Keluar</a>
</div>

<div class="content">
  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= $msgType==='success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="stats">
    <div class="stat"><div class="stat-label">Total Item</div><div class="stat-val"><?= $total ?></div></div>
    <div class="stat"><div class="stat-label">Total Harga</div><div class="stat-val">Rp <?= number_format($totalHarga,0,',','.') ?></div></div>
    <div class="stat"><div class="stat-label">Login Sebagai</div><div class="stat-val" style="font-size:16px"><?= htmlspecialchars($_SESSION['username']) ?></div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2>Data Belanja</h2>
      <form method="GET" class="search-form" style="display:flex;align-items:center;gap:8px;margin-right:12px">
        <input name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" style="padding:8px 14px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;font-size:13px">Cari</button>
      </form>
      <a href="?add=1" class="btn-add">+ Tambah</a>
    </div>
    <table>
      <thead>
        <tr><th>No</th><th>Nama Barang</th><th>Jumlah</th><th>Harga Satuan</th><th>Total</th><th>Kategori</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $i => $item): ?>
        <tr>
          <td style="color:#888"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($item['nama_barang']) ?></strong></td>
          <td><?= $item['jumlah'] ?></td>
          <td>Rp <?= number_format($item['harga'],0,',','.') ?></td>
          <td>Rp <?= number_format($item['harga']*$item['jumlah'],0,',','.') ?></td>
          <td><span class="badge"><?= htmlspecialchars($item['kategori']) ?></span></td>
          <td>
            <a href="?edit=<?= $item['id'] ?>" class="btn-edit">✏️ Edit</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus <?= htmlspecialchars($item['nama_barang']) ?>?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $item['id'] ?>">
              <button type="submit" class="btn-del">🗑️ Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL TAMBAH -->
<?php if (isset($_GET['add'])): ?>
<div class="modal-overlay">
  <div class="modal">
    <h2>➕ Tambah Barang</h2>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="field"><label>Nama Barang</label><input type="text" name="nama_barang" required autofocus></div>
      <div class="field"><label>Jumlah</label><input type="number" name="jumlah" min="1" required></div>
      <div class="field"><label>Harga Satuan</label><input type="number" name="harga" min="0" required></div>
      <div class="field">
        <label>Kategori</label>
        <select name="kategori">
          <option>Buah</option><option>Sayur</option><option>Minuman</option>
          <option>Makanan</option><option>Kebersihan</option><option>Lainnya</option>
        </select>
      </div>
      <div class="modal-btns">
        <a href="dashboard.php" class="btn-cancel">Batal</a>
        <button type="submit" class="btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- MODAL EDIT -->
<?php if ($editItem): ?>
<div class="modal-overlay">
  <div class="modal">
    <h2>✏️ Edit Barang</h2>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
      <div class="field"><label>Nama Barang</label><input type="text" name="nama_barang" value="<?= htmlspecialchars($editItem['nama_barang']) ?>" required></div>
      <div class="field"><label>Jumlah</label><input type="number" name="jumlah" value="<?= $editItem['jumlah'] ?>" min="1" required></div>
      <div class="field"><label>Harga Satuan</label><input type="number" name="harga" value="<?= $editItem['harga'] ?>" min="0" required></div>
      <div class="field">
        <label>Kategori</label>
        <select name="kategori">
          <?php foreach (['Buah','Sayur','Minuman','Makanan','Kebersihan','Lainnya'] as $kat): ?>
            <option <?= $editItem['kategori']===$kat ? 'selected':'' ?>><?= $kat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-btns">
        <a href="dashboard.php" class="btn-cancel">Batal</a>
        <button type="submit" class="btn-primary">Perbarui</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

</body>
</html>