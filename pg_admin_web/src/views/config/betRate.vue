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
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElSpace wrap>
            <ElButton @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
          </ElSpace>
        </template>
      </ArtTableHeader>
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

    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="800"
      center
    >
      <ElForm ref="formRef" label-width="150px">
        <ElFormItem :label="$t('mingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('mingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('pingTai')" required>
          <ElSelect v-model="form.package_id" :disabled="!isAdd">
            <ElOption
              v-for="item in selectPackages"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('leiXing')" required>
          <ElSelect v-model="form.type" :disabled="!isAdd">
            <ElOption v-for="(type, key) in selectTypes" :key="key" :label="type" :value="key" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('daMaBeiShu')" required>
          <ElInput v-model="form.pro_rate" :placeholder="$t('daMaBeiShu')" />
        </ElFormItem>
        <ElFormItem :label="$t('ceShiDaMaBeiShu')" required>
          <ElInput v-model="form.dev_rate" :placeholder="$t('ceShiDaMaBeiShu')" />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>
<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { addBetRate, betRateList, editBetRate } from '@/api/betRate'
  import { ElButton, ElMessage, ElLoading } from 'element-plus'

  const searchBarRef = ref()
  const searchForm = ref({
    need_reload: undefined,
    package_id: undefined,
    type: undefined,
    pro_rate: undefined,
    dev_rate: undefined,
    name: undefined
  })

  const isAdd = ref(false)
  const showEditDialog = ref(false)

  const form = reactive<any>({
    id: null,
    package_id: undefined,
    type: undefined,
    pro_rate: undefined,
    dev_rate: undefined,
    name: undefined
  })

  const packages = ref()
  const selectPackages = ref()
  const packageMap = ref()

  const types = ref()
  const selectTypes = ref()

  const formItems = computed(() => [
    {
      label: 'pingTai',
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
      label: 'leiXing',
      key: 'type',
      type: 'select',
      options: types.value
        ? Object.entries(types.value).map(([key, value]) => ({
            label: value,
            value: Number(key)
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
    columnChecks,
    refreshData,
    searchParams
  } = useTable({
    core: {
      apiFn: betRateList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        username: null,
        status: null
      },
      columnsFactory: () => [
        {
          label: 'mingCheng',
          prop: 'name'
        },
        {
          label: 'pingTai',
          prop: 'package_id',
          formatter: (row: any) => {
            return packageMap.value[row.package_id] || row.package_id
          }
        },
        {
          label: 'leiXing',
          prop: 'type',
          formatter: (row: any) => {
            return types.value[row.type] || row.type
          }
        },
        {
          label: 'daMaBeiShu',
          prop: 'pro_rate'
        },
        {
          label: 'ceShiDaMaBeiShu',
          prop: 'dev_pro'
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 320,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                {
                  type: 'primary',
                  size: 'small',
                  onClick: () => handleShowEditDialog(row)
                },
                $t('bianJi')
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          records: response.list || [],
          total: response.total || 0,
          packages: response.packages || [],
          types: response.type_list || {}
        }
      }
    },
    hooks: {
      onSuccess: async (data: any[], response: any) => {
        if (response.packages && response.packages.length > 0) {
          packages.value = response.packages
          packageMap.value = packages.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          selectPackages.value = [...toRaw(packages.value)]
          packages.value.unshift({ id: null, title: $t('quanBu') })
        }
        if (response.types && Object.keys(response.types).length > 0) {
          types.value = response.types
          selectTypes.value = { ...types.value }
          types.value[0] = $t('quanBu')
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

  function handleShowAddDialog() {
    form.id = null
    form.package_id = null
    form.type = null
    form.name = null
    form.pro_rate = null
    form.dev_rate = null
    isAdd.value = true
    showEditDialog.value = true
  }

  async function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.type = String(row.type)
    form.name = row.name
    form.pro_rate = row.pro_rate
    form.dev_rate = row.dev_rate
    showEditDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      package_id: form.package_id,
      name: form.name,
      type: Number(form.type),
      pro_rate: form.pro_rate,
      dev_rate: form.dev_rate
    }
    try {
      if (form.id) {
        const result = await editBetRate(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addBetRate(params)
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          await getData()
          showEditDialog.value = false
        }
      }
    } finally {
      loadingInstance.close()
    }
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
