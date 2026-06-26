<template>
  <div>
    <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
      <template #left>
        <ElSpace wrap>
          <ElButton type="primary" @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
          <ElButton @click="handleShowTemplateDialog">{{ $t('fanJiangMoBanGuanLi') }}</ElButton>
          <ElButton @click="handleBatchUpdates">{{ $t('yiJianPeiZhi') }}</ElButton>
          <ElButton @click="handleBatchConfig">{{ $t('yiJianTianJiaPeiZhi') }}</ElButton>
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

    <!-- 添加弹窗 -->
    <ElDialog v-model="showAddDialog" :title="$t('tianJia')" width="500" center>
      <ElForm ref="addFormRef" :model="addForm" label-width="120px">
        <ElFormItem :label="$t('xiaZhuJinE')" required>
          <ElInputNumber v-model="addForm.bet_amount" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('suoShuFanJiangMoBan')" required>
          <ElSelect v-model="addForm.reward_id" :placeholder="$t('qingXuanZe')" filterable style="width:100%">
            <ElOption
              v-for="(name, id) in roomGroupConfig"
              :key="id"
              :label="name"
              :value="id"
            />
          </ElSelect>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showAddDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="addLoading" @click="confirmAdd">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 编辑弹窗 -->
    <ElDialog v-model="showEditDialog" :title="$t('bianJi')" width="500" center>
      <ElForm ref="editFormRef" :model="editForm" label-width="120px">
        <ElFormItem :label="$t('xiaZhuJinE')" required>
          <ElInputNumber v-model="editForm.bet_amount" :min="0" style="width:100%" />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="editLoading" @click="confirmEdit">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 返奖模板管理弹窗 -->
    <ElDialog v-model="showTemplateDialog" :title="$t('fanJiangMoBanGuanLi')" width="70%" top="5vh" center>
      <TemplateMgmt :ids="roomId" />
    </ElDialog>

    <!-- 一键配置弹窗 -->
    <ElDialog v-model="showBatchUpdatesDialog" :title="$t('yiJianPeiZhi')" width="500" center>
      <ElForm label-width="120px">
        <ElFormItem :label="$t('xuanZeFenZu')" required>
          <ElSelect v-model="batchRewardId" :placeholder="$t('qingXuanZe')" filterable style="width:100%">
            <ElOption
              v-for="(name, id) in roomGroupConfig"
              :key="id"
              :label="name"
              :value="Number(id)"
            />
          </ElSelect>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showBatchUpdatesDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" @click="confirmBatchUpdates">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 一键添加配置弹窗 -->
    <ElDialog v-model="showBatchConfigDialog" :title="$t('yiJianTianJiaPeiZhi')" width="500" center>
      <ElForm label-width="120px">
        <ElFormItem :label="$t('xuanZeFenZu')" required>
          <ElSelect v-model="batchConfigRewardId" :placeholder="$t('qingXuanZe')" filterable style="width:100%">
            <ElOption
              v-for="(name, id) in roomGroupConfig"
              :key="id"
              :label="name"
              :value="Number(id)"
            />
          </ElSelect>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showBatchConfigDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" @click="confirmBatchConfig">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive, watch } from 'vue'
  import { $t } from '@/locales'
  import {
    ElButton,
    ElSpace,
    ElSelect,
    ElOption,
    ElInputNumber,
    ElMessage,
    ElLoading
  } from 'element-plus'
  import {
    getTtpgDetailList,
    updateTtpgDetailReward,
    addTtpgDetail,
    editTtpgDetail,
    batchUpdateTtpgDetail,
    batchConfigTtpg
  } from '@/api/ttpg'
  import TemplateMgmt from './templateMgmt.vue'

  defineOptions({ name: 'TtpgDetail' })

  const props = defineProps<{
    roomId: number | string
    merchantId: number | string
  }>()

  const artTableRef = ref()

  // 选项数据
  const roomGroupConfig = ref<Record<any, string>>({})

  // 弹窗
  const showAddDialog = ref(false)
  const showEditDialog = ref(false)
  const showTemplateDialog = ref(false)
  const showBatchUpdatesDialog = ref(false)
  const showBatchConfigDialog = ref(false)
  const addLoading = ref(false)
  const editLoading = ref(false)

  const addForm = reactive({ bet_amount: 0, reward_id: '' })
  const editForm = reactive({ bet_amount: 0 })
  const editRowData = ref<any>(null)

  const batchRewardId = ref<number>(0)
  const batchConfigRewardId = ref<number>(0)

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    handleSizeChange,
    handleCurrentChange,
    refreshData
  } = useTable({
    core: {
      apiFn: getTtpgDetailList,
      apiParams: {
        page: 1,
        limit: 20,
        room_id: props.roomId,
        merchant_id: props.merchantId
      },
      immediate: false,
      columnsFactory: () => [
        {
          type: 'selection',
          width: 50
        },
        {
          label: $t('suoShuShangHu'),
          prop: 'merchant_id',
          minWidth: 120
        },
        {
          label: $t('youXiID'),
          prop: 'room_id',
          width: 80
        },
        {
          label: $t('youXiMingCheng'),
          prop: 'name',
          minWidth: 140
        },
        {
          label: $t('xiaZhuJinE'),
          prop: 'bet_amount',
          minWidth: 120
        },
        {
          label: $t('fenZuID'),
          prop: 'reward_id',
          minWidth: 160,
          formatter: (row: any) =>
            h(ElSelect, {
              modelValue: String(row.reward_id),
              placeholder: $t('qingXuanZe'),
              filterable: true,
              size: 'small',
              style: { width: '100%' },
              onChange: (val: string | number | boolean) => handleRewardChange(row, String(val))
            }, () => Object.entries(roomGroupConfig.value).map(([key, value]) =>
              h(ElOption, { key, label: value, value: key })
            ))
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 100,
          fixed: 'right',
          formatter: (row: any) =>
            h(
              ElButton,
              {
                type: 'primary',
                size: 'small',
                onClick: () => handleShowEditDialog(row)
              },
              () => $t('bianJi')
            )
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        roomGroupConfig.value = response.room_group_config || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  // roomId/merchantId 变化时重新请求
  watch(
    () => [props.roomId, props.merchantId],
    () => {
      replaceSearchParams({ room_id: props.roomId, merchant_id: props.merchantId })
      getData()
    },
    { immediate: true }
  )

  // ---- Inline reward_id 修改 ----
  async function handleRewardChange(row: any, rewardId: string) {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await updateTtpgDetailReward({
        room_id: row.room_id,
        bet_amount: row.bet_amount,
        reward_id: rewardId
      })
      if (result.code === 200) {
        row.reward_id = rewardId
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
    }
  }

  // ---- 添加 ----
  function handleShowAddDialog() {
    addForm.bet_amount = 0
    addForm.reward_id = ''
    showAddDialog.value = true
  }

  async function confirmAdd() {
    if (!addForm.reward_id) {
      ElMessage.warning($t('qingXuanZe'))
      return
    }
    addLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const result = await addTtpgDetail({
          bet_amount: addForm.bet_amount,
          reward_id: addForm.reward_id
      }, {
        room_id: props.roomId,
        merchant_id: props.merchantId
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showAddDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      addLoading.value = false
    }
  }

  // ---- 编辑 ----
  function handleShowEditDialog(row: any) {
    editRowData.value = row
    editForm.bet_amount = row.bet_amount
    showEditDialog.value = true
  }

  async function confirmEdit() {
    editLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const result = await editTtpgDetail({
        bet_amount: editForm.bet_amount
      }, {
        room_id: props.roomId,
        merchant_id: props.merchantId,
        bet_amount: editRowData.value.bet_amount
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showEditDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      editLoading.value = false
    }
  }

  // ---- 一键配置 (batch_updates) ----
  function handleBatchUpdates() {
    const rows = artTableRef.value?.elTableRef?.getSelectionRows()
    if (!rows?.length) {
      ElMessage.warning($t('qingZhiShaoXuanZeYiXiang'))
      return
    }
    const firstKey = Object.keys(roomGroupConfig.value)[0]
    batchRewardId.value = firstKey ? Number(firstKey) : 0
    showBatchUpdatesDialog.value = true
  }

  async function confirmBatchUpdates() {
    if (!batchRewardId.value) {
      ElMessage.warning($t('qingXuanZe'))
      return
    }
    const rows = artTableRef.value?.elTableRef?.getSelectionRows()
    const ids = rows.map((r: any) => r.bet_amount).join(',')
    const loadingInstance = ElLoading.service({})
    try {
      const result = await batchUpdateTtpgDetail(
        { reward_id: batchRewardId.value },
        { ids, room_id: props.roomId, merchant_id: props.merchantId }
      )
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showBatchUpdatesDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
    }
  }

  // ---- 一键添加配置 (batch_config) ----
  function handleBatchConfig() {
    const firstKey = Object.keys(roomGroupConfig.value)[0]
    batchConfigRewardId.value = firstKey ? Number(firstKey) : 0
    showBatchConfigDialog.value = true
  }

  function handleShowTemplateDialog() {
    showTemplateDialog.value = true
  }

  async function confirmBatchConfig() {
    if (!batchConfigRewardId.value) {
      ElMessage.warning($t('qingXuanZe'))
      return
    }
    const loadingInstance = ElLoading.service({})
    try {
      const result = await batchConfigTtpg(
        { reward_id: batchConfigRewardId.value },
        { room_id: props.roomId, merchant_id: props.merchantId }
      )
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showBatchConfigDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
    }
  }
</script>

<style scoped lang="scss"></style>
