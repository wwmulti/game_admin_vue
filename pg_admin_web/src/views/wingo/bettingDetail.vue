<template>
  <ElDialog
    :model-value="visible"
    :title="$t('wanJiaXiaZhuXiangQing')"
    width="90%"
    top="5vh"
    center
    destroy-on-close
    @update:model-value="$emit('update:visible', $event)"
  >
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
  </ElDialog>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { ref, watch } from 'vue'
  import { $t } from '@/locales'
  import { getWingoBetDetail } from '@/api/wingo'
  import { ElDialog, ElCard } from 'element-plus'

  defineOptions({ name: 'BettingDetail' })

  const props = defineProps<{
    visible: boolean
    roomId: number
    issue: string
  }>()

  const emit = defineEmits<{
    'update:visible': [value: boolean]
  }>()

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
    refreshData,
    refreshUpdate,
    handleSizeChange,
    handleCurrentChange
  } = useTable({
    core: {
      apiFn: getWingoBetDetail,
      apiParams: { page: 1, limit: 20 },
      immediate: false,
      columnsFactory: () => [
        { label: $t('yongHuID'), prop: 'remote_uid', minWidth: 100 },
        { label: $t('yuE'), prop: 'money', minWidth: 100 },
        { label: $t('zongXiaZhu'), prop: 'sum_bet', minWidth: 100 },
        { label: $t('hongSe'), prop: 'red', minWidth: 80 },
        { label: $t('lvSe'), prop: 'green', minWidth: 80 },
        { label: $t('ziSe'), prop: 'purple', minWidth: 80 },
        { label: '0', prop: 'zero', minWidth: 70 },
        { label: '1', prop: 'one', minWidth: 70 },
        { label: '2', prop: 'two', minWidth: 70 },
        { label: '3', prop: 'three', minWidth: 70 },
        { label: '4', prop: 'four', minWidth: 70 },
        { label: '5', prop: 'five', minWidth: 70 },
        { label: '6', prop: 'six', minWidth: 70 },
        { label: '7', prop: 'seven', minWidth: 70 },
        { label: '8', prop: 'eight', minWidth: 70 },
        { label: '9', prop: 'nine', minWidth: 70 },
        { label: $t('xiao'), prop: 'small', minWidth: 80 },
        { label: $t('da'), prop: 'large', minWidth: 80 }
      ]
    },
    transform: {
      responseAdapter: (response: any) => ({
        records: response.rows || [],
        total: response.total || 0
      })
    }
  })

  watch(
    () => props.visible && props.roomId && props.issue,
    async (val) => {
      if (val) {
        await getData({ room_id: props.roomId, issue: props.issue } as any)
      }
    }
  )
</script>
