<template>
  <div>
    <ElCard class="art-table-card">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElSpace wrap>
            <ElButton @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
          </ElSpace>
        </template>
      </ArtTableHeader>
      <ArtTable
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
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="600"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('fuJi')" required>
          <ElTreeSelect
            default-expand-all
            v-model="form.pid"
            :data="selectRoles"
            :props="{ label: 'label', children: 'children' }"
            node-key="id"
            check-strictly
            filterable
            :placeholder="$t('qingXuanZe')"
            :clearable="false"
          />
        </ElFormItem>
        <ElFormItem :label="$t('jiaoSeMing')" required>
          <ElInput v-model="form.role_name" :placeholder="$t('qingTianXieJiaoSeMing')" />
        </ElFormItem>
        <ElFormItem v-if="!isAdd" :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio value="normal">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio value="disabled">{{ $t('guanBi') }}</ElRadio>
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

    <!-- 授权弹窗 -->
    <ElDialog v-model="showAuthDialog" :title="$t('jiaoSeShouQuan')" width="500" center>
      <div style="max-height: 500px; overflow-y: auto">
        <el-tree
          ref="authTreeRef"
          :data="authMenuList"
          show-checkbox
          node-key="id"
          default-expand-all
          :props="{ label: 'title', children: 'children' }"
        />
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showAuthDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="submitAuth">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <!-- 删除确认弹窗 -->
    <ElDialog v-model="showDeleteDialog" :title="$t('shanChu')" width="500" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{
          $t('ninQueDingYaoShanChuJiaoSeRetdatatitle', [form.role_name])
        }}</span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="danger" @click="deleteRow">{{ $t('shanChu') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>
<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { h, ref, reactive, nextTick } from 'vue'
  import { ElButton, ElMessage, ElLoading, ElTreeSelect, ElTag } from 'element-plus'
  import { getRoleList, addRoleItem, editRoleItem, deleteRoleItem, getRoleRules, updateRoleRules } from '@/api/system'

  // 弹窗
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  // 授权相关
  const showAuthDialog = ref(false)
  const authRoleId = ref(0)
  const authMenuList = ref<any[]>([])
  const authTreeRef = ref()

  // 角色树选择器
  const selectRoles = ref<any[]>([])

  const form = reactive<any>({
    id: null,
    pid: null,
    role_name: '',
    status: 'normal'
  })

  function buildSelectTree(list: any[]) {
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
    selectRoles.value = roots
  }

  async function loadRoleTree() {
    const result = await getRoleList()
    if (result.code === 200) {
      buildSelectTree(result.data.list || [])
    }
  }

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    handleSizeChange,
    handleCurrentChange,
    refreshData
  } = useTable({
    core: {
      apiFn: getRoleList,
      apiParams: {
        page: 1,
        limit: 20
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 80
        },
        {
          label: 'PID',
          prop: 'pid',
          width: 80
        },
        {
          label: 'jiaoSeMingCheng',
          prop: 'name',
          minWidth: 180
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 100,
          formatter: (row: any) => {
            const isNormal = row.status === 'normal'
            return h(ElTag, { type: isNormal ? 'success' : 'danger' }, () =>
              isNormal ? $t('kaiQi') : $t('guanBi')
            )
          }
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 280,
          fixed: 'right',
          formatter: (row: any) => {
            // 超级管理员组（pid=0）不显示操作按钮
            if (row.pid === 0) return null
            return h('div', [
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowAuthDialog(row) },
                () => $t('shouQuan')
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
    },
    hooks: {
      onSuccess: (records: any[]) => {
        if (records.length) {
          buildSelectTree(records)
        }
      }
    }
  })

  async function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.pid = null
    form.role_name = ''
    form.status = 'normal'
    await loadRoleTree()
    form.pid = selectRoles.value.length ? selectRoles.value[0].id : null
    showEditDialog.value = true
  }

  async function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.pid = row.pid
    form.role_name = row.name
    form.status = row.status
    await loadRoleTree()
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.id = row.id
    form.role_name = row.name
    showDeleteDialog.value = true
  }

  async function handleShowAuthDialog(row: any) {
    authRoleId.value = row.id
    const loadingInstance = ElLoading.service({})
    try {
      const result = await getRoleRules({ id: row.id })
      if (result.code === 200) {
        authMenuList.value = result.data.list || []
        showAuthDialog.value = true
        // 在 DOM 渲染后设置选中项，逐个设置避免父级自动勾选全部子节点
        nextTick(() => {
          const tree = authTreeRef.value
          if (!tree) return
          const ids = result.data.auth_ids || []
          tree.setCheckedKeys([])
          ids.forEach((id: number) => {
            tree.setChecked(id, true, false)
          })
        })
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function submitAuth() {
    const tree = authTreeRef.value
    if (!tree) return
    const checkedKeys = tree.getCheckedKeys() || []
    const halfCheckedKeys = tree.getHalfCheckedKeys() || []
    const loadingInstance = ElLoading.service({})
    try {
      const result = await updateRoleRules({
        id: authRoleId.value,
        auth_rule_ids: [...checkedKeys, ...halfCheckedKeys].join(',')
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showAuthDialog.value = false
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function submitRow() {
    const loadingInstance = ElLoading.service({})
    try {
      if (form.id) {
        const result = await editRoleItem({
          id: form.id,
          pid: form.pid || 0,
          role_name: form.role_name,
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
        const result = await addRoleItem({
          pid: form.pid || 0,
          role_name: form.role_name
        })
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

  async function deleteRow() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deleteRoleItem({
        id: form.id
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
</script>

<style scoped lang="scss"></style>
