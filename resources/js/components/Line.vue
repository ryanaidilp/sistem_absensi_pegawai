<script>
import { mixins, Line } from "vue-chartjs";
const { reactiveProp } = mixins;
import dataLabel from 'chartjs-plugin-datalabels'
export default {
  extends: Line,
  mixins: [reactiveProp],
  props: {
    chartdata: {
      type: Object,
      default: null,
    },
    options: {
      type: Object,
      default: null,
    },
  },
  mounted() {
    this.renderChart(this.chartdata, this.options);
    this.addPlugin(dataLabel)
  },
};
</script>