# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

## [1.1.0] - 2025-12-05

### Diubah
- Rebranding dari "APIvro WooNotify" menjadi "NgirimWA Woo"
- Semua teks user-facing diperbarui untuk menggunakan branding NgirimWA Woo
- Dokumentasi README diperbarui dengan nama plugin baru

### Catatan
- Implementasi API NgirimWA sudah benar dan tidak ada perubahan fungsional
- Nama fungsi internal tetap menggunakan prefix `apivro_` untuk kompatibilitas backward

## [1.0.0] - 2025-11-08

### Ditambahkan
- Dukungan WooCommerce 10.0+
- Notifikasi untuk status pesanan baru
- Halaman pengaturan untuk konfigurasi
- Template pesan yang dapat disesuaikan
- Dukungan multi-bahasa
- Subtab "Payment Complete" pada halaman Template Pesan untuk mengonfigurasi pesan kepada admin dan pelanggan ketika pembayaran selesai.

### Diperbaiki
- Perbaikan kompatibilitas dengan tema tertentu
- Peningkatan penanganan error API
- Perbaikan validasi input pengaturan
- Menghapus `require_once` hard-coded untuk Action Scheduler agar lebih tahan perubahan struktur WooCommerce; kini bergantung pada WooCommerce memuat Action Scheduler dengan pemeriksaan `function_exists` sebelum pemanggilan.
- Peningkatan akurasi log notifikasi: jika tidak ada template pesan yang dikonfigurasi (admin maupun pelanggan) untuk suatu notifikasi, sistem kini menandai sebagai "skipped" alih-alih "success".

### Diketahui Masalah
- [ ] Penyesuaian template pesan bisa lebih intuitif
- [ ] Beberapa teks belum sepenuhnya tersedia dalam bahasa Indonesia

