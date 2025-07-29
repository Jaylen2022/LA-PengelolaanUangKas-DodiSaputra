<?php 
require 'connection.php';
checkLogin();

// Get payment months
$bulan_pembayaran = mysqli_query($conn, "SELECT * FROM bulan_pembayaran ORDER BY id_bulan_pembayaran DESC");

// Get all work units for filter (only for income report)
$unit_kerja_query = mysqli_query($conn, "SELECT DISTINCT unit_kerja FROM pegawai WHERE unit_kerja != '' ORDER BY unit_kerja");
$unit_kerja_options = [];
while($unit = mysqli_fetch_assoc($unit_kerja_query)) {
    $unit_kerja_options[] = $unit;
}

// Income Report
if (isset($_POST['btnLaporanPemasukkan'])) {
    $id_bulan_pembayaran = htmlspecialchars($_POST['id_bulan_pembayaran']);
    $filter_unit = $_POST['filter_unit'] ?? '';
    
    // Get month details
    $month_data = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM bulan_pembayaran WHERE id_bulan_pembayaran = '$id_bulan_pembayaran'"));
    
    // Build payment query with optional unit filter
    $payment_query = "SELECT p.nama_pegawai, p.unit_kerja, 
                uk.minggu_ke_1, uk.minggu_ke_2, uk.minggu_ke_3, uk.minggu_ke_4,
                uk.pembayaran_perminggu
         FROM uang_kas uk
         JOIN pegawai p ON uk.id_pegawai = p.id_pegawai
         WHERE uk.id_bulan_pembayaran = '$id_bulan_pembayaran'";
    
    if (!empty($filter_unit)) {
        $payment_query .= " AND p.unit_kerja = '$filter_unit'";
    }
    
    $payment_query .= " ORDER BY p.nama_pegawai ASC";
    
    $payment_data = mysqli_query($conn, $payment_query);
    
    // Calculate total income
    $total_income = 0;
    $payment_rows = [];
    while($row = mysqli_fetch_assoc($payment_data)) {
        $row['total'] = $row['minggu_ke_1'] + $row['minggu_ke_2'] + 
                       $row['minggu_ke_3'] + $row['minggu_ke_4'];
        $total_income += $row['total'];
        $payment_rows[] = $row;
    }
}

// Expense Report
if (isset($_POST['btnLaporanPengeluaran'])) {
    $dari_tanggal = htmlspecialchars($_POST['dari_tanggal']);
    $sampai_tanggal = htmlspecialchars($_POST['sampai_tanggal']);
    
    // Convert to timestamps
    $start_date = strtotime($dari_tanggal . " 00:00:00");
    $end_date = strtotime($sampai_tanggal . " 23:59:59");
    
    // Build expense query
    $expense_query = "SELECT p.jumlah_pengeluaran, p.keterangan, 
                p.tanggal_pengeluaran, u.username
         FROM pengeluaran p
         JOIN user u ON p.id_user = u.id_user
         WHERE p.tanggal_pengeluaran BETWEEN '$start_date' AND '$end_date'
         ORDER BY p.tanggal_pengeluaran ASC";
    
    $expense_data = mysqli_query($conn, $expense_query);
    
    // Calculate total expenses
    $total_expense = 0;
    $expense_rows = [];
    while($row = mysqli_fetch_assoc($expense_data)) {
        $total_expense += $row['jumlah_pengeluaran'];
        $row['tanggal_formatted'] = date('d-m-Y H:i', $row['tanggal_pengeluaran']);
        $expense_rows[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <?php include 'include/css.php'; ?>
  <title>Laporan Keuangan</title>
  <style>
    @media print {
      .not-printed { 
        display: none !important; 
      }
      .total { 
        color: black !important; 
      }
      body { 
        background: white; 
      }
      .table { 
        width: 100%; 
      }
      .report-header, .summary-card {
        page-break-after: avoid;
      }
      table {
        page-break-inside: auto;
      }
      tr {
        page-break-inside: avoid;
        page-break-after: auto;
      }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  
  <?php include 'include/navbar.php'; ?>
  <?php include 'include/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header not-printed">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm">
            <h1 class="m-0 text-dark">Laporan Keuangan</h1>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Report Selection Forms -->
        <div class="not-printed row justify-content-center">
          <!-- Income Report Form -->
          <div class="col-lg-5 mr-4 mb-4">
            <div class="card">
              <div class="card-header bg-primary text-white">
                <h3 class="card-title">Laporan Pemasukan</h3>
              </div>
              <div class="card-body">
                <form method="post">
                  <div class="form-group">
                    <label>Pilih Bulan Pembayaran</label>
                    <select name="id_bulan_pembayaran" class="form-control" required>
                      <?php foreach ($bulan_pembayaran as $month): ?>
                        <option value="<?= $month['id_bulan_pembayaran'] ?>" 
                          <?= isset($month_data) && $month_data['id_bulan_pembayaran'] == $month['id_bulan_pembayaran'] ? 'selected' : '' ?>>
                          <?= ucwords($month['nama_bulan']) ?> <?= $month['tahun'] ?> 
                          (Rp <?= number_format($month['pembayaran_perminggu']) ?>/minggu)
                        </option>
                      <?php endforeach ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Filter Unit Kerja</label>
                    <select name="filter_unit" class="form-control">
                      <option value="">Semua Unit Kerja</option>
                      <?php foreach ($unit_kerja_options as $unit): ?>
                        <option value="<?= $unit['unit_kerja'] ?>" 
                          <?= isset($filter_unit) && $filter_unit == $unit['unit_kerja'] ? 'selected' : '' ?>>
                          <?= $unit['unit_kerja'] ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <button type="submit" name="btnLaporanPemasukkan" class="btn btn-primary">
                    <i class="fas fa-file-alt mr-1"></i> Tampilkan Laporan
                  </button>
                </form>
              </div>
            </div>
          </div>

          <!-- Expense Report Form -->
          <div class="col-lg-5 ml-4 mb-4">
            <div class="card">
              <div class="card-header bg-info text-white">
                <h3 class="card-title">Laporan Pengeluaran</h3>
              </div>
              <div class="card-body">
                <form method="post">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari_tanggal" class="form-control" 
                               value="<?= isset($dari_tanggal) ? $dari_tanggal : date('Y-m-01') ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai_tanggal" class="form-control" 
                               value="<?= isset($sampai_tanggal) ? $sampai_tanggal : date('Y-m-d') ?>" required>
                      </div>
                    </div>
                  </div>
                  <button type="submit" name="btnLaporanPengeluaran" class="btn btn-info">
                    <i class="fas fa-file-alt mr-1"></i> Tampilkan Laporan
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Income Report -->
        <?php if (isset($_POST['btnLaporanPemasukkan']) && !empty($payment_rows)): ?>
          <div class="not-printed mb-3">
            <button onclick="window.print()" class="btn btn-success">
              <i class="fas fa-print mr-1"></i> Cetak Laporan
            </button>
          </div>
          
          <div class="report-header">
            <h2 class="text-center">Laporan Pemasukan</h2>
            <div class="summary-card">
              <h5>Ringkasan:</h5>
              <p>Bulan: <?= ucwords($month_data['nama_bulan']) ?> <?= $month_data['tahun'] ?></p>
              <?php if (!empty($filter_unit)): ?>
                <p>Unit Kerja: <?= htmlspecialchars($filter_unit) ?></p>
              <?php endif; ?>
              <p>Total pemasukan: <strong>Rp <?= number_format($total_income) ?></strong></p>
              <p>Jumlah pegawai: <?= count($payment_rows) ?></p>
              <p>Pembayaran perminggu: Rp <?= number_format($month_data['pembayaran_perminggu']) ?></p>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="thead-dark">
                <tr>
                  <th>No</th>
                  <th>Nama Pegawai</th>
                  <th>Unit Kerja</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payment_rows as $i => $row): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                    <td><?= htmlspecialchars($row['unit_kerja']) ?></td>
                    <td>Rp <?= number_format($row['total']) ?></td>
                  </tr>
                <?php endforeach ?>
              </tbody>
              <tfoot>
                <tr class="bg-light">
                  <td colspan="3" class="text-left"><strong>Total Pemasukan:</strong></td>
                  <td colspan="5"><strong>Rp <?= number_format($total_income) ?></strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php elseif (isset($_POST['btnLaporanPemasukkan'])): ?>
          <div class="alert alert-warning">
            Tidak ada data pemasukan yang ditemukan untuk kriteria yang dipilih.
          </div>
        <?php endif; ?>

        <!-- Expense Report -->
        <?php if (isset($_POST['btnLaporanPengeluaran']) && !empty($expense_rows)): ?>
          <div class="not-printed mb-3">
            <button onclick="window.print()" class="btn btn-success">
              <i class="fas fa-print mr-1"></i> Cetak Laporan
            </button>
          </div>
          
          <div class="report-header">
            <h2 class="text-center">Laporan Pengeluaran</h2>
            <div class="summary-card">
              <h5>Ringkasan:</h5>
              <p>Periode: <?= date('d M Y', strtotime($dari_tanggal)) ?> - <?= date('d M Y', strtotime($sampai_tanggal)) ?></p>
              <p>Total pengeluaran: <strong>Rp <?= number_format($total_expense) ?></strong></p>
              <p>Jumlah transaksi: <?= count($expense_rows) ?></p>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="thead-dark">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Jumlah</th>
                  <th>Keterangan</th>
                  <th>User</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($expense_rows as $i => $row): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= $row['tanggal_formatted'] ?></td>
                    <td>Rp <?= number_format($row['jumlah_pengeluaran']) ?></td>
                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                  </tr>
                <?php endforeach ?>
              </tbody>
              <tfoot>
                <tr class="bg-light">
                  <td colspan="2" class="text-left"><strong>Total Pengeluaran:</strong></td>
                  <td colspan="3"><strong>Rp <?= number_format($total_expense) ?></strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endif ?>
      </div>
    </section>
  </div>
</div>

<script>
$(document).ready(function() {
    // Initialize datepicker if needed
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});
</script>
</body>
</html>