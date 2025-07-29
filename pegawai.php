<?php 
  require 'connection.php';
  checkLogin();
  $pegawai = mysqli_query($conn, "SELECT * FROM pegawai ORDER BY nama_pegawai ASC");
  if (isset($_POST['btnEditpegawai'])) {
    if (editpegawai($_POST) > 0) {
      setAlert("pegawai has been changed", "Successfully changed", "success");
      header("Location: pegawai.php");
    }
  }

  if (isset($_POST['btnTambahpegawai'])) {
    if (addpegawai($_POST) > 0) {
      setAlert("pegawai has been added", "Successfully added", "success");
      header("Location: pegawai.php");
    }
  }
  if (isset($_GET['toggle_modal'])) {
    $toggle_modal = $_GET['toggle_modal'];
    echo "
    <script>
      $(document).ready(function() {
        $('#$toggle_modal').modal('show');
      });
    </script>
    ";
  }

  // Query dengan filter sekolah
  $filter_sekolah = isset($_GET['filter_sekolah']) ? $_GET['filter_sekolah'] : '';
  $sql = "SELECT * FROM pegawai";

  if(!empty($filter_sekolah)) {
      $sql .= " WHERE unit_kerja = '$filter_sekolah'";
  }

  $sql .= " ORDER BY nama_pegawai ASC";
  $pegawai = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
  <?php include 'include/css.php'; ?>
  <title>Pegawai</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  
  <?php include 'include/navbar.php'; ?>

  <?php include 'include/sidebar.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <!-- Kolom untuk Judul -->
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Pegawai</h1>
          </div>
          
          <!-- Kolom untuk Filter dan Tombol -->
          <div class="col-sm-6">
            <div class="row justify-content-end">
              <!-- Filter Sekolah -->
              <div class="col-auto">
                <form method="get" action="" class="form-inline">
                  <div class="input-group input-group-xl">
                    <select name="filter_sekolah" class="form-control">
                      <option value="">- Semua Unit Kerja -</option>
                      <option value="KANTOR YPS" <?= isset($_GET['filter_sekolah']) && $_GET['filter_sekolah'] == 'KANTOR YPS' ? 'selected' : '' ?>>KANTOR YPS</option>
                      <option value="TK YPS" <?= isset($_GET['filter_sekolah']) && $_GET['filter_sekolah'] == 'TK YPS' ? 'selected' : '' ?>>TK YPS</option>
                      <option value="SD 1 YPS" <?= isset($_GET['filter_sekolah']) && $_GET['filter_sekolah'] == 'SD 1 YPS' ? 'selected' : '' ?>>SD 1 YPS</option>
                      <option value="SD 2 YPS" <?= isset($_GET['filter_sekolah']) && $_GET['filter_sekolah'] == 'SD 2 YPS' ? 'selected' : '' ?>>SD 2 YPS</option>
                      <option value="SMP YPS" <?= isset($_GET['filter_sekolah']) && $_GET['filter_sekolah'] == 'SMP YPS' ? 'selected' : '' ?>>SMP YPS</option>
                      <option value="SMK YPS" <?= isset($_GET['filter_sekolah']) && $_GET['filter_sekolah'] == 'SMK YPS' ? 'selected' : '' ?>>SMK YPS</option>
                    </select>
                    <div class="input-group-append">
                      <button class="btn btn-primary" type="submit">
                        <i class="fas fa-filter"></i> Filter
                      </button>
                      <?php if(isset($_GET['filter_sekolah'])): ?>
                        <a href="pegawai.php" class="btn btn-danger">
                          <i class="fas fa-times"></i>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>
              </div>
              
              <!-- Tombol Tambah Pegawai -->
              <?php if ($_SESSION['id_jabatan'] !== '3'): ?>
                <div class="col-auto">
                  <button type="button" class="btn btn-primary btn-xl" data-toggle="modal" data-target="#tambahpegawaiModal">
                    <i class="fas fa-plus"></i> Tambah Pegawai
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg">
            <div class="table-responsive">
              <table class="table table-striped table-hover table-bordered" id="table_id">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama Pegawai</th>
                    <th>Jenis Kelamin</th>
                    <th>Unit Kerja</th>
                    <th>No. Telepon</th>
                    <th>Email</th>
                    <?php if ($_SESSION['id_jabatan'] !== '3'): ?>
                      <th>Aksi</th>
                    <?php endif ?>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1; ?>
                  <?php foreach ($pegawai as $ds): ?>
                    <tr>
                      <td><?= $i++; ?></td>
                      <td><?= ucwords(htmlspecialchars_decode($ds['nama_pegawai'])); ?></td>
                      <td><?= ucwords($ds['jenis_kelamin']); ?></td>
                      <td><?= ucwords($ds['unit_kerja']); ?></td>
                      <td><?= $ds['no_telepon']; ?></td>
                      <td><?= $ds['email']; ?></td>
                      <?php if ($_SESSION['id_jabatan'] !== '3'): ?>
                        <td>
                          <!-- Button trigger modal -->
                          <a href="ubah_pegawai.php?id_pegawai=<?= $ds['id_pegawai']; ?>" class="badge badge-success" data-toggle="modal" data-target="#editpegawai<?= $ds['id_pegawai']; ?>">
                            <i class="fas fa-fw fa-edit"></i> Ubah
                          </a>
                          <!-- Modal -->
                          <div class="modal fade" id="editpegawai<?= $ds['id_pegawai']; ?>" tabindex="-1" role="dialog" aria-labelledby="editpegawai<?= $ds['id_pegawai']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                              <form method="post">
                                <input type="hidden" name="id_pegawai" value="<?= $ds['id_pegawai']; ?>">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title" id="editpegawaiModalLabel<?= $ds['id_pegawai']; ?>">Ubah pegawai</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                    </button>
                                  </div>
                                  <div class="modal-body">
                                    <div class="form-group">
                                      <label for="nama_pegawai<?= $ds['id_pegawai']; ?>">Nama pegawai</label>
                                      <input type="text" id="nama_pegawai<?= $ds['id_pegawai']; ?>" value="<?= $ds['nama_pegawai']; ?>" name="nama_pegawai" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                      <label>Jenis Kelamin</label><br>
                                      <?php if ($ds['jenis_kelamin'] == 'pria'): ?>
                                        <input type="radio" id="pria<?= $ds['id_pegawai']; ?>" name="jenis_kelamin" value="pria" checked="checked"> <label for="pria<?= $ds['id_pegawai']; ?>">Pria</label> |
                                        <input type="radio" id="wanita<?= $ds['id_pegawai']; ?>" name="jenis_kelamin" value="wanita"> <label for="wanita<?= $ds['id_pegawai']; ?>">Wanita</label>
                                      <?php else: ?>
                                        <input type="radio" id="pria<?= $ds['id_pegawai']; ?>" name="jenis_kelamin" value="pria"> <label for="pria<?= $ds['id_pegawai']; ?>">Pria</label> |
                                        <input type="radio" id="wanita<?= $ds['id_pegawai']; ?>" name="jenis_kelamin" value="wanita" checked="checked"> <label for="wanita<?= $ds['id_pegawai']; ?>">Wanita</label>
                                      <?php endif ?>
                                    </div>
                                    <div class="form-group">
                                          <label for="unit_kerja_<?= $ds['id_pegawai']; ?>">Unit Kerja</label><br>
                                          <select id="unit_kerja_<?= $ds['id_pegawai']; ?>" name="unit_kerja" class="form-control">
                                            <option value="KANTOR YPS" <?= ($ds['unit_kerja'] == 'KANTOR YPS') ? 'selected' : ''; ?>>KANTOR YPS</option>
                                            <option value="TK YPS" <?= ($ds['unit_kerja'] == 'TK YPS') ? 'selected' : ''; ?>>TK YPS</option>
                                            <option value="SD 1 YPS" <?= ($ds['unit_kerja'] == 'SD 1 YPS') ? 'selected' : ''; ?>>SD 1 YPS</option>
                                            <option value="SD 2 YPS" <?= ($ds['unit_kerja'] == 'SD 2 YPS') ? 'selected' : ''; ?>>SD 2 YPS</option>
                                            <option value="SMP YPS" <?= ($ds['unit_kerja'] == 'SMP YPS') ? 'selected' : ''; ?>>SMP YPS</option>
                                            <option value="SMK YPS" <?= ($ds['unit_kerja'] == 'SMK YPS') ? 'selected' : ''; ?>>SMK YPS</option>
                                          </select>
                                        </div>
                                    <div class="form-group">
                                      <label for="no_telepon<?= $ds['id_pegawai']; ?>">No. Telepon</label>
                                      <input type="number" name="no_telepon" value="<?= $ds['no_telepon']; ?>" id="no_telepon<?= $ds['id_pegawai']; ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                      <label for="email<?= $ds['id_pegawai']; ?>">Email</label>
                                      <input type="email" name="email" value="<?= $ds['email']; ?>" id="email<?= $ds['id_pegawai']; ?>" class="form-control">
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-fw fa-times"></i> Close</button>
                                    <button type="submit" class="btn btn-primary" name="btnEditpegawai"><i class="fas fa-fw fa-save"></i> Save</button>
                                  </div>
                                </div>
                              </form>
                            </div>
                          </div>
                          <?php if ($_SESSION['id_jabatan'] == '1'): ?>
                            <a data-nama="<?= $ds['nama_pegawai']; ?>" class="btn-delete badge badge-danger" href="hapus_pegawai.php?id_pegawai=<?= $ds['id_pegawai']; ?>"><i class="fas fa-fw fa-trash"></i> Hapus</a>
                          <?php endif ?>
                        </td>
                      <?php endif ?>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
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

<!-- Modal Tambah Pegawai -->
<?php if ($_SESSION['id_jabatan'] !== '3'): ?>
  <div class="modal fade text-left" id="tambahpegawaiModal" tabindex="-1" role="dialog" aria-labelledby="tambahpegawaiModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form method="post">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="tambahpegawaiModalLabel">Tambah Pegawai</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="nama_pegawai">Nama Pegawai</label>
              <input type="text" id="nama_pegawai" name="nama_pegawai" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Jenis Kelamin</label><br>
              <input type="radio" id="pria" name="jenis_kelamin" value="pria"> <label for="pria">Pria</label> |
              <input type="radio" id="wanita" name="jenis_kelamin" value="wanita"> <label for="wanita">Wanita</label>
            </div>
            <div class="form-group">
              <label for="unit_kerja_<?= $ds['id_pegawai']; ?>">Unit Kerja</label><br>
              <select id="unit_kerja_<?= $ds['id_pegawai']; ?>" name="unit_kerja" class="form-control">
                <option value="">-- Pilih Unit Kerja --</option>
                <option value="KANTOR YPS" <?= ($ds['unit_kerja'] == 'KANTOR YPS') ? 'selected' : ''; ?>>KANTOR YPS</option>
                <option value="TK YPS" <?= ($ds['unit_kerja'] == 'TK YPS') ? 'selected' : ''; ?>>TK YPS</option>
                <option value="SD 1 YPS" <?= ($ds['unit_kerja'] == 'SD 1 YPS') ? 'selected' : ''; ?>>SD 1 YPS</option>
                <option value="SD 2 YPS" <?= ($ds['unit_kerja'] == 'SD 2 YPS') ? 'selected' : ''; ?>>SD 2 YPS</option>
                <option value="SMP YPS" <?= ($ds['unit_kerja'] == 'SMP YPS') ? 'selected' : ''; ?>>SMP YPS</option>
                <option value="SMK YPS" <?= ($ds['unit_kerja'] == 'SMK YPS') ? 'selected' : ''; ?>>SMK YPS</option>
              </select>
            </div>
            <div class="form-group">
              <label for="no_telepon">No. Telepon</label>
              <input type="number" name="no_telepon" id="no_telepon" class="form-control">
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" name="email" id="email" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-fw fa-times"></i> Close</button>
            <button type="submit" class="btn btn-primary" name="btnTambahpegawai"><i class="fas fa-fw fa-save"></i> Save</button>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>