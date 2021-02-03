<template>
  <div class="flex flex-col justify-center p-4 px-5">
    <p class="mb-8 text-3xl font-bold text-center">
      Sistem Absensi Pegawai Online
      <br />
      Kantor Camat Balaesang
    </p>
    <hr />
    <div class="flex flex-col items-center justify-between my-4 md:flex-row">
      <div class="mb-4 btn btn-primary md:mb-0">
        <inertia-link :href="route('landing')">
          Kembali ke Halaman Presensi
        </inertia-link>
      </div>

      <div class="flex flex-col justify-end text-right">
        <h5>Pilih Tanggal</h5>
        <date-picker
          :editable="false"
          @clear="reloadData(new Date())"
          @change="reloadData"
          v-model="placeholderDate"
          :placeholder="'Pilih Tanggal'"
        />
      </div>
    </div>
    <div class="mt-4 mb-2 text-xl font-bold">Unduh Data</div>
    <div class="grid w-full grid-cols-1 gap-6 mb-4 sm:grid-cols-2 lg:grid-cols-4">
      <a
        :href="`${route('download')}?type=daily&date=${momentFormat.stringify(
          placeholderDate
        )}`"
        class="font-bold bg-yellow-200 hover:bg-yellow-300 btn"
      >
        <p class="text-yellow-800">Harian ({{ date }})</p>
      </a>
      <a
        :href="`${route('download')}?type=monthly&date=${momentFormat.stringify(
          placeholderDate
        )}`"
        class="font-bold bg-indigo-200 btn hover:bg-indigo-300"
      >
        <p class="text-indigo-800">Bulanan ({{ Intl.DateTimeFormat('id-ID', {month:'long'}).format(placeholderDate) }})</p>
      </a>
      <a
        :href="`${route('download')}?type=annual&date=${momentFormat.stringify(
          placeholderDate
        )}`"
        class="font-bold bg-red-200 btn hover:bg-red-300"
      >
        <p class="text-red-800">Tahunan (PNS/{{ Intl.DateTimeFormat('id-ID', {year:'numeric'}).format(placeholderDate) }})</p>
      </a>
      <a
        :href="`${route('download')}?type=annual&date=${momentFormat.stringify(
          placeholderDate
        )}&employee=Honorer`"
        class="font-bold bg-blue-200 btn hover:bg-blue-300"
      >
        <p class="text-blue-800">Tahunan (Honorer/{{ Intl.DateTimeFormat('id-ID', {year:'numeric'}).format(placeholderDate) }})</p>
      </a>
    </div>
    <div class="my-4 text-xl font-bold">Tabel Absensi</div>
    <div class="text-center border-transparent rounded-lg shadow-lg">
      <div
        class="p-4 text-white uppercase bg-gray-500 border-b-2 border-gray-500 rounded-tl-lg rounded-tr-lg"
      >
        <h1 class="text-xl font-bold">Tabel Absensi Pegawai PNS</h1>
        <p>{{ date }}</p>
      </div>
      <div class="p-4">
        <div class="overflow-x-auto table-responsive">
          <table
            class="table text-sm rounded-lg table_pns table-striped display compact table-bordered"
            style="width: 100%"
            cellspacing="0"
          >
            <thead class="text-gray-900">
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

    <div class="my-8 text-center border-transparent rounded-lg shadow-lg">
      <div
        class="p-4 text-white uppercase bg-gray-500 border-b-2 border-gray-500 rounded-tl-lg rounded-tr-lg"
      >
        <h1 class="text-xl font-bold">Tabel Absensi Pegawai Honorer</h1>
        <p>{{ date }}</p>
      </div>
      <div class="p-4">
        <div class="overflow-x-auto table-responsive">
          <table
            class="table text-sm rounded-lg table_honorer table-striped display compact table-bordered"
            style="width: 100%"
            cellspacing="0"
          >
            <thead class="text-gray-900">
              <tr>
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
    <div class="flex flex-col my-8 text-gray-800">
      <div>
        <span class="text-xl font-bold">Daftar Izin, Dinas Luar, dan Cuti</span>
        <p>Hari/Tanggal : {{ date }}</p>
      </div>
      <div
        v-if="leaves.length > 0"
        class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2 xl:grid-cols-3"
      >
        <div
          class="flex flex-col w-full transition-all duration-150 bg-white rounded-lg shadow-md cursor-pointer hover:shadow-xl"
          v-for="item in leaves"
          :key="item.id"
        >
          <div class="p-4 bg-gray-200 rounded-tl-lg rounded-tr-lg">
            <p class="text-lg font-bold">{{ item.title }}</p>
          </div>
          <div class="flex flex-col flex-1 px-4 pb-4">
            <div class="flex flex-row justify-between my-2 text-xs">
              <p class="font-bold text-gray-500">Status</p>
              <p
                class="font-bold"
                :class="!item.is_aproved ? 'text-green-700' : 'text-red-800'"
              >
                {{ item.is_approved ? "Disetujui" : "Tidak Disetujui" }}
              </p>
            </div>
            <div class="flex flex-row justify-between mb-2 text-xs">
              <p class="font-bold text-gray-500">Jenis</p>
              <p class="font-bold">{{ item.type }}</p>
            </div>
            <div class="flex flex-row justify-between mb-2 text-xs">
              <p class="font-bold text-gray-500">Pegawai</p>
              <p class="font-bold">{{ item.user }}</p>
            </div>
            <div class="flex flex-row justify-between mb-2 text-xs">
              <p class="font-bold text-gray-500">Jabatan/Bagian</p>
              <p class="font-bold">{{ item.position }}</p>
            </div>
            <div class="flex flex-row justify-between mb-2 text-xs">
              <p class="font-bold text-gray-500">Mulai</p>
              <p class="font-bold">{{ item.start_date }}</p>
            </div>
            <div class="flex flex-row justify-between mb-2 text-xs">
              <p class="font-bold text-gray-500">Selesai</p>
              <p class="font-bold">{{ item.due_date }}</p>
            </div>
            <hr />
            <div class="flex flex-col flex-1 my-2">
              <p class="text-xs font-bold text-gray-500">Deskripsi</p>
              <p class="text-sm">{{ item.description }}</p>
            </div>
            <div :id="item.title" class="flex flex-col my-2">
              <p class="text-xs font-bold text-gray-500">Foto</p>
              <img
                @click="show"
                :src="item.photo"
                class="object-cover rounded h-72"
              />
            </div>
          </div>
        </div>
      </div>
      <div
        v-else
        class="items-center mx-auto text-lg text-center text-gray-800 h-72"
      >
        <img
          :src="route('landing') + 'assets/images/weekend_placeholder.png'"
          class="object-cover mx-auto h-72"
        />
        <p>Tidak ada data Izin, Dinas Luar, & Cuti</p>
      </div>
    </div>
    <div class="flex flex-col mt-16 text-sm text-center">
      <img
        :src="route('landing') + 'assets/logo.png'"
        style="width: 5%"
        class="ml-auto mr-auto"
      />
      <p class="text-gray-400">
        &copy; Copyright {{ new Date().getFullYear() }} -
        <span class="text-blue-500 hover:underline hover:text-blue-700">
          <a href="https://banuacoders.com">Banua Coders</a>
        </span>
        by
        <a
          class="text-blue-500 hover:underline hover:text-blue-700"
          href="https://linkedin.com/in/ryanaidilp"
          >Fajrian Aidil Pratama</a
        >
      </p>
    </div>
    <viewer :images="images" @inited="inited" class="viewer" ref="viewer">
      <template slot-scope="scope">
        <img v-for="src in scope.images" :src="src" :key="src" class="hidden" />
        {{ scope.options }}
      </template>
    </viewer>
  </div>
</template>

<script>
import DatePicker from "vue2-datepicker";
import "vue2-datepicker/index.css";
import "vue2-datepicker/locale/id";
import { Inertia } from "@inertiajs/inertia";
import moment from "moment";
import "viewerjs/dist/viewer.css";
import Viewer from "v-viewer/src/component";

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
    str_date: {
      type: String,
    },
    leaves: {
      type: Array,
      default: () => [],
    },
  },

  components: {
    Viewer,
    DatePicker,
  },
  computed: {
    placeholderDate() {
      return new Date(this.str_date);
    },
    images() {
      let images = [];
      this.leaves.forEach((leave) => {
        images.push(leave.photo);
      });
      return images;
    },
  },
  data() {
    return {
      selectedDate: new Date(),
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
      viewerOptions: {
        inline: false,
        button: true,
        navbar: true,
        title: true,
        toolbar: true,
        tooltip: true,
        movable: true,
        zoomable: true,
        rotatable: true,
        scalable: true,
        transition: true,
        fullscreen: true,
        keyboard: true,
        url: "data-source",
      },
    };
  },
  methods: {
    inited(viewer) {
      this.$viewer = viewer;
    },
    show() {
      this.$viewer.show();
    },
    reloadData(date) {
      this.placeholderDate = date;
      Inertia.visit(route("print"), {
        method: "get",
        data: {
          date: moment(date).format("YYYY-MM-DD"),
        },
        replace: false,
        only: ["pns", "honorer", "date", "str_date", "leaves"],
      });
    },
    createTable(id, data, title) {
      let columns = [];
      if (id === ".table_pns") {
        columns = [
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
        ];
      } else {
        columns = [
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
        ];
      }
      $(id).DataTable().destroy();
      $(id).DataTable({
        language: {
          url: "//cdn.datatables.net/plug-ins/1.10.20/i18n/Indonesian.json",
        },

        dom: "Bfrtip",
        pageLength: 25,
        buttons: ["pageLength"],
        // order: [[1, "asc"]],
        data: data,
        columns: columns,
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