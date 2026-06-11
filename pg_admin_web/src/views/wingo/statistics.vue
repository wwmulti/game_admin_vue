<template>
  <div>
    <ArtSearchBar
      ref="searchBarRef"
      v-model="searchForm"
      :items="formItems"
      @reset="handleReset"
      @search="handleSearch"
    />
    <ElCard class="art-table-card">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData" />
      <ArtTable
        ref="artTableRef"
        :loading="loading"
        :data="data as any"
        :columns="columns"
        :pagination="pagination"
        :row-class-name="summaryRowClassName"
        @pagination:size-change="handleSizeChange"
        @pagination:current-change="handleCurrentChange"
      />
    </ElCard>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, computed } from 'vue'
  import { $t } from '@/locales'
  import {
    getWingoStatisticsList
  } from '@/api/wingo'
  import { ElCard } from 'element-plus'

  defineOptions({ name: 'WingoStatistics' })

  const searchBarRef = ref()
  const artTableRef = ref()

  const roomOptions = ref<Record<any, string>>({})
  const merchantOptions = ref<Record<any, string>>({})

  const searchForm = ref<Record<string, any>>({
    room_id: '',
    date_time: [],
    merchant_id: ''
  })

  const formItems = computed(() => [
    {
      label: '',
      key: 'room_id',
      type: 'select',
      props: {
        placeholder: $t('suoShuFangJian'),
        style: { width: '140px' },
        clearable: true,
        options: Object.entries(roomOptions.value).map(([value, label]) => ({ label, value: Number(value) }))
      }
    },
    {
      label: '',
      key: 'date_time',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('riQi'),
        'end-placeholder': $t('riQi'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    },
    {
      label: '',
      key: 'merchant_id',
      type: 'select',
      props: {
        placeholder: $t('shangHu'),
        style: { width: '140px' },
        clearable: true,
        options: Object.entries(merchantOptions.value).map(([value, label]) => ({ label, value: Number(value) }))
      }
    }
  ])

  function summaryRowClassName({ row }: { row: any }) {
    if (row.date_time === $t('heJi')) {
      return 'summary-row'
    }
    return ''
  }

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    refreshData,
    refreshUpdate,
    handleSizeChange,
    handleCurrentChange
  } = useTable({
    core: {
      apiFn: getWingoStatisticsList,
      apiParams: { page: 1, limit: 20 },
      columnsFactory: () => [
        { type: 'selection', width: 50 },
        { label: $t('suoShuFangJian'), prop: 'room_id', minWidth: 120 },
        { label: $t('riQi'), prop: 'date_time', minWidth: 120 },
        { label: $t('xiaZhuZongJinE'), prop: 'bet_amount', minWidth: 120 },
        { label: $t('canYuRenShu'), prop: 'user_number', minWidth: 100 },
        { label: $t('yaZhuCiShu'), prop: 'bet_times', minWidth: 100 },
        { label: $t('yingJin'), prop: 'win_amount', minWidth: 120 },
        { label: $t('wanFaYingShou'), prop: 'win_or_loss', minWidth: 120 }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        // 从 index 接口同步选项数据到搜索下拉
        roomOptions.value = response.wingo_room || {}
        merchantOptions.value = response.merchant_name || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  // ---- 搜索 ----
  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }
</script>

<style scoped lang="scss">
:deep(.summary-row) {
  font-weight: bold;
  background-color: #f5f7fa !important;
}
</style>
