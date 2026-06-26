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
          <ElButton type="primary" @click="handleShowAddDialog">{{ $t('tianJiaFenLei') }}</ElButton>
         <ElButton type="danger" :disabled="!selectedIds.length" @click="handleBatchDelete">{{ $t('piLiangShanChu') }}</ElButton>
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
        @selection-change="handleSelectionChange"
      />
    </ElCard>

    <!-- 添加/编辑弹窗 -->
    <ElDialog v-model="showDialog" :title="isEdit ? $t('bianJiFenLei') : $t('tianJiaFenLei')" width="500px" :close-on-click-modal="false">
      <ElForm ref="formRef" :model="form" label-width="80px">
        <ElFormItem :label="$t('mingCheng')" prop="name" :rules="[{ required: true, message: $t('qingShuRuMingCheng'), trigger: 'blur' }]">
          <ElInput v-model="form.name" :placeholder="$t('qingShuRuMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('jianCheng')" prop="abbreviation">
          <ElInput v-model="form.abbreviation" :placeholder="$t('jianCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuPian')" prop="image">
          <ElInput v-model="form.image" :placeholder="$t('tuPian')" />
        </ElFormItem>
        <ElFormItem :label="$t('kaiGuan')" prop="type_switch">
          <ElSwitch v-model="form.type_switch" :active-value="1" :inactive-value="0" />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <ElButton @click="showDialog = false">{{ $t('quXiao') }}</ElButton>
        <ElButton type="primary" :loading="submitLoading" @click="handleSubmit">{{ $t('queDing') }}</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive } from 'vue'
  import { $t } from '@/locales'
  import {
    getRoomTypeList,
    addRoomType,
    editRoomType,
    deleteRoomType,
    editRoomTypeAttr
  } from '@/api/game'
  import {
    ElButton,
    ElSwitch,
    ElMessage,
    ElMessageBox,
    ElInput,
    ElForm,
    ElFormItem,
    ElDialog
  } from 'element-plus'

  defineOptions({ name: 'GameType' })

  const searchBarRef = ref()
  const artTableRef = ref()
  const formRef = ref()

  const searchForm = ref<Record<string, any>>({
    name: '',
    abbreviation: ''
  })

  const formItems = [
    {
      label: '',
      key: 'name',
      type: 'input',
      props: {
        placeholder: $t('mingCheng'),
        style: { width: '160px' },
        clearable: true
      }
    },
    {
      label: '',
      key: 'abbreviation',
      type: 'input',
      props: {
        placeholder: $t('jianCheng'),
        style: { width: '140px' },
        clearable: true
      }
    }
  ]

  // ---- Dialog ----
  const showDialog = ref(false)
  const isEdit = ref(false)
  const submitLoading = ref(false)
  const form = reactive({
    id: 0,
    name: '',
    abbreviation: '',
    image: '',
    type_switch: 1
  })

  const selectedIds = ref<string[]>([])

  function handleSelectionChange(selection: any[]) {
    selectedIds.value = selection.map((item: any) => item.id)
  }

  // ---- Switch toggle ----
  async function handleToggleSwitch(row: any) {
    const newVal = row.type_switch === 1 ? 0 : 1
    try {
      const result = await editRoomTypeAttr({ ids: row.id, type_switch: newVal })
      if (result.code === 200) {
        row.type_switch = newVal
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } catch {
      ElMessage.error($t('shiBai'))
    }
  }

  // ---- Table ----
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
    refreshData
  } = useTable({
    core: {
      apiFn: getRoomTypeList,
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
          label: $t('mingCheng'),
          prop: 'name',
          minWidth: 160
        },
        {
          label: $t('jianCheng'),
          prop: 'abbreviation',
          minWidth: 120
        },
        {
          label: $t('tuPian'),
          prop: 'image',
          minWidth: 160
        },
        {
          label: $t('kaiGuan'),
          prop: 'type_switch',
          width: 100,
          formatter: (row: any) =>
            h(ElSwitch, {
              modelValue: row.type_switch === 1,
              onChange: () => handleToggleSwitch(row)
            })
        },
        {
          label: $t('caoZuo'),
          prop: 'operate',
          width: 160,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', null, [
              h(ElButton, {
                size: 'small',
                type: 'primary',
                onClick: () => handleShowEditDialog(row)
              }, () => $t('bianJi')),
              h(ElButton, {
                size: 'small',
                type: 'danger',
                onClick: () => handleDelete(row)
              }, () => $t('shanChu'))
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

  // ---- CRUD ----
  function resetForm() {
    form.id = 0
    form.name = ''
    form.abbreviation = ''
    form.image = ''
    form.type_switch = 1
  }

  function handleShowAddDialog() {
    isEdit.value = false
    resetForm()
    showDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isEdit.value = true
    form.id = row.id
    form.name = row.name
    form.abbreviation = row.abbreviation || ''
    form.image = row.image || ''
    form.type_switch = row.type_switch
    showDialog.value = true
  }

  async function handleSubmit() {
    const valid = await formRef.value?.validate().catch(() => false)
    if (!valid) return
    submitLoading.value = true
    try {
      let result: any
      if (isEdit.value) {
        result = await editRoomType({ ids: form.id, name: form.name, abbreviation: form.abbreviation, image: form.image, type_switch: form.type_switch })
      } else {
        result = await addRoomType({ name: form.name, abbreviation: form.abbreviation, image: form.image, type_switch: form.type_switch })
      }
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showDialog.value = false
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } catch {
      ElMessage.error($t('shiBai'))
    } finally {
      submitLoading.value = false
    }
  }

  async function handleDelete(row: any) {
    try {
      await ElMessageBox.confirm($t('queDingYaoShanChuFenLei', [row.name]), $t('common.tips'), {
        confirmButtonText: $t('queDing'),
        cancelButtonText: $t('quXiao'),
        type: 'warning'
      })
      const result = await deleteRoomType({ ids: row.id })
      if (result.code === 200) {
        ElMessage.success($t('shanChuChengGong'))
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shanChuShiBai'))
      }
    } catch {
      // cancelled
    }
  }

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }

  async function handleBatchDelete() {
    if (!selectedIds.value.length) return
    try {
      await ElMessageBox.confirm($t('queDingYaoShanChuXuanZhongFenLei'), $t('common.tips'), {
        confirmButtonText: $t('queDing'),
        cancelButtonText: $t('quXiao'),
        type: 'warning'
      })
      const result = await deleteRoomType({ ids: selectedIds.value.join(',') })
      if (result.code === 200) {
        ElMessage.success($t('shanChuChengGong'))
        selectedIds.value = []
        await refreshData()
      } else {
        ElMessage.error(result.msg || $t('shanChuShiBai'))
      }
    } catch {
      // cancelled
    }
  }
</script>

<style scoped lang="scss"></style>
