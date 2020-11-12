<template class="p-5 d-flex flex-column justify-content-center">
  <div class="p-4 px-5">
    <h1 class="text-center font-weight-bold">
      Sistem Presensi Online
      <br />
      Pegawai Kantor Camat Balaesang
    </h1>
    <div class="flex-row d-flex justify-content-between">
      <inertia-link
        class="inline shadow-sm h-50 btn btn-primary"
        :href="route('landing')"
      >
        Kembali ke Halaman Presensi
      </inertia-link>
      <div class="text-right d-flex flex-column justify-content-end">
        <h5>Pilih Tanggal</h5>
        <date-picker
          @change="reloadData"
          v-model="placeholderDate"
          :placeholder="'Pilih Tanggal'"
        />
      </div>
    </div>

    <div class="my-4 text-center rounded shadow-sm card">
      <div class="card-header">
        <h1>Tabel Absensi Pegawai PNS</h1>
        <p>{{ date }}</p>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table
            class="table rounded-lg table_pns table-striped display compact table-bordered"
            style="width: 99%"
            cellspacing="0"
          >
            <thead class="thead-dark">
              <tr>
                <th rowspan="3">NIP</th>
                <th rowspan="3">Nama</th>
                <th rowspan="3">Jabatan</th>
                <th rowspan="3">Bagian</th>
                <th colspan="8">Absen</th>
              </tr>
              <tr>
                <th colspan="2">Pagi</th>
                <th colspan="2">ISHOMA</th>
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

    <div class="my-4 text-center rounded shadow-sm card">
      <div class="card-header">
        <h1>Tabel Absensi Pegawai Honorer</h1>
        <p>{{ date }}</p>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table
            class="table rounded-lg table_honorer table-striped display compact table-bordered"
            style="width: 99%"
            cellspacing="0"
          >
            <thead class="thead-dark">
              <tr>
                <th rowspan="3">NIP</th>
                <th rowspan="3">Nama</th>
                <th rowspan="3">Jabatan</th>
                <th rowspan="3">Bagian</th>
                <th colspan="8">Absen</th>
              </tr>
              <tr>
                <th colspan="2">Pagi</th>
                <th colspan="2">ISHOMA</th>
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
    <div class="text-center d-flex flex-column">
      <img
        :src="route('landing') + 'assets/logo.png'"
        style="width: 5%"
        class="ml-auto mr-auto"
      />
      <p class="text-muted">
        &copy; Copyright 2020 -
        <span> <a href="https://banuacoders.com">Banua Coders</a> </span>
        by <a href="https://linkedin.com/in/ryanaidilp">Fajrian Aidil Pratama</a>
      </p>
    </div>
  </div>
</template>

<script>
import DatePicker from "vue2-datepicker";
import "vue2-datepicker/index.css";
import "vue2-datepicker/locale/id";
import { Inertia } from "@inertiajs/inertia";
import moment from "moment";

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

  components: {
    DatePicker,
  },
  data() {
    return {
      selectedDate: new Date(),
      placeholderDate: new Date(),
      momentFormat: {
        //[optional] Date to String
        stringify: (date) => {
          return date ? moment(date).format("YYYY-MM-DD") : "";
        },
        //[optional]  String to Date
        parse: (value) => {
          return value ? moment(value, "YYYY-MM-DD").toDate() : null;
        },
        //[optional] getWeekNumber
        getWeek: (date) => {
          return; // a number
        },
      },
    };
  },
  methods: {
    reloadData(date) {
      this.placeholderDate = date;
      Inertia.visit(route('print'), {
        method: "get",
        data: {
          date: moment(date).format("YYYY-MM-DD"),
        },
        replace: false,
        only: ["pns", "honorer", "date"],
      });
    },
    createTable(id, data, title) {
      $(id).DataTable().destroy();
      $(id).DataTable({
        language: {
          url: "//cdn.datatables.net/plug-ins/1.10.20/i18n/Indonesian.json",
        },

        dom: "Bfrtip",
        buttons: [
          "pageLength",
          {
            extend: "excel",
            title: `Tabel Presensi Pegawai ${title} Kantor Camat Balaesang. ${this.date}`,
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
          { data: "presensi.0.attend_time" },
          { data: "presensi.1.status" },
          { data: "presensi.1.attend_time" },
          { data: "presensi.2.status" },
          { data: "presensi.2.attend_time" },
          { data: "presensi.3.status" },
          { data: "presensi.3.attend_time" },
        ],
      });
    },
  },
  mounted() {
    this.createTable(".table_pns", this.pns, "PNS");
    this.selectedDate = this.date;
    this.createTable(".table_honorer", this.honorer, "Honorer");
  },
  beforeDestroy() {
    $(".table_pns").DataTable().destroy();
    $(".table_honorer").DataTable().destroy();
  },
};
</script>

<style>
</style>