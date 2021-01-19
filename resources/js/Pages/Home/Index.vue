<template>
  <div
    class="flex flex-col justify-center w-full min-h-screen p-4 mx-auto"
  >
    <p class="mb-4 text-3xl font-bold text-center">
      Sistem Absensi Pegawai Online
      <br />
      Kantor Camat Balaesang
    </p>
    <div class="flex flex-col justify-center mt-4" v-if="holiday.is_holiday">
      <p class="text-center text-muted">Libur Nasional</p>
      <br />
      <p class="text-center">{{ holiday.name }}</p>
      <img
        :src="route('landing') + 'assets/images/weekend_placeholder.png'"
        class="object-cover h-64 mx-auto"
      />
      <p class="text-center">Tidak ada jadwal kantor hari ini</p>
    </div>
    <div
      v-else-if="!weekend"
      class="flex flex-col items-center justify-center w-full"
    >
      <div v-if="code != null" class="flex flex-col justify-center mt-2">
        <flip-countdown
          @timeElapsed="refreshPage()"
          :labels="labels"
          :deadline="formattedTime"
        />
        <p class="my-4 text-center text-gray-500">
          <span class="font-bold text-black uppercase">{{ code.type }}</span>
          <br />
          {{ code.date }}
          <br />
          {{ code.start_time }} - {{ code.end_time }}
        </p>
        <p class="text-center"></p>
        <img :src="code.code" class="object-contain mx-auto" />
        <p class="my-2 italic text-center text-gray-500">
          Scan Disini untuk melakukan presensi
        </p>
      </div>
      <div class="flex flex-col" v-else>
        <p class="mb-4 text-center text-muted">Presensi selanjutnya</p>
        <flip-countdown
          @timeElapsed="refreshPage()"
          :labels="labels"
          :deadline="formattedTime"
        />
        <img
          :src="route('landing') + 'assets/images/not_found_placeholder.png'"
          class="object-cover h-64 mx-auto"
        />
        <p class="text-center">Belum bisa melakukan presensi</p>
      </div>
      <div class="justify-center mx-auto mt-4">
        <inertia-link
          :href="route('print')"
          class="btn-primary btn"
          >Unduh Data Presensi</inertia-link
        >
      </div>
    </div>
    <div
      class="flex flex-col justify-center w-full mt-4"
      v-else
    >
      <p class="text-center text-gray-500">Hari kerja selanjutnya</p>
      <flip-countdown
        @timeElapsed="refreshPage()"
        :labels="labels"
        :deadline="formattedTime"
      />
      <img
        :src="route('landing') + 'assets/images/weekend_placeholder.png'"
        class="object-cover h-64 mx-auto"
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
      default: () => {},
    },
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
    this.updater();
  },
};
</script>

<style>
</style>