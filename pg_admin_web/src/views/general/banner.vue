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
      width="1000"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('mingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('mingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('leiXing')" required>
          <ElSelect v-model="form.type">
            <ElOption
              v-for="item in selectBannerTypes"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('tuPian')" required>
          <ElInput v-model="form.url" :placeholder="$t('tuPian')" />
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')">
          <ElInput type="number" v-model="form.sort" :placeholder="$t('paiXu')" />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('pingTai')" v-if="isAdd">
          <ElCheckbox v-model="isAllPackagesSelected">
            {{ $t('quanPingTai') }}
          </ElCheckbox>
        </ElFormItem>
        <ElFormItem v-if="isAdd">
          <ElCheckboxGroup v-model="form.package_ids">
            <ElCheckbox v-for="item in selectPackages" :key="item.id" :value="Number(item.id)">
              {{ item.title }}
            </ElCheckbox>
          </ElCheckboxGroup>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <ElDialog v-model="showDeleteDialog" :title="$t('shanChu')" width="800" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{ $t('shanChu') }} - {{ form.name }} ? </span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="deleteRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import '@wangeditor/editor/dist/css/style.css'
  import { useTable } from '@/hooks'
  import {
    bannerList,
    addBanner,
    editBanner,
    deleteBanner,
    changeBannerStatus,
    refreshPackageBanner
  } from '@/api/general'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    name: null,
    type: null,
    package_id: null,
    status: null
  })

  const form = reactive<any>({
    id: null,
    package_id: null,
    name: null,
    type: null,
    url: null,
    sort: null,
    package_ids: [],
    status: 1
  })

  const switchLoading = ref<Record<string, boolean>>({})
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const packages = ref()
  const packageMaps = ref()
  const selectPackages = ref()
  const bannerTypes = ref()
  const bannerTypeMaps = ref()
  const selectBannerTypes = ref()

  const isAllPackagesSelected = computed({
    get() {
      if (!selectPackages.value || selectPackages.value.length === 0) {
        return false
      }
      return form.package_ids?.length === selectPackages.value.length
    },
    set(val: boolean) {
      if (val) {
        form.package_ids = selectPackages.value.map((item: any) => Number(item.id))
      } else {
        form.package_ids = []
      }
    }
  })

  const formItems = computed(() => [
    {
      label: 'mingCheng',
      key: 'name',
      type: 'input'
    },
    {
      label: 'pingTai',
      key: 'package_id',
      type: 'select',
      placeholder: 'pingTai',
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
      placeholder: 'leiXing',
      options: bannerTypes.value
        ? bannerTypes.value.map((item: any) => ({
            label: item.name,
            value: item.id
          }))
        : []
    },
    {
      label: 'zhuangTai',
      key: 'status',
      type: 'select',
      props: {
        placeholder: 'xuanZe',
        options: [
          { label: $t('quanBu'), value: null },
          { label: $t('qiYong'), value: 1 },
          { label: $t('jinYong'), value: 0 }
        ]
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
    refreshUpdate,
    searchParams
  } = useTable({
    core: {
      apiFn: bannerList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        status: null,
        name: null,
        type: null
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 150
        },
        {
          label: 'pingTai',
          prop: 'package_id',
          width: 150,
          formatter: (row: any) => {
            return packageMaps.value?.[row.package_id] || row.package_id
          }
        },
        {
          label: 'leiXing',
          prop: 'type',
          width: 150,
          formatter: (row: any) => {
            return bannerTypeMaps.value[row.type]
          }
        },
        {
          label: 'mingCheng',
          prop: 'name',
          width: 150
        },
        {
          label: 'tuPian',
          prop: 'url',
          width: 300,
          formatter: (row: any) => {
            if (!row.url) {
              return null
            }
            return h('img', {
              src: row.url
            })
          }
        },
        {
          label: 'paiXu',
          prop: 'sort',
          width: 100
        },
        {
          label: 'kaiGuan',
          prop: 'status',
          width: 120,
          formatter: (row: any) => {
            const isEnabled = row.status === 1
            const switchStatus = isEnabled ? 0 : 1
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus)
            })
          }
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 300,
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
                () => $t('bianJi')
              ),
              h(
                ElButton,
                {
                  type: 'success',
                  size: 'small',
                  onClick: () => asyncConfig(row)
                },
                () => $t('tongBuShuJu')
              ),
              h(
                ElButton,
                {
                  type: 'danger',
                  size: 'small',
                  onClick: () => handleShowDeleteDialog(row)
                },
                () => $t('shanChu')
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
          bannerType: response.banner_type || []
        }
      }
    },
    hooks: {
      onSuccess: async (_data: any[], response: any) => {
        if (response.packages && response.packages.length > 0) {
          packages.value = response.packages
          packageMaps.value = packages.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          selectPackages.value = [...toRaw(packages.value)]
          packages.value.unshift({ id: null, title: $t('quanBu') })
        }
        if (response.bannerType && response.bannerType.length > 0) {
          bannerTypes.value = response.bannerType
          bannerTypeMaps.value = bannerTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectBannerTypes.value = [...toRaw(bannerTypes.value)]
          bannerTypes.value.unshift({ id: null, name: $t('quanBu') })
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
    isAdd.value = true
    form.id = null
    form.package_id = null
    form.name = null
    form.type = null
    form.url = null
    form.sort = null
    form.package_ids = []
    form.status = 1
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.name = row.name
    form.type = row.type
    form.url = row.url
    form.sort = row.sort
    form.status = row.status
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.id = row.id
    form.name = row.name
    showDeleteDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      package_id: form.package_id,
      name: form.name,
      type: form.type,
      url: form.url,
      sort: form.sort,
      status: form.status,
      package_ids: form.package_ids?.length ? `${form.package_ids.join(',')}` : ''
    }
    try {
      if (form.id) {
        const result = await editBanner(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addBanner(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function deleteRow() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deleteBanner({
        id: form.id
      })
      if (result.code === 200) {
        await getData()
        showDeleteDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  const handleStatusChange = async (row: any, value: number) => {
    const key = row.id
    switchLoading.value[key] = true
    try {
      const result = await changeBannerStatus({
        id: row.id,
        status: value
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
      await refreshUpdate()
    } finally {
      switchLoading.value[key] = false
    }
  }

  const asyncConfig = async (row: any) => {
    switchLoading.value[row.id] = true
    try {
      const result = await refreshPackageBanner({
        package_id: row.package_id
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
      await refreshUpdate()
    } finally {
      switchLoading.value[row.id] = false
    }
  }
</script>

<style scoped>
  .agent-config-row .header-col {
    font-weight: normal;
  }
  .dialog-footer {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
  }
</style>
