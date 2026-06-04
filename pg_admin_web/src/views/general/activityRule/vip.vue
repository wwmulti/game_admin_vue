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
      width="1000"
      center
    >
      <ElForm ref="formRef" label-width="200px">
        <ElFormItem :label="$t('dengJi')" required>
          <ElInput type="number" v-model="form.lv" :placeholder="$t('dengJi')" />
        </ElFormItem>
        <ElFormItem :label="$t('leiJiChongZhi')" required>
          <ElInput type="number" v-model="form.total_pays" :placeholder="$t('leiJiChongZhi')" />
        </ElFormItem>
        <ElFormItem :label="$t('leiJiYaZhu')" required>
          <ElInput type="number" v-model="form.total_bets" :placeholder="$t('leiJiYaZhu')" />
        </ElFormItem>
        <ElFormItem :label="$t('tiaoJianManZu')" required>
          <ElSelect v-model="form.con_and">
            <ElOption
              v-for="item in selectConditionTypes"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('jinJiJin')" required>
          <ElInput type="number" v-model="form.rewards" :placeholder="$t('jinJiJin')" />
        </ElFormItem>
        <ElFormItem :label="$t('meiRiTiXianCiShu')" required>
          <ElInput
            type="number"
            v-model="form.withdraw_num_limit"
            :placeholder="$t('fuYiWeiBuXianZhi')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('withdraw_amount_limit')" required>
          <ElInput
            type="number"
            v-model="form.withdraw_amount_limit"
            :placeholder="$t('fuYiWeiBuXianZhi')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('meiRiMianFeiJiaoYiCiShu')" required>
          <ElInput
            type="number"
            v-model="form.withdraw_free_num"
            :placeholder="$t('fuYiWeiBuXianZhi')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('tiXianShouXuFei')" required>
          <ElInput
            type="number"
            v-model="form.withdraw_fee"
            :placeholder="$t('wanFenBi')"
          />
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
        <span style="font-size: 16px">{{ $t('shanChu') }} ? </span>
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
    allActiveVipRule,
    addActiveVipRule,
    editActiveVipRule,
    deleteActiveVipRule,
    changeActiveVipRuleStatus,
    allActiveVipRuleConditionType
  } from '@/api/activity'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { ref, reactive, h } from 'vue'

  const switchLoading = ref<Record<string, boolean>>({})

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const form = reactive<any>({
    id: null,
    lv: null,
    total_pays: null,
    total_bets: null,
    con_and: null,
    rewards: null,
    withdraw_num_limit: null,
    withdraw_amount_limit: null,
    withdraw_free_num: null,
    withdraw_fee: null,
    status: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const conditionTypes = ref()
  const conditionTypeMaps = ref()
  const selectConditionTypes = ref()

  onMounted(async () => {
    try {
      const result = await allActiveVipRuleConditionType()
      if (result.code === 200) {
        if (result.data && result.data.length > 0) {
          conditionTypes.value = result.data
          conditionTypeMaps.value = conditionTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectConditionTypes.value = [...toRaw(conditionTypes.value)]
          conditionTypes.value.unshift({ id: null, name: $t('quanBu') })
        }
        showDeleteDialog.value = false
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      /* empty */
    }
  })

  const { columns, columnChecks, data, loading, getData, refreshData, refreshUpdate } = useTable({
    core: {
      apiFn: allActiveVipRule,
      apiParams: {
        page: 1,
        page_size: 1000,
        active_id: props.activity_id
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 100
        },
        {
          label: 'dengJi',
          prop: 'lv',
          width: 100
        },
        {
          label: 'leiJiChongZhi',
          prop: 'total_pays',
          width: 100
        },
        {
          label: 'leiJiYaZhu',
          prop: 'total_bets',
          width: 100
        },
        {
          label: 'tiaoJianManZu',
          prop: 'con_and',
          width: 200,
          formatter: (row: any) => {
            return conditionTypeMaps.value[row.con_and]
          }
        },
        {
          label: 'jinJiJin',
          prop: 'rewards',
          width: 100
        },
        {
          label: 'meiRiTiXianCiShu',
          prop: 'withdraw_num_limit',
          width: 100
        },
        {
          label: 'meiRiTiXianJinE',
          prop: 'withdraw_amount_limit',
          width: 100
        },
        {
          label: 'meiRiMianFeiJiaoYiCiShu',
          prop: 'withdraw_free_num',
          width: 100
        },
        {
          label: 'tiXianShouXuFeiWanFenBi',
          prop: 'withdraw_fee',
          width: 100
        },
        {
          label: 'kaiGuan',
          prop: 'status',
          width: 100,
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

  function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.lv = null
    form.total_pays = null
    form.total_bets = null
    form.con_and = null
    form.rewards = null
    form.withdraw_num_limit = null
    form.withdraw_amount_limit = null
    form.withdraw_free_num = null
    form.withdraw_fee = null
    form.status = null
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.lv = row.lv
    form.total_pays = row.total_pays
    form.total_bets = row.total_bets
    form.con_and = row.con_and
    form.rewards = row.rewards
    form.withdraw_num_limit = row.withdraw_num_limit
    form.withdraw_amount_limit = row.withdraw_amount_limit
    form.withdraw_free_num = row.withdraw_free_num
    form.withdraw_fee = row.withdraw_fee
    form.status = row.status
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.id = row.id
    showDeleteDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      lv: form.lv,
      total_pays: form.total_pays,
      total_bets: form.total_bets,
      con_and: form.con_and,
      rewards: form.rewards,
      withdraw_num_limit: form.withdraw_num_limit,
      withdraw_amount_limit: form.withdraw_amount_limit,
      withdraw_free_num: form.withdraw_free_num,
      withdraw_fee: form.withdraw_fee,
      status: form.status
    }
    try {
      if (form.id) {
        const result = await editActiveVipRule(params)
        if (result.code === 200) {
          ;(window as any).refreshConfig()
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addActiveVipRule(params)
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
      const result = await deleteActiveVipRule({
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

  const handleStatusChange = async (row: any, value: number) => {
    const key = row.id
    switchLoading.value[key] = true
    try {
      const result = await changeActiveVipRuleStatus({
        id: row.id,
        status: value
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
  .dialog-footer {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
  }
</style>
