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
    <ElDialog v-model="showDetailDialog" :title="$t('riZhiXiangQing')" width="700" center>
      <ElDescriptions :column="1" border>
        <ElDescriptionsItem label="ID">{{ detailRow.id }}</ElDescriptionsItem>
        <ElDescriptionsItem label="Admin ID">{{ detailRow.admin_id }}</ElDescriptionsItem>
        <ElDescriptionsItem :label="$t('yongHuMing')">{{ detailRow.username }}</ElDescriptionsItem>
        <ElDescriptionsItem label="URL">{{ detailRow.url }}</ElDescriptionsItem>
        <ElDescriptionsItem :label="$t('biaoTi')">{{ detailRow.title }}</ElDescriptionsItem>
        <ElDescriptionsItem :label="$t('neiRong')">
          <pre style="white-space: pre-wrap; word-break: break-all; margin: 0">{{ formatContent(detailRow.content) }}</pre>
        </ElDescriptionsItem>
        <ElDescriptionsItem label="IP">{{ detailRow.ip }}</ElDescriptionsItem>
        <ElDescriptionsItem label="User-Agent">{{ detailRow.useragent }}</ElDescriptionsItem>
        <ElDescriptionsItem :label="$t('chuangJianShiJian')">{{ detailRow.recordTime }}</ElDescriptionsItem>
      </ElDescriptions>
      <template #footer>
        <ElButton @click="showDetailDialog = false">{{ $t('btn.close') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 删除确认弹窗 -->
    <ElDialog v-model="showDeleteDialog" :title="$t('shanChu')" width="500" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{ $t('queRenYaoShanChuRiZhiMa') }}</span>
      </div>
      <template #footer>
        <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="danger" @click="confirmDelete">{{ $t('shanChu') }}</ElButton>
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
      props: { placeholder: $t('yongHuId'), style: { width: '100px' } }
    },
    {
      label: '',
      key: 'username',
      type: 'input',
      props: { placeholder: $t('yongHuMing'), style: { width: '130px' } }
    },
    {
      label: '',
      key: 'title',
      type: 'input',
      props: { placeholder: $t('biaoTi'), style: { width: '160px' } }
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
        'start-placeholder': $t('kaiShiShiJian'),
        'end-placeholder': $t('jieShuShiJian'),
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
          label: $t('yongHuMing'),
          prop: 'username',
          minWidth: 100
        },
        {
          label: $t('biaoTi'),
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
          label: $t('chuangJianShiJian'),
          prop: 'recordTime',
          minWidth: 160,
          formatter: (row: any) => (window as any).formatTimestamp(row.recordTime)
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
                { type: 'primary', size: 'small', onClick: () => handleShowDetail(row) },
                () => $t('btn.detail')
              ),
              h(
                ElButton,
                { type: 'danger', size: 'small', onClick: () => handleShowDeleteDialog(row) },
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
        ElMessage.success($t('shanChuChengGong'))
        showDeleteDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shanChuShiBai'))
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
      ElMessage.warning($t('qingZhiShaoXuanZeYiXiang'))
      return
    }
    const ids = rows.map((r: any) => r.id)
    ElMessageBox.confirm(
      $t('queDingYaoShanChuXuanZhongRiZhi'),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    ).then(async () => {
      const loadingInstance = ElLoading.service({})
      try {
        const result = await delAdminLog({
          ids: ids.join(',')
        })
        if (result.code === 200) {
          ElMessage.success($t('shanChuChengGong'))
          await refreshData()
        } else {
          ElMessage.error(result.msg || $t('shanChuShiBai'))
        }
      } finally {
        loadingInstance.close()
      }
    }).catch(() => {})
  }
</script>

<style scoped lang="scss"></style>
