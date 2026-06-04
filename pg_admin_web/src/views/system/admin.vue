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
            <ElButton @click="handleShowAddDialog">{{ $t('tianJiaYongHu') }}</ElButton>
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

    <ElDialog v-model="showBindDialog" :title="$t('bangDingGuGe')" width="800" center>
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('googleYanZhengMa')">
          <ElInput v-model="google.qrcode" :placeholder="$t('qingShuRuGuGeYanZhengMa')" />
        </ElFormItem>
        <ElFormItem :label="$t('googleYanZhengMa')">
          <ElImage :src="google.qrcode" fit="cover" style="width: 200px; height: 200px" />
        </ElFormItem>
        <ElFormItem :label="$t('googleYanZhengMa')">
          <span>{{ google.secret }}</span
          >&nbsp;
          <ElButton @click="copy" type="default" size="small">{{ $t('btn.copy') }}</ElButton>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showBindDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="bindSecret">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="800"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('jiaoSeMing')" required>
          <ElTreeSelect
            v-model="form.group_id"
            :data="roleData"
            :props="{ label: 'label', children: 'children' }"
            node-key="id"
            check-strictly
            :placeholder="$t('qingXuanZe')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('yongHuMing')" required>
          <ElInput v-model="form.username" :placeholder="$t('yongHuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('miMa')" :required="!form.id">
          <ElInput v-model="form.pwd" :placeholder="$t('miMa')" />
        </ElFormItem>
        <ElFormItem :label="$t('queRenMiMa')" :required="!form.id">
          <ElInput v-model="form.conf_pwd" :placeholder="$t('queRenMiMa')" />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <ElDialog v-model="showUnbindDialog" :title="$t('jieBangGuGe')" width="800" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{
          $t('ninQueDingYaoJieBangGuGeGuanLiYuanRetdatausername', [google.username])
        }}</span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showUnbindDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="unbindSecret">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <ElDialog v-model="showDeleteDialog" :title="$t('shanChu')" width="800" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{
          $t('ninQueDingYaoShanChuGuanLiYuanRetdatausername', [google.username])
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
  import {
    adminList,
    roleList,
    changeAdminStatus,
    addAdmin,
    editAdmin,
    deleteAdmin,
    googleKey,
    bindGoogleKey,
    unbindGoogleKey
  } from '@/api/system'
  import { ElSwitch, ElMessage, ElButton, ElTreeSelect, ElLoading } from 'element-plus'

  const switchLoading = ref<Record<string, boolean>>({})
  const searchBarRef = ref()
  const searchForm = ref({
    username: undefined,
    status: undefined
  })

  const google = reactive({
    id: 0,
    qrcode: '',
    secret: '',
    username: ''
  })

  const form = reactive<any>({
    id: null,
    group_id: null,
    username: null,
    pwd: null,
    conf_pwd: null
  })

  const roleData = ref()

  const showBindDialog = ref(false)
  const showUnbindDialog = ref(false)
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const formItems = computed(() => [
    {
      label: 'yongHuMing',
      key: 'username',
      type: 'input',
      placeholder: 'yongHuMing',
      clearable: true
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
    refreshUpdate
  } = useTable({
    core: {
      apiFn: adminList,
      apiParams: {
        page: 1,
        page_size: 10,
        username: undefined,
        status: undefined
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 120
        },
        {
          label: 'yongHuMing',
          prop: 'username',
          width: 200
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 200,
          formatter: (row: any) => {
            const isEnabled = row.status === 1
            const switchStatus = (window as any).toggleStatus(row.status)
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus)
            })
          }
        },
        {
          label: 'chuangJianShiJian',
          prop: 'register_time',
          width: 300,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.register_time)
          }
        },
        {
          label: 'zuiHouDengLuShiJian',
          prop: 'last_login_time',
          width: 300,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.last_login_time)
          }
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 320,
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
                $t('bianJi')
              ),
              h(
                ElButton,
                {
                  type: 'success',
                  size: 'small',
                  onClick: () => handleShowBindDialog(row)
                },
                $t('bangDingGuGe')
              ),
              h(
                ElButton,
                {
                  type: 'warning',
                  size: 'small',
                  onClick: () => handleShowUnbindDialog(row)
                },
                $t('jieBangGuGe')
              ),
              h(
                ElButton,
                {
                  type: 'danger',
                  size: 'small',
                  onClick: () => handleShowDeleteDialog(row)
                },
                $t('shanChu')
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

  onMounted(async () => {
    try {
      const result = await roleList()
      if (result.code === 200) {
        roleData.value = result.data.list
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      /* empty */
    }
  })

  const handleStatusChange = async (row: any, value: number) => {
    const key = row.id
    switchLoading.value[key] = true

    try {
      const result = await changeAdminStatus({
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

  function handleReset() {
    resetSearchParams()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    replaceSearchParams(params)
    await getData()
  }

  async function handleShowBindDialog(row: any) {
    try {
      const result = await googleKey({
        uid: row.id,
        username: row.username
      })
      if (result.code === 200) {
        google.id = row.id
        google.qrcode = result.data.qrcode
        google.secret = result.data.secret
        google.username = row.username
        showBindDialog.value = true
      } else {
        ElMessage.error(result.message)
      }
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (error) {
      ElMessage.error('httpMsg.requestFailed')
    }
  }

  async function handleShowUnbindDialog(row: any) {
    google.id = row.id
    google.username = row.username
    showUnbindDialog.value = true
  }

  function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.group_id = null
    form.username = null
    form.pwd = null
    form.conf_pwd = null
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.group_id = row.group.id
    form.username = row.username
    form.pwd = null
    form.conf_pwd = null
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    google.id = row.id
    google.username = row.username
    showDeleteDialog.value = true
  }

  function copy() {
    ;(window as any).copy(google.secret)
    ElMessage.success($t('chengGong'))
  }

  async function bindSecret() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await bindGoogleKey({
        uid: google.id,
        code: google.qrcode
      })
      if (result.code === 200) {
        await getData()
        showBindDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function unbindSecret() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await unbindGoogleKey({
        id: google.id
      })
      if (result.code === 200) {
        await getData()
        showUnbindDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      group_id: form.group_id,
      username: form.username,
      pwd: form.pwd,
      conf_pwd: form.conf_pwd
    }
    try {
      if (form.id) {
        const result = await editAdmin(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addAdmin(params)
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          await getData()
          showEditDialog.value = false
        }
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function deleteRow() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deleteAdmin({
        id: google.id
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
</script>
