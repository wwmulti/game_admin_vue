<template>
  <div>
    <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
      <template #left>
        <ElButton type="primary" @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
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

    <!-- 添加弹窗 -->
    <ElDialog v-model="showAddDialog" :title="$t('tianJia')" width="500" center>
      <ElForm ref="addFormRef" :model="addForm" label-width="120px">
        <ElFormItem :label="$t('bianHao')" required>
          <ElInput v-model="addForm.name" :placeholder="$t('bianHao')" />
        </ElFormItem>
        <ElFormItem :label="$t('fenZuID')" required>
          <ElInput
            v-model="addForm.group_ids"
            type="textarea"
            :rows="6"
            :placeholder="$t('qingShuRuFenZuID')"
          />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showAddDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="addLoading" @click="confirmAdd">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 编辑弹窗 -->
    <ElDialog v-model="showEditDialog" :title="$t('bianJi')" width="500" center>
      <ElForm ref="editFormRef" :model="editForm" label-width="120px">
        <ElFormItem :label="$t('bianHao')" required>
          <ElInput v-model="editForm.name" :placeholder="$t('bianHao')" />
        </ElFormItem>
        <ElFormItem :label="$t('fenZuID')" required>
          <ElInput
            v-model="editForm.group_ids"
            type="textarea"
            :rows="6"
            :placeholder="$t('qingShuRuFenZuID')"
          />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="editLoading" @click="confirmEdit">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive, watch } from 'vue'
  import { $t } from '@/locales'
  import { ElButton, ElInput, ElMessage, ElLoading, ElMessageBox } from 'element-plus'
  import { getRtpTemplateList, addRtpTemplate, editRtpTemplate, deleteRtpTemplate } from '@/api/ttpg'

  defineOptions({ name: 'RtpTemplateMgmt' })

  const props = defineProps<{
    ids: number | string
  }>()

  const artTableRef = ref()

  // 弹窗
  const showAddDialog = ref(false)
  const showEditDialog = ref(false)
  const addLoading = ref(false)
  const editLoading = ref(false)

  const addForm = reactive({ name: '', group_ids: '' })
  const editForm = reactive({ id: null, name: '', group_ids: '' })
  const editRowId = ref<number | null>(null)

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    handleSizeChange,
    handleCurrentChange,
    refreshData
  } = useTable({
    core: {
      apiFn: getRtpTemplateList,
      apiParams: {
        page: 1,
        limit: 20,
        ids: props.ids
      },
      immediate: false,
      columnsFactory: () => [
        {
          type: 'selection',
          width: 50
        },
        {
          label: $t('suoShuYouXi'),
          prop: 'room_id',
          minWidth: 100
        },
        {
          label: $t('bianHao'),
          prop: 'name',
          minWidth: 140
        },
        {
          label: $t('fenZuID'),
          prop: 'group_ids',
          minWidth: 200
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 160,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
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
    }
  })

  // ids 变化时重新请求
  watch(
    () => props.ids,
    () => {
      replaceSearchParams({ ids: props.ids })
      getData()
    },
    { immediate: true }
  )

  // ---- 添加 ----
  function handleShowAddDialog() {
    addForm.name = ''
    addForm.group_ids = ''
    showAddDialog.value = true
  }

  async function confirmAdd() {
    if (!addForm.name || !addForm.group_ids) {
      ElMessage.warning($t('qingTianXieWanZheng'))
      return
    }
    addLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const result = await addRtpTemplate(
        { name: addForm.name, group_ids: addForm.group_ids },
        { ids: props.ids }
      )
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showAddDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      addLoading.value = false
    }
  }

  // ---- 编辑 ----
  function handleShowEditDialog(row: any) {
    editRowId.value = row.id
    editForm.id = row.id
    editForm.name = row.name || ''
    editForm.group_ids = row.group_ids || ''
    showEditDialog.value = true
  }

  async function confirmEdit() {
    if (!editForm.name || !editForm.group_ids) {
      ElMessage.warning($t('qingTianXieWanZheng'))
      return
    }
    editLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const result = await editRtpTemplate(
        {name: editForm.name, group_ids: editForm.group_ids},
        {ids: editRowId.value}
      )
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showEditDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      editLoading.value = false
    }
  }

  // ---- 删除 ----
  async function handleDelete(row: any) {
    ElMessageBox.confirm(
      $t('queDingShanChu'),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    )
      .then(async () => {
        const loadingInstance = ElLoading.service({})
        try {
          const result = await deleteRtpTemplate({ ids: row.id })
          if (result.code === 200) {
            ElMessage.success($t('chengGong'))
            await getData()
          } else {
            ElMessage.error(result.msg || $t('shiBai'))
          }
        } finally {
          loadingInstance.close()
        }
      })
      .catch(() => {})
  }
</script>

<style scoped lang="scss"></style>
