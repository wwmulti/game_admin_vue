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
        <ElFormItem :label="$t('xuHao')" required>
          <ElInput v-model="form.serial_number" :placeholder="$t('xuHao')" />
        </ElFormItem>
        <ElFormItem :label="$t('leiJiCunKuanJinE')" required>
          <ElInput v-model="form.total_recharge_amount" :placeholder="$t('leiJiCunKuanJinE')" />
        </ElFormItem>
        <ElFormItem :label="$t('jiangLiJinE')">
          <ElInput v-model="form.reward_amount" :placeholder="$t('jiangLiJinE')" />
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
    allActiveFirstRechargeReward,
    addActiveFirstRechargeReward,
    editActiveFirstRechargeReward,
    deleteActiveFirstRechargeReward
  } from '@/api/activity'
  import { ElMessage, ElButton, ElLoading } from 'element-plus'
  import { ref, reactive, h } from 'vue'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const form = reactive<any>({
    id: null,
    serial_number: null,
    total_recharge_amount: null,
    reward_amount: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const { columns, columnChecks, data, loading, getData, refreshData } = useTable({
    core: {
      apiFn: allActiveFirstRechargeReward,
      apiParams: {
        page: 1,
        page_size: 1000,
        activity_id: props.activity_id
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 200
        },
        {
          label: 'xuHao',
          prop: 'serial_number',
          width: 250
        },
        {
          label: 'leiJiCunKuanJinE',
          prop: 'total_recharge_amount',
          width: 250
        },
        {
          label: 'jiangLiJinE',
          prop: 'reward_amount',
          width: 250
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
    form.serial_number = null
    form.total_recharge_amount = null
    form.reward_amount = null
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.serial_number = row.serial_number
    form.total_recharge_amount = row.total_recharge_amount
    form.reward_amount = row.reward_amount
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
      serial_number: form.serial_number,
      total_recharge_amount: form.total_recharge_amount,
      reward_amount: form.reward_amount
    }
    try {
      if (form.id) {
        const result = await editActiveFirstRechargeReward(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addActiveFirstRechargeReward(params)
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
      const result = await deleteActiveFirstRechargeReward({
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
  .dialog-footer {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
  }
</style>
