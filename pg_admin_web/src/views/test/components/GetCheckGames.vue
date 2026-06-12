<template>
  <div v-loading="loading" style="margin: 10px auto; width: 85%; font-size: 15px;">
    <p v-if="data">
      游戏类型【<span style="color: red; font-weight: bold;">{{ type }}</span>】
      还有【<span style="color: red; font-weight: bold;">{{ data.diff }}</span>】条检测记录待更新
    </p>

    <table class="check-table">
      <thead>
        <tr>
          <th>id</th>
          <th>游戏名</th>
          <th>检测状态</th>
          <th>时间</th>
          <th>地址</th>
          <th>错误信息</th>
          <th>复制地址</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in resultList" :key="row.game_code">
          <td>{{ row.game_code }}</td>
          <td>{{ row.game_name }}</td>
          <td>{{ row.status }}</td>
          <td>{{ row.time }}</td>
          <td>
            <a class="btn-link" :href="row.url" target="_blank">跳转</a>
          </td>
          <td>{{ row.err }}</td>
          <td>
            <a class="btn-link" href="javascript:;" @click="copyUrl(row.url)">复制</a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
  import { ref, computed, watch, onMounted } from 'vue'
  import { ElMessage } from 'element-plus'
  import { getCheckGamesResult } from '@/api/spin'

  const props = defineProps<{
    type: string
    visible: boolean
  }>()

  const loading = ref(false)
  const data = ref<any>(null)

  const resultList = computed(() => data.value?.result || [])

  onMounted(() => {
    if (props.type) {
      fetchData()
    }
  })

  watch(() => props.type, () => {
    if (props.type) {
      fetchData()
    }
  })

  async function fetchData() {
    loading.value = true
    try {
      const res = await getCheckGamesResult({ type: props.type })
      data.value = res.data || res
    } catch {
      data.value = null
    } finally {
      loading.value = false
    }
  }

  function copyUrl(text: string) {
    if (!text) {
      ElMessage.warning('地址为空！')
      return
    }
    const input = document.createElement('input')
    input.value = text
    document.body.appendChild(input)
    input.select()
    document.execCommand('Copy')
    document.body.removeChild(input)
    ElMessage.success('复制成功')
  }
</script>

<style scoped>
.check-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #dee2e6;
}
.check-table th,
.check-table td {
  padding: 8px;
  border: 1px solid #dee2e6;
  text-align: left;
}
.check-table th {
  background-color: #f8f9fa;
  font-weight: bold;
}
.btn-link {
  color: #67c23a;
  text-decoration: none;
  cursor: pointer;
}
.btn-link:hover {
  text-decoration: underline;
}
</style>
