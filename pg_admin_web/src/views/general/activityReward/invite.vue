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
        <ElFormItem :label="$t('daChengTiaoJianRenShu')" required>
          <ElInput v-model="form.mens" :placeholder="$t('daChengTiaoJianRenShu')" />
        </ElFormItem>
        <ElFormItem :label="$t('jiangLiJinE')" required>
          <ElInput v-model="form.rewards" :placeholder="$t('jiangLiJinE')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuBiaoKai')">
          <ElInput v-model="form.icon_on" :placeholder="$t('tuBiaoKai')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuBiaoGuan')">
          <ElInput v-model="form.icon_off" :placeholder="$t('tuBiaoGuan')" />
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
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import {
    allActiveInviteReward,
    addActiveInviteReward,
    editActiveInviteReward,
    deleteActiveInviteReward,
    changeActiveInviteRewardStatus
  } from '@/api/activity'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { ref, reactive, h } from 'vue'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const form = reactive<any>({
    id: null,
    mens: null,
    rewards: null,
    icon_on: null,
    icon_off: null,
    status: 1
  })

  const switchLoading = ref<Record<string, boolean>>({})
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const { columns, columnChecks, data, loading, getData, refreshData, refreshUpdate } = useTable({
    core: {
      apiFn: allActiveInviteReward,
      apiParams: {
        page: 1,
        page_size: 1000,
        active_id: props.activity_id
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 150
        },
        {
          label: 'daChengTiaoJianRenShu',
          prop: 'mens',
          width: 200
        },
        {
          label: 'jiangLiJinE',
          prop: 'rewards',
          width: 200
        },
        {
          label: 'tuBiaoKai',
          prop: 'icon_on',
          width: 150,
          formatter: (row: any) => {
            if (!row.icon_on) {
              return null
            }
            return h('img', {
              src: row.icon_on,
              style: {
                width: '80px',
                height: '80px'
              }
            })
          }
        },
        {
          label: 'tuBiaoGuan',
          prop: 'icon_off',
          width: 150,
          formatter: (row: any) => {
            if (!row.icon_off) {
              return null
            }
            return h('img', {
              src: row.icon_off,
              style: {
                width: '80px',
                height: '80px'
              }
            })
          }
        },
        {
          label: 'kaiGuan',
          prop: 'status',
          width: 100,
          formatter: (row: any) => {
            const isEnabled = row.status === 1
            const switchStatus = isEnabled ? 0 : 1
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
    form.mens = null
    form.rewards = null
    form.icon_on = null
    form.icon_off = null
    form.status = 1
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.mens = row.mens
    form.rewards = row.rewards
    form.icon_on = row.icon_on
    form.icon_off = row.icon_off
    form.status = row.status
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
      mens: form.mens,
      rewards: form.rewards,
      icon_on: form.icon_on,
      icon_off: form.icon_off,
      status: form.status
    }
    try {
      if (form.id) {
        const result = await editActiveInviteReward(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addActiveInviteReward(params)
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
      const result = await deleteActiveInviteReward({
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
      const result = await changeActiveInviteRewardStatus({
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
</script>

<style scoped>
  .dialog-footer {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
  }
</style>
