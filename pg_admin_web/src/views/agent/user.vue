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
        <ElFormItem :label="$t('yongHuMing')" required>
          <ElInput v-model="form.username" :placeholder="$t('yongHuMing')" />
        </ElFormItem>
        <ElFormItem :label="$t('miMa')" required>
          <ElInput type="password" v-model="form.password" :placeholder="$t('miMa')" />
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { userList } from '@/api/agent'
  import { ElMessage, ElButton, ElLoading } from 'element-plus'
  import { editUser } from '@/api/user'

  const searchBarRef = ref()
  const searchForm = ref({
    role_id: null,
    username: null
  })

  const form = reactive<any>({
    id: null,
    username: null,
    password: null
  })

  const props = defineProps({
    package_id: {
      type: Number,
      default: null
    }
  })

  const showEditDialog = ref(false)
  const isAdd = ref(false)

  const formItems = computed(() => [
    {
      label: 'ID',
      key: 'role_id',
      type: 'input',
      placeholder: 'ID',
      clearable: true
    },
    {
      label: 'yongHuMing',
      key: 'username',
      type: 'input',
      placeholder: 'yongHuMing',
      clearable: true
    }
  ])

  const {
    columns,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    handleSizeChange,
    handleCurrentChange
  } = useTable({
    core: {
      apiFn: userList,
      apiParams: {
        page: 1,
        page_size: 10,
        role_id: null,
        username: null,
        package_id: props.package_id
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'role_id',
          width: 500
        },
        {
          label: 'yongHuMing',
          prop: 'username',
          width: 500
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 320,
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
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          records: response.list || [],
          total: response.total || 0
        }
      }
    }
  })

  onMounted(async () => {})

  function handleReset() {
    resetSearchParams()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    replaceSearchParams(params)
    await getData()
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.username = row.username
    form.password = null
    showEditDialog.value = true
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      username: form.username,
      password: form.password
    }
    try {
      const result = await editUser(params)
      if (result.code === 200) {
        await getData()
        showEditDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }
</script>
