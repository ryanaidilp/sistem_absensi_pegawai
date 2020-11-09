<template class="d-flex flex-column justify-content-center p-5">
  <div class="container p-4">
    <inertia-link class="btn btn-primary shadow-sm" :href="route('landing')">
      Kembali ke Halaman Presensi
    </inertia-link>

    <div class="card my-4 shadow-lg text-center">
      <div class="card-header">
        <h1>Tabel Absensi Pegawai PNS</h1>
        <p>{{ date }}</p>
      </div>
      <div class="justify-content-center card-body">
        <table
          class="table_pns table-responsive table-striped display compact cell-border"
          style="width: 100%; padding-top: 1em; padding-bottom: 1em"
          cellspacing="0"
        >
          <thead class="thead-dark">
           <tr>
              <th rowspan="3">NIP</th>
              <th rowspan="3">Nama</th>
              <th rowspan="3">Jabatan</th>
              <th rowspan="3">Bagian</th>
              <th colspan="8">Presensi</th>
            </tr>
            <tr>
              <th colspan="2">Pagi</th>
              <th colspan="2">Istrahat</th>
              <th colspan="2">Siang</th>
              <th colspan="2">Pulang</th>
            </tr>
            <tr>
              <th>Status</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Jam</th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot></tfoot>
        </table>
      </div>
    </div>

    <div class="card my-4 shadow-lg text-center">
      <div class="card-header">
        <h1>Tabel Absensi Pegawai Honorer</h1>
        <p>{{ date }}</p>
      </div>
      <div class="justify-content-center card-body">
        <table
          class="table_honorer table-responsive table-striped display compact cell-border"
          style="width: 100%; padding-top: 1em; padding-bottom: 1em"
          cellspacing="0"
        >
          <thead class="thead-dark">
            <tr>
              <th rowspan="3">NIP</th>
              <th rowspan="3">Nama</th>
              <th rowspan="3">Jabatan</th>
              <th rowspan="3">Bagian</th>
              <th colspan="8">Presensi</th>
            </tr>
            <tr>
              <th colspan="2">Pagi</th>
              <th colspan="2">Istrahat</th>
              <th colspan="2">Siang</th>
              <th colspan="2">Pulang</th>
            </tr>
            <tr>
              <th>Status</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Jam</th>
              <th>Status</th>
              <th>Jam</th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot></tfoot>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
const tableOptions = {
  responsive: true,
  dom: "Bfrtip",
  buttons: ["pageLength"],
  order: [[1, "desc"]],
};

export default {
  props: {
    honorer: {
      type: Array,
      default: () => [],
    },
    pns: {
      type: Array,
      default: () => [],
    },
    date: {
      type: String,
      default: () => "",
    },
  },
  data() {
    return {
      tablePns: null,
    };
  },
  methods: {
    createTable(id, data, title) {
      $(id)
        .DataTable({
          language: {
            url: "//cdn.datatables.net/plug-ins/1.10.20/i18n/Indonesian.json",
          },
          responsive: true,
          dom: "Bfrtip",
          buttons: [
            "pageLength",
            {
              extend: "excel",
              title: `Tabel Presensi Pegawai ${title} Kantor Camat Balaesang ${this.date}`,
            },
          ],
          // order: [[1, "asc"]],
          data: data,
          columns: [
            { data: "nip" },
            { data: "name" },
            { data: "position" },
            { data: "department" },
            { data: "presensi.0.status" },
            { data: "presensi.0.jam_absen" },
            { data: "presensi.1.status" },
            { data: "presensi.1.jam_absen" },
            { data: "presensi.2.status" },
            { data: "presensi.2.jam_absen" },
            { data: "presensi.3.status" },
            { data: "presensi.3.jam_absen" },
          ],
        })
        .columns.adjust()
        .responsive.recalc();
    },
  },
  mounted() {
    this.createTable(".table_pns", this.pns, "PNS");
    this.createTable(".table_honorer", this.honorer, "Honorer");
  },
  beforeDestroy() {
    $(".table_pns").DataTable().destroy();
  },
};
</script>

<style>
</style>