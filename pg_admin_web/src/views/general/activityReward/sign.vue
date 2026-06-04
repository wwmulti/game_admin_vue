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
      <ArtTable border stripe :loading="loading" :data="data as any" :columns="columns" />
    </ElCard>

    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="1200"
      center
    >
      <ElForm ref="formRef" label-width="150px">
        <ElFormItem :label="$t('tianShu')" required>
          <ElInput v-model="form.day" :placeholder="$t('tianShu')" />
        </ElFormItem>
        <ElFormItem :label="$t('jiangLiLeiXing')" required>
          <ElSelect v-model="form.reward_type_id">
            <ElOption
              v-for="item in selectRewardType"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('jiangLiJinE')" required>
          <ElInput v-model="form.reward_amount" :placeholder="$t('jiangLiJinE')" />
        </ElFormItem>
        <ElFormItem :label="$t('meiRiChongZhiMenKan')">
          <ElInput v-model="form.day_recharge_line" :placeholder="$t('meiRiChongZhiMenKan')" />
        </ElFormItem>
        <ElFormItem :label="$t('meiRiLiuShuiMenKan')">
          <ElInput v-model="form.day_running_line" :placeholder="$t('meiRiLiuShuiMenKan')" />
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
  import {
    allActiveSignReward,
    addActiveSignReward,
    editActiveSignReward,
    deleteActiveSignReward,
    allActiveSignRewardType
  } from '@/api/activity'
  import { ElMessage, ElButton, ElLoading } from 'element-plus'
  import { ref, reactive, h, toRaw } from 'vue'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const form = reactive<any>({
    id: null,
    day: null,
    reward_type_id: null,
    reward_amount: null,
    day_recharge_line: null,
    day_running_line: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const rewardTypes = ref()
  const rewardTypeMaps = ref()
  const selectRewardType = ref()

  const { columns, columnChecks, data, loading, getData, refreshData } = useTable({
    core: {
      apiFn: allActiveSignReward,
      apiParams: {
        activity_id: props.activity_id,
        page: 1,
        page_size: 1000
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 100
        },
        {
          label: 'tianShu',
          prop: 'day',
          width: 100
        },
        {
          label: 'jiangLiLeiXing',
          prop: 'reward_type_id',
          width: 150,
          formatter: (row: any) => {
            return rewardTypeMaps.value?.[row.reward_type_id] || row.reward_type_id
          }
        },
        {
          label: 'jiangLiJinE',
          prop: 'reward_amount',
          width: 150
        },
        {
          label: 'meiRiChongZhiMenKan',
          prop: 'day_recharge_line',
          width: 150
        },
        {
          label: 'meiRiLiuShuiMenKan',
          prop: 'day_running_line',
          width: 150
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
          width: 180,
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
          records: response || []
        }
      }
    }
  })

  onMounted(async () => {
    try {
      const result = await allActiveSignRewardType()
      if (result.code === 200) {
        if (result.data && result.data.length > 0) {
          rewardTypes.value = result.data
          rewardTypeMaps.value = rewardTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectRewardType.value = [...toRaw(rewardTypes.value)]
          rewardTypes.value.unshift({ id: null, name: $t('quanBu') })
        }
        showDeleteDialog.value = false
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      /* empty */
    }
  })

  function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.day = null
    form.reward_type_id = null
    form.reward_amount = null
    form.day_recharge_line = null
    form.day_running_line = null
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.day = row.day
    form.reward_type_id = row.reward_type_id
    form.reward_amount = row.reward_amount
    form.day_recharge_line = row.day_recharge_line
    form.day_running_line = row.day_running_line
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
      id: form.id,
      day: form.day,
      activity_id: props.activity_id,
      reward_type_id: form.reward_type_id,
      reward_amount: form.reward_amount,
      day_recharge_line: form.day_recharge_line,
      day_running_line: form.day_running_line
    }
    try {
      if (form.id) {
        const result = await editActiveSignReward(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addActiveSignReward(params)
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
      const result = await deleteActiveSignReward({
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
