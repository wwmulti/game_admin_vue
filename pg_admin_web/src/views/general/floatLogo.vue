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
        <ElFormItem :label="$t('tuBiao')" required>
          <ElInput v-model="form.logo" :placeholder="$t('tuBiao')" />
        </ElFormItem>
        <ElFormItem :label="$t('tiaoZhuanDiZhi')" required>
          <ElInput v-model="form.link" :placeholder="$t('tiaoZhuanDiZhi')" />
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
        <span style="font-size: 16px">{{ $t('shanChu') }} ? </span>
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
    floatLogoList,
    addFloatLogo,
    editFloatLogo,
    deleteFloatLogo,
    changeFloatLogoStatus,
    refreshPackageFloatLogo
  } from '@/api/general'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    name: null,
    package_id: null,
    status: null
  })

  const form = reactive<any>({
    id: null,
    package_id: null,
    logo: null,
    link: null,
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
      apiFn: floatLogoList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        status: null
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
          label: 'tuBiao',
          prop: 'logo',
          width: 300,
          formatter: (row: any) => {
            if (!row.logo) {
              return null
            }
            return h('img', {
              src: row.logo,
              style: {
                width: '80px',
                height: '80px'
              }
            })
          }
        },
        {
          label: 'tiaoZhuanDiZhi',
          prop: 'link',
          width: 300
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
          packages: response.packages || []
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
    form.logo = null
    form.link = null
    form.package_ids = []
    form.status = 1
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.logo = row.logo
    form.link = row.link
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
      logo: form.logo,
      link: form.link,
      status: form.status,
      package_ids: form.package_ids?.length ? `${form.package_ids.join(',')}` : ''
    }
    try {
      if (form.id) {
        const result = await editFloatLogo(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addFloatLogo(params)
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
      const result = await deleteFloatLogo({
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
      const result = await changeFloatLogoStatus({
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
      const result = await refreshPackageFloatLogo({
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
