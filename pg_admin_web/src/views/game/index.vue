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
          <ElButton type="primary" @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
          <ElButton @click="handleBatchCategory">{{ $t('piLiangXiuGaiFenLei') }}</ElButton>
          <ElButton type="warning" @click="handleShowRefreshConfig">{{ $t('peiZhiShuaXin') }}</ElButton>
        </template>
      </ArtTableHeader>
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

    <!-- 新增/编辑弹窗 -->
    <ElDialog v-model="showEditDialog" :title="isAdd ? $t('tianJia') : $t('bianJi')" width="600" top="5vh" center>
      <ElForm ref="formRef" :model="form" label-width="120px" @submit.prevent>
        <ElFormItem :label="$t('youXiMingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('youXiMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('fenLei')" required>
          <ElSelect v-model="form.type_id" :placeholder="$t('fenLei')" filterable style="width:100%">
            <ElOption
              v-for="(value, key) in typeOptions"
              :key="key"
              :label="value"
              :value="Number(key)"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('youXiLeiXing')">
          <ElSelect v-model="form.type" :placeholder="$t('youXiLeiXing')" style="width:100%">
            <ElOption :label="$t('jiShi')" :value="1" />
            <ElOption :label="$t('yanChi')" :value="2" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('youXiKaiGuan')">
          <ElSwitch v-model="form.status" :active-value="1" :inactive-value="0" />
        </ElFormItem>
        <ElFormItem :label="$t('shiFouFanJiang')">
          <ElSwitch v-model="form.reward_status" :active-value="1" :inactive-value="0" />
        </ElFormItem>
        <ElFormItem :label="$t('youXiMuLu')">
          <ElInput v-model="form.dir" :placeholder="$t('youXiMuLu')" />
        </ElFormItem>
        <ElFormItem :label="$t('liShiBiaoMingCheng')">
          <ElInput v-model="form.history_table_name" :placeholder="$t('liShiBiaoMingCheng')" />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="submitLoading" @click="submitRow">{{ $t('tiJiao') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 批量修改分类弹窗 -->
    <ElDialog v-model="showBatchCategoryDialog" :title="$t('piLiangXiuGaiFenLei')" width="500" center>
      <ElForm label-width="120px">
        <ElFormItem :label="$t('fenLei')" required>
          <ElSelect v-model="batchCategoryTypeId" :placeholder="$t('qingXuanZe')" filterable style="width:100%">
            <ElOption
              v-for="(value, key) in typeOptions"
              :key="key"
              :label="value"
              :value="Number(key)"
            />
          </ElSelect>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showBatchCategoryDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" @click="confirmBatchCategory">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 配置刷新弹窗 -->
    <ElDialog v-model="showRefreshConfigDialog" :title="$t('peiZhiShuaXin')" width="500" center>
      <ElForm label-width="120px">
        <ElFormItem :label="$t('youXiLeiXing')">
          <ElSelect v-model="refreshConfigTypeId" :placeholder="$t('qingXuanZe')" filterable clearable style="width:100%" @change="handleRefreshConfigTypeChange">
              <ElOption v-for="item in refreshConfigTypeOptions" :key="item.id" :label="item.name" :value="item.id" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('suoShuYouXi')">
          <ElSelect v-model="refreshConfigRoomId" :placeholder="$t('qingXuanZe')" filterable clearable style="width:100%">
            <ElOption v-for="item in refreshConfigRoomOptions" :key="item.id" :label="item.name" :value="item.id" />
          </ElSelect>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showRefreshConfigDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="refreshConfigLoading" @click="confirmRefreshConfig">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed } from 'vue'
  import { $t } from '@/locales'
  import {
    ElButton,
    ElTag,
    ElSwitch,
    ElMessage,
    ElLoading,
    ElMessageBox,
    ElSelect,
    ElOption,
    ElInput
  } from 'element-plus'
  import { getRoomList, addRoom, editRoom, deleteRoom, editRoomAttr, batchUpdateRoomCategory, refreshRoomConfig, getTypeIdName, getRoomSelect } from '@/api/game'

  defineOptions({ name: 'GameIndex' })

  const searchBarRef = ref()
  const artTableRef = ref()

  const searchForm = ref<Record<string, any>>({
    name: '',
    type_id: '',
    type: ''
  })

  const typeOptions = ref<Record<any, string>>({})

  const typeSelectOptions = [
    { label: $t('jiShi'), value: '1' },
    { label: $t('yanChi'), value: '2' }
  ]

  const formItems = computed(() => [
    {
      label: '',
      key: 'name',
      type: 'input',
      props: { placeholder: $t('youXiMingCheng'), style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'type_id',
      type: 'select',
      props: {
        placeholder: $t('fenLei'),
        style: { width: '120px' },
        clearable: true,
        filterable: true,
        options: Object.entries(typeOptions.value).map(([key, value]) => ({ label: value, value: key }))
      }
    },
    {
      label: '',
      key: 'type',
      type: 'select',
      props: {
        placeholder: $t('youXiLeiXing'),
        style: { width: '120px' },
        clearable: true,
        options: typeSelectOptions
      }
    }
  ])

  // 弹窗
  const showEditDialog = ref(false)
  const isAdd = ref(false)
  const submitLoading = ref(false)

  // 批量修改分类弹窗
  const showBatchCategoryDialog = ref(false)
  const batchCategoryTypeId = ref<number>(0)

  // 配置刷新弹窗
  const showRefreshConfigDialog = ref(false)
  const refreshConfigTypeId = ref<number | null>(null)
  const refreshConfigRoomId = ref<number | null>(null)
  const refreshConfigTypeOptions = ref<any[]>([])
  const refreshConfigRoomOptions = ref<any[]>([])
  const refreshConfigLoading = ref(false)

  const initForm = () => ({
    id: null,
    name: '',
    type_id: 1,
    type: 1,
    status: 1,
    reward_status: 1,
    dir: '',
    history_table_name: ''
  })

  const form = reactive<any>(initForm())

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
    refreshData
  } = useTable({
    core: {
      apiFn: getRoomList,
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
          label: 'ID',
          prop: 'id',
          width: 70
        },
        {
          label: $t('youXiMingCheng'),
          prop: 'name',
          minWidth: 140
        },
        {
          label: $t('fenLei'),
          prop: 'type_id',
          minWidth: 100,
          formatter: (row: any) => typeOptions.value[row.type_id] ?? row.type_id
        },
        {
          label: $t('youXiKaiGuan'),
          prop: 'status',
          width: 80,
          formatter: (row: any) =>
            h(ElSwitch, {
              modelValue: row.status === 1,
              onChange: (val: string | number | boolean) => handleToggleSwitch(row, 'status', val ? 1 : 0)
            })
        },
        {
          label: $t('shiFouFanJiang'),
          prop: 'reward_status',
          width: 90,
          formatter: (row: any) =>
            h(ElSwitch, {
              modelValue: row.reward_status === 1,
              onChange: (val: string | number | boolean) => handleToggleSwitch(row, 'reward_status', val ? 1 : 0)
            })
        },
        {
          label: $t('youXiLeiXing'),
          prop: 'type',
          width: 90,
          formatter: (row: any) => {
            const isMenu = row.type === 1
            return h(ElTag, { type: isMenu ? 'success' : 'warning' }, () =>
              isMenu ? $t('jiShi') : $t('yanChi')
            )
          }
        },
        {
          label: $t('youXiMuLu'),
          prop: 'dir',
          minWidth: 120
        },
        {
          label: $t('liShiBiaoMingCheng'),
          prop: 'history_table_name',
          minWidth: 160
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 300,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                { type: 'warning', size: 'small', onClick: () => handleRefresh(row) },
                () => $t('shuaXinPeiZhi')
              ),
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowEditDialog(row) },
                () => $t('bianJi')
              ),
              h(
                ElButton,
                { type: 'danger', size: 'small', onClick: () => handleDelete(row) },
                () => $t('shanChu')
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        typeOptions.value = response.type_list || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  // ---- Switch 开关 ----
  async function handleToggleSwitch(row: any, field: string, value: number) {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await editRoomAttr({ ids: row.id, [field]: value })
      if (result.code === 200) {
        row[field] = value
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
    }
  }

  // ---- 刷新配置 ----
  function handleRefresh(row: any) {
    ElMessageBox.confirm(
      $t('queDingYaoShuaXinPeiZhi'),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    )
      .then(async () => {
        const loadingInstance = ElLoading.service({})
        try {
          const result = await refreshRoomConfig({ room_id: row.id })
          if (result.code === 200) {
            ElMessage.success($t('chengGong'))
          } else {
            ElMessage.error(result.msg || $t('shiBai'))
          }
        } finally {
          loadingInstance.close()
        }
      })
      .catch(() => {})
  }

  // ---- 添加 ----
  function handleShowAddDialog() {
    isAdd.value = true
    Object.assign(form, initForm())
    showEditDialog.value = true
  }

  // ---- 编辑 ----
  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.name = row.name || ''
    form.type_id = row.type_id ?? 0
    form.type = row.type ?? 1
    form.status = row.status ?? 1
    form.reward_status = row.reward_status ?? 1
    form.dir = row.dir || ''
    form.history_table_name = row.history_table_name || ''
    showEditDialog.value = true
  }

  // ---- 提交 ----
  async function submitRow() {
    if (!form.name?.trim()) {
      ElMessage.warning($t('youXiMingChengBuNengWeiKong'))
      return
    }
    submitLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const apiFn = isAdd.value ? addRoom : editRoom
      const payload = isAdd.value
        ? form
        : { ids: form.id, ...form }
      const result = await apiFn(payload)
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showEditDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      submitLoading.value = false
    }
  }

  // ---- 删除 ----
  async function handleDelete(row: any) {
    ElMessageBox.confirm(
      `${$t('queDingYaoShanChuYouXi')} "${row.name}"`,
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    )
      .then(async () => {
        const loadingInstance = ElLoading.service({})
        try {
          const result = await deleteRoom({ ids: row.id })
          if (result.code === 200) {
            ElMessage.success($t('chengGong'))
            await refreshData()
          } else {
            ElMessage.error(result.msg || $t('shiBai'))
          }
        } finally {
          loadingInstance.close()
        }
      })
      .catch(() => {})
  }

  // ---- 批量修改分类 ----
  function handleBatchCategory() {
    const tableRef = artTableRef.value?.elTableRef
    if (!tableRef) return
    const rows = tableRef.getSelectionRows()
    if (!rows.length) {
      ElMessage.warning($t('qingZhiShaoXuanZeYiXiang'))
      return
    }
    batchCategoryTypeId.value = 1
    showBatchCategoryDialog.value = true
  }

  async function confirmBatchCategory() {
    if (!batchCategoryTypeId.value) {
      ElMessage.warning($t('qingXuanZe'))
      return
    }
    const tableRef = artTableRef.value?.elTableRef
    if (!tableRef) return
    const rows = tableRef.getSelectionRows()
    const ids = rows.map((r: any) => r.id).join(',')
    const loadingInstance = ElLoading.service({})
    try {
      const result = await batchUpdateRoomCategory({ type_id: batchCategoryTypeId.value, ids })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showBatchCategoryDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
    }
  }

  // ---- 配置刷新 ----
  async function handleShowRefreshConfig() {
    refreshConfigTypeId.value = null
    refreshConfigRoomId.value = null
    refreshConfigRoomOptions.value = []
    showRefreshConfigDialog.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const result = await getTypeIdName()
      if (result.code === 200) {
        refreshConfigTypeOptions.value = result.data.list || []
        await handleRefreshConfigTypeChange(0)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function handleRefreshConfigTypeChange(typeId: number) {
    refreshConfigRoomId.value = null
    refreshConfigRoomOptions.value = []
    // if (!typeId) return
    const loadingInstance = ElLoading.service({})
    try {
      const result = await getRoomSelect({ 'custom.type_id': typeId })
      if (result.code === 200) {
        refreshConfigRoomOptions.value = result.data.list || []
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function confirmRefreshConfig() {
    refreshConfigLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const result = await refreshRoomConfig({ room_id: refreshConfigRoomId.value })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showRefreshConfigDialog.value = false
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      refreshConfigLoading.value = false
    }
  }

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }
</script>

<style scoped lang="scss"></style>
