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
  import { systemLog } from '@/api/log'
  import { ElTag } from 'element-plus'

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
    admin_id: null,
    c_time: defaultDateTimeRange,
    role_id: null,
    path: null,
    log_type: null,
    body: null
  })

  const packages = ref()
  const packageMap = ref()
  const routerLists = ref()
  const routerListMap = ref()
  const types = ref()
  const typeMap = ref()

  const formItems = computed(() => [
    {
      label: 'caoZuoRen',
      key: 'admin_id',
      type: 'input',
      placeholder: 'caoZuoRen'
    },
    {
      label: 'wanJiaId',
      key: 'role_id',
      type: 'input',
      placeholder: 'wanJiaId'
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
      label: 'caoZuoFangFa',
      key: 'path',
      type: 'select',
      placeholder: 'caoZuoFangFa',
      options: routerLists.value
        ? routerLists.value.map((item: any) => ({
            label: item.title,
            value: item.uri
          }))
        : []
    },
    {
      label: 'leiXing',
      key: 'log_type',
      type: 'select',
      placeholder: 'leiXing',
      options: types.value
        ? types.value.map((item: any) => ({
            label: item.title,
            value: item.id
          }))
        : []
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
      apiFn: systemLog,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        admin_id: null,
        c_time: defaultDateTimeRange,
        role_id: null,
        path: null,
        log_type: null,
        body: null
      },
      columnsFactory: () => [
        {
          label: 'id',
          prop: 'id',
          width: 150
        },
        {
          label: 'wanJiaId',
          prop: 'role_id',
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
          label: 'caoZuoRen',
          prop: 'admin_id',
          width: 150
        },
        {
          label: 'caoZuoShiJian',
          prop: 'c_time',
          width: 200,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.c_time)
          }
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 100,
          formatter: (row: any) => {
            if (row.status === 1) {
              return h(
                ElTag,
                {
                  type: 'success',
                  size: 'small'
                },
                $t('chengGong')
              )
            } else {
              return h(
                ElTag,
                {
                  type: 'danger',
                  size: 'small'
                },
                $t('shiBai')
              )
            }
          }
        },
        {
          label: 'caoZuoFangFa',
          prop: 'path',
          width: 300
        },
        {
          label: 'leiXing',
          prop: 'log_type',
          formatter: (row: any) => {
            if (row.log_type) {
              return typeMap.value[row.log_type]
            }
          },
          width: 150
        },
        {
          label: 'caoZuoShuJu',
          prop: 'body',
          width: 500
        },
        {
          label: 'ip',
          prop: 'ip',
          width: 250
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          records: response.list || [],
          total: response.total || 0,
          packages: response.packages || [],
          routerLists: response.router_list || [],
          types: response.type_list || []
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
        if (response.routerLists.length > 0) {
          routerLists.value = response.routerLists
          routerListMap.value = routerLists.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          routerLists.value.unshift({ id: null, title: $t('quanBu'), uri: null })
        }
        if (response.types.length > 0) {
          types.value = response.types
          typeMap.value = types.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          types.value.unshift({ id: null, title: $t('quanBu') })
        }
        searchParams.need_reload = 0
      }
    }
  })

  function handleReset() {
    resetSearchParams()
    searchForm.value.c_time = getDefaultDateTimeRange()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    replaceSearchParams(params)
    await getData()
  }
</script>
