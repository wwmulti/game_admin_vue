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
        <ElFormItem :label="$t('yuMing')" required>
          <ElInput v-model="form.domain" :placeholder="$t('yuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('apiYuMing')" required>
          <ElInput v-model="form.api_domain" :placeholder="$t('apiYuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('suoShuPgShangHu')">
          <ElCheckboxGroup v-model="form.pg_arr">
            <ElCheckbox v-for="item in pg" :key="item.id" :value="item.id">
              {{ item.title }}
            </ElCheckbox>
          </ElCheckboxGroup>
        </ElFormItem>
        <ElFormItem :label="$t('suoShuPpShangHu')">
          <ElCheckboxGroup v-model="form.pp_arr">
            <ElCheckbox v-for="item in pp" :key="item.id" :value="item.id">
              {{ item.title }}
            </ElCheckbox>
          </ElCheckboxGroup>
        </ElFormItem>
        <ElFormItem :label="$t('pingTaiFenZu')" required>
          <ElSelect v-model="form.group_id">
            <ElOption
              v-for="item in packageGroup"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
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
        <span style="font-size: 16px">{{ $t('queRenShanChuPingTai') }} - {{ form.title }} ? </span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="deleteRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <ElDialog v-model="showConfigDialog" :title="$t('gongNengPeiZhi')" width="800" center>
      <ElForm ref="formRef" label-width="150px">
        <ElFormItem :label="$t('yaoQingZhuCeHuoDong')" required>
          <ElSelect v-model="activeTypes[0]">
            <ElOption :label="$t('kaiQi')" :value="1" />
            <ElOption :label="$t('weiKaiQi')" :value="0" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('qianDaoHuoDong')" required>
          <ElSelect v-model="activeTypes[1]">
            <ElOption :label="$t('kaiQi')" :value="1" />
            <ElOption :label="$t('weiKaiQi')" :value="0" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('shouChongHuoDong')" required>
          <ElSelect v-model="activeTypes[2]">
            <ElOption :label="$t('kaiQi')" :value="1" />
            <ElOption :label="$t('weiKaiQi')" :value="0" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('sunShiYuanZhuHuoDong')" required>
          <ElSelect v-model="activeTypes[3]">
            <ElOption :label="$t('kaiQi')" :value="1" />
            <ElOption :label="$t('weiKaiQi')" :value="0" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('liXiBaoHuoDong')" required>
          <ElSelect v-model="activeTypes[4]">
            <ElOption :label="$t('kaiQi')" :value="1" />
            <ElOption :label="$t('weiKaiQi')" :value="0" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('xingYunZhuanPanHuoDong')" required>
          <ElSelect v-model="activeTypes[5]">
            <ElOption :label="$t('kaiQi')" :value="1" />
            <ElOption :label="$t('weiKaiQi')" :value="0" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('vipDengJiHuoDong')" required>
          <ElSelect v-model="activeTypes[6]">
            <ElOption :label="$t('kaiQi')" :value="1" />
            <ElOption :label="$t('weiKaiQi')" :value="0" />
          </ElSelect>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showConfigDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="submitConfig">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>
    <ElDialog v-model="showUserDialog" :title="$t('yongHu')" width="1200" center>
      <User
        :package_id="form.package_id"
        v-if="showUserDialog && form.package_id"
        :key="Date.now()"
      ></User>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import {
    packageList,
    addPackage,
    editPackage,
    deletePackage,
    configList,
    editConfig
  } from '@/api/agent'
  import { ElSwitch, ElMessage, ElButton, ElLoading } from 'element-plus'
  import User from '@/views/agent/user.vue'

  const switchLoading = ref<Record<string, boolean>>({})
  const searchBarRef = ref()
  const searchForm = ref({
    title: undefined,
    domain: undefined
  })

  const form = reactive<any>({
    id: null,
    title: null,
    api_domain: null,
    domain: null,
    pp: null,
    pg: null,
    pp_arr: [],
    pg_arr: [],
    group_id: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const showConfigDialog = ref(false)
  const showUserDialog = ref(false)
  const isAdd = ref(false)

  const pg = ref()
  const pp = ref()
  const packageGroup = ref()

  const activeTypes = ref<Record<number, number>>({
    '0': 0,
    '1': 0,
    '2': 0,
    '3': 0,
    '4': 0,
    '5': 0,
    '6': 0
  })

  const formItems = computed(() => [
    {
      label: 'mingCheng',
      key: 'title',
      type: 'input',
      placeholder: 'mingCheng'
    },
    {
      label: 'yuMing',
      key: 'domain',
      type: 'input',
      placeholder: 'yuMing'
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
      apiFn: packageList,
      apiParams: {
        page: 1,
        page_size: 10,
        title: undefined,
        domain: undefined
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
          width: 200
        },
        {
          label: 'yuMing',
          prop: 'domain',
          width: 200
        },
        {
          label: 'jieKouYuMing',
          prop: 'api_domain',
          width: 300
        },
        {
          label: 'zhuCeKaiGuan',
          prop: 'open_register',
          width: 150,
          formatter: (row: any) => {
            const isEnabled = row.open_register === 1
            const switchStatus = (window as any).toggleStatus(row.open_register)
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus)
            })
          }
        },
        {
          label: 'suoShuPgShangHu',
          prop: 'pg',
          width: 200,
          formatter: (row: any) => {
            return (window as any).formatFieldByKeys(row.pg, pg.value, 'id', 'title')
          }
        },
        {
          label: 'suoShuPpShangHu',
          prop: 'pp',
          width: 200,
          formatter: (row: any) => {
            return (window as any).formatFieldByKeys(row.pp, pg.value, 'id', 'title')
          }
        },
        {
          label: 'chuangJianShiJian',
          prop: 'create_time',
          width: 300,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.create_time)
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
                  type: 'success',
                  size: 'small',
                  onClick: () => handleShowConfigDialog(row)
                },
                $t('gongNengPeiZhi')
              ),
              h(
                ElButton,
                {
                  type: 'warning',
                  size: 'small',
                  onClick: () => handleShowUserDialog(row)
                },
                $t('zhangHaoLieBiao')
              ),
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
          pg: response.pg || [],
          pp: response.pp || [],
          group_list: response.group_list || []
        }
      }
    },
    hooks: {
      onSuccess: async (data: any[], response: any) => {
        pg.value = response.pg
        pp.value = response.pp
        packageGroup.value = response.group_list
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
    form.domain = null
    form.api_domain = null
    form.title = null
    form.group_id = null
    form.pg_arr = []
    form.pp_arr = []
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.domain = row.domain
    form.api_domain = row.api_domain
    form.title = row.title
    form.group_id = row.group_id
    form.pg_arr = row.pg ? row.pg.split(',').map(Number) : []
    form.pp_arr = row.pp ? row.pp.split(',').map(Number) : []
    showEditDialog.value = true
  }

  async function handleShowConfigDialog(row: any) {
    form.id = row.id
    try {
      const result = await configList({
        package_id: form.id
      })
      if (result.code === 200) {
        activeTypes.value = parseActiveTypes(result.data)
        showConfigDialog.value = true
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      /* empty */
    }
  }

  function parseActiveTypes(responseData: Record<string, any>): Record<number, number> {
    const activeType: Record<number, number> = {}
    if (!responseData) return activeType
    Object.keys(responseData).forEach((key) => {
      const match = key.match(/^activeType_(\d+)$/)
      if (match) {
        const index = parseInt(match[1], 10)
        activeType[index - 1] = responseData[key] === true ? 1 : 0
      }
    })
    return activeType
  }

  function handleShowUserDialog(row: any) {
    form.package_id = row.id
    showUserDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.title = row.title
    showDeleteDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      group_id: form.group_id,
      title: form.title,
      domain: form.domain,
      api_domain: form.api_domain,
      pg: form.pg_arr.join(','),
      pp: form.pp_arr.join(','),
      open_register: form.open_register
    }
    try {
      if (form.id) {
        const result = await editPackage(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addPackage(params)
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

  async function submitConfig() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      activeType: [
        activeTypes.value[0],
        activeTypes.value[1],
        activeTypes.value[2],
        activeTypes.value[3],
        activeTypes.value[4],
        activeTypes.value[5],
        activeTypes.value[6]
      ]
    }
    try {
      const result = await editConfig(params)
      if (result.code === 200) {
        await getData()
        showConfigDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function deleteRow() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deletePackage({
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

  const handleStatusChange = async (row: any, value: number) => {
    const key = row.id
    switchLoading.value[key] = true
    try {
      const result = await editPackage({
        id: row.id,
        open_register: value,
        is_modify_open_register: true
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
</script>
