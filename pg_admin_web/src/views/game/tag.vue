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
      <ElForm ref="formRef" label-width="200px">
        <ElFormItem :label="$t('fenBao')" required v-if="isAdd">
          <ElSelect v-model="form.package_id">
            <ElOption
              v-for="item in selectPackages"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('biaoQianMingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('biaoQianMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('puYuMingCheng')">
          <ElInput v-model="form.pt_name" :placeholder="$t('puYuMingCheng')" />
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
  import { useTable } from '@/hooks'
  import { gameTagList, addGameTag, editGameTag, deleteGameTag } from '@/api/game'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    id: null,
    name: null,
    pt_name: null,
    package_id: null
  })

  const form = reactive<any>({
    id: null,
    package_id: null,
    name: null,
    pt_name: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const packages = ref()
  const packageMaps = ref()
  const selectPackages = ref()

  const formItems = computed(() => [
    {
      label: 'ID',
      key: 'id',
      type: 'input',
      placeholder: 'ID'
    },
    {
      label: 'biaoQianMingCheng',
      key: 'name',
      type: 'input',
      placeholder: 'biaoQianMingCheng'
    },
    {
      label: 'puYuMingCheng',
      key: 'pt_name',
      type: 'input',
      placeholder: 'puYuMingCheng'
    },
    {
      label: 'fenBao',
      key: 'package_id',
      type: 'select',
      placeholder: 'fenBao',
      options: packages.value
        ? packages.value.map((item: any) => ({
            label: item.title,
            value: item.id
          }))
        : []
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
      apiFn: gameTagList,
      apiParams: {
        page: 1,
        page_size: 10,
        package_id: null,
        id: null,
        name: null,
        pt_name: null
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 300
        },
        {
          label: 'fenBao',
          prop: 'package_id',
          width: 300,
          formatter: (row: any) => {
            return packageMaps.value?.[row.package_id] || row.package_id
          }
        },
        {
          label: 'biaoQianMingCheng',
          prop: 'name',
          width: 350
        },
        {
          label: 'puYuMingCheng',
          prop: 'pt_name',
          width: 350
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 150,
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
    form.package_id = null
    form.name = null
    form.pt_name = null
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.package_id = row.package_id
    form.name = row.name
    form.pt_name = row.pt_name
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.id = row.id
    form.name = row.name
    showDeleteDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      package_id: form.package_id,
      name: form.name,
      pt_name: form.pt_name
    }
    try {
      if (form.id) {
        const result = await editGameTag(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addGameTag(params)
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
      const result = await deleteGameTag({
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
