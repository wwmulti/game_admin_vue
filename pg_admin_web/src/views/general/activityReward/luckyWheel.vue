<template>
  <div>
    <ArtSearchBar
      ref="searchBarRef"
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
      <ArtTable border stripe :loading="loading" :data="data as any" :columns="columns" />
    </ElCard>

    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="1000"
      center
    >
      <ElForm ref="formRef" label-width="200px">
        <ElFormItem :label="$t('zhuanPanLeiXing')" required>
          <ElSelect v-model="form.wheel_type">
            <ElOption
              v-for="item in selectTypes"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('gaiLv')" required>
          <ElInput v-model="form.weight" :placeholder="$t('gaiLv')" />
        </ElFormItem>
        <ElFormItem :label="$t('jiangLiJinE')" required>
          <ElInput v-model="form.reward" :placeholder="$t('jiangLiJinE')" />
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
    allActiveLuckyWheelReward,
    addActiveLuckyWheelReward,
    editActiveLuckyWheelReward,
    deleteActiveLuckyWheelReward,
    allActiveLuckyWheelRewardType
  } from '@/api/activity'
  import { ElMessage, ElButton, ElLoading } from 'element-plus'
  import { ref, reactive, h, computed } from 'vue'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const searchBarRef = ref()
  const form = reactive<any>({
    id: null,
    wheel_type: null,
    reward: null,
    weight: null
  })

  const types = ref()
  const typeMaps = ref()
  const selectTypes = ref()

  onMounted(async () => {
    try {
      const result = await allActiveLuckyWheelRewardType()
      if (result.code === 200) {
        if (result.data && result.data.length > 0) {
          types.value = result.data
          typeMaps.value = types.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectTypes.value = [...toRaw(types.value)]
          types.value.unshift({ id: null, name: $t('quanBu') })
        }
        showDeleteDialog.value = false
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      /* empty */
    }
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const formItems = computed(() => [
    {
      label: 'zhuanPanLeiXing',
      key: 'wheel_type',
      type: 'select',
      placeholder: 'zhuanPanLeiXing',
      options: types.value
        ? types.value.map((item: any) => ({
            label: item.name,
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
    getData,
    replaceSearchParams,
    resetSearchParams,
    refreshData
  } = useTable({
    core: {
      apiFn: allActiveLuckyWheelReward,
      apiParams: {
        page: 1,
        page_size: 1000,
        activity_id: props.activity_id
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 250
        },
        {
          label: 'zhuanPanLeiXing',
          prop: 'wheel_type',
          width: 250,
          formatter: (row: any) => {
            return typeMaps.value[row.wheel_type]
          }
        },
        {
          label: 'gaiLv',
          prop: 'weight',
          width: 250
        },
        {
          label: 'jiangLiJinE',
          prop: 'reward',
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
    form.wheel_type = null
    form.reward = null
    form.weight = null
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.wheel_type = row.wheel_type
    form.reward = row.reward
    form.weight = row.weight
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
      wheel_type: form.wheel_type,
      reward: form.reward,
      weight: form.weight
    }
    try {
      if (form.id) {
        const result = await editActiveLuckyWheelReward(params)
        if (result.code === 200) {
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addActiveLuckyWheelReward(params)
        if (result.code === 200) {
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
      const result = await deleteActiveLuckyWheelReward({
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

  function handleReset() {
    resetSearchParams()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    replaceSearchParams(params)
    await getData()
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
