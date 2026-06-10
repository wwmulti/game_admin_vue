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
      <RtpDetail
        v-if="showDetailDialog"
        :room-id="detailRoomId"
        :rtp-id="detailRtpId"
      />
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, computed } from 'vue'
  import { $t } from '@/locales'
  import { ElButton } from 'element-plus'
  import { getRtpList } from '@/api/ttpg'
  import RtpDetail from './rtpDetail.vue'

  defineOptions({ name: 'RtpIndex' })

  const searchBarRef = ref()
  const artTableRef = ref()

  const searchForm = ref<Record<string, any>>({
    rtp_id: ''
  })

  const rtpOptions = ref<Record<any, string>>({})

  const formItems = computed(() => [
    {
      label: '',
      key: 'rtp_id',
      type: 'select',
      props: {
        placeholder: $t('RTP'),
        style: { width: '140px' },
        clearable: true,
        filterable: true,
        options: Object.entries(rtpOptions.value).map(([key, value]) => ({ label: value, value: key }))
      }
    }
  ])

  // 弹窗
  const showDetailDialog = ref(false)
  const detailRoomId = ref<number | string>(0)
  const detailRtpId = ref<number | string>(0)
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
      apiFn: getRtpList,
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
                disabled: !row.rtp_id,
                onClick: () => handleShowDetail(row)
              },
              () => $t('xiangQing')
            )
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        rtpOptions.value = response.rtp_list || {}
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
    detailRtpId.value = row.rtp_id
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
