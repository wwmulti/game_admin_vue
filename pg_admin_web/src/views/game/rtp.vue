<template>
  <div>
    <ElCard class="art-table-card">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElButton type="primary" @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
          <ElButton type="danger" :disabled="!selectedIds.length" @click="handleBatchDelete">{{ $t('piLiangShanChu') }}</ElButton>
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
        @selection-change="handleSelectionChange"
      />
    </ElCard>

    <!-- 添加/编辑弹窗 -->
    <ElDialog v-model="showDialog" :title="isEdit ? $t('bianJi') : $t('tianJia')" width="500px" :close-on-click-modal="false">
      <ElForm ref="formRef" :model="form" label-width="80px">
        <ElFormItem :label="$t('rtp')" prop="rtp">
          <ElInputNumber v-model="form.rtp" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')" prop="status">
          <ElSwitch v-model="form.status" :active-value="1" :inactive-value="0" />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="submitLoading" @click="handleSubmit">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref } from 'vue'
  import { $t } from '@/locales'
  import { ElMessage, ElMessageBox, ElButton, ElDialog, ElForm, ElFormItem, ElInputNumber, ElSwitch, ElTag, ElCard } from 'element-plus'
  import { getRtpList, addRtp, editRtp, delRtp } from '@/api/rtp'

  defineOptions({ name: 'GameRtp' })

  const artTableRef = ref()

  const selectedIds = ref<number[]>([])

  // 弹窗状态
  const showDialog = ref(false)
  const isEdit = ref(false)
  const submitLoading = ref(false)
  const form = ref({ id: 0, rtp: 0, status: 1 })

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    refreshData,
    refreshUpdate,
    handleSizeChange,
    handleCurrentChange
  } = useTable({
    core: {
      apiFn: getRtpList,
      apiParams: { page: 1, limit: 20 },
      columnsFactory: () => [
        { type: 'selection', width: 50 },
        { label: 'ID', prop: 'id', width: 70 },
        { label: $t('rtp'), prop: 'rtp', minWidth: 120 },
        {
          label: $t('zhuangTai'),
          prop: 'status',
          width: 80,
          formatter: (row: any) =>
            h(ElTag, { type: row.status === 1 ? 'success' : 'info' }, () =>
              row.status === 1 ? $t('kaiQi') : $t('jinYong')
            )
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 160,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', { style: 'display: flex; gap: 8px;' }, [
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
      responseAdapter: (response: any) => ({
        records: response.rows || [],
        total: response.total || 0
      })
    }
  })

  function handleSelectionChange(selection: any[]) {
    selectedIds.value = selection.map((item: any) => item.id)
  }

  // ---- 添加 ----
  function handleShowAddDialog() {
    isEdit.value = false
    form.value = { id: 0, rtp: 0, status: 1 }
    showDialog.value = true
  }

  // ---- 编辑 ----
  function handleShowEditDialog(row: any) {
    isEdit.value = true
    form.value = { id: row.id, rtp: row.rtp, status: row.status ?? 1 }
    showDialog.value = true
  }

  // ---- 提交 ----
  async function handleSubmit() {
    submitLoading.value = true
    try {
      let result: any
      if (isEdit.value) {
        result = await editRtp({ ids: form.value.id, rtp: form.value.rtp, status: form.value.status })
      } else {
        result = await addRtp({ rtp: form.value.rtp, status: form.value.status })
      }
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showDialog.value = false
        await refreshUpdate()
      }
    } catch {
      /* empty */
    } finally {
      submitLoading.value = false
    }
  }

  // ---- 删除 ----
  async function handleDelete(row: any) {
    try {
      await ElMessageBox.confirm($t('queRenShanChu'), $t('caoZuoTiShi'), {
        confirmButtonText: $t('queDing'),
        cancelButtonText: $t('quXiao'),
        type: 'warning'
      })
    } catch {
      return
    }
    try {
      const res = await delRtp({ ids: row.id })
      if (res.code === 200) {
        ElMessage.success($t('chengGong'))
        await refreshUpdate()
      }
    } catch {
      /* empty */
    }
  }

  // ---- 批量删除 ----
  async function handleBatchDelete() {
    if (!selectedIds.value.length) return
    try {
      await ElMessageBox.confirm($t('queRenShanChu'), $t('caoZuoTiShi'), {
        confirmButtonText: $t('queDing'),
        cancelButtonText: $t('quXiao'),
        type: 'warning'
      })
    } catch {
      return
    }
    try {
      const res = await delRtp({ ids: selectedIds.value.join(',') })
      if (res.code === 200) {
        ElMessage.success($t('chengGong'))
        await refreshUpdate()
      }
    } catch {
      /* empty */
    }
  }
</script>
