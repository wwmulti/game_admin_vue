<template>
  <div>
    <ElRow :gutter="20">
      <ElCol :span="8">
        <ElCard shadow="hover" class="statistic-card">
          <div class="card-header">
            <span class="tag-label">{{ $t('zongChongZhi') }}</span>
          </div>
          <div class="card-value">{{ total_recharge }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="8">
        <ElCard shadow="hover" class="statistic-card">
          <div class="card-header">
            <span class="tag-label">{{ $t('zongTiXian') }}</span>
          </div>
          <div class="card-value">{{ total_withdraw }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="8">
        <ElCard shadow="hover" class="statistic-card">
          <div class="card-header">
            <span class="tag-label">{{ $t('zongYingLi') }}</span>
          </div>
          <div class="card-value">{{ total_profit }}</div>
        </ElCard>
      </ElCol>
    </ElRow>
    <br />
    <ArtSearchBar
      ref="searchBarRef"
      v-model="searchForm"
      :items="formItems"
      span="4"
      @reset="handleReset"
      @search="handleSearch"
    />
    <ElCard class="art-table-card">
      <ArtTable
        border
        stripe
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
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { dayStatistics } from '@/api/statistic'
  const searchBarRef = ref()
  const searchForm = ref({
    time: [] as string[],
    merge_by_day_flag: undefined,
    merge_by_platform_flag: undefined,
    need_reload: undefined,
    package_id: undefined
  })
  const total_profit = ref()
  const total_recharge = ref()
  const total_withdraw = ref()
  const packages = ref<any[]>([])
  const packageMap = ref<Record<string | number, string>>({})

  const formItems = computed(() => [
    {
      label: 'kaiFangPingTai',
      key: 'package_id',
      type: 'select',
      placeholder: 'kaiFangPingTai',
      options: packages.value
        ? packages.value.map((item: any) => ({
            label: item.title,
            value: item.id
          }))
        : []
    },
    {
      label: 'riQi',
      key: 'time',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('kaiShiRiQi'),
        'end-placeholder': $t('jieShuRiQi'),
        'value-format': 'YYYY-MM-DD'
      }
    },
    {
      label: 'anTianHeBing',
      key: 'merge_by_day_flag',
      type: 'checkbox',
      props: {
        'true-value': 1,
        'false-value': 0
      }
    },
    {
      label: 'anPingTaiHeBing',
      key: 'merge_by_platform_flag',
      type: 'checkbox',
      props: {
        'true-value': 1,
        'false-value': 0
      }
    }
  ])

  const {
    columns,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    handleSizeChange,
    handleCurrentChange,
    searchParams
  } = useTable({
    core: {
      apiFn: dayStatistics,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        time: []
      },
      columnsFactory: () => [
        {
          label: 'riQi',
          prop: 'date',
          formatter: (row: any) => {
            return (window as any).formatPureDate(row.date)
          }
        },
        {
          label: 'suoShuPingTai',
          prop: 'package_id',
          formatter: (row: any) => {
            return packageMap.value[row.package_id]
          }
        },
        {
          label: 'pingTaiRtp',
          prop: 'platform_rtp'
        },
        {
          label: 'youXiRtp',
          prop: 'game_rtp'
        },
        {
          label: 'zongChongZhi',
          prop: 'total_charge_amount'
        },
        {
          label: 'zongTiXian',
          prop: 'total_withdraw'
        },
        {
          label: 'zongYingLi',
          prop: 'total_profit'
        },
        {
          label: 'jiaTiXianJinE',
          prop: 'total_fake_withdraw'
        },
        {
          label: 'pingTaiYingKui',
          prop: 'platform_profit'
        },
        {
          label: 'pingTaiZengSong',
          prop: 'platform_send'
        },
        {
          label: 'moNiZengSong',
          prop: 'simulation_send'
        },
        {
          label: 'houTaiShangFen',
          prop: 'gm_send'
        },
        {
          label: 'yongHuZongYuE',
          prop: 'total_balance'
        },
        {
          label: 'tiXianZhanBi',
          prop: 'withdraw_percent'
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          records: response.list || [],
          total: response.total || 0,
          packages: response.packages || [],
          total_profit: response.total_profit || 0,
          total_recharge: response.total_recharge || 0,
          total_withdraw: response.total_withdraw || 0
        }
      }
    },
    hooks: {
      onSuccess: async (data: any[], response: any) => {
        total_profit.value = response.total_profit
        total_recharge.value = response.total_recharge
        total_withdraw.value = response.total_withdraw
        if (response.packages.length > 0) {
          packages.value = response.packages
          packages.value.unshift({ id: null, title: $t('quanBu') })
          packageMap.value = packages.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
        }
        searchParams.need_reload = 0
      }
    }
  })

  function handleReset() {
    resetSearchParams()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    replaceSearchParams(params)
    await getData()
  }
</script>

<style scoped lang="scss">
  /* 头部有一条淡淡的横线分隔 */
  .card-header {
    border-bottom: 1px solid #f0f2f5;
    padding: 10px 0;
    margin-bottom: 15px;
  }

  /* 蓝色小标签样式 */
  .tag-label {
    background-color: #409eff;
    color: #fff;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: bold;
    border-radius: 2px;
  }

  /* 大数字样式 */
  .card-value {
    font-size: 36px;
    color: #409eff;
    padding: 10px 0 20px 0;
  }
</style>
