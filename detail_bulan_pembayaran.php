<?php 
require 'connection.php';
checkLogin();

// Validate and get id_bulan_pembayaran
$id_bulan_pembayaran = $_GET['id_bulan_pembayaran'] ?? null;
if (!$id_bulan_pembayaran || !is_numeric($id_bulan_pembayaran)) {
    die("ID Bulan Pembayaran tidak valid!");
}

// Get month details with validation
$detail_bulan_pembayaran = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bulan_pembayaran WHERE id_bulan_pembayaran = '$id_bulan_pembayaran'"));
if (!$detail_bulan_pembayaran) {
    die("Data bulan pembayaran tidak ditemukan!");
}

$pembayaran_perbulan = $detail_bulan_pembayaran['pembayaran_perminggu']; // Using monthly value directly

// Handle filter
$filter_sekolah = $_GET['filter_sekolah'] ?? '';

// Base query for employees
$sql_pegawai = "SELECT * FROM pegawai";
if (!empty($filter_sekolah)) {
    $sql_pegawai .= " WHERE unit_kerja = '$filter_sekolah'";
}
$sql_pegawai .= " ORDER BY nama_pegawai ASC";
$pegawai = mysqli_query($conn, $sql_pegawai);

// Query for new employees (not in uang_kas)
$pegawai_baru = mysqli_query($conn, "SELECT * FROM pegawai WHERE id_pegawai NOT IN (SELECT id_pegawai FROM uang_kas) ORDER BY nama_pegawai ASC");

// Main payment query with filter
$sql_uang_kas = "SELECT uk.*, p.nama_pegawai, p.unit_kerja, bp.nama_bulan, bp.tahun 
                 FROM uang_kas uk
                 INNER JOIN pegawai p ON uk.id_pegawai = p.id_pegawai
                 INNER JOIN bulan_pembayaran bp ON uk.id_bulan_pembayaran = bp.id_bulan_pembayaran
                 WHERE uk.id_bulan_pembayaran = '$id_bulan_pembayaran'";

if (!empty($filter_sekolah)) {
    $sql_uang_kas .= " AND p.unit_kerja = '$filter_sekolah'";
}

$sql_uang_kas .= " ORDER BY p.nama_pegawai ASC";
$uang_kas = mysqli_query($conn, $sql_uang_kas);

// Get first payment month
$bulan_pembayaran_pertama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bulan_pembayaran ORDER BY id_bulan_pembayaran ASC LIMIT 1")); 
$id_bulan_pembayaran_pertama = $bulan_pembayaran_pertama['id_bulan_pembayaran'] ?? 1;

// Previous month logic
$id_bulan_pembayaran_sebelum = $id_bulan_pembayaran - 1;
if ($id_bulan_pembayaran_sebelum <= 0) {
    $id_bulan_pembayaran_sebelum = 1;
}

// Payment edit logic
if (isset($_POST['btnEditPembayaranUangKas'])) {
    // Check if user has permission
    if ($_SESSION['id_jabatan'] == '1' || $_SESSION['id_jabatan'] == '2') {
        $id_uang_kas = $_POST['id_uang_kas'];
        $jumlah_pembayaran = $_POST['jumlah_pembayaran'];
        
        // Get current payment data
        $current_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM uang_kas WHERE id_uang_kas = '$id_uang_kas'"));
        $total_paid = $current_data['minggu_ke_1'] ?? 0;
        
        // Check previous month payment status
        $prev_month_paid = true;
        if ($current_data['id_bulan_pembayaran'] != $id_bulan_pembayaran_pertama) {
            $prev_month_query = mysqli_query($conn, "SELECT status_lunas FROM uang_kas 
                                                   WHERE id_pegawai = '".$current_data['id_pegawai']."' 
                                                   AND id_bulan_pembayaran = ".($current_data['id_bulan_pembayaran'] - 1));
            $prev_month_data = mysqli_fetch_assoc($prev_month_query);
            $prev_month_paid = ($prev_month_data['status_lunas'] ?? '0') == '1';
        }
        
        // Calculate new total
        $new_total = $total_paid + $jumlah_pembayaran;
        
        // Update status
        $status_lunas = ($new_total >= $pembayaran_perbulan) && $prev_month_paid ? '1' : '0';
        
        // Update database
        $query = "UPDATE uang_kas SET 
                  minggu_ke_1 = '$new_total',
                  status_lunas = '$status_lunas'
                  WHERE id_uang_kas = '$id_uang_kas'";
        
        if (mysqli_query($conn, $query)) {
            setAlert("Pembayaran berhasil diupdate", "Berhasil", "success");
            header("Location: detail_bulan_pembayaran.php?id_bulan_pembayaran=$id_bulan_pembayaran");
        }
    } else {
        setAlert("Anda tidak memiliki hak akses", "Gagal", "error");
        header("Location: detail_bulan_pembayaran.php?id_bulan_pembayaran=$id_bulan_pembayaran");
    }
}

// Add employee logic
if (isset($_POST['btnTambahpegawai'])) {
    if ($_SESSION['id_jabatan'] == '1' || $_SESSION['id_jabatan'] == '2') {
        if (tambahpegawaiUangKas($_POST) > 0) {
            setAlert("Pegawai has been added", "Successfully added", "success");
            header("Location: detail_bulan_pembayaran.php?id_bulan_pembayaran=$id_bulan_pembayaran");
        }
    } else {
        setAlert("Anda tidak memiliki hak akses", "Gagal", "error");
        header("Location: detail_bulan_pembayaran.php?id_bulan_pembayaran=$id_bulan_pembayaran");
    }
}

// Calculate totals for sidebar
$total_kas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(minggu_ke_1) as total FROM uang_kas"));
$total_pengeluaran = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah_pengeluaran) as total FROM pengeluaran"));
$_SESSION['jml_uang_kas'] = $total_kas['total'] ?? 0;
$_SESSION['jml_pengeluaran'] = $total_pengeluaran['total'] ?? 0;
$_SESSION['sisa_uang'] = $_SESSION['jml_uang_kas'] - $_SESSION['jml_pengeluaran'];
?>

<!DOCTYPE html>
<html>
<head>
  <?php include 'include/css.php'; ?>
  <title>Detail Bulan Pembayaran : <?= ucwords($detail_bulan_pembayaran['nama_bulan'] ?? 'N/A'); ?> <?= $detail_bulan_pembayaran['tahun'] ?? ''; ?></title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  
  <?php include 'include/navbar.php'; ?>
  <?php include 'include/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm">
            <h1 class="m-0 text-dark">Detail Bulan Pembayaran : <?= ucwords($detail_bulan_pembayaran['nama_bulan'] ?? 'N/A'); ?> <?= $detail_bulan_pembayaran['tahun'] ?? ''; ?></h1>
            <h4>Rp. <?= number_format($pembayaran_perbulan); ?> / bulan</h4>
          </div>
          <div class="">
            <div class="row justify-content-end">
              <!-- Filter Form -->
              <div class="col-auto">
                <form method="get" action="">
                  <input type="hidden" name="id_bulan_pembayaran" value="<?= $id_bulan_pembayaran ?>">
                  <div class="input-group input-group-xl">
                    <select name="filter_sekolah" class="form-control">
                      <option value="">- Semua Unit Kerja -</option>
                      <option value="KANTOR YPS" <?= $filter_sekolah == 'KANTOR YPS' ? 'selected' : '' ?>>KANTOR YPS</option>
                      <option value="TK YPS" <?= $filter_sekolah == 'TK YPS' ? 'selected' : '' ?>>TK YPS</option>
                      <option value="SD 1 YPS" <?= $filter_sekolah == 'SD 1 YPS' ? 'selected' : '' ?>>SD 1 YPS</option>
                      <option value="SD 2 YPS" <?= $filter_sekolah == 'SD 2 YPS' ? 'selected' : '' ?>>SD 2 YPS</option>
                      <option value="SMP YPS" <?= $filter_sekolah == 'SMP YPS' ? 'selected' : '' ?>>SMP YPS</option>
                      <option value="SMK YPS" <?= $filter_sekolah == 'SMK YPS' ? 'selected' : '' ?>>SMK YPS</option>
                    </select>
                    <div class="input-group-append">
                      <button class="btn btn-primary" type="submit">
                        <i class="fas fa-filter"></i> Filter
                      </button>
                      <?php if(!empty($filter_sekolah)): ?>
                        <a href="detail_bulan_pembayaran.php?id_bulan_pembayaran=<?= $id_bulan_pembayaran ?>" class="btn btn-danger">
                          <i class="fas fa-times"></i>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>
              </div>
              <div class="col-sm text-right">
                <?php if ($_SESSION['id_jabatan'] == '1' || $_SESSION['id_jabatan'] == '2'): ?>
                  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tambahpegawaiModal">
                    <i class="fas fa-fw fa-plus"></i> Tambah Pegawai
                  </button>
                <?php endif ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid bg-white p-3 rounded">
        <div class="table-responsive">
          <table class="table table-hover table-striped table-bordered" id="table_id">
            <thead>
              <tr>
                <th>No.</th>
                <th>Nama Pegawai</th>
                <th>Unit Kerja</th>
                <th>Total Pembayaran</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($uang_kas) > 0): ?>
                <?php $i = 1; ?>
                <?php while ($duk = mysqli_fetch_assoc($uang_kas)): ?>
                  <?php 
                    $total_paid = $duk['minggu_ke_1'] ?? 0;
                    
                    // Check previous month payment status
                    $prev_month_paid = true;
                    if ($id_bulan_pembayaran != $id_bulan_pembayaran_pertama) {
                        $prev_month_query = mysqli_query($conn, "SELECT status_lunas FROM uang_kas 
                                                               WHERE id_pegawai = '".$duk['id_pegawai']."' 
                                                               AND id_bulan_pembayaran = $id_bulan_pembayaran_sebelum");
                        $prev_month_data = mysqli_fetch_assoc($prev_month_query);
                        $prev_month_paid = ($prev_month_data['status_lunas'] ?? '0') == '1';
                    }
                    
                    $is_paid_full = ($total_paid >= $pembayaran_perbulan) && $prev_month_paid;
                  ?>

                  <tr>
                    <td><?= $i++; ?></td>
                    <td><?= ucwords(htmlspecialchars_decode($duk['nama_pegawai'] ?? '')); ?></td>
                    <td><?= $duk['unit_kerja'] ?? ''; ?></td>
                    <td>Rp. <?= number_format($total_paid); ?> / Rp. <?= number_format($pembayaran_perbulan); ?></td>
                    <td>
                      <?php if ($is_paid_full): ?>
                        <span class="badge badge-success">Lunas</span>
                      <?php elseif ($total_paid >= $pembayaran_perbulan && !$prev_month_paid): ?>
                        <span class="badge badge-warning">Lunas (Bulan Sebelumnya Belum)</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Belum Lunas</span>
                      <?php endif ?>
                    </td>
                    <td>
                      <?php if ($_SESSION['id_jabatan'] == '1' || $_SESSION['id_jabatan'] == '2'): ?>
                        <?php if (!$prev_month_paid && $id_bulan_pembayaran != $id_bulan_pembayaran_pertama): ?>
                          <button class="btn btn-sm btn-secondary" disabled title="Harus lunas bulan sebelumnya dulu">Bayar</button>
                        <?php else: ?>
                          <a href="" data-toggle="modal" data-target="#editPembayaran<?= $duk['id_uang_kas'] ?? ''; ?>" class="btn btn-sm btn-primary">Bayar</a>
                        <?php endif; ?>
                      <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled title="Hanya untuk Admin dan Bendahara">Bayar</button>
                      <?php endif; ?>
                    </td>
                  </tr>

                  <!-- Edit Payment Modal -->
                  <div class="modal fade" id="editPembayaran<?= $duk['id_uang_kas'] ?? ''; ?>" tabindex="-1" role="dialog" aria-labelledby="editPembayaranLabel<?= $duk['id_uang_kas'] ?? ''; ?>" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <form method="post">
                        <input type="hidden" name="id_uang_kas" value="<?= $duk['id_uang_kas'] ?? ''; ?>">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="editPembayaranLabel<?= $duk['id_uang_kas'] ?? ''; ?>">Pembayaran: <?= $duk['nama_pegawai'] ?? ''; ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label>Total Dibayar: Rp. <?= number_format($total_paid); ?></label>
                            </div>
                            <div class="form-group">
                              <label>Total Harus Dibayar: Rp. <?= number_format($pembayaran_perbulan); ?></label>
                            </div>
                            <?php if (!$prev_month_paid && $id_bulan_pembayaran != $id_bulan_pembayaran_pertama): ?>
                              <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Pembayaran bulan sebelumnya belum lunas!
                              </div>
                            <?php endif; ?>
                            <div class="form-group">
                              <label for="jumlah_pembayaran">Jumlah Pembayaran</label>
                              <input type="number" name="jumlah_pembayaran" class="form-control" min="0" max="<?= $pembayaran_perbulan - $total_paid; ?>" value="<?= $pembayaran_perbulan - $total_paid; ?>">
                              <small class="text-muted">Sisa yang harus dibayar: Rp. <?= number_format($pembayaran_perbulan - $total_paid); ?></small>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-fw fa-times"></i> Close</button>
                            <button type="submit" name="btnEditPembayaranUangKas" class="btn btn-primary" <?= ((!$prev_month_paid && $id_bulan_pembayaran != $id_bulan_pembayaran_pertama) || ($_SESSION['id_jabatan'] != '1' && $_SESSION['id_jabatan'] != '2')) ? 'disabled' : '' ?>>
                              <i class="fas fa-fw fa-save"></i> Simpan
                            </button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center">
                    Tidak ada data ditemukan<?= !empty($filter_sekolah) ? " untuk unit kerja $filter_sekolah" : '' ?>.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <!-- Add Employee Modal -->
  <?php if ($_SESSION['id_jabatan'] == '1' || $_SESSION['id_jabatan'] == '2'): ?>
    <div class="modal fade" id="tambahpegawaiModal" tabindex="-1" role="dialog" aria-labelledby="tambahpegawaiModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <form method="post">
          <input type="hidden" name="id_bulan_pembayaran" value="<?= $id_bulan_pembayaran; ?>">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="tambahpegawaiModalLabel">Tambah Pegawai</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="id_pegawai">Nama Pegawai</label>
                <select name="id_pegawai" id="id_pegawai" class="form-control">
                  <?php while ($dsb = mysqli_fetch_assoc($pegawai_baru)): ?>
                    <option value="<?= $dsb['id_pegawai']; ?>"><?= $dsb['nama_pegawai']; ?></option>
                  <?php endwhile ?>
                </select>
                <a href="pegawai.php?toggle_modal=tambahpegawaiModal">Tidak ada nama Pegawai diatas? Tambahkan Pegawai disini!</a>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-fw fa-times"></i> Close</button>
              <button type="submit" name="btnTambahpegawai" class="btn btn-primary"><i class="fas fa-fw fa-save"></i> Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php endif ?>

  <!-- Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; By Dodi Saputra.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.0
    </div>
  </footer>
</div>
</body>
</html>