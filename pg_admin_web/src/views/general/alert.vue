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
        <ElFormItem :label="$t('tanChuangLeiXing')" required>
          <ElSelect v-model="form.type">
            <ElOption
              v-for="item in selectAlertTypes"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('tanChuangGuiZe')" required>
          <ElSelect v-model="form.alert_rule">
            <ElOption
              v-for="item in selectAlertRuleTypes"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('lengQueShiJian')" required v-if="form.alert_rule === 4">
          <ElInput v-model="form.alert_hours" :placeholder="$t('danWeiXiaoShi')" />
        </ElFormItem>
        <ElFormItem :label="$t('neiRongLeixing')" required>
          <ElSelect v-model="form.content_type">
            <ElOption :label="$t('tuPian')" :value="1" />
            <ElOption :label="$t('fuWenBen')" :value="2" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('enBiaoTi')" required>
          <ElInput v-model="form.en_title" :placeholder="$t('enBiaoTi')" />
        </ElFormItem>
        <ElFormItem
          :label="$t('enNeiRong')"
          class="editor-form-item"
          v-if="form.content_type === 2"
        >
          <div class="editor-container">
            <Toolbar
              class="editor-toolbar"
              :editor="enEditorRef"
              :default-config="toolbarConfig"
              mode="default"
            />
            <Editor
              style="height: 300px"
              class="editor-content"
              v-model="form.en_content"
              :default-config="editorConfig"
              mode="default"
              @onCreated="handleEnCreated"
            />
          </div>
        </ElFormItem>
        <ElFormItem :label="$t('ptBiaoTi')" required>
          <ElInput v-model="form.pt_title" :placeholder="$t('ptBiaoTi')" />
        </ElFormItem>
        <ElFormItem
          :label="$t('ptNeiRong')"
          class="editor-form-item"
          v-if="form.content_type === 2"
        >
          <div class="editor-container">
            <Toolbar
              class="editor-toolbar"
              :editor="ptEditorRef"
              :default-config="toolbarConfig"
              mode="default"
            />
            <Editor
              style="height: 300px"
              class="editor-content"
              v-model="form.pt_content"
              :default-config="editorConfig"
              mode="default"
              @onCreated="handlePtCreated"
            />
          </div>
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')">
          <ElInput type="number" v-model="form.sort" :placeholder="$t('paiXu')" />
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
  import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
  import { useTable } from '@/hooks'
  import {
    alertList,
    addAlert,
    editAlert,
    deleteAlert,
    changeAlertStatus,
    refreshPackageAlert
  } from '@/api/general'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    name: null,
    package_id: null,
    type: null,
    alert_rule: null,
    status: null
  })

  const toolbarConfig = {}
  const editorConfig = {}

  const enEditorRef = shallowRef()
  const handleEnCreated = (editor: any) => {
    enEditorRef.value = editor
  }

  const ptEditorRef = shallowRef()
  const handlePtCreated = (editor: any) => {
    ptEditorRef.value = editor
  }

  const form = reactive<any>({
    id: null,
    package_id: null,
    name: null,
    alert_type: null,
    alert_rule_type: null,
    alert_hours: null,
    content_type: 1,
    en_title: null,
    pt_title: null,
    en_content: null,
    pt_content: null,
    image: null,
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
  const alertTypes = ref()
  const alertTypeMaps = ref()
  const selectAlertTypes = ref()
  const alertRuleTypes = ref()
  const alertRuleTypeMaps = ref()
  const selectAlertRuleTypes = ref()

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
      options: alertTypes.value
        ? alertTypes.value.map((item: any) => ({
            label: item.name,
            value: item.id
          }))
        : []
    },
    {
      label: 'tanChuangGuiZe',
      key: 'alert_rule',
      type: 'select',
      placeholder: 'tanChuangGuiZe',
      options: alertRuleTypes.value
        ? alertRuleTypes.value.map((item: any) => ({
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
      apiFn: alertList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        status: null,
        name: null,
        type: null,
        alert_rule: null
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
          label: 'mingCheng',
          prop: 'name',
          width: 200
        },
        {
          label: 'paiXu',
          prop: 'sort',
          width: 200
        },
        {
          label: 'tanChuangLeiXing',
          prop: 'type',
          width: 150,
          formatter: (row: any) => {
            return alertTypeMaps.value[row.type]
          }
        },
        {
          label: 'tanChuangGuiZe',
          prop: 'alert_rule',
          width: 150,
          formatter: (row: any) => {
            return alertRuleTypeMaps.value[row.alert_rule]
          }
        },
        {
          label: 'kaiGuan',
          prop: 'status',
          width: 100,
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
          alertType: response.alert_type || [],
          alertRuleType: response.alert_rule_type || []
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
        if (response.alertType && response.alertType.length > 0) {
          alertTypes.value = response.alertType
          alertTypeMaps.value = alertTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectAlertTypes.value = [...toRaw(alertTypes.value)]
          alertTypes.value.unshift({ id: null, name: $t('quanBu') })
        }
        if (response.alertRuleType && response.alertRuleType.length > 0) {
          alertRuleTypes.value = response.alertRuleType
          alertRuleTypeMaps.value = alertRuleTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectAlertRuleTypes.value = [...toRaw(alertRuleTypes.value)]
          alertRuleTypes.value.unshift({ id: null, name: $t('quanBu') })
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
    form.alert_rule = null
    form.alert_hours = null
    form.content_type = 1
    form.en_title = null
    form.pt_title = null
    form.en_content = null
    form.pt_content = null
    form.image = null
    form.status = 1
    form.package_ids = []
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.name = row.name
    form.type = row.type
    form.alert_rule = row.alert_rule
    form.alert_hours = row.alert_hours
    form.content_type = row.content_type
    form.en_title = row.en_title
    form.pt_title = row.pt_title
    form.en_content = row.en_content
    form.pt_content = row.pt_content
    form.image = row.image
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
    if (form.en_content === '<p><br></p>') form.en_content = null
    if (form.pt_content === '<p><br></p>') form.pt_content = null
    const params = {
      id: form.id,
      package_id: form.package_id,
      name: form.name,
      type: form.type,
      alert_rule: form.alert_rule,
      alert_hours: form.alert_hours,
      content_type: form.content_type,
      en_title: form.en_title,
      pt_title: form.pt_title,
      en_content: form.en_content,
      pt_content: form.pt_content,
      image: form.image,
      status: form.status,
      package_ids: form.package_ids?.length ? `${form.package_ids.join(',')}` : ''
    }
    try {
      if (form.id) {
        const result = await editAlert(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addAlert(params)
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
      const result = await deleteAlert({
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
      const result = await changeAlertStatus({
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

  onBeforeUnmount(() => {
    if (enEditorRef.value) {
      enEditorRef.value.destroy()
    }
    if (ptEditorRef.value) {
      ptEditorRef.value.destroy()
    }
  })
  const asyncConfig = async (row: any) => {
    switchLoading.value[row.id] = true
    try {
      const result = await refreshPackageAlert({
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
  .editor-form-item {
    :deep(.el-form-item__content) {
      display: block; /* 核心：防止 flex 弹性拉伸破坏富文本结构 */
    }
  }
  .editor-container {
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    width: 100%;
    margin-bottom: 15px; /* 两个富文本之间留点空隙 */
    &:hover {
      border-color: #c0c4cc;
    }
    .editor-toolbar {
      border-bottom: 1px solid #dcdfe6;
      background-color: #f8fafc;
    }
    .editor-content {
      overflow-y: auto;
    }
  }
</style>
