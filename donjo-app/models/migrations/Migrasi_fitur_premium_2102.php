<?php

/**
 * File ini:
 *
 * Model untuk modul database
 *
 * donjo-app/models/migrations/Migrasi_fitur_premium_2101.php
 *
 */

/**
 *
 * File ini bagian dari:
 *
 * OpenSID
 *
 * Sistem informasi desa sumber terbuka untuk memajukan desa
 *
 * Aplikasi dan source code ini dirilis berdasarkan lisensi GPL V3
 *
 * Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * Hak Cipta 2016 - 2020 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:

 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.

 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package	OpenSID
 * @author	Tim Pengembang OpenDesa
 * @copyright	Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright	Hak Cipta 2016 - 2020 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license	http://www.gnu.org/licenses/gpl.html	GPL V3
 * @link 	https://github.com/OpenSID/OpenSID
 */

class Migrasi_fitur_premium_2102 extends MY_model {

	public function up()
	{
		log_message('error', 'Jalankan ' . get_class($this));
		$hasil = true;

		$hasil =& $this->pengaturan_latar($hasil);

		//tambah kolom urut di tabel tweb_wil_clusterdesa
		if (!$this->db->field_exists('urut', 'tweb_wil_clusterdesa'))
			$hasil = $this->dbforge->add_column('tweb_wil_clusterdesa', array(
				'urut' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => TRUE,
				),
			));

		$hasil =& $this->url_suplemen($hasil);
		// Buat folder untuk cache - 'cache\';
		mkdir(config_item('cache_path'), 0775, true);

		$hasil =& $this->create_table_pembangunan($hasil);
		$hasil =& $this->create_table_pembangunan_ref_dokumentasi($hasil);
		$hasil =& $this->add_modul_pembangunan($hasil);
		$hasil =& $this->sebutan_kepala_desa($hasil);
		$hasil =& $this->urut_cetak($hasil);
		$hasil =& $this->bumindes_updates($hasil);

		// Tambah kolom ganti_pin di tabel tweb_penduduk_mandiri
		if ( ! $this->db->field_exists('ganti_pin', 'tweb_penduduk_mandiri'))
		{
			$fields = [
				'ganti_pin' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
			];
			$hasil = $this->dbforge->add_column('tweb_penduduk_mandiri', $fields);
			// Set ulang value ganti_pin = 0 jika last_login sudah terisi
			$hasil =& $this->db
				->where('last_login !=', NULL)
				->set('ganti_pin', 0)
				->update('tweb_penduduk_mandiri');
		}

		status_sukses($hasil);
		return $hasil;
	}

	private function pengaturan_latar($hasil)
	{
		$old = "desa/css";
		$new = "desa/pengaturan";

		if (is_dir($old))
		{
			// Ubah nama folder desa/csss jadi desa/pengaturan
			rename($old, $new);
		}
		// Buat folder untuk latar
		mkdir($new . "/siteman/images", 0775, true);
		mkdir($new . "/klasik/images", 0775, true);
		mkdir($new . "/natra/images", 0775, true);
		return $hasil;
	}

	// Tambahkan clear pada url suplemen
	private function url_suplemen($hasil)
	{
		$hasil =& $this->db->where('id', 25)
			->set('url', 'suplemen/clear')
			->update('setting_modul');
		return $hasil;
	}

	protected function create_table_pembangunan($hasil)
	{
		$this->dbforge->add_field([
			'id'                 => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
			'id_lokasi'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
			'sumber_dana'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'judul'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'keterangan'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'lokasi'             => ['type' => 'VARCHAR','constraint' => 225, 'null' => true],
			'lat'                => ['type' => 'VARCHAR','constraint' => 225, 'null' => true],
			'lng'                => ['type' => 'VARCHAR','constraint' => 255, 'null' => true],
			'volume'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
			'tahun_anggaran'     => ['type' => 'YEAR', 'null' => true],
			'pelaksana_kegiatan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'status'             => ['type' => 'TINYINT', 'constraint' => 3, 'default' => 1],
			'created_at'         => ['type' => 'datetime', 'null' => true],
			'updated_at'         => ['type' => 'datetime', 'null' => true],
		]);

		$this->dbforge->add_key('id', true);
		$this->dbforge->add_key('id_lokasi');
		$hasil =& $this->dbforge->create_table('pembangunan', true);
		return $hasil;
	}

	protected function create_table_pembangunan_ref_dokumentasi($hasil)
	{
		$this->dbforge->add_field([
			'id'             => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
			'id_pembangunan' => ['type' => 'INT', 'constraint' => 11],
			'gambar'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'persentase'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'keterangan'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
			'created_at'     => ['type' => 'datetime', 'null' => true],
			'updated_at'     => ['type' => 'datetime', 'null' => true],
		]);

		$this->dbforge->add_key('id', true);
		$this->dbforge->add_key('id_pembangunan');
		$hasil =& $this->dbforge->create_table('pembangunan_ref_dokumentasi', true);
		return $hasil;
	}

	protected function add_modul_pembangunan($hasil)
	{
		$hasil =& $this->tambah_modul([
			'id'         => 220,
			'modul'      => 'Pembangunan',
			'url'        => 'pembangunan',
			'aktif'      => 1,
			'ikon'       => 'fa-institution',
			'urut'       => 9,
			'level'      => 2,
			'hidden'     => 0,
			'ikon_kecil' => 'fa-institution',
			'parent'     => 0
		]);

		$hasil =& $this->tambah_modul([
			'id'         => 221,
			'modul'      => 'Pembangunan Dokumentasi',
			'url'        => 'pembangunan_dokumentasi',
			'aktif'      => 1,
			'ikon'       => '',
			'urut'       => 0,
			'level'      => 0,
			'hidden'     => 2,
			'ikon_kecil' => '',
			'parent'     => 220
		]);

		// Hapus cache menu navigasi
		$this->load->driver('cache');
		$this->cache->hapus_cache_untuk_semua('_cache_modul');

		//tambah kolom Foto utama di tabel pembangunan
		if (!$this->db->field_exists('foto', 'pembangunan'))
			$hasil = $this->dbforge->add_column('pembangunan', array(
				'foto' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,
				),
			));

		//tambah kolom Anggaran di tabel pembangunan
		if (!$this->db->field_exists('anggaran', 'pembangunan'))
			$hasil = $this->dbforge->add_column('pembangunan', array(
				'anggaran' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => FALSE,
				'default' => '0',
				),
			));

		return $hasil;
	}

	// Tambah Sebutan jabatan kepala desa
	private function sebutan_kepala_desa($hasil)
	{
		$setting = [
					'key' => 'sebutan_kepala_desa',
					'value' => 'Kepala',
					'keterangan' => 'Pengganti sebutan jabatan Kepala Desa'
					];
		$hasil =& $this->tambah_setting($setting);

		return $hasil;
	}

	private function urut_cetak($hasil)
	{
		//tambah kolom urut untuk tabel cetak semua di tabel tweb_wil_clusterdesa
		if ( ! $this->db->field_exists('urut_cetak', 'tweb_wil_clusterdesa'))
			$hasil =& $this->dbforge->add_column('tweb_wil_clusterdesa', array(
				'urut_cetak' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => TRUE,
				),
			));

		return $hasil;
	}

	// Bumindes updates
	protected function bumindes_updates($hasil){
		// Updates for issues #2777
		$hasil =& $this->penduduk_induk($hasil);

		// Menambahkan data pada setting_modul untuk controller bumindes_penduduk
		$data = array(
			['id'=> 303, 'modul' => 'Administrasi Penduduk', 'url' => 'bumindes_penduduk_induk', 'aktif' => 1, 'ikon' => 'fa-users', 'urut' => 2, 'level' => 2, 'hidden' => 0, 'ikon_kecil' => 'fa fa-users', 'parent' => 301],
			['id'=> 315, 'modul' => 'Buku Mutasi Penduduk', 'url' => 'bumindes_penduduk_mutasi/clear', 'aktif' => '1', 'ikon' => 'fa-files-o', 'urut' => 0, 'level' => 0, 'hidden' => 0, 'ikon_kecil' => '', 'parent' => 303],
			['id'=> 316, 'modul' => 'Buku Rekapitulasi Jumlah Penduduk', 'url' => 'bumindes_penduduk_rekapitulasi/clear', 'aktif' => '1', 'ikon' => 'fa-files-o', 'urut' => 0, 'level' => 0, 'hidden' => 0, 'ikon_kecil' => '', 'parent' => 303],
			['id'=> 317, 'modul' => 'Buku Penduduk Sementara', 'url' => 'bumindes_penduduk_sementara/clear', 'aktif' => '1', 'ikon' => 'fa-files-o', 'urut' => 0, 'level' => 0, 'hidden' => 0, 'ikon_kecil' => '', 'parent' => 303],
			['id'=> 318, 'modul' => 'Buku KTP dan KK', 'url' => 'bumindes_penduduk_ktpkk/clear', 'aktif' => '1', 'ikon' => 'fa-files-o', 'urut' => 0, 'level' => 0, 'hidden' => 0, 'ikon_kecil' => '', 'parent' => 303],
		);

		foreach ($data as $modul)
		{
			$sql = $this->db->insert_string('setting_modul', $modul);
			$sql .= " ON DUPLICATE KEY UPDATE
					id = VALUES(id),
					modul = VALUES(modul),
					url = VALUES(url),
					aktif = VALUES(aktif),
					ikon = VALUES(ikon),
					urut = VALUES(urut),
					level = VALUES(level),
					hidden = VALUES(hidden),
					ikon_kecil = VALUES(ikon_kecil),
					parent = VALUES(parent)";
			$hasil =& $this->db->query($sql);
		}

		return $hasil;
	}

	protected function penduduk_induk($hasil)
	{
		// Membuat table ref_penduduk_bahasa
		$this->dbforge->add_field([
			'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
			'nama' 			=> ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
			'inisial'       => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false]
		]);

		$this->dbforge->add_key('id', true);
		$hasil =& $this->dbforge->create_table('ref_penduduk_bahasa', true);

		// Menambahkan bahasa_id pada table tweb_penduduk, digunakan untuk define bahasa penduduk
		if (! $this->db->field_exists('bahasa_id', 'tweb_penduduk'))
			$hasil =& $this->dbforge->add_column('tweb_penduduk', array(
				'bahasa_id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => TRUE,
				),
			));

		// Menambahkan column ket pada table tweb_penduduk, digunakan untuk keterangan penduduk
		if (! $this->db->field_exists('ket', 'tweb_penduduk'))
			$hasil =& $this->dbforge->add_column('tweb_penduduk', array(
				'ket' => array(
				'type' => 'TINYTEXT',
				'null' => TRUE,
				),
			));

		// Menambahkan data ke ref_penduduk_bahasa
		$data = array(
			['id'=> 1, 'nama' => 'Latin', 'inisial' => 'L'],
			['id'=> 2, 'nama' => 'Daerah', 'inisial' => 'D'],
			['id'=> 3, 'nama' => 'Arab', 'inisial' => 'A'],
			['id'=> 4, 'nama' => 'Arab dan Latin', 'inisial' => 'AL'],
			['id'=> 5, 'nama' => 'Arab dan Daerah', 'inisial' => 'AD'],
			['id'=> 6, 'nama' => 'Arab, Latin dan Daerah', 'inisial' => 'ALD']
		);

		foreach ($data as $bahasa)
		{
			$sql = $this->db->insert_string('ref_penduduk_bahasa', $bahasa);
			$sql .= " ON DUPLICATE KEY UPDATE
					id = VALUES(id),
					nama = VALUES(nama),
					inisial = VALUES(inisial)";
			$hasil =& $this->db->query($sql);
		}

		return $hasil;
	}
}
