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
            <ElButton @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
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

    <!-- 新增/编辑弹窗 -->
    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJiaGuanLiYuan') : $t('bianJiGuanLiYuan')"
      width="600"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('yongHuMing')" required>
          <ElInput v-model="form.username" :placeholder="$t('yongHuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('niCheng')">
          <ElInput v-model="form.nickname" :placeholder="$t('niCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('miMa')" :required="isAdd">
          <ElInput v-model="form.password" type="password" :placeholder="$t('miMa')" show-password />
        </ElFormItem>
        <ElFormItem :label="$t('suoShuZuBie')" required>
          <ElTreeSelect
            v-model="form.groupId"
            :data="roleTree"
            :props="{ label: 'label', children: 'children' }"
            node-key="id"
            check-strictly
            default-expand-all
            filterable
            :placeholder="$t('qingXuanZe')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio value="normal">{{ $t('zhengChang') }}</ElRadio>
            <ElRadio value="hidden">{{ $t('yinCang') }}</ElRadio>
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
        <span style="font-size: 16px">{{
          $t('queDingYaoShanChuYongHu', [deleteTargetName])
        }}</span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="danger" @click="confirmDelete">{{ $t('shanChu') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>
<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed } from 'vue'
  import { ElButton, ElMessage, ElLoading, ElMessageBox, ElTreeSelect, ElSwitch } from 'element-plus'
  import { getAdminList, addAdminItem, editAdminItem, deleteAdminItem, getRoleList, editAdminAttr, unbindingAdmin } from '@/api/system'

  const searchBarRef = ref()
  const searchForm = ref<Record<string, any>>({
    id: '',
    username: '',
    nickname: '',
    status: '',
    createTime: []
  })

  const formItems = computed(() => [
    {
      label: '',
      key: 'id',
      type: 'input',
      props: { placeholder: 'ID', style: { width: '100px' } }
    },
    {
      label: '',
      key: 'username',
      type: 'input',
      props: { placeholder: $t('yongHuMing'), style: { width: '140px' } }
    },
    {
      label: '',
      key: 'nickname',
      type: 'input',
      props: { placeholder: $t('niCheng'), style: { width: '140px' } }
    },
    {
      label: '',
      key: 'status',
      type: 'select',
      props: {
        placeholder: 'xuanZe',
        style: { width: '120px' },
        clearable: true,
        options: [
          { label: $t('zhengChang'), value: 'normal' },
          { label: $t('yinCang'), value: 'hidden' }
        ]
      }
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
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)
  const deleteTargetName = ref('')
  const deleteTargetId = ref<number | null>(null)
  const artTableRef = ref()

  // 角色树
  const roleTree = ref<any[]>([])

  const form = reactive<any>({
    id: null,
    username: '',
    nickname: '',
    password: '',
    groupId: null,
    status: 'normal'
  })

  function buildRoleTree(list: any[]) {
    const map = new Map<number, any>()
    const roots: any[] = []
    for (const item of list) {
      map.set(item.id, { ...item, label: item.name, children: [] })
    }
    for (const item of list) {
      const node = map.get(item.id)
      const parent = map.get(item.pid)
      if (parent) {
        parent.children.push(node)
      } else {
        roots.push(node)
      }
    }
    roleTree.value = roots
  }

  async function loadRoleTree() {
    const result = await getRoleList()
    if (result.code === 200) {
      buildRoleTree(result.data.list || [])
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
      apiFn: getAdminList,
      apiParams: {
        page: 1,
        limit: 20
      },
      columnsFactory: () => [
        {
          type: 'selection',
          width: 50,
          selectable: (row: any) => row.id !== 1
        },
        {
          label: 'ID',
          prop: 'id',
          width: 70
        },
        {
          label: 'yongHuMing',
          prop: 'username',
          minWidth: 120
        },
        {
          label: 'niCheng',
          prop: 'nickname',
          minWidth: 120
        },
        {
          label: 'suoShuZuBie',
          prop: 'groupName',
          minWidth: 120
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 100,
          formatter: (row: any) => {
            return h(ElSwitch, {
              modelValue: row.status === 'normal',
              activeValue: true,
              inactiveValue: false,
              onChange: async (val: string | number | boolean) => {
                const loadingInstance = ElLoading.service({ fullscreen: true, text: '' })
                try {
                  const result = await editAdminAttr({ id: row.id, field: 'status', value: val ? 'normal' : 'hidden' })
                  if (result.code === 200) {
                    ElMessage.success($t('chengGong'))
                    await refreshData()
                  } else {
                    ElMessage.error(result.msg || $t('shiBai'))
                  }
                } finally {
                  loadingInstance.close()
                }
              }
            })
          }
        },
        {
          label: $t('guGeYanZheng'),
          prop: 'google_switch',
          width: 110,
          formatter: (row: any) => {
            return h(ElSwitch, {
              modelValue: !!row.google_switch,
              activeValue: true,
              inactiveValue: false,
              onChange: async (val: string | number | boolean) => {
                const loadingInstance = ElLoading.service({ fullscreen: true, text: '' })
                try {
                  const result = await editAdminAttr({ id: row.id, field: 'google_switch', value: val ? 1 : 0 })
                  if (result.code === 200) {
                    ElMessage.success($t('chengGong'))
                    await refreshData()
                  } else {
                    ElMessage.error(result.msg || $t('shiBai'))
                  }
                } finally {
                  loadingInstance.close()
                }
              }
            })
          }
        },
        {
          label: 'zuiHouDengLuShiJian',
          prop: 'loginTimeFormatted',
          minWidth: 160,
          formatter: (row: any) => (window as any).formatTimestamp(row.loginTimeFormatted)
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 210,
          fixed: 'right',
          formatter: (row: any) => {
            // 超级管理员组（pid=0）不显示操作按钮
            if (row.id === 1) return null
            return  h('div', [
                h(
                  ElButton,
                  { type: 'warning',size: 'small', disabled: !row.google_verify, onClick: () => handleUnbindGoogle(row) },
                  () => $t('jieBangGuGe')
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
            ].filter(Boolean))
          }
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

  onMounted(async () => {
    const roleResult = await getRoleList()
    if (roleResult.code === 200) {
      buildRoleTree(roleResult.data.list || [])
    }
  })

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }

  // 解绑谷歌验证
  async function handleUnbindGoogle(row: any) {
    ElMessageBox.confirm(
      $t('queDingYaoJieBangGuGeYanZheng', [row.username]),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    ).then(async () => {
      const loadingInstance = ElLoading.service({})
      try {
        const result = await unbindingAdmin({ id: row.id })
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          await refreshData()
        } else {
          ElMessage.error(result.msg || $t('shiBai'))
        }
      } finally {
        loadingInstance.close()
      }
    }).catch(() => {})
  }

  // 添加
  async function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.username = ''
    form.nickname = ''
    form.password = ''
    form.groupId = null
    form.status = 'normal'
    await loadRoleTree()
    showEditDialog.value = true
  }

  // 编辑
  async function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.username = row.username
    form.nickname = row.nickname
    form.password = ''
    form.groupId = row.group_id
    form.status = row.status
    await loadRoleTree()
    showEditDialog.value = true
  }

  async function submitRow() {
    const loadingInstance = ElLoading.service({})
    try {
      if (isAdd.value) {
        const result = await addAdminItem({
          username: form.username,
          nickname: form.nickname,
          password: form.password,
          groupId: form.groupId,
          status: form.status
        })
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          showEditDialog.value = false
          await refreshData()
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const params: any = {
          id: form.id,
          username: form.username,
          nickname: form.nickname,
          groupId: form.groupId,
          status: form.status
        }
        if (form.password) {
          params.password = form.password
        }
        const result = await editAdminItem(params)
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          showEditDialog.value = false
          await refreshData()
        } else {
          ElMessage.error(result.message)
        }
      }
    } finally {
      loadingInstance.close()
    }
  }

  // 删除
  function handleShowDeleteDialog(row: any) {
    deleteTargetId.value = row.id
    deleteTargetName.value = row.username
    showDeleteDialog.value = true
  }

  async function confirmDelete() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deleteAdminItem({
        ids: deleteTargetId.value
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showDeleteDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.message)
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
      $t('queRenYaoShanChuZheXieYongHuMa'),
      $t('tiShi'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    ).then(async () => {
      const loadingInstance = ElLoading.service({})
      try {
        const result = await deleteAdminItem({
          ids: ids.join(',')
        })
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          await refreshData()
        } else {
          ElMessage.error(result.message)
        }
      } finally {
        loadingInstance.close()
      }
    }).catch(() => {})
  }
</script>

<style scoped lang="scss"></style>
