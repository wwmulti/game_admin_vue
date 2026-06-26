<template>
  <div>
    <ElCard class="art-table-card">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElSpace wrap>
            <ElButton @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
            <ElButton @click="toggleExpand">
              {{ isAllExpanded ? $t('shouQi') : $t('zhanKai') }}
            </ElButton>
          </ElSpace>
        </template>
      </ArtTableHeader>
      <ArtTable
        :border=false
        stripe
        row-key="id"
        :loading="loading"
        :data="data as any"
        :columns="columns"
        :default-expand-all="isAllExpanded"
        :key="tableKey"
      />
    </ElCard>
    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="800"
      center
    >
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('shangJiCaiDan')" required>
          <ElTreeSelect
            default-expand-all
            v-model="form.pid"
            :data="selectMenu"
            :props="{ label: 'label', children: 'children' }"
            node-key="id"
            check-strictly
            filterable
            :placeholder="$t('qingXuanZe')"
          />
        </ElFormItem>
        <ElFormItem :label="$t('leiXing')" required>
          <ElSelect v-model="form.tag">
            <ElOption :label="$t('caiDan')" :value="1" />
            <ElOption :label="$t('jieKou')" :value="0" />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('luYouDiZhi')" required>
          <ElInput v-model="form.path" :placeholder="$t('luYouDiZhi')" />
        </ElFormItem>
        <ElFormItem :label="$t('luYouMingCheng')">
          <ElInput v-model="form.name" :placeholder="$t('luYouMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('biaoTi')">
          <ElInput v-model="form.title" :placeholder="$t('biaoTi')" />
        </ElFormItem>
        <ElFormItem :label="$t('qingQiuDiZhi')">
          <ElInput v-model="form.uri" :placeholder="$t('qingQiuDiZhi')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuBiao')" v-if="form.tag === 1">
          <ElInput v-model="form.icon" :placeholder="$t('tuBiao')" />
        </ElFormItem>
        <ElFormItem v-if="form.tag === 1">
          <el-link type="primary" href="https://icones.js.org/collection/all" target="_blank">{{
            $t('tuBiaoCanKao')
          }}</el-link>
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')">
          <ElInput v-model="form.sort" :placeholder="$t('paiXu')" />
        </ElFormItem>
        <ElFormItem :label="$t('luYouZuJian')" v-if="form.tag === 1">
          <ElInput v-model="form.component" :placeholder="$t('luYouZuJian')" />
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
        <span style="font-size: 16px">{{
          $t('ninQueDingYaoShanChuCaiDanRowdatatitle', [form.title])
        }}</span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="danger" @click="deleteRow">{{ $t('shanChu') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>
<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed, resolveComponent } from 'vue'
  import { ElButton, ElMessage, ElLoading, ElTreeSelect, ElSwitch, ElTag } from 'element-plus'
  import {
    addAuthMenu,
    editAuthMenu,
    deleteAuthMenu,
    allMenuList,
    changeAuthMenuStatus
  } from '@/api/system'

  const switchLoading = ref<Record<string, boolean>>({})
  const isAllExpanded = ref(true)
  const tableKey = ref(0)
  const ArtSvgIcon = resolveComponent('ArtSvgIcon') as any

  function toggleExpand() {
    isAllExpanded.value = !isAllExpanded.value
    tableKey.value++
  }

  const selectMenu = computed(() => [
    {
      id: 0,
      pid: null,
      title: $t('dingJiCaiDan'),
      label: $t('dingJiCaiDan'),
      icon: 'mdi-light:circle',
      children: data.value || []
    }
  ])

  const form = reactive<any>({
    id: null,
    pid: null,
    path: null,
    name: null,
    title: null,
    icon: 'mdi-light:circle',
    uri: null,
    tag: 1,
    status: 1,
    sort: 0,
    component: null
  })

  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const handleStatusChange = async (row: any, value: number) => {
    const key = row.id
    // switchLoading.value[key] = true

    try {
      const result = await changeAuthMenuStatus({
        id: row.id,
        status: value
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
      await refreshData()
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (error) {
      ElMessage.error($t('httpMsg.requestFailed'))
    } finally {
      // switchLoading.value[key] = false
    }
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      pid: form.pid,
      path: form.path,
      title: form.title,
      name: form.name,
      icon: form.icon,
      uri: form.uri,
      tag: form.tag,
      status: form.status,
      sort: form.sort,
      component: form.component
    }
    try {
      if (form.id) {
        const result = await editAuthMenu(params)
        if (result.code === 200) {
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
          await refreshData()
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addAuthMenu(params)
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
          await refreshData()
          showEditDialog.value = false
        }
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function deleteRow() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deleteAuthMenu({
        id: form.id
      })
      if (result.code === 200) {
        await refreshData()
        showDeleteDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.pid = null
    form.name = null
    form.title = null
    form.icon = 'mdi-light:circle'
    form.path = null
    form.uri = null
    form.tag = 1
    form.status = 1
    form.sort = 0
    form.component = '/index/index'
    showEditDialog.value = true
  }

  const handleShowEditDialog = (row: any) => {
    isAdd.value = false
    form.id = row.id
    form.pid = row.pid
    form.title = row.title
    form.name = row.name
    form.icon = row.icon
    form.path = row.path
    form.uri = row.uri
    form.tag = row.tag
    form.status = row.status
    form.sort = row.sort
    form.component = row.component
    showEditDialog.value = true
  }

  const handleShowDeleteDialog = (row: any) => {
    form.id = row.id
    form.title = row.title
    showDeleteDialog.value = true
  }

  const {
    columns,
    columnChecks,
    data,
    loading,
    getData,
    refreshData
  } = useTable({
    core: {
      apiFn: allMenuList,
      apiParams: {},
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 150
        },
        {
          label: 'caiDanMingCheng',
          prop: 'title',
          width: 200
        },
        {
          label: 'luYouLuJing',
          prop: 'path',
          width: 180
        },
        {
          label: 'jieKou',
          prop: 'uri',
          width: 250
        },
        {
          label: 'tuBiao',
          prop: 'icon',
          width: 100,
          formatter: (row: any) => {
            return h(ArtSvgIcon, { icon: row.icon })
          }
        },
        {
          label: 'leiXing',
          width: 100,
          formatter: (row: any) => {
            const isMenu = row.tag === 1
            return h(ElTag, { type: isMenu ? 'success' : 'warning' }, () =>
              isMenu ? $t('caiDan') : $t('jieKou')
            )
          }
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 100,
          formatter: (row: any) => {
            return h(ElSwitch, {
              modelValue: row.status,
              'active-value': 1,
              'inactive-value': 0,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': (val: any) => handleStatusChange(row, Number(val))
            })
          }
        },
        {
          label: 'quanZhong',
          prop: 'sort',
          width: 100
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 200,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowEditDialog(row) },
                () => $t('bianJi')
              ),
              h(
                ElButton,
                { type: 'danger', size: 'small', onClick: () => handleShowDeleteDialog(row) },
                () => $t('shanChu')
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        if (Array.isArray(response)) {
          return { records: response, total: response.length }
        }
        return { records: response?.list || [], total: response?.total || 0 }
      }
    }
  })
</script>

<style scoped lang="scss"></style>
