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
      <ElForm ref="formRef" label-width="150px">
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
        <ElFormItem :label="$t('mingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('mingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuBiao')">
          <ElInput v-model="form.logo" :placeholder="$t('tuBiao')" />
        </ElFormItem>
        <ElFormItem :label="$t('daMaJiSuanBiLi')" required>
          <ElInput type="number" v-model="form.bet_rate" :placeholder="$t('daMaJiSuanBiLi')" />
        </ElFormItem>
        <ElFormItem :label="$t('meiYeXianShiShuLiang')" required>
          <ElInput
            type="number"
            v-model="form.page_game_number"
            :placeholder="$t('meiYeXianShiShuLiang')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')">
          <ElInput type="number" v-model="form.sort" :placeholder="$t('paiXu')" />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
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

    <ElDialog
      v-model="showBetRateDialog"
      :title="$t('daMaJiSuanBiLi')"
      @open="handleOpenBetRate"
      center
    >
      <div class="dialog-content">
        <div class="reset-container">
          <ElButton type="primary" @click="handleResetBetRate">{{ $t('yiJianChongZhi') }}</ElButton>
        </div>

        <div class="game-grid">
          <div v-for="item in gameListData" :key="item.id" class="game-item">
            <span class="game-label">({{ item.name }})</span>
            <ElInput v-model.number="betRateForm[item.id]" placeholder="0" class="game-input" />
          </div>
        </div>

        <div class="dialog-footer">
          <ElButton @click="showBetRateDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="handleEditBetRate">{{ $t('tiJiao') }}</ElButton>
        </div>
      </div>
    </ElDialog>

    <ElDialog v-model="showAgentConfigDialog" :title="$t('daiLiPeiZhi')" width="1200px" center>
      <div class="dialog-content">
        <div class="agent-config-header">
          <div class="header-col">{{ $t('dengJiCengJiDaiLiDengJi') }}</div>
          <div class="header-col">{{ $t('youXiaoTuanDuiHuiYuanShu') }}</div>
          <div class="header-col">{{ $t('tuanDuiZongYeJiYaoQiu') }}</div>
          <div class="header-col">{{ $t('fanDianBiLiWanFenBi') }}</div>
          <div class="header-action"></div>
        </div>

        <div v-for="(item, index) in agentConfigForm" :key="index" class="agent-config-row">
          <div class="header-col">
            <ElInput v-model.number="item.level" type="number" placeholder="1" />
          </div>
          <div class="header-col">
            <ElInput v-model.number="item.members" type="number" placeholder="0" />
          </div>
          <div class="header-col">
            <ElInput v-model.number="item.commission" type="number" placeholder="0" />
          </div>
          <div class="header-col">
            <ElInput v-model.number="item.rate" type="number" placeholder="0" />
          </div>
          <div class="header-action">
            <ElButton
              v-if="index === agentConfigForm.length - 1"
              type="primary"
              link
              @click="handleAddAgentRow"
            >
              <span style="font-size: 24px; font-weight: bold; line-height: 1">+</span>
            </ElButton>
            <ElButton v-else type="danger" link @click="handleRemoveAgentRow(index)">
              <span style="font-size: 24px; font-weight: bold; line-height: 1">-</span>
            </ElButton>
          </div>
        </div>

        <div class="dialog-footer">
          <ElButton @click="showAgentConfigDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="handleEditAgentConfig">{{ $t('tiJiao') }}</ElButton>
        </div>
      </div>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import {
    gameList,
    gameTypeList,
    addGameType,
    editGameType,
    deleteGameType,
    editGameTypeAttr,
    betRateConfig,
    agentConfig
  } from '@/api/game'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    package_id: null,
    id: null,
    status: null,
    name: null
  })

  const form = reactive<any>({
    id: null,
    package_id: null,
    name: null,
    logo: null,
    bet_rate: null,
    page_game_number: null,
    sort: null,
    status: 1
  })

  const switchLoading = ref<Record<string, boolean>>({})
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const showBetRateDialog = ref(false)
  const showAgentConfigDialog = ref(false)
  const isAdd = ref(false)

  const packages = ref()
  const packageMaps = ref()
  const selectPackages = ref()
  const gameListData = ref()

  const betRate = ref()
  const betRateForm = ref<Record<number, number>>({})
  const agentConfigForm = ref<
    Array<{
      level: number | null
      members: number | null
      commission: number | null
      rate: number | null
    }>
  >([])

  const formItems = computed(() => [
    {
      label: 'ID',
      key: 'id',
      type: 'input',
      placeholder: 'ID'
    },
    {
      label: 'mingCheng',
      key: 'name',
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
      apiFn: gameTypeList,
      apiParams: {
        page: 1,
        page_size: 10,
        package_id: null,
        id: null,
        status: null,
        name: null
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 150
        },
        {
          label: 'fenBao',
          prop: 'package_id',
          width: 150,
          formatter: (row: any) => {
            return packageMaps.value?.[row.package_id] || row.package_id
          }
        },
        {
          label: 'mingCheng',
          prop: 'name',
          width: 200
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 150,
          formatter: (row: any) => {
            const isEnabled = row.status === 1
            const switchStatus = isEnabled ? 0 : 1
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus, 'status')
            })
          }
        },
        {
          label: 'tuBiao',
          prop: 'logo',
          width: 150,
          formatter: (row: any) => {
            if (!row.logo) {
              return null
            }
            return h('img', {
              src: row.logo,
              style: {
                width: '80px',
                height: '80px'
              }
            })
          }
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 400,
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
                  type: 'success',
                  size: 'small',
                  onClick: () => handleShowAgentConfigDialog(row)
                },
                () => $t('daiLiPeiZhi')
              ),
              h(
                ElButton,
                {
                  type: 'warning',
                  size: 'small',
                  onClick: () => handleShowBetRateDialog(row)
                },
                () => $t('daMaJiSuanBiLi')
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
    form.package_id = null
    form.name = null
    form.logo = null
    form.bet_rate = null
    form.page_game_number = null
    form.sort = null
    form.status = 1
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.name = row.name
    form.logo = row.logo
    form.bet_rate = row.bet_rate
    form.page_game_number = row.page_game_number
    form.sort = row.sort
    form.status = row.status
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.id = row.id
    form.name = row.name
    showDeleteDialog.value = true
  }

  async function handleShowBetRateDialog(row: any) {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await gameList({
        game_type_id: row.id,
        page_size: 1000,
        page: 1
      })
      if (result.code === 200) {
        form.id = row.id
        betRate.value = row.games_bet_rate
        gameListData.value = result.data.list
        showBetRateDialog.value = true
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function handleShowAgentConfigDialog(row: any) {
    form.id = row.id
    let rawConfig = toRaw(row.agent_config)
    if (typeof rawConfig === 'string' && rawConfig.trim() !== '') {
      try {
        rawConfig = JSON.parse(rawConfig)
      } catch {
        rawConfig = null
      }
    }
    if (rawConfig && Array.isArray(rawConfig) && rawConfig.length > 0) {
      agentConfigForm.value = rawConfig.map((item: any) => ({
        level: Number(item.level) || 1,
        members: item.members ?? null,
        commission: item.commission ?? null,
        rate: item.rate ?? null
      }))
    } else {
      agentConfigForm.value = [{ level: 1, members: null, commission: null, rate: null }]
    }
    showAgentConfigDialog.value = true
  }

  function handleAddAgentRow() {
    const nextLevel = agentConfigForm.value.length + 1
    agentConfigForm.value.push({
      level: nextLevel,
      members: null,
      commission: null,
      rate: null
    })
  }

  function handleRemoveAgentRow(index: number) {
    agentConfigForm.value.splice(index, 1)
    agentConfigForm.value.forEach((item, idx) => {
      item.level = idx + 1
    })
  }

  async function handleEditAgentConfig() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await agentConfig({
        id: form.id,
        agent_config: agentConfigForm.value
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showAgentConfigDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  function handleOpenBetRate() {
    const valuesMap: Record<number, number> = {}
    if (gameListData.value) {
      gameListData.value.forEach((game: any) => {
        valuesMap[game.id] = 0
      })
    }
    if (betRate.value) {
      const pairs = betRate.value.split(';')
      pairs.forEach((pair: string) => {
        if (pair) {
          const [id, value] = pair.split(',')
          if (id) {
            valuesMap[Number(id)] = Number(value) || 0
          }
        }
      })
    }
    betRateForm.value = valuesMap
  }

  function handleResetBetRate() {
    Object.keys(betRateForm.value).forEach((key) => {
      betRateForm.value[Number(key)] = 0
    })
  }

  async function handleEditBetRate() {
    const loadingInstance = ElLoading.service({})
    const resultPairs = gameListData.value.map((game: any) => {
      const val = betRateForm.value[game.id] ?? 0
      return `${game.id},${val}`
    })
    const newBetRateStr = resultPairs.join(';')

    try {
      const result = await betRateConfig({
        id: form.id,
        games_bet_rate: newBetRateStr
      })
      if (result.code === 200) {
        ;(window as any).refreshConfig()
        showBetRateDialog.value = false
        await getData()
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
      package_id: form.package_id,
      name: form.name,
      logo: form.logo,
      bet_rate: form.bet_rate,
      page_game_number: form.page_game_number,
      sort: form.sort,
      status: form.status
    }
    try {
      if (form.id) {
        const result = await editGameType(params)
        if (result.code === 200) {
          ;(window as any).refreshConfig()
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addGameType(params)
        if (result.code === 200) {
          ;(window as any).refreshConfig()
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
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
      const result = await deleteGameType({
        id: form.id
      })
      if (result.code === 200) {
        ;(window as any).refreshConfig()
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

  const handleStatusChange = async (row: any, value: number, field: string) => {
    const key = row.id
    switchLoading.value[key] = true
    try {
      const result = await editGameTypeAttr({
        id: row.id,
        value: value,
        field: field
      })
      if (result.code === 200) {
        ;(window as any).refreshConfig()
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
