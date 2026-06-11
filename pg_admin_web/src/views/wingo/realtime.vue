<template>
  <div>
    <!-- Tab 切换 -->
    <ElCard class="art-table-card" style="margin-bottom: 12px">
      <ElRadioGroup v-model="tabType" @change="handleTabChange">
        <ElRadioButton :value="1">{{ $t('yuGuKaiJiangHaoMa') }}</ElRadioButton>
        <ElRadioButton :value="2">{{ $t('wingoYuGuJiLu') }}</ElRadioButton>
      </ElRadioGroup>
    </ElCard>

    <!-- Tab 1: 预估 -->
    <template v-if="tabType === 1">
      <!-- 房间切换按钮 -->
      <ElCard class="art-table-card" style="margin-bottom: 12px; align-items: center;">
        <div class="room-buttons">
          <ElButton
            v-for="item in roomList"
            :key="item.id"
            :type="item.id === currentRoomId ? 'primary' : 'default'"
            @click="switchRoom(item.id)"
          >
            {{ item.formatted_time }}
          </ElButton>
        </div>
      </ElCard>

      <div class="realtime-layout">
        <!-- 左侧 -->
        <div class="realtime-left">
          <div class="left-actions">
            <p class="section-title">{{ $t('yuGuKaiJiangHaoMa') }}：</p>
            <ElButton type="primary" size="small" @click="showDataOverlay = true">
              <ElIcon><Search /></ElIcon> {{ $t('yuGuShuJu') }}
            </ElButton>
            <ElButton type="primary" size="small" @click="openBettingDetail">
              <ElIcon><Setting /></ElIcon> {{ $t('wanJiaXiaZhuXiangQing') }}
            </ElButton>
          </div>
        </div>

        <!-- 右侧 -->
        <div class="realtime-right">
          <!-- 期数 / 预设号码 / 倒计时 -->
          <ElForm label-width="140px" style="max-width: 600px;">
            <ElFormItem :label="$t('qiShu')">
              <ElInput :model-value="detailData.issue" disabled />
            </ElFormItem>
            <ElFormItem :label="$t('yuSheKaiJiangHaoMa')">
              <ElInput :model-value="detailData.number_pre" disabled />
            </ElFormItem>
            <ElFormItem :label="$t('juLiKaiJiangHaiYou')">
              <ElInput :model-value="countdownText" disabled />
            </ElFormItem>
            <ElFormItem :label="$t('sheZhiKaiJiangHaoMa')">
              <ElRadioGroup v-model="winningNumbers">
                <ElRadio v-for="n in 10" :key="n - 1" :value="n - 1" class="number-radio">
                  {{ n - 1 }}
                </ElRadio>
              </ElRadioGroup>
            </ElFormItem>
          </ElForm>

          <!-- 统计栏 -->
          <div class="stats-bar">
            <ElTag type="success">{{ $t('zongXiaZhu') }}：{{ totalData.sum_amount }}</ElTag>
            <ElTag type="primary">{{ $t('wanJiaZhongJiangJinE') }}：{{ totalData.win_amount }}</ElTag>
            <ElTag type="warning">{{ $t('zongTouZhuShu') }}：{{ totalData.bet_number }}</ElTag>
            <ElTag type="danger">{{ $t('zhongJiangRenShu') }}：{{ totalData.win_number }}</ElTag>
            <ElTag>{{ $t('shouXuFei') }}：{{ totalData.sum_fee }}</ElTag>
            <ElTag>{{ $t('yingKui') }}：{{ totalData.sum_transfer }}</ElTag>
          </div>

          <!-- 提交按钮 -->
          <div style="margin-left: 30%; margin-top: 16px">
            <ElButton type="primary" size="large" :loading="submitLoading" @click="handleSubmit">
              {{ $t('tiJiaoKaiJiangXinXi') }}
            </ElButton>
          </div>
        </div>
      </div>

      <!-- 投注详情表格 -->
      <ElCard class="art-table-card" style="margin-top: 12px">
        <template #header>
          <span>{{ currentRoomName }} - {{ $t('wanJiaXiaZhuXiangQing') }}</span>
        </template>

        <!-- 预估开奖号码按钮 -->
        <div class="detail-number-wrap">
          <span class="detail-number-label">{{ $t('yuGuKaiJiangHaoMa') }}</span>
          <div class="detail-number-buttons">
            <ElButton
              v-for="n in 10"
              :key="n - 1"
              :type="detailSelectedNumber === n - 1 ? 'primary' : 'default'"
              size="small"
              @click="selectDetailNumber(n - 1)"
            >
              {{ n - 1 }}
            </ElButton>
          </div>
        </div>

        <ElTable :data="detailTableData" border stripe style="width: 80%; margin: 0 auto" :header-cell-style="headerCellStyle" class="detail-table">
          <ElTableColumn label="号码 / 类型" align="center" min-width="100">
            <template #default="{ row }">
              <ElTag :color="row._color" style="color: #fff; border: none;">
                {{ row._name }}
              </ElTag>
            </template>
          </ElTableColumn>
          <ElTableColumn
            v-for="col in detailColumns"
            :key="col.prop"
            :label="col.label"
            :prop="col.prop"
            min-width="80"
            align="center"
          />
        </ElTable>
      </ElCard>
    </template>

    <!-- Tab 2: 记录 -->
    <template v-if="tabType === 2">
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

      <!-- 编辑弹窗 -->
      <ElDialog v-model="showEditDialog" :title="$t('bianJi')" width="400" center>
        <ElForm ref="editFormRef" :model="editForm" label-width="120px">
          <ElFormItem :label="$t('yuSheKaiJiangHaoMa')" required>
            <ElInputNumber v-model="editForm.number_pre" :min="0" :max="9" style="width:100%" />
          </ElFormItem>
        </ElForm>
        <template #footer>
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" :loading="editLoading" @click="confirmEdit">{{ $t('queDing') }}</ElButton>
        </template>
      </ElDialog>
    </template>

    <!-- 预估数据弹窗 overlay -->
    <ElDialog v-model="showDataOverlay" :title="currentRoomName + ' - ' + $t('yuGuShuJu')" width="80%" top="5vh" center destroy-on-close>
      <div v-if="roomData.length" class="overlay-table-wrap">
        <ElTable :data="roomDataTable" border stripe style="width: 70%; margin: 0 auto">
          <ElTableColumn label="号码 / 类型" min-width="100">
            <template #default="{ row }">
              <ElTag :color="row._color" style="color: #fff; border: none">
                {{ row._name }}
              </ElTag>
            </template>
          </ElTableColumn>
          <ElTableColumn
            v-for="n in 10"
            :key="'n' + (n - 1)"
            :label="'数字 ' + (n - 1)"
            :prop="'n' + (n - 1)"
            min-width="80"
            align="center"
          />
        </ElTable>
      </div>
    </ElDialog>

    <!-- 玩家下注详情弹窗 -->
    <BettingDetail v-model:visible="bettingDetailVisible" :room-id="bettingDetailRoomId" :issue="bettingDetailIssue" />
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed, onMounted, onUnmounted } from 'vue'
  import { $t } from '@/locales'
  import { ElButton, ElMessage, ElMessageBox, ElTag, ElInput, ElRadio, ElTable, ElTableColumn } from 'element-plus'
  import { Search, Setting } from '@element-plus/icons-vue'
  import {
    getWingoRealtimeList,
    getWingoRooms,
    getLatestRoomData,
    getRoomDetails,
    submitWinningNumber,
    editWingoIssue
  } from '@/api/wingo'
  import BettingDetail from './bettingDetail.vue'

  defineOptions({ name: 'WingoRealtime' })

  // ---- Tabs ----
  const tabType = ref(1)

  // ---- 预估 Tab ----
  const roomList = ref<any[]>([])
  const currentRoomId = ref<number>(0)
  const currentRoomName = ref('')
  const winningNumbers = ref(0)
  const submitLoading = ref(false)
  const showDataOverlay = ref(false)

  // LatestData
  const roomData = ref<any[]>([])
  const roomDataTable = computed(() => {
    return roomData.value.map((row: any) => {
      const obj: Record<string, any> = { _name: row.name?.name || '', _color: row.name?.color || '#909399' }
      for (let i = 0; i <= 9; i++) {
        obj['n' + i] = row[i] ?? '-'
      }
      return obj
    })
  })

  // Detail data
  const detailData = reactive({
    issue: '',
    number_pre: '',
    draw_time: 0
  })
  const totalData = reactive({
    sum_amount: 0,
    win_amount: 0,
    bet_number: 0,
    win_number: 0,
    sum_fee: 0,
    sum_transfer: 0
  })
  const detailColumns = ref<any[]>([])
  const detailTableData = ref<any[]>([])
  const detailSelectedNumber = ref<number>(0)

  // 为动态列表头设置背景色
  function headerCellStyle({ column }: { column: any }) {
    const col = detailColumns.value.find((c: any) => c.prop === column.property)
    if (col?.color) {
      return { backgroundColor: col.color, color: '#fff' }
    }
    return {}
  }

  // Countdown
  const countdownText = ref('')
  let countdownTimer: ReturnType<typeof setInterval> | null = null

  // ---- 记录 Tab ----
  const searchBarRef = ref()
  const artTableRef = ref()

  const searchForm = ref<Record<string, any>>({
    issue: '',
    room_id: '',
    number: '',
    draw_time_pre: [],
    draw_time: []
  })

  const roomOptions = ref<Record<any, string>>({})

  const formItems = computed(() => [
    {
      label: '',
      key: 'issue',
      type: 'input',
      props: { placeholder: $t('qiShu'), style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'room_id',
      type: 'select',
      props: {
        placeholder: $t('fangJianMing'),
        style: { width: '140px' },
        clearable: true,
        options: Object.entries(roomOptions.value).map(([value, label]) => ({ label, value: Number(value) }))
      }
    },
    {
      label: '',
      key: 'number',
      type: 'input',
      props: { placeholder: $t('kaiJiangHaoMa'), style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'draw_time_pre',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('yuJiKaiJiangShiJian'),
        'end-placeholder': $t('yuJiKaiJiangShiJian'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    },
    {
      label: '',
      key: 'draw_time',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('shiJiKaiJiangShiJian'),
        'end-placeholder': $t('shiJiKaiJiangShiJian'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    }
  ])

  const showEditDialog = ref(false)
  const editLoading = ref(false)
  const editForm = ref({ id: 0, number_pre: 0 })
  const getSwitch = ref(0)

  // 玩家下注详情弹窗
  const bettingDetailVisible = ref(false)
  const bettingDetailRoomId = ref(0)
  const bettingDetailIssue = ref('')

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
      apiFn: getWingoRealtimeList,
      apiParams: { page: 1, limit: 20 },
      columnsFactory: () => [
        { type: 'selection', width: 50 },
        { label: 'ID', prop: 'id', width: 80 },
        { label: $t('qiShu'), prop: 'issue', minWidth: 100 },
        { label: $t('fangJianMing'), prop: 'room_id', minWidth: 120 },
        { label: $t('moRenKaiJiangHaoMa'), prop: 'number_rand', width: 120 },
        { label: $t('zhiDingKaiJiangHaoMa'), prop: 'number_pre', width: 120 },
        { label: $t('kaiJiangHaoMa'), prop: 'number', width: 100 },
        {
          label: $t('zhuangTai'),
          prop: 'status',
          width: 100,
          formatter: (row: any) => {
            const isOpen = row.status === 1
            return h(ElTag, { type: isOpen ? 'success' : 'info' }, () =>
              isOpen ? $t('yiKaiJiang') : $t('weiKaiJiang')
            )
          }
        },
        { label: $t('yuJiKaiJiangShiJian'), prop: 'draw_time_pre', minWidth: 160, formatter: (row: any) => (window as any).formatTimestamp?.(row.draw_time_pre) ?? row.draw_time_pre },
        { label: $t('shiJiKaiJiangShiJian'), prop: 'draw_time', minWidth: 160, formatter: (row: any) => (window as any).formatTimestamp?.(row.draw_time) ?? row.draw_time },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 180,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', { style: 'display: flex; gap: 8px;' }, [
              h(
                ElButton,
                {
                  type: 'primary',
                  size: 'small',
                  onClick: () => handleShowEditDialog(row)
                },
                () => $t('bianJi')
              ),
              h(
                ElButton,
                {
                  type: 'warning',
                  size: 'small',
                  onClick: () => openRecordBettingDetail(row)
                },
                () => $t('chaKanXiangQing')
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

  // ---- 生命周期 ----
  onMounted(async () => {
    await loadRooms()
    if (roomList.value.length) {
      switchRoom(roomList.value[0].id)
    }
  })

  onUnmounted(() => {
    stopCountdown()
    stopPolling()
  })

  // ---- 房间切换 ----
  async function loadRooms() {
    try {
      const res = await getWingoRooms()
      if (res.code === 200) {
        roomList.value = res.data || []
        // 填充房间下拉选项
        const options: Record<any, string> = {}
        ;(res.data || []).forEach((r: any) => {
          options[r.id] = r.formatted_time
        })
        roomOptions.value = options
      }
    } catch {
      /* empty */
    }
  }

  function switchRoom(roomId: number) {
    currentRoomId.value = roomId
    const found = roomList.value.find((r: any) => r.id === roomId)
    currentRoomName.value = found?.formatted_time || ''
    detailSelectedNumber.value = 0
    winningNumbers.value = 0
    getSwitch.value = 0
    fetchLatestData()
    fetchDetails()
    startPolling()
  }

  // ---- 数据获取 ----
  async function fetchLatestData() {
    if (tabType.value !== 1 || !currentRoomId.value) return
    try {
      const res = await getLatestRoomData({ room_id: currentRoomId.value })
      if (res.code === 200) {
        roomData.value = res.data || []
      }
    } catch {
      /* empty */
    }
  }

  async function fetchDetails() {
    if (tabType.value !== 1 || !currentRoomId.value) return
    try {
      const res = await getRoomDetails({
        room_id: currentRoomId.value,
        number: detailSelectedNumber.value
      })
      if (res.code === 200) {
        const d = res.data
        detailData.issue = d.issue || ''
        detailData.number_pre = d.number_pre ?? ''
        detailData.draw_time = d.draw_time ?? 0
        if (d.total_data) {
          Object.assign(totalData, d.total_data)
        }
        // Build detail columns from new_tab
        const cols: any[] = []
        const rows: any[] = []
        if (d.new_tab?.length) {
          d.new_tab.forEach((tab: any, idx: number) => {
            cols.push({ label: tab.name, prop: 'c' + idx, align: 'center', color: tab.color || '' })
          })
        }
        if (d.list?.length) {
          d.list.forEach((row: any) => {
            const obj: Record<string, any> = {
              _name: row.name?.name || '',
              _color: row.name?.color || '#909399'
            }
            if (d.new_tab?.length) {
              d.new_tab.forEach((tab: any, idx: number) => {
                obj['c' + idx] = row[idx] ?? '-'
              })
            }
            rows.push(obj)
          })
        }
        detailColumns.value = cols
        detailTableData.value = rows

        // Auto-select winning number from preset (only on first load)
        if (getSwitch.value < 1) {
          if (d.number_pre && d.number_pre !== '未设定') {
            const num = parseInt(d.number_pre)
            if (!isNaN(num)) {
              winningNumbers.value = num
              detailSelectedNumber.value = num
            }
          } else {
            winningNumbers.value = 0
          }
        }
        getSwitch.value++
        startCountdown()
      }
    } catch {
      /* empty */
    }
  }

  // ---- 倒计时 ----
  function startCountdown() {
    stopCountdown()
    updateCountdown()
    countdownTimer = setInterval(updateCountdown, 1000)
  }

  function stopCountdown() {
    if (countdownTimer) {
      clearInterval(countdownTimer)
      countdownTimer = null
    }
  }

  function updateCountdown() {
    const t = detailData.draw_time
    if (t <= 0) {
      countdownText.value = '0s'
      return
    }
    const h = Math.floor(t / 3600)
    const m = Math.floor((t % 3600) / 60)
    const s = t % 60
    countdownText.value = [h ? h + 'h' : '', m ? m + 'm' : '', s + 's'].filter(Boolean).join(' ')
  }

  // ---- 轮询 ----
  let latestDataTimer: ReturnType<typeof setInterval> | null = null
  let detailsTimer: ReturnType<typeof setInterval> | null = null

  function startPolling() {
    stopPolling()
    if (tabType.value !== 1 || !currentRoomId.value) return
    latestDataTimer = setInterval(fetchLatestData, 3000)
    detailsTimer = setInterval(fetchDetails, 3000)
  }

  function stopPolling() {
    if (latestDataTimer) {
      clearInterval(latestDataTimer)
      latestDataTimer = null
    }
    if (detailsTimer) {
      clearInterval(detailsTimer)
      detailsTimer = null
    }
  }

  // ---- 提交 ----
  async function handleSubmit() {
    if (!currentRoomId.value || !detailData.issue) {
      ElMessage.warning($t('caoZuoTiShi'))
      return
    }
    try {
      await ElMessageBox.confirm($t('queRenTijiaoKaijiangXinxi'), $t('caoZuoTiShi'), {
        confirmButtonText: $t('queDing'),
        cancelButtonText: $t('quXiao'),
        type: 'warning'
      })
    } catch {
      return
    }
    submitLoading.value = true
    try {
      const res = await submitWinningNumber({
        room_id: currentRoomId.value,
        issue: detailData.issue,
        winning_numbers: winningNumbers.value
      })
      if (res.code === 200) {
        ElMessage.success($t('chengGong'))
        await fetchLatestData()
        await fetchDetails()
      }
    } catch {
      /* empty */
    } finally {
      submitLoading.value = false
      getSwitch.value = 0

    }
  }

  // ---- 投注详情 ----
  function selectDetailNumber(num: number) {
    detailSelectedNumber.value = num
    fetchDetails()
  }

  function openBettingDetail() {
    if (!currentRoomId.value || !detailData.issue) {
      ElMessage.warning('暂无期数数据')
      return
    }
    bettingDetailRoomId.value = currentRoomId.value
    bettingDetailIssue.value = detailData.issue
    bettingDetailVisible.value = true
  }

  function openRecordBettingDetail(row: any) {
    bettingDetailRoomId.value = row.room_ids || row.room_id
    bettingDetailIssue.value = row.issue
    bettingDetailVisible.value = true
  }

  // ---- Tab 切换 ----
  function handleTabChange(val: string | number | boolean | undefined) {
    if (val === 1) {
      stopCountdown()
      stopPolling()
      getSwitch.value = 0
      fetchLatestData()
      fetchDetails()
      startPolling()
    } else {
      stopCountdown()
      stopPolling()
    }
  }

  // ---- 记录 Tab 搜索 ----
  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }

  // ---- 编辑 ----
  function handleShowEditDialog(row: any) {
    editForm.value = { id: row.id, number_pre: row.number_pre === '-' ? 0 : Number(row.number_pre) }
    showEditDialog.value = true
  }

  async function confirmEdit() {
    editLoading.value = true
    try {
      const res = await editWingoIssue({
        ids: editForm.value.id,
        number_pre: editForm.value.number_pre
      })
      if (res.code === 200) {
        ElMessage.success($t('chengGong'))
        showEditDialog.value = false
        await refreshUpdate()
      }
    } finally {
      editLoading.value = false
    }
  }
</script>

<style scoped lang="scss">
.room-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.realtime-layout {
  display: flex;
  gap: 16px;
}
.realtime-left {
  width: 20%;
  flex-shrink: 0;
}
.realtime-right {
  flex: 1;
}
.left-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: baseline;
}
.section-title {
  padding: 5px 5px 5px 12px;
  border-left: 5px solid #18bc9c;
  border-bottom: 1px solid #18bc9c;
  background: #f3f3f3;
  font-weight: bold;
}
.stats-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}
.number-radio {
  margin-right: 30px;
}
.detail-number-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.detail-number-label {
  font-weight: bold;
  flex-shrink: 0;
}
.detail-number-buttons {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: center;
  flex: 1;
}
.detail-number-buttons :deep(.el-button--small) {
  height: 32px !important;
  min-height: 32px !important;
  line-height: 30px !important;
}
.overlay-table-wrap {
  display: flex;
  justify-content: center;
}
.detail-table :deep(tbody tr:nth-child(1)) {
  background-color: #c9ffe0 !important;
}
.detail-table :deep(tbody tr:nth-child(2)) {
  background-color: #d1ecfd !important;
}
.detail-table :deep(tbody tr:nth-child(3)) {
  background-color: #ffded9 !important;
}
.detail-table :deep(tbody tr:nth-child(4)) {
  background-color: #fff1dc !important;
}
.detail-table :deep(tbody tr:nth-child(5)) {
  background-color: #c9ffe0 !important;
}
.detail-table :deep(thead tr th:first-child) {
  background-color: #d1ecfd !important;
}
</style>
