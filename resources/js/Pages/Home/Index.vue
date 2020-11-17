<template>
  <div
    class="p-4 align-middle d-flex justify-content-center flex-column"
  >
    <h1 class="text-center font-weight-bold">
      Sistem Presensi Online
      <br />
      Pegawai Kantor Camat Balaesang
    </h1>
    <div
      class="mt-4 justify-content-center d-flex flex-column container-md"
      v-if="holiday.is_holiday"
    >
      <p class="text-center h5 text-muted">Libur Nasional</p>
      <br>
      <p class="text-center">{{ holiday.name }}</p>
      <img
        style="width: 30%"
        :src="route('landing') + 'assets/images/weekend_placeholder.png'"
        class="ml-auto mr-auto img d-block img-fluid"
      />
      <p class="text-center">Tidak ada jadwal kantor hari ini</p>
    </div>
    <div
      v-else-if="!weekend"
      class="justify-content-center d-flex flex-column container-md"
    >
      <div
        v-if="code != null"
        class="mt-2 justify-content-center d-flex flex-column container-md"
      >
        <flip-countdown
          @timeElapsed="refreshPage()"
          :labels="labels"
          :deadline="formattedTime"
        />
        <p class="text-center h5 text-muted">
          <span class="text-uppercase text-dark font-weight-bold">{{
            code.type
          }}</span>
          <br />
          {{ code.date }}
          <br />
          {{ code.start_time }} - {{ code.end_time }}
        </p>
        <p class="text-center"></p>
        <img :src="code.code" class="ml-auto mr-auto img img-fluid" />
        <p class="mt-2 text-center text-muted font-italic">
          Scan Disini untuk melakukan presensi
        </p>
      </div>
      <div
        class="justify-content-center d-flex flex-column container-md"
        v-else
      >
        <p class="text-center h5 text-muted">Presensi selanjutnya</p>
        <flip-countdown
          @timeElapsed="refreshPage()"
          :labels="labels"
          :deadline="formattedTime"
        />
        <img
          :src="route('landing') + 'assets/images/not_found_placeholder.png'"
          style="width: 30%; display: block"
          class="ml-auto mr-auto img img-fluid"
        />
        <p class="text-center">Belum bisa melakukan presensi</p>
      </div>
      <div class="mt-2 justify-content-center d-flex">
        <inertia-link
          :href="route('print')"
          class="shadow shadow-lg btn btn-primary"
          >Unduh Data Presensi</inertia-link
        >
      </div>
    </div>
    <div
      class="mt-4 justify-content-center d-flex flex-column container-md"
      v-else
    >
      <p class="text-center h5 text-muted">Hari kerja selanjutnya</p>
      <flip-countdown
        @timeElapsed="refreshPage()"
        :labels="labels"
        :deadline="formattedTime"
      />
      <img
        style="width: 30%"
        :src="route('landing') + 'assets/images/weekend_placeholder.png'"
        class="ml-auto mr-auto img d-block img-fluid"
      />
      <p class="text-center">Tidak ada jadwal kantor hari ini</p>
    </div>
  </div>
</template>

<script>
import { Inertia } from "@inertiajs/inertia";
import FlipCountdown from "vue2-flip-countdown";
import moment from "moment";
export default {
  props: {
    code: {
      type: Object,
      default: () => {},
    },
    weekend: {
      type: Boolean,
      default: () => false,
    },
    deadline: {
      type: String,
      default: () => "",
    },
    holiday: {
      type: Object,
      default: () => {}
    }
  },
  data() {
    return {
      labels: {
        days: "Hari",
        hours: "Jam",
        minutes: "Menit",
        seconds: "Detik",
      },
    };
  },
  components: {
    FlipCountdown,
  },
  computed: {
    formattedTime() {
      return moment(this.deadline).format("YYYY-MM-DD HH:mm:ss");
    },
  },
  methods: {
    refreshPage() {
      Inertia.reload({ only: ["code", "deadline", "weekend"] });
    },
    updater() {
      setInterval(() => {
        this.refreshPage();
      }, 60 * 1000);
    },
  },
  mounted() {
    this.updater()
  },
};
</script>

<style>
</style>