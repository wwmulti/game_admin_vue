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
  import { userBalanceLogList } from '@/api/log'

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
    game_type_id: null,
    game_id: null,
    activity_type_id: null,
    activity_id: null,
    role_id: null
  })

  const packages = ref()
  const packageMap = ref()
  const gameTypes = ref()
  const gameTypeMap = ref()
  const activityTypes = ref()
  const activityTypeMap = ref()
  const types = ref()
  const typeMap = ref()

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
      label: 'riZhiLeiXing',
      key: 'type',
      type: 'select',
      placeholder: 'riZhiLeiXing',
      options: types.value
        ? types.value.map((item: any) => ({
            label: item.name,
            value: item.id
          }))
        : []
    },
    {
      label: 'youXiLeiXing',
      key: 'game_type_id',
      type: 'select',
      placeholder: 'youXiLeiXing',
      options: gameTypes.value
        ? gameTypes.value.map((item: any) => ({
            label: item.name,
            value: item.id
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
      label: 'huoDongLeiXing',
      key: 'activity_type_id',
      type: 'select',
      placeholder: 'huoDongLeiXing',
      options: activityTypes.value
        ? activityTypes.value.map((item: any) => ({
            label: item.name,
            value: item.id
          }))
        : []
    },
    {
      label: 'huoDongId',
      key: 'activity_id',
      type: 'input',
      placeholder: 'huoDongId'
    },
    {
      label: 'yongHuId',
      key: 'role_id',
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
      apiFn: userBalanceLogList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        createTime: defaultDateTimeRange, // 🌟 4. 接口初始请求参数也同步带上带有时分秒的默认值
        type: null,
        game_type_id: null,
        game_id: null,
        activity_type_id: null,
        activity_id: null,
        role_id: null
      },
      columnsFactory: () => [
        {
          label: 'Id',
          prop: 'id',
          width: 150
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
          label: 'bianDongJinE',
          prop: 'amount',
          width: 150
        },
        {
          label: 'riZhiLeiXing',
          prop: 'type',
          formatter: (row: any) => {
            return typeMap.value[row.type]
          },
          width: 150
        },
        {
          label: 'huoDongLeiXing',
          prop: 'activity_type_id',
          formatter: (row: any) => {
            if (row.activity_id) {
              return activityTypeMap.value[row.activity_id]
            }
          },
          width: 150
        },
        {
          label: 'huoDongId',
          prop: 'activity_id',
          formatter: (row: any) => {
            if (row.activity_id) {
              return row.activity_id
            }
          },
          width: 150
        },
        {
          label: 'huoDongMingCheng',
          prop: 'activity_title',
          width: 150
        },
        {
          label: 'youXiLeiXing',
          prop: 'game_type_id',
          formatter: (row: any) => {
            if (row.game_type_id) {
              return gameTypeMap.value[row.game_type_id]
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
          label: 'chuangJianShiJian',
          prop: 'created_time',
          width: 300,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.created_time)
          }
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
          activityTypes: response.activity_type || [],
          types: response.user_balance_log_type || []
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
        if (response.activityTypes.length > 0) {
          activityTypes.value = response.activityTypes
          activityTypeMap.value = activityTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          activityTypes.value.unshift({ id: null, name: $t('quanBu') })
        }
        if (response.types.length > 0) {
          types.value = response.types
          typeMap.value = types.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          types.value.unshift({ id: null, name: $t('quanBu') })
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
    replaceSearchParams(params)
    await getData()
  }
</script>
