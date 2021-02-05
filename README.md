<p align="center"><img src="https://i.ibb.co/X2tG5vD/logo-siap.png" width="400"></p>

<h1 align="center">
Sistem Absensi Pegawai (SiAP)
</h1>

Website berisi *API Backend* dan manajemen data pegawai untuk aplikasi **[SIAP](https://github.com/ryanaidilp/sistem_absensi_pegawai_app)**. Website ini dibangun dengan [Laravel](https://laravel.com), [Tailwind CSS](https://tailwindcss.com/), [Vue JS](https://vuejs.org), dan [Inertia JS](https://inertiajs.com). Website ini juga memiliki halaman absensi (QR Code) dan tabel untuk menampilkan data kehadiran pegawai.

<p align="center">
<img src="https://i.ibb.co/ZVyHrMh/screely-1611734499039.png"/>
<img src="https://i.ibb.co/3vMYDqg/screely-1612410329484.png"/>
<img src="https://i.ibb.co/dmDLn5J/screely-1612410516708.png"/>
<img src="https://i.ibb.co/6ZbqRDY/screely-1612410549780.png"/>
<img src="https://i.ibb.co/BGrbd4D/screely-1612410576769.png"/>
<img src="https://i.ibb.co/6vTB6zc/screely-1612410606804.png"/>
</p>

## About

Aplikasi dan website ini dibangun untuk mengatasi permasalahan pencatatan absensi pegawai di lingkungan kantor pemerintahan Kecamatan Balaesang. Pencatatan kehadiran pegawai di kantor pemerintahan Kecamatan Balaesang selama ini masih dilakukan secara manual yaitu dengan memberi paraf pada absensi.

Permasalahan timbul saat sebagian besar pegawai tidak jujur dalam mengisi absen tersebut, ada yang titip ke teman untuk diparaf namanya, ada yang langsung isi absen sampai beberapa hari ke depan, ada yang mengisi absen diluar waktunya, dsb. Dengan adanya sistem ini, diharapkan bisa membantu mengatasi permasalahan-permasalahan yang telah disebutkan.

## Feature

* Halaman absensi yang menampilkan ***QR Code*** untuk di-*scan* oleh pegawai menggunakan aplikasi [SiAP](https://play.google.com/store/apps/details?id=com.banuacoders.siap) Android.
* Halaman cetak yang menampilkan tabel data kehadiran pegawai (PNS & Honorer). Halaman ini juga menampilkan daftar Izin, Dinas Luar, dan Cuti di hari berjalan.
* Halaman administrator menggunakan [Voyager](https://voyager.devdojo.com/)
* ***API Backend*** untuk [SiAP](https://play.google.com/store/apps/details?id=com.banuacoders.siap)
* ***Export*** daftar hadir harian, bulanan, dan tahunan ke dalam file excel (.xlsx)

## Konfigurasi

* ***Environment Variable***

    ```dotenv
    CALENDARIFIC_KEY = *Your calendarific API-KEY*
    ONESIGNAL_APP_ID='Your One Signal APP-ID'
    ONESIGNAL_API_KEY='Your One Signal API-KEY'
    ONESIGNAL_API_URL='https://onesignal.com/api/v1/notifications'
    MEDIA_URL="Your media storage url" #Required if you deploy app in shared hosting
    LATITUDE_OFFSET="latitude of your office location"
    LONGITUDE_OFFSET="longitude of your office location"
    ```

  * ***Calendarific Key***

    *Calendarific Key* diperlukan untuk mendapatkan data hari libur nasional dari [Calendarific API](https://calendarific.com/).

  * ***One Signal APP_ID, API_KEY, & API_URL***

    Variabel ini diperlukan untuk mengirim ***push notification*** ke aplikasi [SiAP](https://play.google.com/store/apps/details?id=com.banuacoders.siap). Untuk mendapatkan data ini, silahkan buat akun di [One Signal](https://app.onesignal.com) lalu ikuti petunjuk pada dokumentasi resminya.

  * ***Latitude & Longirude Offset***

    Data ini diperlukan untuk mengecek jarak user dari kantor saat melakukan presensi. Hal ini dilakukan untuk memastikan bahwa user melakukan presensi di kantor.

* **Konfigurasi**
  * ***Clone*** *repository* ini
  * Jalankan perintah `composer install` & `npm install`
  * Setelah semua module npm terinstall, jalankan perintah `npm run watch`
  * Isikan konfigurasi ***database*** anda pada file **.env**
  * Jalankan perintah `php artisan voyager:install` untuk menginstall *admin panel* Voyager
  * Setelah voyager berhasil diinstall, buat admin dengan menjalankan perintah `php artisan voyager:admin {email_anda@mail.com} --create`
  * Buat file csv berisi data user dan department lalu masukkan ke dalam folder database dengan struktur

    ```directory
    ├── database
      └── csv
        ├── users.csv
        └── departments.csv
    ```

    pastikan struktur data pada csv sesuai dengan struktur tabel.

  * Jalankan perintah `php artisan db:seed`, jika tidak ada file csv pada proses sebelumnya, *comment*/hilangkan **UserSeeder** & **DepartmentSeeder** dari **DatabaseSeeder.php** sebelum menjalankan seeder
  * Untuk mengambil dan menyimpan data hari libur nasional, jalankan perintah  `php artisan holiday:generate` tambahkan option  `--year` untuk menentukan tahun libur yang di-generate (default  `--year=2021`). **Pastikan anda suda mendapatkan API Key dari** [Calendarific API](https://calendarific.com/).
  * Buat kode absen dengan jalankan perintah `php artisan absent:code` lalu buat daftar absen dengan perintah `php artisan absent:attende`. Pastikan data **User** dan **Department** sudah ada saat menjalankan perintah ini.
  * Jalankan perintah `php artisan serve` lalu kunjungi url **localhost:8000**

## To-Do List

* [x] Halaman menampilkan **QR Code** dan ***Countdown Timer***.
* [x] ***API Backend*** untuk ***Mobile App***.
* [x] ***Tracking*** performa pegawai (PNS & Honorer)
* [x] **Export** data laporan kehadiran (Excel)
  * [x] Harian
  * [x] Bulanan
  * [x] Tahunan

## License

**SIAP** is open-sourced software licensed under the [GPL v2.0](https://www.gnu.org/licenses/gpl-2.0.html).
