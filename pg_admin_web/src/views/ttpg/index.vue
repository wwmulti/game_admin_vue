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

    <!-- 返奖详情弹窗 -->
    <ElDialog v-model="showDetailDialog" :title="detailTitle" width="90%" top="3vh" center>
      <TtpgDetail
        v-if="showDetailDialog"
        :room-id="detailRoomId"
        :merchant-id="detailMerchantId"
      />
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, computed } from 'vue'
  import { $t } from '@/locales'
  import { ElButton } from 'element-plus'
  import { getTtpgList } from '@/api/ttpg'
  import TtpgDetail from './detail.vue'

  defineOptions({ name: 'TtpgIndex' })

  const searchBarRef = ref()
  const artTableRef = ref()

  const searchForm = ref<Record<string, any>>({
    merchant_id: ''
  })

  const merchantOptions = ref<Record<any, string>>({})

  const formItems = computed(() => [
    {
      label: '',
      key: 'merchant_id',
      type: 'select',
      props: {
        placeholder: $t('suoShuShangHu'),
        style: { width: '140px' },
        clearable: true,
        filterable: true,
        options: Object.entries(merchantOptions.value).map(([key, value]) => ({ label: value, value: key }))
      }
    }
  ])

  // 弹窗
  const showDetailDialog = ref(false)
  const detailRoomId = ref<number | string>(0)
  const detailMerchantId = ref<number | string>(0)
  const detailTitle = ref('')

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
      apiFn: getTtpgList,
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
          width: 120
        },
        {
          label: $t('youXiMingCheng'),
          prop: 'name',
          minWidth: 140
        },
        {
          label: $t('fenZuID'),
          prop: 'reward_id',
          minWidth: 200,
          formatter: (row: any) =>
            h('span', {}, row.reward_id || '-')
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 100,
          fixed: 'right',
          formatter: (row: any) =>
            h(
              ElButton,
              {
                type: 'primary',
                size: 'small',
                disabled: !row.merchant_id,
                onClick: () => handleShowDetail(row)
              },
              () => $t('xiangQing')
            )
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        merchantOptions.value = response.merchant_name || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  // ---- 详情 ----
  function handleShowDetail(row: any) {
    detailTitle.value = `${row.name} -- ${$t('fanJiangXiangQing')}`
    detailRoomId.value = row.id
    detailMerchantId.value = row.merchant_id
    showDetailDialog.value = true
  }

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }
</script>

<style scoped lang="scss"></style>
