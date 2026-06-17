<template>
  <div>
    <!-- 统计条件表单 -->
    <ElCard class="art-table-card" style="margin-bottom: 16px">
      <ElForm :model="searchForm" inline>
        <ElFormItem :label="$t('shiJian')" prop="time">
          <ElDatePicker
            v-model="searchForm.time"
            type="daterange"
            range-separator="-"
            :start-placeholder="$t('kaiShiRiQi')"
            :end-placeholder="$t('jieShuRiQi')"
            value-format="YYYY-MM-DD"
            :shortcuts="dateShortcuts"
          />
        </ElFormItem>
        <ElFormItem :label="$t('shangHu')" prop="merchant">
          <ElSelect
            v-model="searchForm.merchant"
            :placeholder="$t('shangHu')"
            clearable
            filterable
            style="width: 140px"
          >
            <ElOption
              v-for="(name, id) in merchantOptions"
              :key="id"
              :label="name"
              :value="Number(id)"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('youXi')" prop="game">
          <ElSelect
            v-model="searchForm.game"
            :placeholder="$t('youXi')"
            clearable
            filterable
            style="width: 140px"
          >
            <ElOption
              v-for="(name, id) in roomOptions"
              :key="id"
              :label="name"
              :value="Number(id)"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem>
          <ElButton type="primary" :loading="executing" @click="handleExecute">
            {{ $t('zhiXingTongJi') }}
          </ElButton>
        </ElFormItem>
      </ElForm>
    </ElCard>

    <!-- 任务列表 -->
    <ElCard class="art-table-card">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData" />
      <ArtTable
        ref="artTableRef"
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

    <!-- 详情弹窗 -->
    <ElDialog
      v-model="showDetailDialog"
      :title="$t('xiangQing')"
      width="80%"
      top="5vh"
      center
      @closed="handleDetailClosed"
    >
      <template v-if="detailLoading">
        <div style="text-align: center; padding: 40px">
          <el-icon class="is-loading" :size="32"><Loading /></el-icon>
        </div>
      </template>
      <template v-else-if="detailError">
        <div style="text-align: center; padding: 40px; color: #909399">
          {{ detailError }}
        </div>
      </template>
      <template v-else-if="detailInfo">
        <ElTable :data="detailInfo.result" border stripe>
          <ElTableColumn :label="$t('shiJian')" min-width="130">
            <template #default="{ row }">{{ detailInfo.time }}</template>
          </ElTableColumn>
          <ElTableColumn :label="$t('youXiRtp')" min-width="150">
            <template #default="{ row }">
              {{ row.game_rtp }}
            </template>
          </ElTableColumn>
          <ElTableColumn :label="$t('xiangQing')" min-width="300">
            <template #default="{ row }">
              {{ $t('xiaZhuCiShu') }}：{{ row.bet_times ?? '-' }}&nbsp;&nbsp;&nbsp;
              {{ $t('zongRenShu') }}：{{ row.total_users ?? '-' }}&nbsp;&nbsp;&nbsp;
              {{ $t('yingJiaShuLiang') }}：{{ row.win_count ?? '-' }}&nbsp;&nbsp;&nbsp;
              {{ $t('shuJiaShuLiang') }}：{{ row.lose_count ?? '-' }}&nbsp;&nbsp;&nbsp;
              {{ $t('biLi') }}：{{ row.win_rate ?? '-' }}
            </template>
          </ElTableColumn>
          <ElTableColumn :label="$t('shangHu')" min-width="120">
            <template #default="{ row }">{{ detailInfo.merchant }}</template>
          </ElTableColumn>
        </ElTable>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { ref, reactive, onUnmounted, h } from 'vue'
  import dayjs from 'dayjs'
  import { $t } from '@/locales'
  import { ElMessage, ElMessageBox, ElButton, ElTag, ElIcon, ElTooltip } from 'element-plus'
  import { Loading } from '@element-plus/icons-vue'
  import {
    getWinAnalysList,
    getWinAnalysDetail,
    checkWinAnalysStatus
  } from '@/api/analys'

  defineOptions({ name: 'AnalysWin' })

  // ===== 日期快捷选项 =====
  const dateShortcuts = [
    {
      text: $t('jinTian'),
      value: () => [dayjs().startOf('day').toDate(), dayjs().endOf('day').toDate()]
    },
    {
      text: $t('zuoTian'),
      value: () => [
        dayjs().subtract(1, 'day').startOf('day').toDate(),
        dayjs().subtract(1, 'day').endOf('day').toDate()
      ]
    },
    {
      text: $t('btn.benZhou'),
      value: () => [dayjs().startOf('week').toDate(), dayjs().endOf('week').toDate()]
    },
    {
      text: $t('jin_7Tian'),
      value: () => [
        dayjs().subtract(7, 'day').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('jin_14Tian'),
      value: () => [
        dayjs().subtract(14, 'day').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('jin_30Tian'),
      value: () => [
        dayjs().subtract(30, 'day').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('btn.benYue'),
      value: () => [dayjs().startOf('month').toDate(), dayjs().endOf('month').toDate()]
    },
    {
      text: $t('btn.shangYue'),
      value: () => [
        dayjs().subtract(1, 'month').startOf('month').toDate(),
        dayjs().subtract(1, 'month').endOf('month').toDate()
      ]
    },
    {
      text: $t('jinSanGeYue'),
      value: () => [
        dayjs().subtract(3, 'month').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('qianSanGeYue'),
      value: () => [
        dayjs().subtract(3, 'month').startOf('month').toDate(),
        dayjs().subtract(1, 'month').endOf('month').toDate()
      ]
    }
  ]

  // ===== 搜索/统计表单 =====
  const searchForm = reactive({
    time: [],
    merchant: '',
    game: ''
  })

  // ===== 选项数据 =====
  const merchantOptions = ref<Record<string, string>>({})
  const roomOptions = ref<Record<string, string>>({})

  // ===== 执行统计 =====
  const executing = ref(false)
  let pollTimer: ReturnType<typeof setInterval> | null = null
  let pollCount = 0

  function startPolling() {
    stopPolling()
    pollCount = 0
    pollTimer = setInterval(async () => {
      pollCount++
      if (pollCount >=  10) {
        stopPolling()
        ElMessage.warning($t('zhiXingShiJianGuoChang'))
        return
      }
      try {
        const res = await checkWinAnalysStatus({ type: 1 })
        if (res.data.count === 0) {
          stopPolling()
          refreshData()
          ElMessage.success($t('tongJiZhiXingWanCheng'))
        }
      } catch {
        // ignore polling errors
      }
    }, 3000)
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  async function handleExecute() {
    const time = searchForm.time
    const merchant = searchForm.merchant
    const game = searchForm.game

    if (!game) {
      ElMessage.warning($t('qingXuanZeYouXi'))
      return
    }
    if (!time) {
      ElMessage.warning($t('qingXuanZeShiJianFanWei'))
      return
    }

    try {
      await ElMessageBox.confirm($t('queRenZhiXingTongJi'), $t('caoZuoTiShi'), {
        confirmButtonText: $t('queRen'),
        cancelButtonText: $t('quXiao'),
        type: 'warning'
      })
    } catch {
      return
    }

    executing.value = true
    try {
      const params: Record<string, any> = { stat: 1 }
      if (time) params.time = time
      if (merchant) params.merchant = merchant
      if (game) params.game = game

      await getWinAnalysList(params)
      ElMessage.success($t('tongJiZhiXingZhong'))
      refreshData()
      startPolling()
    } catch (err: any) {
      ElMessage.error(err?.msg || $t('qingQiuShiBai'))
    } finally {
      executing.value = false
    }
  }

  // ===== 详情弹窗 =====
  const showDetailDialog = ref(false)
  const detailLoading = ref(false)
  const detailError = ref('')
  const detailInfo = ref<any>(null)

  async function handleShowDetail(row: any) {
    showDetailDialog.value = true
    detailLoading.value = true
    detailError.value = ''
    detailInfo.value = null

    try {
      const res = await getWinAnalysDetail({ id: row.id })
      if (res?.data) {
        detailInfo.value = res.data
      } else if (res?.id) {
        detailInfo.value = res
      } else {
        detailError.value = $t('wuFaHuoQuXiangQingShuJu')
      }
    } catch {
      detailError.value = $t('huoQuXiangQingShiBai')
    } finally {
      detailLoading.value = false
    }
  }

  function handleDetailClosed() {
    detailInfo.value = null
    detailError.value = ''
  }

  // ===== 表格 =====
  const artTableRef = ref()

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    handleSizeChange,
    handleCurrentChange,
    refreshData
  } = useTable({
    core: {
      apiFn: getWinAnalysList,
      apiParams: {
        page: 1,
        limit: 20
      },
      columnsFactory: () => {
        const statusKeys: Record<number, { key: string; type: 'info' | 'warning' | 'success' | 'danger' }> = {
          0: { key: 'weiZhiXing', type: 'info' },
          1: { key: 'zhiXingZhong', type: 'warning' },
          2: { key: 'zhiXingChengGong', type: 'success' },
          3: { key: 'zhiXingShiBai', type: 'danger' }
        }

        return [
          {
            label: $t('shiJian'),
            prop: 'task_time',
            minWidth: 160
          },
          {
            label: $t('shangHu'),
            prop: 'merchant_name',
            minWidth: 120
          },
          {
            label: $t('youXi'),
            prop: 'game_name',
            minWidth: 140
          },
          {
            label: $t('zhuangTai'),
            prop: 'status',
            width: 110,
            formatter: (row: any) => {
              const s = statusKeys[row.status]
              if (!s) return h(ElTag, () => String(row.status))
              if (row.status === 1) {
                return h(ElTag, { type: s.type }, () => [
                  h(ElIcon, { class: 'is-loading' }, () => h(Loading)),
                  ' ',
                  $t(s.key)
                ])
              }
              return h(ElTag, { type: s.type }, () => $t(s.key))
            }
          },
          {
            label: $t('caoZuo'),
            prop: 'operate',
            width: 90,
            fixed: 'right',
            formatter: (row: any) => {
              if (row.status === 2) {
                return h(ElButton, {
                  type: 'success',
                  size: 'small',
                  onClick: () => handleShowDetail(row)
                }, () => $t('xiangQing'))
              }
              return h('span', '-')
            }
          },
          {
            label: $t('chuangJianShiJian'),
            prop: 'c_time',
            minWidth: 160
          },
          {
            label: $t('yuYin'),
            prop: 'remark',
            minWidth: 160,
            formatter: (row: any) => {
              if (!row.remark || row.remark === '-') return '-'
              const text = row.remark
              const show = text.length > 20 ? text.substring(0, 20) + '...' : text
              if (text.length > 20) {
                return h(ElTooltip, { content: text, placement: 'top' }, () => show)
              }
              return show
            }
          }
        ]
      }
    },
    transform: {
      responseAdapter: (response: any) => {
        merchantOptions.value = response.merchant_name || {}
        roomOptions.value = response.rooms || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  // ===== 清理 =====
  onUnmounted(() => {
    stopPolling()
  })
</script>

<style scoped lang="scss"></style>
