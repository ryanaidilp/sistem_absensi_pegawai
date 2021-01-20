<template>
  <div>
    <nav class="px-1 py-1 shadow-lg nav justify-content-between">
      <div class="col navbar-brand">
        <img
          :src="route('landing') + 'assets/images/logo.png'"
          class="-ml-4 img-fluid navbar-brand-logo"
          width="120"
        />
      </div>
      <div class="px-4 row" style="margin: auto">
        <inertia-link :href="route('landing')" class="nav-item nav-link">
          Halaman Presensi
        </inertia-link>
        <inertia-link :href="route('print')" class="nav-item nav-link">
          Cetak
        </inertia-link>
      </div>
    </nav>
    <div class="pt-5">
      <div class="text-center">
        <h2>Statistik Data Kehadiran Pegawai</h2>
        <div
          class="mt-5 row justify-content-center justify-content-md-around"
          style="margin: auto"
        >
          <div class="bg-white rounded-lg shadow-lg card">
            <pie
              :chartdata="
                setDataChart([
                  summary_today.absent_count,
                  summary_today.late_count,
                  summary_today.present_count,
                  summary_today.permission_count,
                ])
              "
              :options="setChartOptions('Persentase Kehadiran Seluruh Pegawai')"
              role="img"
            />
          </div>
          <div class="mt-4 bg-white rounded-lg shadow-lg mt-md-0 card">
            <pie
              :chartdata="
                setDataChart([
                  summary_pns.absent_count,
                  summary_pns.late_count,
                  summary_pns.present_count,
                  summary_pns.permission_count,
                ])
              "
              :options="setChartOptions('Persentase Kehadiran Pegawai PNS')"
              role="img"
            />
          </div>
          <div class="mt-4 bg-white rounded-lg shadow-lg mt-md-0 card">
            <pie
              :chartdata="
                setDataChart([
                  summary_honorer.absent_count,
                  summary_honorer.late_count,
                  summary_honorer.present_count,
                  summary_honorer.permission_count,
                ])
              "
              :options="setChartOptions('Persentase Kehadiran Pegawai Honorer')"
              role="img"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Pie from "@/components/Pie";
import Line from "@/components/Line";
export default {
  components: {
    Pie,
    Line,
  },
  data() {
    return {};
  },
  methods: {
    setLineChartData(data) {
        let labels = data.forEach((obj) => obj.created_at)
        let comp = [
            data.absent_count,
            data.late_count,
            data.present_count,
            data.permission_count,
        ]
        return {
        labels: labels,
        datasets: [
          {
            data: data,
            backgroundColor: [
              "rgba(246, 71, 71, 1)",
              "rgba(245, 171, 53, 1)",
              "rgba(42, 187, 155, 1)",
              "rgba(65, 131, 215, 1)",
            ],
          },
        ],
      };
    },
    setDataChart(data) {
      return {
        labels: ["Tidak Hadir", "Terlambat", "Hadir", "Izin"],
        datasets: [
          {
            data: data,
            backgroundColor: [
              "rgba(246, 71, 71, 1)",
              "rgba(245, 171, 53, 1)",
              "rgba(42, 187, 155, 1)",
              "rgba(65, 131, 215, 1)",
            ],
          },
        ],
      };
    },
    setChartOptions(title) {
      return {
        title: {
          display: true,
          fontSize: 16,
          text: title,
        },
        tooltips: {
          enabled: true,
        },
        plugins: {
          datalabels: {
            formatter: (value, ctx) => {
              let sum = 0;
              let dataArr = ctx.chart.data.datasets[0].data;
              dataArr.map((data) => {
                sum += data;
              });
              let percentage = ((value * 100) / sum).toFixed(2) + "%";
              return percentage;
            },
            color: "#fff",
          },
        },
        maintainAspectRatio: false,
        responsive: true,
        legend: { position: "bottom", usePointStyle: false, display: true },
      };
    },
  },

  props: {
    summary_today: {
      type: Object,
    },
    summary_pns: {
      type: Object,
    },
    summary_honorer: {
      type: Object,
    },
    summary_all: {
        type: Array
    }
  },
};
</script>

<style>
</style>