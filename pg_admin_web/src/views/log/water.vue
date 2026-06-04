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
  import { ref, computed } from 'vue'
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { gameWaterLogList } from '@/api/log'

  const getDefaultDateTimeRange = (): string[] => {
    const end = new Date()
    const start = new Date()
    start.setTime(start.getTime() - 3600 * 1000 * 24 * 7) // 前推 7 天（时分秒保持一致）

    const formatDateTime = (date: Date) => {
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      const hours = String(date.getHours()).padStart(2, '0')
      const minutes = String(date.getMinutes()).padStart(2, '0')
      const seconds = String(date.getSeconds()).padStart(2, '0')
      return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
    }

    return [formatDateTime(start), formatDateTime(end)]
  }

  const defaultDateTimeRange = getDefaultDateTimeRange()

  const searchBarRef = ref()
  const searchForm = ref({
    package_id: null,
    createTime: defaultDateTimeRange,
    type: null,
    game_type_title: null,
    game_platform_title: null,
    game_id: null,
    activity_type_id: null,
    activity_id: null,
    role_id: null
  })

  const packages = ref()
  const packageMap = ref()
  const gameTypes = ref()
  const gameTypeMap = ref()
  const platforms = ref()
  const platformMaps = ref()

  const formItems = computed(() => [
    {
      label: 'riQi',
      key: 'createTime',
      type: 'datetimerange',
      props: {
        type: 'datetimerange',
        'start-placeholder': $t('kaiShiShiJian'),
        'end-placeholder': $t('jieShuShiJian'),
        'value-format': 'YYYY-MM-DD HH:mm:ss',
        'default-time': [new Date(2000, 1, 1, 0, 0, 0), new Date(2000, 1, 1, 23, 59, 59)] // 手动选新日期时，默认左右的时间点
      }
    },
    {
      label: 'fenBao',
      key: 'package_id',
      type: 'select',
      placeholder: 'fenBao',
      options: packages.value
        ? packages.value.map((item: any) => ({
            label: item.title,
            value: item.id
          }))
        : []
    },
    {
      label: 'youXiPingTai',
      key: 'game_platform_title',
      type: 'select',
      placeholder: 'youXiPingTai',
      options: platforms.value
        ? platforms.value.map((item: any) => ({
            label: item.name,
            value: item.name
          }))
        : []
    },
    {
      label: 'youXiLeiXing',
      key: 'game_type_title',
      type: 'select',
      placeholder: 'youXiLeiXing',
      options: gameTypes.value
        ? gameTypes.value.map((item: any) => ({
            label: item.name,
            value: item.name
          }))
        : []
    },
    {
      label: 'youXiId',
      key: 'game_id',
      type: 'input',
      placeholder: 'youXiId'
    },
    {
      label: 'yongHuId',
      key: 'role_id',
      type: 'input',
      placeholder: 'yongHuId'
    },
    {
      label: 'dingDanId',
      key: 'order_id',
      type: 'input',
      placeholder: 'yongHuId'
    },
    {
      label: 'fuDingDanId',
      key: 'parent_order_id',
      type: 'input',
      placeholder: 'yongHuId'
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
      apiFn: gameWaterLogList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        createTime: defaultDateTimeRange,
        type: null,
        game_type_title: null,
        game_platform_title: null,
        game_id: null,
        activity_type_id: null,
        activity_id: null,
        role_id: null
      },
      columnsFactory: () => [
        {
          label: 'riQi',
          prop: 'c_time',
          width: 200,
          fixed: 'left',
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.c_time)
          }
        },
        {
          label: 'fenBao',
          prop: 'package_id',
          formatter: (row: any) => {
            return packageMap.value[row.package_id]
          },
          width: 150
        },
        {
          label: 'wanJiaId',
          prop: 'role_id',
          width: 150
        },
        {
          label: 'yaZhuJinE',
          prop: 'bet_amount',
          width: 150
        },
        {
          label: 'yinKuiJinE',
          prop: 'get_amount',
          width: 150
        },
        {
          label: 'youXiLeiXing',
          prop: 'game_cat_id',
          formatter: (row: any) => {
            if (row.game_cat_id) {
              return gameTypeMap.value[row.game_cat_id]
            }
          },
          width: 150
        },
        {
          label: 'youXiId',
          prop: 'game_id',
          formatter: (row: any) => {
            if (row.game_id) {
              return row.game_id
            }
          },
          width: 150
        },
        {
          label: 'youXiMingCheng',
          prop: 'game_title',
          width: 150
        },
        {
          label: 'youXiPingTai',
          prop: 'game_platform_id',
          formatter: (row: any) => {
            if (row.game_platform_id) {
              return platformMaps.value[row.game_platform_id]
            }
          },
          width: 150
        },
        {
          label: 'dingDanId',
          prop: 'order_id',
          width: 150
        },
        {
          label: 'fuDingDanId',
          prop: 'parent_order_id',
          width: 150
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          records: response.list || [],
          total: response.total || 0,
          packages: response.packages || [],
          gameTypes: response.game_type || [],
          platforms: response.platform || []
        }
      }
    },
    hooks: {
      onSuccess: async (data: any[], response: any) => {
        if (response.packages.length > 0) {
          packages.value = response.packages
          packageMap.value = packages.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          packages.value.unshift({ id: null, title: $t('quanBu') })
        }
        if (response.gameTypes.length > 0) {
          gameTypes.value = response.gameTypes
          gameTypeMap.value = gameTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          gameTypes.value.unshift({ id: null, name: $t('quanBu') })
        }
        if (response.platforms.length > 0) {
          platforms.value = response.platforms
          platformMaps.value = platforms.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          platforms.value.unshift({ id: null, name: $t('quanBu') })
        }
        searchParams.need_reload = 0
      }
    }
  })

  function handleReset() {
    resetSearchParams()
    searchForm.value.createTime = getDefaultDateTimeRange()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    const customParams = { ...params }
    if (customParams.game_type_title === $t('quanBu')) {
      delete customParams.game_type_title
    }
    if (customParams.game_platform_title === $t('quanBu')) {
      delete customParams.game_platform_title
    }
    replaceSearchParams(customParams)
    await getData()
  }
</script>
