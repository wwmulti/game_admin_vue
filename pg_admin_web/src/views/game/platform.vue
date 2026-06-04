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
      <ElForm ref="formRef" label-width="200px">
        <ElFormItem :label="$t('fenBao')" required v-if="isAdd">
          <ElSelect v-model="form.package_id">
            <ElOption
              v-for="item in selectPackages"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('youXiPingTaiFenLeiMingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('youXiPingTaiFenLeiMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuBiao')">
          <ElInput v-model="form.logo" :placeholder="$t('tuBiao')" />
        </ElFormItem>
        <ElFormItem :label="$t('shouYeChuShiXianShiYouXiShuLiang')" required>
          <ElInput
            type="number"
            v-model="form.game_show_count"
            :placeholder="$t('shouYeChuShiXianShiYouXiShuLiang')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('shouYeFenYeXianShiShuLiang')" required>
          <ElInput
            type="number"
            v-model="form.game_show_more"
            :placeholder="$t('shouYeFenYeXianShiShuLiang')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')">
          <ElInput type="number" v-model="form.sort" :placeholder="$t('paiXu')" />
        </ElFormItem>
        <ElFormItem :label="$t('youXiLeiBie')">
          <ElCheckboxGroup v-model="form.game_type_ids">
            <ElCheckbox v-for="item in selectTypes" :key="item.id" :value="Number(item.id)">
              {{ item.name }}
            </ElCheckbox>
          </ElCheckboxGroup>
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
          </ElRadioGroup>
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
  import { useTable } from '@/hooks'
  import {
    platformList,
    addPlatform,
    editPlatform,
    deletePlatform,
    editPlatformAttr
  } from '@/api/game'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    id: null,
    name: null,
    status: null,
    package_id: null
  })

  const form = reactive<any>({
    id: null,
    package_id: null,
    name: null,
    logo: null,
    game_show_count: null,
    game_show_more: null,
    game_type_ids: [],
    sort: null,
    status: 1
  })

  const switchLoading = ref<Record<string, boolean>>({})
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const packages = ref()
  const packageMaps = ref()
  const selectPackages = ref()
  const types = ref()
  const selectTypes = ref()
  const typeMaps = ref()

  const formItems = computed(() => [
    {
      label: 'ID',
      key: 'id',
      type: 'input',
      placeholder: 'ID'
    },
    {
      label: 'youXiPingTaiFenLeiMingCheng',
      key: 'name',
      type: 'input',
      placeholder: 'youXiPingTaiFenLeiMingCheng'
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
      apiFn: platformList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        id: null,
        status: null,
        name: null
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 150
        },
        {
          label: 'fenBao',
          prop: 'package_id',
          width: 150,
          formatter: (row: any) => {
            return packageMaps.value?.[row.package_id] || row.package_id
          }
        },
        {
          label: 'youXiPingTaiFenLeiMingCheng',
          prop: 'name',
          width: 200
        },
        {
          label: 'youXiLeiBie',
          prop: 'game_type_ids',
          width: 300,
          formatter: (row: any) => {
            return (window as any).formatFieldByKeys(row.game_type_ids, types.value, 'id', 'name')
          }
        },
        {
          label: 'kaiGuan',
          prop: 'status',
          width: 150,
          formatter: (row: any) => {
            const isEnabled = row.status === 1
            const switchStatus = isEnabled ? 0 : 1
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus, 'status')
            })
          }
        },
        {
          label: 'tuBiao',
          prop: 'logo',
          width: 150,
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
          label: 'paiXu',
          prop: 'sort',
          width: 100
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 150,
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
          types: response.game_types || []
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
        if (response.types && response.types.length > 0) {
          types.value = response.types
          typeMaps.value = types.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectTypes.value = [...toRaw(types.value)]
          types.value.unshift({ id: null, name: $t('quanBu') })
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
    form.logo = null
    form.game_show_count = null
    form.game_show_more = null
    form.game_type_ids = []
    form.sort = null
    form.status = 1
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.name = row.name
    form.logo = row.logo
    form.game_show_count = row.game_show_count
    form.game_show_more = row.game_show_more
    form.game_type_ids = row.game_type_ids
      ? row.game_type_ids.replace(/^,|,$/g, '').split(',').filter(Boolean).map(Number)
      : []
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
      logo: form.logo,
      game_show_count: form.game_show_count,
      game_show_more: form.game_show_more,
      game_type_ids: form.game_type_ids?.length ? `,${form.game_type_ids.join(',')},` : '',
      sort: form.sort,
      status: form.status
    }
    try {
      if (form.id) {
        const result = await editPlatform(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addPlatform(params)
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          await getData()
          showEditDialog.value = false
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
      const result = await deletePlatform({
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

  const handleStatusChange = async (row: any, value: number, field: string) => {
    const key = row.id
    switchLoading.value[key] = true
    try {
      const result = await editPlatformAttr({
        id: row.id,
        value: value,
        field: field
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
</script>

<style scoped>
  .dialog-content {
    padding: 10px 20px;
  }

  .reset-container {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
  }

  .game-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    column-gap: 40px;
    row-gap: 16px;
  }

  .game-item {
    display: flex;
    align-items: center;
    justify-content: flex-end;
  }

  .game-label {
    font-size: 14px;
    color: #606266;
    margin-right: 12px;
    white-space: nowrap;
  }

  .game-input {
    width: 100px;
  }

  .agent-config-header {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e4e7ed;
  }

  .agent-config-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
  }

  .header-col {
    flex: 1;
    text-align: center;
    padding: 0 8px;
    font-size: 14px;
    color: #606266;
    font-weight: bold;
  }

  .agent-config-row .header-col {
    font-weight: normal;
  }

  .header-action {
    width: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .dialog-footer {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
  }
</style>
