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
      <ElForm ref="formRef" label-width="100px">
        <ElFormItem :label="$t('fenBao')" required>
          <ElSelect v-model="form.package_id">
            <ElOption
              v-for="item in selectPackages"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('leiBie')" required>
          <ElSelect v-model="form.game_type_name">
            <ElOption
              v-for="item in selectTypes"
              :key="item.id"
              :label="item.name"
              :value="item.name"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('fenLeiPingTai')" required>
          <ElSelect v-model="form.platform_name">
            <ElOption
              v-for="item in selectPlatforms"
              :key="item.id"
              :label="item.name"
              :value="item.name"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('youXiBiaoQian')" required>
          <ElSelect v-model="form.tag">
            <ElOption
              v-for="item in selectTags"
              :key="item.id"
              :label="item.name"
              :value="item.name"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('youXiDaiMa')" required>
          <ElInput v-model="form.code" :placeholder="$t('youXiDaiMa')" />
        </ElFormItem>
        <ElFormItem :label="$t('youXiMingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('youXiMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('puYuMingCheng')">
          <ElInput v-model="form.pt_name" :placeholder="$t('puYuMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')">
          <ElInput type="number" v-model="form.sort" :placeholder="$t('paiXu')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuBiao')">
          <ElInput v-model="form.cover" :placeholder="$t('tuBiao')" />
        </ElFormItem>
        <ElFormItem :label="$t('youXiKaiGuan')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :label="1">{{ $t('shi') }}</ElRadio>
            <ElRadio :label="0">{{ $t('fou') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('kaiQiWeiHu')">
          <ElRadioGroup v-model="form.maintain">
            <ElRadio :label="1">{{ $t('shi') }}</ElRadio>
            <ElRadio :label="0">{{ $t('fou') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('daMaGuiZe')">
          <ElRadioGroup v-model="form.code_rule">
            <ElRadio :label="1">{{ $t('liuShui') }}</ElRadio>
            <ElRadio :label="2">{{ $t('jingYing') }}</ElRadio>
            <ElRadio :label="3">{{ $t('yingJinDaMa') }}</ElRadio>
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
        <span style="font-size: 16px">{{ $t('queRenShanChuYouXi') }} - {{ form.name }} ? </span>
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
  import { gameList, addGame, editGame, deleteGame, editGameAttr } from '@/api/game'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref } from 'vue'

  const searchBarRef = ref()
  const searchForm = ref({
    title: undefined,
    domain: undefined
  })

  const form = reactive<any>({
    id: null,
    package_id: null,
    game_type_id: null,
    platform_id: null,
    tag: null,
    code: null,
    name: null,
    pt_name: null,
    sort: null,
    cover: null,
    status: null,
    maintain: null,
    code_rule: null,
    title: null
  })

  const switchLoading = ref<Record<string, boolean>>({})
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const isAdd = ref(false)

  const packages = ref()
  const packageMaps = ref()
  const selectPackages = ref()
  const platforms = ref()
  const selectPlatforms = ref()
  const platformMaps = ref()
  const tags = ref()
  const selectTags = ref()
  const tagMaps = ref()
  const types = ref()
  const selectTypes = ref()
  const typeMaps = ref()

  const formItems = computed(() => [
    {
      label: 'youXiMingCheng',
      key: 'name',
      type: 'input',
      placeholder: 'youXiMingCheng'
    },
    {
      label: 'youXiDaiMa',
      key: 'code',
      type: 'input',
      placeholder: 'youXiDaiMa'
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
      label: 'kaiQiWeiHu',
      key: 'maintain',
      type: 'select',
      props: {
        placeholder: 'xuanZe',
        options: [
          { label: $t('quanBu'), value: null },
          { label: $t('shi'), value: 1 },
          { label: $t('fou'), value: 0 }
        ]
      }
    },
    {
      label: 'tuiJian',
      key: 'recommend',
      type: 'select',
      props: {
        placeholder: 'xuanZe',
        options: [
          { label: $t('quanBu'), value: null },
          { label: $t('shi'), value: 1 },
          { label: $t('fou'), value: 0 }
        ]
      }
    },
    {
      label: 'daMaGuiZe',
      key: 'code_rule',
      type: 'select',
      props: {
        placeholder: 'xuanZe',
        options: [
          { label: $t('quanBu'), value: null },
          { label: $t('liuShui'), value: 1 },
          { label: $t('jingYing'), value: 2 },
          { label: $t('yingJinDaMa'), value: 3 }
        ]
      }
    },
    {
      label: 'fenLeiPingTai',
      key: 'platform_name',
      type: 'select',
      placeholder: 'fenLeiPingTai',
      options: platforms.value
        ? platforms.value.map((item: any) => ({
            label: item.name,
            value: item.name
          }))
        : []
    },
    {
      label: 'leiBie',
      key: 'game_type_name',
      type: 'select',
      placeholder: 'leiBie',
      options: types.value
        ? types.value.map((item: any) => ({
            label: item.name,
            value: item.name
          }))
        : []
    },
    {
      label: 'youXiBiaoQian',
      key: 'tag',
      type: 'select',
      placeholder: 'youXiBiaoQian',
      options: tags.value
        ? tags.value.map((item: any) => ({
            label: item.name,
            value: item.name
          }))
        : []
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
    refreshUpdate,
    searchParams
  } = useTable({
    core: {
      apiFn: gameList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        name: null,
        code: null,
        status: null,
        maintain: null,
        recommend: null,
        code_rule: null,
        platform_name: null,
        game_type_name: null,
        tag: null,
        package_id: null
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
            return packageMaps.value[row.package_id]
          }
        },
        {
          label: 'leiBie',
          prop: 'game_type_name',
          width: 150
        },
        {
          label: 'fenLeiPingTai',
          prop: 'platform_name',
          width: 150
        },
        {
          label: 'youXiBiaoQian',
          prop: 'tag',
          width: 150
        },
        {
          label: 'paiXu',
          prop: 'sort',
          width: 150
        },
        {
          label: 'daMaGuiZe',
          prop: 'code_rule',
          width: 150,
          formatter: (row: any) => {
            if (row.code_rule === 1) {
              return $t('liuShui')
            } else if (row.code_rule === 2) {
              return $t('jingYing')
            } else if (row.code_rule === 3) {
              return $t('yingJinDaMa')
            } else {
              return null
            }
          }
        },
        {
          label: 'youXiDaiMa',
          prop: 'code',
          width: 150
        },
        {
          label: 'youXiMingCheng',
          prop: 'name',
          width: 150
        },
        {
          label: 'tuBiao',
          prop: 'cover',
          width: 150,
          formatter: (row: any) => {
            if (!row.cover) {
              return null
            }
            return h('img', {
              src: row.cover,
              style: {
                width: '80px',
                height: '80px'
              }
            })
          }
        },
        {
          label: 'zhuangTai',
          prop: 'status',
          width: 150,
          formatter: (row: any) => {
            const isEnabled = row.status === 1
            const switchStatus = (window as any).toggleStatus(row.status)
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus, 'status')
            })
          }
        },
        {
          label: 'kaiQiWeiHu',
          prop: 'maintain',
          width: 150,
          formatter: (row: any) => {
            const isEnabled = row.maintain === 1
            const switchStatus = (window as any).toggleStatus(row.maintain)
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus, 'maintain')
            })
          }
        },
        {
          label: 'tuiJian',
          prop: 'recommend',
          width: 150,
          formatter: (row: any) => {
            const isEnabled = row.recommend === 1
            const switchStatus = (window as any).toggleStatus(row.recommend)
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus, 'recommend')
            })
          }
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
                {
                  type: 'primary',
                  size: 'small',
                  onClick: () => handleShowEditDialog(row)
                },
                $t('bianJi')
              ),
              h(
                ElButton,
                {
                  type: 'danger',
                  size: 'small',
                  onClick: () => handleShowDeleteDialog(row)
                },
                $t('shanChu')
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
          packages: response.packages || [],
          platforms: response.platforms || [],
          tags: response.game_tags || [],
          types: response.game_types || []
        }
      }
    },
    hooks: {
      onSuccess: async (data: any[], response: any) => {
        if (response.packages.length > 0) {
          packages.value = response.packages
          packageMaps.value = packages.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          selectPackages.value = [...toRaw(packages.value)]
          packages.value.unshift({ id: null, title: $t('quanBu') })
        }
        if (response.platforms.length > 0) {
          platforms.value = response.platforms
          platformMaps.value = platforms.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectPlatforms.value = [...toRaw(platforms.value)]
          platforms.value.unshift({ id: null, name: $t('quanBu') })
        }
        if (response.tags.length > 0) {
          tags.value = response.tags
          tagMaps.value = tags.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectTags.value = [...toRaw(tags.value)]
          tags.value.unshift({ id: null, name: $t('quanBu') })
        }
        if (response.types.length > 0) {
          types.value = response.types
          typeMaps.value = types.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectTypes.value = [...toRaw(types.value)]
          types.value.unshift({ id: null, name: $t('quanBu') })
        }
        searchParams.need_reload = 0
      }
    }
  })

  onMounted(async () => {})

  function handleReset() {
    resetSearchParams()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    const customParams = { ...params }
    if (customParams.game_type_name === $t('quanBu')) {
      delete customParams.game_type_name
    }
    if (customParams.platform_name === $t('quanBu')) {
      delete customParams.platform_name
    }
    replaceSearchParams(customParams)
    await getData()
  }

  function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.package_id = null
    form.game_type_id = null
    form.platform_id = null
    form.tag = null
    form.code = null
    form.name = null
    form.pt_name = null
    form.sort = null
    form.cover = null
    form.status = 1
    form.maintain = 1
    form.code_rule = 1
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.game_type_id = row.game_type_id
    form.platform_id = row.platform_id
    form.tag = row.tag
    form.code = row.code
    form.name = row.name
    form.pt_name = row.pt_name
    form.sort = row.sort
    form.cover = row.cover
    form.status = row.status
    form.maintain = row.maintain
    form.code_rule = row.code_rule
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.name = row.name
    showDeleteDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      package_id: form.package_id,
      game_type_id: form.game_type_id,
      platform_id: form.platform_id,
      tag: form.tag,
      code: form.code,
      name: form.name,
      pt_name: form.pt_name,
      sort: form.sort,
      cover: form.cover,
      status: form.status,
      maintain: form.maintain,
      code_rule: form.code_rule
    }
    try {
      if (form.id) {
        const result = await editGame(params)
        if (result.code === 200) {
          ;(window as any).refreshConfig()
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addGame(params)
        if (result.code === 200) {
          ;(window as any).refreshConfig()
          await getData()
          ElMessage.success($t('chengGong'))
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
      const result = await deleteGame({
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
      const result = await editGameAttr({
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
