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
          <ElButton @click="handleShowAddDialog">{{ $t('btn.add') }}</ElButton>
          <ElButton @click="handleBatchDelete">{{ $t('piLiangShanChu') }}</ElButton>
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
    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJiaPingTai') : $t('bianJiPingTai')"
      width="600"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('pingTaiMingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('pingTaiMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('yongHuMing')" :required="isAdd">
          <ElInput v-model="form.username" :placeholder="$t('yongHuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('miMa')" :required="isAdd">
          <ElInput
            v-model="form.password"
            type="password"
            :placeholder="isAdd ? $t('miMa') : $t('buXiuGaiMiMaQingLiuKong')"
            show-password
          />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('zhengChang') }}</ElRadio>
            <ElRadio :value="0">{{ $t('jinYong') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="submitRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <!-- 删除确认弹窗 -->
    <ElDialog v-model="showDeleteDialog" :title="$t('shanChu')" width="500" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{ $t('queDingYaoShanChuPingTai', [deleteTargetName]) }}</span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="danger" @click="confirmDelete">{{ $t('shanChu') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <!-- 商户列表弹窗 -->
    <ElDialog
      v-model="showMerchantDialog"
      :title="$t('shangHuLieBiao')"
      width="1200"
      top="5vh"
      center
      destroy-on-close
    >
      <MerchantIndex :platform_id="merchantPlatformId" />
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed } from 'vue'
  import { $t } from '@/locales'
  import {
    ElButton,
    ElMessage,
    ElLoading,
    ElMessageBox,
    ElTag
  } from 'element-plus'
  import { getPlatformList, addPlatform, editPlatform, deletePlatform } from '@/api/platform'
  import MerchantIndex from '@/views/merchant/index.vue'

  const searchBarRef = ref()
  const searchForm = ref<Record<string, any>>({
    name: '',
    status: '',
    createtime: [],
    updatetime: []
  })

  const formItems = computed(() => [
    {
      label: '',
      key: 'name',
      type: 'input',
      props: { placeholder: $t('niCheng'), style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'status',
      type: 'select',
      props: {
        placeholder: $t('zhuangTai'),
        style: { width: '120px' },
        clearable: true,
        options: [
          { label: $t('zhengChang'), value: 1 },
          { label: $t('jinYong'), value: 0 }
        ]
      }
    },
    {
      label: '',
      key: 'createtime',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('chuangJianKaiShiShiJian'),
        'end-placeholder': $t('chuangJianJieShuShiJian'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    },
    {
      label: '',
      key: 'updatetime',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('gengXinKaiShiShiJian'),
        'end-placeholder': $t('gengXinJieShuShiJian'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    }
  ])

  // 弹窗
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const showMerchantDialog = ref(false)
  const merchantPlatformId = ref<number | null>(null)
  const isAdd = ref(false)
  const deleteTargetName = ref('')
  const deleteTargetId = ref<number | null>(null)
  const artTableRef = ref()

  const form = reactive<any>({
    id: null,
    name: '',
    username: '',
    password: '',
    status: 1
  })

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
      apiFn: getPlatformList,
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
          minWidth: 120
        },
        {
          label: $t('niCheng'),
          prop: 'name',
          minWidth: 140
        },
        {
          label: $t('zhuangTai'),
          prop: 'status',
          width: 90,
          formatter: (row: any) => {
            const isNormal = row.status == 1 || row.status === 'normal'
            return h(ElTag, { type: isNormal ? 'success' : 'danger' }, () =>
              isNormal ? $t('zhengChang') : $t('jinYong')
            )
          }
        },
        {
          label: $t('chuangJianShiJian'),
          prop: 'createtime',
          minWidth: 160,
          formatter: (row: any) => (window as any).formatTimestamp(row.createtime)
        },
        {
          label: $t('gengXinShiJian'),
          prop: 'updatetime',
          minWidth: 160,
          formatter: (row: any) => (window as any).formatTimestamp(row.updatetime)
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 240,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowMerchant(row) },
                () => $t('shangHuLieBiao')
              ),
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowEditDialog(row) },
                () => $t('bianJi')
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
          records: response.rows || [],
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

  // 添加
  function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.name = ''
    form.username = ''
    form.password = ''
    form.status = 1
    showEditDialog.value = true
  }

  // 编辑
  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.name = row.name
    form.username = row.username
    form.password = ''
    form.status = row.status
    showEditDialog.value = true
  }

  // 商户列表
  function handleShowMerchant(row: any) {
    merchantPlatformId.value = row.id
    showMerchantDialog.value = true
  }

  async function submitRow() {
    const loadingInstance = ElLoading.service({})
    try {
      if (isAdd.value) {
        const result = await addPlatform({
          name: form.name,
          username: form.username,
          password: form.password,
          status: form.status
        })
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          showEditDialog.value = false
          await getData()
        } else {
          ElMessage.error(result.msg || $t('shiBai'))
        }
      } else {
        const params: any = {
          id: form.id,
          name: form.name,
          username: form.username,
          status: form.status
        }
        if (form.password) {
          params.password = form.password
        }
        const result = await editPlatform(params)
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          showEditDialog.value = false
          await getData()
        } else {
          ElMessage.error(result.msg || $t('shiBai'))
        }
      }
    } finally {
      loadingInstance.close()
    }
  }

  // 删除
  function handleShowDeleteDialog(row: any) {
    deleteTargetId.value = row.id
    deleteTargetName.value = row.name || row.username
    showDeleteDialog.value = true
  }

  async function confirmDelete() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deletePlatform({
        ids: deleteTargetId.value
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showDeleteDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
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
      $t('queDingYaoShanChuXuanZhongPingTai'),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    )
      .then(async () => {
        const loadingInstance = ElLoading.service({})
        try {
          const result = await deletePlatform({
            ids: ids.join(',')
          })
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
