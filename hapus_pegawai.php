<?php 
	require 'connection.php';
	$id_pegawai = $_GET['id_pegawai'];
	if (isset($id_pegawai)) {
		if (deletepegawai($id_pegawai) > 0) {
			setAlert("pegawai has been deleted", "Successfully deleted", "success");
		    header("Location: pegawai.php");
	    }
	} else {
	   header("Location: pegawai.php");
	}