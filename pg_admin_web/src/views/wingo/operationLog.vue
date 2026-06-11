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
  import { getWingoOperationLog } from '@/api/wingo'
  import { ElCard, ElTag } from 'element-plus'

  defineOptions({ name: 'WingoOperationLog' })

  const searchBarRef = ref()
  const artTableRef = ref()

  const searchForm = ref<Record<string, any>>({
    room_id: '',
    issue: '',
    number: '',
    time: []
  })

  const formItems = computed(() => [
    {
      label: '',
      key: 'room_id',
      type: 'input',
      props: { placeholder: $t('fangjianId'), style: { width: '130px' }, clearable: true }
    },
    {
      label: '',
      key: 'issue',
      type: 'input',
      props: { placeholder: $t('qiHao'), style: { width: '130px' }, clearable: true }
    },
    {
      label: '',
      key: 'number',
      type: 'input',
      props: { placeholder: $t('xiGaiHaoMa'), style: { width: '130px' }, clearable: true }
    },
    {
      label: '',
      key: 'time',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('chuangJianShiJian'),
        'end-placeholder': $t('chuangJianShiJian'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    }
  ])

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
      apiFn: getWingoOperationLog,
      apiParams: { page: 1, limit: 20 },
      columnsFactory: () => [
        { type: 'selection', width: 50 },
        { label: 'ID', prop: 'id', width: 70 },
        { label: $t('guanLiYuanID'), prop: 'admin_id', minWidth: 100 },
        { label: $t('fangJianID'), prop: 'room_id', minWidth: 120 },
        { label: $t('qiHao'), prop: 'issue', minWidth: 120 },
        {
          label: $t('xiGaiHaoMa'),
          prop: 'number',
          minWidth: 100,
          formatter: (row: any) =>
            row.number != null
              ? h(ElTag, { type: 'primary' }, () => String(row.number))
              : '-'
        },
        {
          label: $t('chuangJianShiJian'),
          prop: 'time',
          minWidth: 160,
          formatter: (row: any) => (window as any).formatTimestamp?.(row.time) ?? row.time
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => ({
        records: response.rows || [],
        total: response.total || 0
      })
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
