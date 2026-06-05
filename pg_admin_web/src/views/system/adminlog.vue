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
            <ElButton @click="handleBatchDelete">{{ $t('piLiangShanChu') }}</ElButton>
          </ElSpace>
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

    <!-- 详情弹窗 -->
    <ElDialog v-model="showDetailDialog" title="日志详情" width="700" center>
      <ElDescriptions :column="1" border>
        <ElDescriptionsItem label="ID">{{ detailRow.id }}</ElDescriptionsItem>
        <ElDescriptionsItem label="Admin ID">{{ detailRow.admin_id }}</ElDescriptionsItem>
        <ElDescriptionsItem label="用户名">{{ detailRow.username }}</ElDescriptionsItem>
        <ElDescriptionsItem label="URL">{{ detailRow.url }}</ElDescriptionsItem>
        <ElDescriptionsItem label="标题">{{ detailRow.title }}</ElDescriptionsItem>
        <ElDescriptionsItem label="内容">
          <pre style="white-space: pre-wrap; word-break: break-all; margin: 0">{{ formatContent(detailRow.content) }}</pre>
        </ElDescriptionsItem>
        <ElDescriptionsItem label="IP">{{ detailRow.ip }}</ElDescriptionsItem>
        <ElDescriptionsItem label="User-Agent">{{ detailRow.useragent }}</ElDescriptionsItem>
        <ElDescriptionsItem label="创建时间">{{ detailRow.recordTime }}</ElDescriptionsItem>
      </ElDescriptions>
      <template #footer>
        <ElButton @click="showDetailDialog = false">关闭</ElButton>
      </template>
    </ElDialog>

    <!-- 删除确认弹窗 -->
    <ElDialog v-model="showDeleteDialog" title="删除" width="500" center>
      <div style="text-align: center">
        <span style="font-size: 16px">确定要删除该日志吗？</span>
      </div>
      <template #footer>
        <ElButton @click="showDeleteDialog = false">取消</ElButton>
        <ElButton type="danger" @click="confirmDelete">删除</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed } from 'vue'
  import { ElButton, ElMessage, ElLoading, ElMessageBox } from 'element-plus'
  import { getAdminLog, delAdminLog } from '@/api/system'

  defineOptions({ name: 'AdminLog' })

  const searchBarRef = ref()
  const searchForm = ref<Record<string, any>>({
    adminId: '',
    username: '',
    title: '',
    url: '',
    ip: '',
    createTime: []
  })

  const formItems = computed(() => [
    {
      label: '',
      key: 'adminId',
      type: 'input',
      props: { placeholder: '用户ID', style: { width: '100px' } }
    },
    {
      label: '',
      key: 'username',
      type: 'input',
      props: { placeholder: '用户名', style: { width: '130px' } }
    },
    {
      label: '',
      key: 'title',
      type: 'input',
      props: { placeholder: '标题', style: { width: '160px' } }
    },
    {
      label: '',
      key: 'url',
      type: 'input',
      props: { placeholder: 'URL', style: { width: '180px' } }
    },
    {
      label: '',
      key: 'ip',
      type: 'input',
      props: { placeholder: 'IP', style: { width: '140px' } }
    },
    {
      label: '',
      key: 'createTime',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': '开始时间',
        'end-placeholder': '结束时间',
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    }
  ])

  // 弹窗
  const showDetailDialog = ref(false)
  const showDeleteDialog = ref(false)
  const detailRow = ref<any>({})
  const deleteTargetId = ref<number | null>(null)
  const artTableRef = ref()

  function formatContent(content: string) {
    if (!content) return ''
    try {
      const obj = typeof content === 'string' ? JSON.parse(content) : content
      return JSON.stringify(obj, null, 2)
    } catch {
      return content
    }
  }

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
      apiFn: getAdminLog,
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
          label: '用户名',
          prop: 'username',
          minWidth: 100
        },
        {
          label: '标题',
          prop: 'title',
          minWidth: 160
        },
        {
          label: 'IP',
          prop: 'ip',
          width: 130
        },
        {
          label: 'URL',
          prop: 'url',
          minWidth: 200
        },
        {
          label: '创建时间',
          prop: 'recordTime',
          minWidth: 160
        },
        {
          prop: 'operate',
          label: '操作',
          width: 160,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowDetail(row) },
                () => '详情'
              ),
              h(
                ElButton,
                { type: 'danger', size: 'small', onClick: () => handleShowDeleteDialog(row) },
                () => '删除'
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          records: response.list || [],
          total: response.total || 0
        }
      }
    }
  })

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }

  function handleShowDetail(row: any) {
    detailRow.value = row
    showDetailDialog.value = true
  }

  // 删除
  function handleShowDeleteDialog(row: any) {
    deleteTargetId.value = row.id
    showDeleteDialog.value = true
  }

  async function confirmDelete() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await delAdminLog({
        ids: String(deleteTargetId.value)
      })
      if (result.code === 200) {
        ElMessage.success('删除成功')
        showDeleteDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.msg || '删除失败')
      }
    } finally {
      loadingInstance.close()
    }
  }

  // 批量删除
  function handleBatchDelete() {
    const tableRef = artTableRef.value?.elTableRef
    if (!tableRef) return
    const rows = tableRef.getSelectionRows()
    if (!rows.length) {
      ElMessage.warning('请至少选择一项')
      return
    }
    const ids = rows.map((r: any) => r.id)
    ElMessageBox.confirm(
      '确定要删除选中的日志吗？',
      '提示',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    ).then(async () => {
      const loadingInstance = ElLoading.service({})
      try {
        const result = await delAdminLog({
          ids: ids.join(',')
        })
        if (result.code === 200) {
          ElMessage.success('删除成功')
          await getData()
        } else {
          ElMessage.error(result.msg || '删除失败')
        }
      } finally {
        loadingInstance.close()
      }
    }).catch(() => {})
  }
</script>

<style scoped lang="scss"></style>
