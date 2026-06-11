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
          <ElButton type="primary" @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
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
    </ElCard>

    <!-- 添加弹窗 -->
    <ElDialog v-model="showAddDialog" :title="$t('tianJia')" width="500" center>
      <ElForm ref="addFormRef" :model="addForm" label-width="140px">
        <ElFormItem :label="$t('jianGeMiaoShu')" required>
          <ElInputNumber v-model="addForm.seconds" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('tiQianJieZhiYaZhuMiaoShu')" required>
          <ElInputNumber v-model="addForm.stop_second" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('jiSuanKaiJiangGaiLv')" required>
          <ElInputNumber v-model="addForm.odds" :min="0" :max="100" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')" required>
          <ElInputNumber v-model="addForm.weight" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="addForm.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('jinYong') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showAddDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" @click="confirmAdd">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>

    <!-- 编辑弹窗 -->
    <ElDialog v-model="showEditDialog" :title="$t('bianJi')" width="500" center>
      <ElForm ref="editFormRef" :model="editForm" label-width="140px">
        <ElFormItem :label="$t('jianGeMiaoShu')" required>
          <ElInputNumber v-model="editForm.seconds" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('tiQianJieZhiYaZhuMiaoShu')" required>
          <ElInputNumber v-model="editForm.stop_second" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('jiSuanKaiJiangGaiLv')" required>
          <ElInputNumber v-model="editForm.odds" :min="0" :max="100" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')" required>
          <ElInputNumber v-model="editForm.weight" :min="0" style="width:100%" />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="editForm.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('jinYong') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" @click="confirmEdit">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed } from 'vue'
  import { $t } from '@/locales'
  import { ElButton, ElMessage, ElMessageBox, ElSwitch } from 'element-plus'
  import {
    getWingoRoomList,
    addWingoRoom,
    editWingoRoom,
    deleteWingoRoom,
    updateWingoRoomAttr
  } from '@/api/wingo'
  import { toggleStatus } from '@/utils/common'

  defineOptions({ name: 'WingoSetting' })

  const searchBarRef = ref()
  const artTableRef = ref()

  const searchForm = ref<Record<string, any>>({
    status: ''
  })

  const formItems = computed(() => [
    {
      label: '',
      key: 'status',
      type: 'select',
      props: {
        placeholder: $t('zhuangTai'),
        style: { width: '120px' },
        clearable: true,
        options: [
          { label: $t('zhengChang'), value: 1 },
          { label: $t('jinYong'), value: 0 }
        ]
      }
    }
  ])

  // ----- 弹窗状态 -----
  const showAddDialog = ref(false)
  const showEditDialog = ref(false)

  const addForm = ref({
    seconds: 0,
    stop_second: 0,
    odds: 0,
    weight: 0,
    status: 1
  })

  const editForm = ref({
    id: 0,
    seconds: 0,
    stop_second: 0,
    odds: 0,
    weight: 0,
    status: 1
  })


  // ----- 表格 -----
  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    refreshData,
    refreshUpdate,
    refreshRemove,
    handleSizeChange,
    handleCurrentChange
  } = useTable({
    core: {
      apiFn: getWingoRoomList,
      apiParams: {
        page: 1,
        limit: 20
      },
      columnsFactory: () => [
        {
          type: 'selection',
          width: 50
        },
        {
          label: 'ID',
          prop: 'id',
          width: 80
        },
        {
          label: $t('jianGeMiaoShu'),
          prop: 'seconds',
          minWidth: 120
        },
        {
          label: $t('tiQianJieZhiYaZhuMiaoShu'),
          prop: 'stop_second',
          minWidth: 160
        },
        {
          label: $t('jiSuanKaiJiangGaiLv'),
          prop: 'odds',
          minWidth: 140
        },
        {
          label: $t('paiXu'),
          prop: 'weight',
          width: 80
        },
        {
          label: $t('zhuangTai'),
          prop: 'status',
          width: 120,
          formatter: (row: any) => {
            return h(ElSwitch, {
              modelValue: row.status === 1,
              onChange: (val: string | number | boolean) => handleStatusChange(row, val ? 1 : 0)
            })
          }
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 160,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                {
                  type: 'primary',
                  size: 'small',
                  style: { marginRight: '8px' },
                  onClick: () => handleShowEditDialog(row)
                },
                () => $t('bianJi')
              ),
              h(
                ElButton,
                {
                  type: 'danger',
                  size: 'small',
                  onClick: () => handleDelete(row)
                },
                () => $t('shanChu')
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => ({
        records: response.rows || [],
        total: response.total || 0
      })
    }
  })

  // ----- 搜索 -----
  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }

  // ----- 状态切换 -----
  async function handleStatusChange(row: any, value: number) {
    const key = row.id
    try {
      const result = await updateWingoRoomAttr({ ids: row.id, status: value })
      if (result.code === 200) {
        ElMessage.success($t('caoZuoChengGong'))
      }
      await refreshUpdate()
    } finally {
    }
  }

  // ----- 添加 -----
  function handleShowAddDialog() {
    addForm.value = {
      seconds: 0,
      stop_second: 0,
      odds: 0,
      weight: 0,
      status: 1
    }
    showAddDialog.value = true
  }

  async function confirmAdd() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await addWingoRoom(addForm.value)
      if (result.code === 200) {
        ElMessage.success($t('tianJiaChengGong'))
        showAddDialog.value = false
        await refreshData()
      }
    } finally {
      loadingInstance.close()
    }
  }

  // ----- 编辑 -----
  function handleShowEditDialog(row: any) {
    editForm.value = {
      id: row.id,
      seconds: row.seconds,
      stop_second: row.stop_second,
      odds: row.odds,
      weight: row.weight,
      status: row.status
    }
    showEditDialog.value = true
  }

  async function confirmEdit() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await editWingoRoom({
        seconds: editForm.value.seconds,
        stop_second: editForm.value.stop_second,
        odds: editForm.value.odds,
        weight: editForm.value.weight,
        status: editForm.value.status
      }, { ids: editForm.value.id })
      if (result.code === 200) {
        ElMessage.success($t('caoZuoChengGong'))
        showEditDialog.value = false
        await refreshUpdate()
      }
    } finally {
      loadingInstance.close()
    }
  }

  // ----- 删除 -----
  async function handleDelete(row: any) {
    try {
      await ElMessageBox.confirm($t('queDingShanChu'), $t('caoZuoTiShi'), {
        confirmButtonText: $t('queDing'),
        cancelButtonText: $t('quXiao'),
        type: 'warning'
      })
    } catch {
      return
    }
    try {
      const result = await deleteWingoRoom({ ids: row.id })
      if (result.code === 200) {
        ElMessage.success($t('shanChuChengGong'))
        await refreshRemove()
      }
    } catch {
      /* empty */
    }
  }
</script>

<style scoped lang="scss"></style>
