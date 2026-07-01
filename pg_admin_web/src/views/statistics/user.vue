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
        @sort-change="handleSortChange"
      />
    </ElCard>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { ref, computed } from 'vue'
  import { $t } from '@/locales'
  import { getUserStats } from '@/api/statistic'

  defineOptions({ name: 'StatisticsUser' })

  const searchBarRef = ref()
  const artTableRef = ref()

  const searchForm = ref<Record<string, any>>({
    date_time: [],
    merchant_id: ''
  })

  const merchantOptions = ref<Record<any, string>>({})

  const formItems = computed(() => [
    {
      label: '',
      key: 'date_time',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('kaiShiRiQi'),
        'end-placeholder': $t('jieShuRiQi'),
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
        filterable: true,
        options: Object.entries(merchantOptions.value).map(([key, value]) => ({ label: value, value: key }))
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
    handleSizeChange,
    handleCurrentChange,
    refreshData,
    handleSortChange
  } = useTable({
    core: {
      apiFn: getUserStats,
      apiParams: {
        page: 1,
        limit: 20
      },
      columnsFactory: () => [
        {
          type: 'selection',
          width: 50
        },
        {
          label: $t('riQi'),
          prop: 'date_time',
          minWidth: 120
        },
        {
          label: $t('shangHu'),
          prop: 'merchant_id',
          minWidth: 120
        },
        {
          label: $t('yongHu'),
          prop: 'user_id',
          minWidth: 120
        },
        {
          label: $t('yaZhu'),
          prop: 'bet_amount',
          minWidth: 120,
          sortable: true
        },
        {
          label: $t('yingJin'),
          prop: 'win_amount',
          minWidth: 120,
          sortable: true
        },
        {
          label: $t('yaZhuCiShu'),
          prop: 'bet_times',
          minWidth: 100,
          sortable: true
        },
        {
          label: $t('wanFaYingShou'),
          prop: 'win_or_loss',
          minWidth: 120
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        merchantOptions.value = response.merchant_name || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams(artTableRef.value?.elTableRef)
  }
</script>

<style scoped lang="scss"></style>
