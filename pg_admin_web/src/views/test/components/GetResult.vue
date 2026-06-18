<template>
  <div v-loading="loading" style="margin: 10px auto; width: 60%; font-size: 15px;">
    <div v-if="data">
      <p>
        游戏类型【{{ data.type_id }}】-【{{ data.game_name }}】-
        当前设置RTP【{{ data.rtp }}】-
        实际RTP【{{ data.real_rtp }}】
      </p>
      <p>单次局数：{{ data.nums }}</p>
      <p>总轮次：{{ data.rounds }}</p>
      <p>总次数：{{ totalCount }}</p>
      <p>成功轮次：{{ data.sus }}</p>
      <p>赢局(&gt;1)：{{ data.win }}</p>
      <p>输局：{{ data.lose }}</p>
      <p>倍率详情</p>
    </div>

    <table class="result-table">
      <thead>
        <tr>
          <th>倍率</th>
          <th>局数</th>
          <th>占比</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in resultList" :key="row.rate">
          <td>{{ row.rate }}</td>
          <td>{{ row.nums }}</td>
          <td>{{ row.percent }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
  import { ref, computed, watch, onMounted } from 'vue'
  import { getRunResult } from '@/api/spin'

  const props = defineProps<{
    id: number
    visible: boolean
  }>()

  const loading = ref(false)
  const data = ref<any>(null)

  const resultList = computed(() => data.value?.result || [])
  const totalCount = computed(() => {
    if (!data.value) return 0
    return (Number(data.value.nums) || 0) * (Number(data.value.rounds) || 0)
  })

  onMounted(() => {
    if (props.id) {
      fetchData()
    }
  })

  watch(() => props.id, () => {
    if (props.id) {
      fetchData()
    }
  })

  async function fetchData() {
    loading.value = true
    try {
      const res = await getRunResult({ id: props.id })
      data.value = res.data || res
    } catch {
      data.value = null
    } finally {
      loading.value = false
    }
  }
</script>

<style scoped>
.result-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid var(--default-border);
}
.result-table th,
.result-table td {
  padding: 8px;
  border: 1px solid var(--default-border);
  text-align: left;
}
.result-table th {
  background-color: var(--art-gray-200);
  font-weight: bold;
}
</style>
