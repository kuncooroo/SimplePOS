# Panduan Pengguna SimplePOS

Panduan ini untuk pemilik toko, admin, dan kasir. Isinya menjelaskan cara memakai SimplePOS sehari-hari: masuk, berjualan, mengatur barang, melihat laporan, dan apa yang perlu dilakukan jika sesuatu tidak berjalan seperti biasa.

Nama tombol dan menu di aplikasi berbahasa Inggris. Di panduan ini nama itu ditulis apa adanya, supaya mudah dicocokkan dengan layar.

---

## 1. Pengenalan

SimplePOS adalah kasir untuk **satu toko**. Anda membukanya lewat peramban (browser), seperti Chrome atau Edge, lalu mencatat penjualan tunai.

Yang bisa dilakukan:

- Mencari barang, memasukkan ke keranjang, menerima uang tunai, dan mencetak struk.
- Menyimpan riwayat penjualan. Nama dan harga di struk tetap seperti saat barang dijual, meskipun nanti harga di katalog diubah.
- Mengurangi stok otomatis setiap penjualan selesai.
- Mengelompokkan barang, mengatur harga, dan menandai barang yang tidak dijual lagi.
- Melihat stok yang menipis dan mencatat penyesuaian stok (barang rusak, koreksi hitung fisik, dan sebagainya).
- Melihat ringkasan hari ini dan laporan penjualan.
- Membuat akun kasir atau admin, serta mengubah nama toko yang tercetak di struk.

Yang **tidak** tersedia di versi ini:

- Pendaftaran mandiri dari halaman masuk. Akun dibuat oleh pemilik atau admin.
- Pembatalan atau pengembalian barang dari penjualan yang sudah selesai.
- Pembayaran kartu, dompet digital, atau transfer. Kasir ini hanya menerima tunai.
- Kirim struk lewat email atau pesan singkat.
- Toko daring, banyak cabang, atau ekspor laporan ke file.

Penjualan yang sudah dikonfirmasi langsung tersimpan. Keranjang yang belum dibayar tidak tersimpan jika halaman ditutup.

---

## 2. Siapa memakai apa

Ada tiga peran. Menu di kiri layar menyesuaikan peran Anda.

| Peran di layar | Siapa biasanya | Yang bisa dilakukan |
|---|---|---|
| **Owner** | Pemilik toko | Semua menu, termasuk catatan aktivitas. Hanya pemilik yang dapat memberi peran Owner kepada orang lain. |
| **Administrator** | Pengelola toko | Kasir, barang, stok, laporan, pengguna, dan pengaturan toko. Tidak melihat catatan aktivitas, dan tidak dapat mengubah akun Owner. |
| **Cashier** | Kasir | Berjualan, melihat penjualan milik sendiri, mencetak struk, dan mengubah profil sendiri. |

Setelah masuk:

- Kasir langsung membuka layar **POS**.
- Pemilik dan administrator membuka **Dashboard**.

---

## 3. Masuk, keluar, dan mendapat akun

### 3.1 Mendapat akun

Tidak ada tombol daftar di halaman masuk.

1. Saat toko pertama kali disiapkan, dibuat satu akun **Owner**. Simpan email dan kata sandinya. Akun ini tidak dapat dimatikan oleh administrator.
2. Pemilik atau administrator membuat akun lain lewat menu **Users**, lalu **New user**.
3. Berikan nama, email, kata sandi sementara, dan peran (**Cashier** atau **Administrator**) kepada orang yang bersangkutan.
4. Minta mereka masuk, lalu segera mengganti kata sandi lewat **Profile**.

### 3.2 Masuk

1. Buka alamat toko yang diberikan pengelola, misalnya alamat di komputer kasir.
2. Jika belum masuk, Anda akan melihat halaman **Sign in**.
3. Isi **Email** dan **Password**.
4. Klik **Sign in**.

Jika email dan kata sandi benar, Anda masuk ke beranda sesuai peran.

### 3.3 Keluar

Di kanan atas, klik **Sign out**. Lakukan ini jika komputer kasir dipakai bergantian, supaya penjualan tercatat atas nama kasir yang benar.

---

## 4. Mengenal layar

Layar kerja punya tiga bagian:

- **Kiri:** menu. Kelompoknya **Main**, **Management**, **Reports**, dan **Administration**. Menu yang tidak boleh Anda buka tidak ditampilkan.
- **Atas:** nama halaman, nama Anda, tautan **Profile**, dan **Sign out**.
- **Tengah:** isi halaman.

Gunakan komputer atau layar yang cukup lebar. Pada layar ponsel yang sempit, menu kiri tidak muncul.

| Menu | Untuk apa |
|---|---|
| Dashboard | Ringkasan penjualan hari ini |
| POS | Kasir |
| Transactions | Riwayat penjualan |
| Categories | Kelompok barang |
| Products | Daftar barang, harga, dan stok awal |
| Inventory | Posisi stok sekarang |
| Stock movements | Riwayat perubahan stok |
| Sales report | Laporan penjualan menurut tanggal |
| Product sales | Barang apa yang terjual |
| Users | Akun pegawai |
| Settings | Nama toko, alamat, logo, dan teks di bawah struk |
| Activity log | Jejak perubahan penting (khusus pemilik) |

---

## 5. Berjualan di kasir

Buka menu **POS**. Layar terbagi tiga: pencarian barang di kiri, **Cart** (keranjang) di kanan, dan **Payment** di bawah keranjang.

### 5.1 Menambahkan barang

**Dengan nama atau kode barang**

1. Klik kotak **Search products**.
2. Ketik nama, SKU, atau sebagian barcode.
3. Klik barang pada hasil pencarian. Satu klik menambah satu buah.

**Dengan pemindai barcode**

1. Pastikan kursor ada di kotak pencarian.
2. Pindai barcode. Jika pemindai menekan Enter sendiri, barang yang barcode-nya sama persis langsung masuk keranjang dan kotak pencarian dikosongkan.
3. Jika tidak masuk, tekan Enter setelah kode lengkap terlihat.

Hanya barang berstatus **Active** yang muncul. Barang nonaktif atau yang stoknya tidak cukup tidak dapat ditambahkan.

### 5.2 Mengatur keranjang

Di panel **Cart**:

- **+** menambah jumlah.
- **−** mengurangi jumlah. Jika jumlah menjadi nol, baris itu hilang.
- **Remove** menghapus barang dari keranjang.
- Harga di kanan adalah jumlah untuk baris itu. Harga satuan tertulis di bawahnya.

Keranjang masih ada di layar ini saja. Jika Anda menutup peramban atau pindah menu sebelum bayar, keranjang kosong lagi dan tidak ada nota.

### 5.3 Menerima pembayaran

Panel **Payment** hanya aktif jika keranjang berisi barang.

1. Lihat **Subtotal**.
2. Isi **Discount** jika ada potongan untuk seluruh nota. Kosongkan atau isi 0 jika tidak ada potongan. Potongan tidak diisi per barang.
3. Lihat **Amount due**, yaitu jumlah yang harus dibayar setelah potongan.
4. Isi **Cash received** dengan uang yang diterima dari pembeli.
5. Periksa **Change** (kembalian).
6. Klik **Confirm checkout**.
7. Pada jendela **Complete sale?**, periksa lagi total, uang diterima, dan kembalian.
8. Klik **Complete Sale**. Jika Anda ingin mengubah jumlah, klik **Back**.

Setelah berhasil, struk terbuka. Klik **Print receipt** untuk mencetak, atau **New sale** untuk melayani pembeli berikutnya.

Nomor nota berbentuk seperti `INV-` diikuti tanggal dan nomor urut hari itu. Simpan struk jika pembeli memintanya.

---

## 6. Riwayat penjualan dan struk

Buka **Transactions**.

- Kasir hanya melihat penjualan yang ia selesaikan sendiri.
- Pemilik dan administrator melihat semua penjualan, dan dapat menyaring menurut kasir.

Untuk mencari:

1. Isi **Invoice number** jika Anda punya nomor nota.
2. Isi **From** dan **To** untuk rentang tanggal.
3. Pemilik atau administrator dapat memilih **Cashier**. Pilihan kosong berarti semua kasir.
4. Klik **Clear filters** untuk menghapus saringan.

Klik satu baris untuk melihat rinciannya, lalu buka struk jika perlu dicetak ulang.

Penjualan yang sudah selesai tidak dapat diubah atau dihapus dari aplikasi. Jika ada kekeliruan, catat secara manual dan sesuaikan stok lewat **Inventory** bila jumlah barang di rak perlu dikoreksi. Jangan mengulang penjualan yang sama hanya untuk “membatalkan” nota lama, karena stok akan berkurang dua kali.

---

## 7. Kategori dan produk

Bagian ini untuk pemilik dan administrator. Siapkan kategori dulu, baru produk. Kasir tidak melihat menu ini.

### 7.1 Kategori

1. Buka **Categories**.
2. Klik **New category**.
3. Isi nama, pastikan kategori aktif, lalu simpan.
4. Untuk mengubah nama atau mematikan kategori, buka **Edit**.

Produk hanya dapat memakai kategori yang masih ada. Matikan kategori yang tidak dipakai lagi, jangan berharap kategori terhapus beserta riwayatnya.

### 7.2 Menambah produk

1. Buka **Products**.
2. Klik **New product**.
3. Isi:
   - **Name** — nama yang terlihat kasir dan pembeli.
   - **SKU** — kode barang. Harus unik.
   - **Barcode** — boleh dikosongkan jika tidak ada barcode.
   - **Category** — pilih kategori yang aktif.
   - **Selling price** — harga jual.
   - **Cost price** — harga modal, boleh dikosongkan.
   - **Stock quantity** — jumlah yang ada di rak saat barang pertama kali dicatat.
   - **Active (sellable)** — centang jika barang boleh dijual sekarang.
4. Klik **Create product**.

### 7.3 Mengubah atau menghentikan penjualan barang

- **Edit** mengubah nama, harga, barcode, atau kategori.
- Stok setelah barang dibuat sebaiknya diubah lewat **Inventory**, bukan dengan mengedit angka stok di formulir produk, supaya riwayat stok tetap jelas.
- **Deactivate** membuat barang tidak muncul di kasir, tetapi riwayat penjualan lama tetap ada. Konfirmasi pada jendela yang muncul.
- **Activate** menghidupkan kembali barang yang sempat dimatikan.

Di daftar produk Anda dapat mencari nama, SKU, atau barcode, lalu menyaring menurut kategori dan status **Active** atau **Inactive**.

---

## 8. Stok

### 8.1 Melihat stok

Buka **Inventory**.

- **Current stock** adalah jumlah sekarang.
- Lencana **Low stock** muncul jika jumlah sama atau di bawah batas yang diatur di **Settings**.
- Centang **Low stock only** untuk melihat hanya barang yang menipis.
- Angka **Low stock** di Dashboard juga menuju halaman ini.

### 8.2 Menyesuaikan stok

Gunakan ini untuk barang masuk, barang rusak, atau hasil hitung fisik. Penjualan di kasir sudah mengurangi stok sendiri, jadi tidak perlu dikurangi lagi di sini.

1. Pada baris barang, klik **Adjust**.
2. Lihat **Current stock**.
3. Isi **Quantity change**:
   - angka positif menambah, misalnya `5` berarti tambah 5;
   - angka negatif mengurangi, misalnya `-2` berarti kurang 2.
4. Isi **Reason**. Alasan wajib diisi, misalnya “hitung fisik” atau “barang rusak”.
5. Klik **Confirm adjustment**.

Stok tidak boleh menjadi minus. Jika pengurangan lebih besar daripada stok yang ada, simpanan ditolak. Ubah angkanya lalu coba lagi.

### 8.3 Riwayat perubahan stok

Buka **Stock movements**. Setiap penjualan dan setiap penyesuaian manual tercatat: jumlah sebelum, perubahan, jumlah sesudah, siapa yang melakukan, dan kapan.

Saring dengan tanggal jika daftarnya panjang. Tanggal akhir tidak boleh lebih awal daripada tanggal awal.

---

## 9. Ringkasan dan laporan

Menu ini untuk pemilik dan administrator.

### 9.1 Dashboard

**Dashboard** menunjukkan hari ini:

- **Sales today** — nilai penjualan.
- **Transactions today** — jumlah nota.
- **Low stock** — berapa barang yang berada di batas stok atau di bawahnya.
- **Best seller today** — barang yang paling banyak terjual hari ini. Jika belum ada penjualan, tertulis **No sales today**.

### 9.2 Laporan penjualan

Buka **Sales report**.

1. Secara bawaan, laporan menampilkan hari ini.
2. Isi **From** dan **To** untuk rentang lain.
3. Klik **Reset to today** untuk kembali ke hari ini.

Rentang paling lama 366 hari. Jika tanggal akhir lebih awal daripada tanggal awal, laporan tidak ditampilkan sampai tanggalnya diperbaiki.

### 9.3 Laporan per barang

Buka **Product sales** untuk melihat barang mana yang terjual dan berapa jumlahnya pada rentang tanggal yang sama. Cara mengisi tanggal sama dengan laporan penjualan.

---

## 10. Pengguna, profil, dan pengaturan toko

### 10.1 Profil sendiri

Semua peran dapat membuka **Profile** di kanan atas.

- **Name** boleh diubah.
- **Email** dan **Role** hanya dapat dibaca. Untuk menggantinya, minta pemilik atau administrator.
- Untuk mengganti kata sandi, isi **Current password**, **New password**, dan **Confirm new password**.
- Jika kata sandi tidak ingin diubah, biarkan ketiga kotak itu kosong, lalu simpan nama saja.

### 10.2 Mengelola akun pegawai

Pemilik dan administrator membuka **Users**.

1. Klik **New user**.
2. Isi nama, email, kata sandi, konfirmasi kata sandi, dan peran.
3. Biarkan akun **Active** agar orang itu bisa masuk.
4. Simpan, lalu beritahu email dan kata sandi sementara kepada pegawai.

Pada daftar pengguna:

- **Edit** mengubah data akun.
- Akun yang tidak dipakai lagi dimatikan, bukan dihapus, supaya riwayat penjualan tetap punya nama kasir.
- Akun yang **Inactive** tidak bisa masuk. Pesan di halaman masuk tetap umum, tidak menyatakan bahwa akun dimatikan.
- Administrator tidak dapat mengubah atau mematikan akun Owner.

### 10.3 Pengaturan toko

Buka **Settings** untuk mengatur identitas yang muncul di struk:

- nama toko, alamat, telepon, dan email;
- logo;
- mata uang (rupiah atau dolar AS) dan simbolnya;
- teks kaki struk, misalnya ucapan terima kasih;
- **low stock threshold**, yaitu batas jumlah yang membuat barang disebut stok menipis.

Simpan perubahan, lalu cetak satu struk percobaan untuk memastikan nama dan logo sudah benar. Logo baru terlihat setelah berkasnya berhasil diunggah dan disimpan.

### 10.4 Catatan aktivitas

Hanya **Owner** yang melihat **Activity log**. Di sana tercatat pembuatan pengguna, perubahan peran, pengaktifan atau penonaktifan akun, penyesuaian stok manual, dan perubahan pengaturan toko.

Gunakan halaman ini jika Anda perlu tahu siapa yang mengubah stok atau data pegawai. Ini bukan daftar penjualan. Daftar penjualan ada di **Transactions**.

---

## 11. Jika ada masalah

### Tidak bisa masuk

Pesan yang muncul biasanya: *Unable to sign in with those credentials.*

Periksa hal berikut, berurutan:

1. Email diketik lengkap, tanpa spasi di awal atau akhir.
2. Kata sandi memperhatikan huruf besar dan kecil.
3. Anda memakai akun yang masih **Active**. Jika akun dimatikan, pesan yang tampil sama dengan kata sandi yang salah. Minta pemilik atau administrator mengaktifkan akun di **Users**.
4. Setelah beberapa percobaan gagal, masuk ditahan sementara. Tunggu sekitar satu menit, lalu coba lagi. Jangan terus menekan **Sign in**.
5. Jika tetap gagal, minta pemilik mengatur ulang kata sandi lewat **Users**, lalu **Edit**.

Aplikasi ini tidak mengirim tautan “lupa kata sandi” ke email.

### Menu yang dicari tidak ada

Menu mengikuti peran.

- Kasir tidak melihat barang, stok, laporan, pengguna, atau pengaturan.
- Administrator tidak melihat **Activity log**.
- Jika layar sempit, menu kiri disembunyikan. Lebarkan jendela atau gunakan komputer kasir.

Jika Anda yakin perannya salah, minta pemilik mengubah peran di **Users**.

### Barang tidak muncul di kasir

1. Di **Products**, pastikan statusnya **Active**, bukan **Inactive**.
2. Pastikan kategori barang juga masih aktif.
3. Cari dengan nama lengkap atau SKU. Barcode harus sama persis saat dipindai.
4. Jika stok 0, barang tidak dapat ditambahkan. Tambah stok lewat **Inventory** → **Adjust** jika barang secara fisik ada.

### Muncul pesan stok tidak cukup

Tulisannya seperti *Insufficient stock for* diikuti nama barang.

Artinya jumlah di keranjang lebih besar daripada stok yang tersisa. Kurangi jumlah di keranjang, atau batalkan barang itu. Jika hitungan di rak berbeda dengan angka di aplikasi, selesaikan atau kosongkan penjualan yang sedang berjalan, lalu koreksi stok lewat **Inventory**.

### Uang yang diterima kurang

Tulisannya: *Cash received is less than the amount due.*

Isi **Cash received** dengan jumlah yang sama atau lebih besar daripada **Amount due**. Kembalian dihitung otomatis. Tombol bayar tidak menyelesaikan nota selama uangnya masih kurang.

### Tombol bayar tidak dapat diklik

Keranjang masih kosong, atau isinya baru saja dihapus. Tambahkan minimal satu barang.

### Data atau laporan kosong

- **No transactions yet** berarti belum ada penjualan yang selesai.
- **No matching transactions** berarti saringan tanggal, nomor nota, atau kasir terlalu sempit. Klik **Clear filters**.
- Laporan hari ini kosong jika memang belum ada penjualan hari ini. Periksa tanggal **From** dan **To**.
- Kasir yang mencari nota rekannya tidak akan menemukannya. Hanya pemilik dan administrator yang melihat semua nota.
- **No products yet** berarti kategori atau produk belum dibuat.

### Tanggal laporan ditolak

- Tanggal **To** harus sama atau sesudah **From**.
- Jarak kedua tanggal tidak boleh lebih dari 366 hari. Pecah menjadi dua laporan jika Anda butuh periode yang lebih panjang.

### Penjualan salah, tetapi struk sudah tercetak

Nota yang sudah **Complete Sale** tidak bisa dibatalkan di dalam aplikasi. Catat kekeliruannya di luar sistem. Jika barang harus kembali ke rak, tambah stok lewat **Adjust** dan tulis alasannya dengan jelas, misalnya “koreksi nota salah, barang dikembalikan”. Nilai penjualan di laporan tidak berkurang dengan cara ini. Sampaikan ke pemilik agar catatan itu dibaca bersama laporan.

### Halaman terasa macet saat bayar

Tunggu sampai tulisan **Processing...** selesai. Jangan menekan **Complete Sale** berulang kali. Jika halaman benar-benar berhenti, muat ulang. Kemudian cek **Transactions**: jika nota sudah ada, jangan membuat penjualan yang sama lagi. Jika nota belum ada, ulangi dari kasir.

### Logo atau nama toko di struk masih lama

Buka **Settings**, perbaiki data, simpan, lalu cetak struk baru. Struk lama tetap menampilkan data yang tersimpan untuk penjualan itu hanya untuk nama barang dan harga barang. Nama toko mengikuti pengaturan saat struk dibuka.

---

## 12. Pertanyaan yang sering diajukan

**Apakah pembeli atau pegawai bisa mendaftar sendiri?**  
Tidak. Hanya pemilik atau administrator yang membuat akun lewat **Users**.

**Apakah kasir bisa melihat omzet seluruh toko?**  
Tidak. Kasir melihat penjualan miliknya sendiri. Ringkasan dan laporan hanya untuk pemilik dan administrator.

**Apakah keranjang tersimpan jika listrik mati sebelum dibayar?**  
Tidak. Hanya penjualan yang sudah melewati **Complete Sale** yang tersimpan. Ulangi keranjang setelah aplikasi bisa dibuka lagi.

**Mengapa stok berkurang padahal saya tidak membuka Inventory?**  
Setiap penjualan yang selesai mengurangi stok secara otomatis. Itu normal.

**Bagaimana cara membatalkan nota?**  
Tidak bisa dari aplikasi. Lihat bagian “Penjualan salah, tetapi struk sudah tercetak” di atas.

**Bisakah satu nota memakai diskon berbeda untuk tiap barang?**  
Tidak. **Discount** berlaku untuk seluruh nota.

**Bisakah pembeli bayar dengan kartu atau QR?**  
Tidak di versi ini. Terima tunai, lalu isi **Cash received**.

**Apakah barang yang sudah pernah terjual boleh dihapus?**  
Jangan dihapus. Gunakan **Deactivate** agar barang tidak terjual lagi, sementara struk lama tetap utuh.

**Mengapa mengubah harga tidak mengubah struk kemarin?**  
Harga di struk dikunci pada saat penjualan. Perubahan harga hanya berlaku untuk penjualan berikutnya.

**Siapa yang boleh mengganti kata sandi kasir?**  
Kasir dapat mengganti kata sandinya sendiri di **Profile**, selama masih ingat kata sandi lama. Jika lupa, pemilik atau administrator menggantinya lewat **Users**.

**Mengapa saya tidak melihat Activity log?**  
Menu itu hanya untuk peran **Owner**.

**Apa arti Low stock?**  
Jumlah barang sama atau di bawah batas di **Settings**. Itu pengingat untuk belanja atau restok, bukan larangan menjual. Selama jumlahnya masih cukup untuk keranjang, kasir tetap dapat menjualnya.

**Apakah ada batas berapa kali saya boleh salah kata sandi?**  
Setelah beberapa percobaan gagal, masuk ditahan sebentar. Tunggu, lalu coba lagi dengan kata sandi yang benar.

**Di mana saya mengubah nama yang tercetak di struk?**  
Di **Settings**, pada **store name**, alamat, telepon, logo, dan teks kaki struk.
