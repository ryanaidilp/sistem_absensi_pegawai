<p align="center"><img src="https://i.ibb.co/X2tG5vD/logo-siap.png" width="400"></p>

<h1 align="center">
Sistem Absensi Pegawai (SIAP)
</h1>

Website berisi *API Backend* dan manajemen data pegawai untuk aplikasi **[SIAP](https://github.com/ryanaidilp/sistem_absensi_pegawai_app)**. Website ini dibangun dengan [Laravel](https://laravel.com), [Tailwind CSS](https://tailwindcss.com/), [Vue JS](https://vuejs.org), dan [Inertia JS](https://inertiajs.com). Website ini juga memiliki halaman absensi (QR Code) dan tabel untuk menampilkan data kehadiran pegawai.

<p align="center">
<img src="https://i.ibb.co/rGYjC8F/image.png"/>
<img src="https://i.ibb.co/DYkXdzM/Untitled-1.jpg"/>
</p>

## About

Aplikasi dan website ini dibangun untuk mengatasi permasalahan pencatatan absensi pegawai di lingkungan kantor pemerintahan Kecamatan Balaesang. Pencatatan kehadiran pegawai di kantor pemerintahan Kecamatan Balaesang selama ini masih dilakukan secara manual yaitu dengan memberi paraf pada absensi.

Permasalahan timbul saat sebagian besar pegawai tidak jujur dalam mengisi absen tersebut, ada yang titip ke teman untuk diparaf namanya, ada yang langsung isi absen sampai beberapa hari ke depan, ada yang mengisi absen diluar waktunya, dsb. Dengan adanya sistem ini, diharapkan bisa membantu mengatasi permasalahan-permasalahan yang telah disebutkan.

## Feature

* Halaman absensi yang menampilkan ***QR Code*** untuk di-*scan* oleh pegawai menggunakan aplikasi [SiAP](https://play.google.com/store/apps/details?id=com.banuacoders.siap) Android.
* Halaman cetak yang menampilkan tabel data kehadiran pegawai (PNS & Honorer). Halaman ini juga menampilkan daftar Izin, Dinas Luar, dan Cuti di hari berjalan.
* Halaman administrator menggunakan [Voyager](https://voyager.devdojo.com/)

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

## To-Do List

* [ ] Visualisasi data kehadiran pegawai (PNS & Honorer).
* [ ] Tracking performa pegawai (PNS & Honorer)

## License

**SIAP** is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
