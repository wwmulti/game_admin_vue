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
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData" />
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
    <ElDialog v-model="gameLogVisible" :title="$t('youXiXiangQing')" width="80%" top="5vh">
      <GameLogDetail v-if="selectedUserId" :userId="selectedUserId" />
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, computed } from 'vue'
  import { $t } from '@/locales'
  import {
    ElButton,
    ElMessage,
    ElLoading,
    ElMessageBox,
    ElDialog
  } from 'element-plus'
  import { Refresh } from '@element-plus/icons-vue'
  import { getUserList, updateBalance } from '@/api/user'
  import GameLogDetail from './gameLogDetail.vue'

  defineOptions({ name: 'UserIndex' })

  const searchBarRef = ref()
  const searchForm = ref<Record<string, any>>({
    platform_id: '',
    merchant_id: '',
    remote_uid: '',
    name: '',
    created_at: []
  })

  // 游戏详情弹窗
  const gameLogVisible = ref(false)
  const selectedUserId = ref<number | null>(null)

  // 选项数据
  const platformOptions = ref<Record<any, string>>({})
  const merchantOptions = ref<Record<any, string>>({})
  const rtpOptions = ref<Record<any, string>>({})

  const formItems = computed(() => [
    {
      label: '',
      key: 'platform_id',
      type: 'select',
      props: {
        placeholder: $t('pingTai'),
        style: { width: '140px' },
        clearable: true,
        filterable: true,
        options: Object.entries(platformOptions.value).map(([key, value]) => ({
          label: value,
          value: key
        }))
      }
    },
    {
      label: '',
      key: 'merchant_id',
      type: 'select',
      props: {
        placeholder: $t('suoShuShangHu'),
        style: { width: '140px' },
        clearable: true,
        filterable: true,
        options: Object.entries(merchantOptions.value).map(([key, value]) => ({
          label: value,
          value: key
        }))
      }
    },
    {
      label: '',
      key: 'rtp_id',
      type: 'select',
      props: {
        placeholder: $t('RTP'),
        style: { width: '140px' },
        clearable: true,
        filterable: true,
        options: Object.entries(rtpOptions.value).map(([key, value]) => ({
          label: value,
          value: key
        }))
      }
    },
    {
      label: '',
      key: 'remote_uid',
      type: 'input',
      props: { placeholder: $t('wanJiaUID'), style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'name',
      type: 'input',
      props: { placeholder: $t('yongHuMingCheng'), style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'created_at',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('kaiShiRiQi'),
        'end-placeholder': $t('jieShuRiQi'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    }
  ])

  const artTableRef = ref()

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
      apiFn: getUserList,
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
          width: 70
        },
        {
          label: $t('pingTai'),
          prop: 'platform_id',
          minWidth: 120
        },
        {
          label: $t('suoShuShangHu'),
          prop: 'merchant_id',
          minWidth: 120
        },
        {
          label: $t('wanJiaUID'),
          prop: 'remote_uid',
          minWidth: 140
        },
        {
          label: $t('RTP'),
          prop: 'rtp',
          width: 80
        },
        {
          label: $t('yuE'),
          prop: 'money',
          minWidth: 160,
          formatter: (row: any) =>
            h('div', { style: 'display:flex;align-items:center;gap:8px' }, [
              h('span', row.money),
              h(
                ElButton,
                {
                  size: 'small',
                  type: 'warning',
                  icon: Refresh,
                  onClick: () => handleUpdateBalance(row)
                },
                () => $t('gengXinYuE')
              )
            ])
        },
        {
          label: $t('yongHuMingCheng'),
          prop: 'name',
          minWidth: 120
        },
        {
          label: $t('chuangJianShiJian'),
          prop: 'created_at',
          minWidth: 160,
          formatter: (row: any) => row.created_at
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 140,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                {
                  type: 'primary',
                  size: 'small',
                  onClick: () => handleShowGameDetail(row)
                },
                () => $t('youXiXiangQing')
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        // 从 index 接口同步选项数据到搜索下拉
        platformOptions.value = response.platform_name || {}
        merchantOptions.value = response.merchant_name || {}
        rtpOptions.value = response.rtp || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }

  // 更新余额
  async function handleUpdateBalance(row: any) {
    ElMessageBox.confirm(
      $t('shiFouGengXinYongHuYuE'),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    )
      .then(async () => {
        const loadingInstance = ElLoading.service({})
        try {
          const result = await updateBalance(row.id)
          if (result.code === 200) {
            ElMessage.success($t('chengGong'))
            refreshData()
          } else {
            ElMessage.error(result.msg || $t('shiBai'))
          }
        } finally {
          loadingInstance.close()
        }
      })
      .catch(() => {})
  }

  // 游戏详情
  function handleShowGameDetail(row: any) {
    selectedUserId.value = row.id
    gameLogVisible.value = true
  }
</script>

<style scoped lang="scss"></style>
