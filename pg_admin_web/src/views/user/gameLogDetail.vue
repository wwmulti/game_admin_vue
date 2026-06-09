<template>
  <div>
    <ArtSearchBar
      ref="searchBarRef"
      v-model="searchForm"
      :items="formItems"
      span="4"
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
  import { h, ref, computed, watch } from 'vue'
  import { $t } from '@/locales'
  import { ElButton } from 'element-plus'
  import { getUserGameLog } from '@/api/user'

  defineOptions({ name: 'GameLogDetail' })

  const props = defineProps({
    userId: {
      type: Number,
      required: true
    }
  })

  const searchBarRef = ref()
  const artTableRef = ref()

  // 房间选项
  const roomOptions = ref<Record<any, string>>({})

  const searchForm = ref<Record<string, any>>({
    room_id: '',
    created_at: []
  })

  const formItems = computed(() => [
    {
      label: '',
      key: 'room_id',
      type: 'select',
      props: {
        placeholder: $t('suoShuYouXi'),
        style: { width: '140px' },
        clearable: true,
        filterable: true,
        options: Object.entries(roomOptions.value).map(([key, value]) => ({ label: value, value: key }))
      }
    },
    {
      label: '',
      key: 'created_at',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('kaiShiShiJian'),
        'end-placeholder': $t('jieShuShiJian'),
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
    handleSizeChange,
    handleCurrentChange,
    refreshData
  } = useTable({
    core: {
      apiFn: getUserGameLog,
      apiParams: {
        page: 1,
        limit: 20
      },
      columnsFactory: () => [
        {
          label: $t('dingDanId'),
          prop: 'sid',
          minWidth: 160
        },
        {
          label: $t('suoShuYouXi'),
          prop: 'room_id',
          minWidth: 120
        },
        {
          label: $t('xiaZhuJinE'),
          prop: 'bet',
          minWidth: 120
        },
        {
          label: $t('jingShuYing'),
          prop: 'transfer',
          minWidth: 120
        },
        {
          label: $t('chuangJianShiJian'),
          prop: 'created_at',
          minWidth: 160,
          formatter: (row: any) => {
            if (!row.created_at) return ''
            // 后端返回的是毫秒时间戳，转为秒再格式化
            const ts = String(row.created_at).length > 10 ? Math.floor(Number(row.created_at) / 1000) : Number(row.created_at)
            return (window as any).formatTimestamp?.(ts) ?? row.created_at
          }
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        roomOptions.value = response.room_name || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  async function handleSearch(params: any) {
    replaceSearchParams({ ...params, user_id: props.userId })
    await getData()
  }

  function handleReset() {
    searchForm.value = { room_id: '', created_at: [] }
    replaceSearchParams({ user_id: props.userId })
    getData()
  }

  watch(() => props.userId, (newVal) => {
    if (newVal) {
      replaceSearchParams({ user_id: newVal })
      getData()
    }
  }, { immediate: true })
</script>

<style scoped lang="scss"></style>
