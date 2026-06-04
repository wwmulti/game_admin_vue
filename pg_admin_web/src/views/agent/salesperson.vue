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
        <ElFormItem :label="$t('yongHuMing')" required>
          <ElInput v-model="form.username" :placeholder="$t('yongHuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('miMa')" required>
          <ElInput type="password" v-model="form.password" :placeholder="$t('miMa')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuanDuiMingCheng')" required>
          <ElInput v-model="form.title" :placeholder="$t('tuanDuiMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('suoShuTuanDui')" required>
          <ElSelect v-model="form.pid">
            <ElOption v-for="item in selectTeams" :key="item.id" :label="item.title" :value="item.id" />
          </ElSelect>
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
        <span style="font-size: 16px">{{ $t('shanChu') }} - {{ form.title }} ? </span>
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
  import { salespersonList, addSalesperson, editSalesperson, deleteSalesperson } from '@/api/team'
  import { ElMessage, ElButton, ElLoading } from 'element-plus'
  import { computed } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    title: undefined,
    domain: undefined
  })

  const form = reactive<any>({
    id: null,
    pid: null,
    username: null,
    password: null,
    title: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const packages = ref()
  const packageMaps = ref()
  const teams = ref()
  const selectTeams = ref()
  const teamMaps = ref()

  const formItems = computed(() => [
    {
      label: 'mingCheng',
      key: 'title',
      type: 'input',
      placeholder: 'mingCheng',
      clearable: true
    },
    {
      label: 'pingTai',
      key: 'package_id',
      type: 'select',
      placeholder: 'pingTai',
      options: packages.value
        ? packages.value.map((item: any) => ({
            label: item.title,
            value: item.id
          }))
        : []
    },
    {
      label: 'suoShuTuanDui',
      key: 'pid',
      type: 'select',
      placeholder: 'suoShuTuanDui',
      options: teams.value
        ? teams.value.map((item: any) => ({
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
    searchParams
  } = useTable({
    core: {
      apiFn: salespersonList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        title: null,
        package_id: null
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 120
        },
        {
          label: 'mingCheng',
          prop: 'title',
          width: 150
        },
        {
          label: 'yongHuMing',
          prop: 'username',
          width: 150
        },
        {
          label: 'tuanDuiId',
          prop: 'role_id',
          width: 150
        },
        {
          label: 'suoShuTuanDui',
          prop: 'pid',
          width: 150,
          formatter: (row: any) => {
            return teamMaps.value[row.pid]
          }
        },
        {
          label: 'pingTai',
          prop: 'package_id',
          width: 150,
          formatter: (row: any) => {
            return packageMaps.value[row.package_id]
          }
        },
        {
          label: 'fenXiangLianJie',
          prop: 'share',
          width: 400,
          formatter: (row: any) => {
            const domain = (window as any).formatFieldByKeys(
              row.package_id,
              packages.value,
              'id',
              'domain'
            )
            return domain + '/#/home?inviter_id=' + row.role_id
          }
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 200,
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
          total: response.total || 0,
          packages: response.packages || [],
          teams: response.team_list || []
        }
      }
    },
    hooks: {
      onSuccess: async (data: any[], response: any) => {
        if (response.packages.length > 0) {
          packages.value = response.packages
          packageMaps.value = packages.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          packages.value.unshift({ id: null, title: $t('quanBu') })
        }
        if (response.teams.length > 0) {
          teams.value = response.teams
          teamMaps.value = teams.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          selectTeams.value = [...toRaw(teams.value)]
          teams.value.unshift({ id: null, title: $t('quanBu') })
        }
        searchParams.need_reload = 0
      }
    }
  })

  onMounted(async () => {})

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
    form.pid = null
    form.username = null
    form.password = null
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.title = row.title
    form.pid = row.pid
    form.username = row.username
    form.password = row.password
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.title = row.title
    showDeleteDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      title: form.title,
      pid: form.pid,
      username: form.username,
      password: form.password
    }
    try {
      if (form.id) {
        const result = await editSalesperson(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addSalesperson(params)
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
      const result = await deleteSalesperson({
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
