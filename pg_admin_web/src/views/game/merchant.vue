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
      width="800"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('mingCheng')" required>
          <ElInput v-model="form.title" :placeholder="$t('mingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('yuMing')">
          <ElInput v-model="form.domain" :placeholder="$t('yuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('token')">
          <ElInput v-model="form.token" :placeholder="$t('token')" />
        </ElFormItem>
        <ElFormItem :label="$t('miYue')">
          <ElInput v-model="form.secret" :placeholder="$t('miYue')" />
        </ElFormItem>
        <ElFormItem :label="$t('biZhong')">
          <ElInput v-model="form.currency" :disabled="true" :placeholder="$t('biZhong')" />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('leiXing')">
          <ElRadioGroup v-model="form.type">
            <ElRadio :value="0">PG</ElRadio>
            <ElRadio :value="1">PP</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('gongYingShang')">
          <ElRadioGroup v-model="form.supplier_type">
            <ElRadio :value="0">{{ $t('ziYan') }}</ElRadio>
            <ElRadio :value="1">{{ $t('guanFang') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <ElDialog
      v-model="showPullGameDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="800px"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('fenBao')" required>
          <ElSelect v-model="form.package_id">
            <ElOption
              v-for="item in selectPackages"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showPullGameDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="pullGame">{{ $t('tiJiao') }}</ElButton>
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
  import { useTable } from '@/hooks'
  import {
    merchantList,
    addMerchant,
    editMerchant,
    deleteMerchant,
    pullMerchantGame
  } from '@/api/game'
  import { ElMessage, ElButton, ElLoading } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    title: null,
    status: null
  })

  const form = reactive<any>({
    id: null,
    title: null,
    domain: null,
    token: null,
    secret: null,
    currency: null,
    type: null,
    supplier_type: null,
    status: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const showPullGameDialog = ref(false)
  const isAdd = ref(false)

  const packages = ref()
  const packageMaps = ref()
  const selectPackages = ref()

  const formItems = computed(() => [
    {
      label: 'mingCheng',
      key: 'title',
      type: 'input',
      placeholder: 'mingCheng'
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
    refreshData
  } = useTable({
    core: {
      apiFn: merchantList,
      apiParams: {
        page: 1,
        page_size: 10,
        title: null,
        status: null
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 200
        },
        {
          label: 'mingCheng',
          prop: 'title',
          width: 250
        },
        {
          label: 'leiXing',
          prop: 'type',
          width: 250,
          formatter: (row: any) => {
            if (row.type === 0) {
              return 'PG'
            } else if (row.type === 1) {
              return 'PP'
            } else {
              return
            }
          }
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 250,
          formatter: (row: any) => {
            if (row.status === 0) {
              return $t('guanBi')
            } else if (row.status === 1) {
              return $t('kaiQi')
            } else {
              return
            }
          }
        },
        {
          label: 'gongYingShang',
          prop: 'supplier_type',
          width: 250,
          formatter: (row: any) => {
            if (row.supplier_type === 0) {
              return $t('ziYan')
            } else if (row.supplier_type === 1) {
              return $t('guanFang')
            } else {
              return
            }
          }
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 250,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                {
                  type: 'success',
                  size: 'small',
                  onClick: () => handleShowPullGameDialog(row)
                },
                () => $t('laQuYouXi')
              ),
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
          packages: response.packages || []
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
    form.title = null
    form.domain = null
    form.token = null
    form.secret = null
    form.currency = 'BRL'
    form.type = 0
    form.supplier_type = 0
    form.status = 1
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.title = row.title
    form.domain = row.domain
    form.token = row.token
    form.secret = row.secret
    form.currency = row.currency
    form.type = row.type
    form.supplier_type = row.supplier_type
    form.status = row.status
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.id = row.id
    form.name = row.name
    showDeleteDialog.value = true
  }

  function handleShowPullGameDialog(row: any) {
    form.id = row.id
    showPullGameDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      title: form.title,
      domain: form.domain,
      token: form.token,
      secret: form.secret,
      currency: form.currency,
      type: form.type,
      supplier_type: form.supplier_type,
      status: form.status
    }
    try {
      if (form.id) {
        const result = await editMerchant(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addMerchant(params)
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          await getData()
          showEditDialog.value = false
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
      const result = await deleteMerchant({
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

  async function pullGame() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await pullMerchantGame({
        id: form.id,
        package_id: form.package_id
      })
      if (result.code === 200) {
        await getData()
        showPullGameDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }
</script>

<style scoped>
  .dialog-content {
    padding: 10px 20px;
  }

  .reset-container {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
  }

  .game-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    column-gap: 40px;
    row-gap: 16px;
  }

  .game-item {
    display: flex;
    align-items: center;
    justify-content: flex-end;
  }

  .game-label {
    font-size: 14px;
    color: #606266;
    margin-right: 12px;
    white-space: nowrap;
  }

  .game-input {
    width: 100px;
  }

  .agent-config-header {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e4e7ed;
  }

  .agent-config-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
  }

  .header-col {
    flex: 1;
    text-align: center;
    padding: 0 8px;
    font-size: 14px;
    color: #606266;
    font-weight: bold;
  }

  .agent-config-row .header-col {
    font-weight: normal;
  }

  .header-action {
    width: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .dialog-footer {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
  }
</style>
