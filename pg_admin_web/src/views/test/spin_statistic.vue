<template>
  <div>
    <!-- 生成返奖面板 -->
    <ElCard class="art-table-card" style="margin-bottom: 16px;">
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <span>{{ $t('shengChengFanJiang') }}</span>
          <ElButton :icon="postExpand ? ArrowUp : ArrowDown" size="small" circle @click="postExpand = !postExpand" />
        </div>
      </template>
      <ElCollapseTransition>
        <div v-show="postExpand">
          <ElForm :model="postForm" label-width="100px" inline>
            <ElFormItem :label="$t('xuanZeFangJian')" prop="room_id">
              <ElSelect v-model="postForm.room_id" style="width: 220px" clearable filterable multiple collapse-tags collapse-tags-tooltip @change="handleRoomChange">
                <ElOption key="0" label="--全部--" value="0" />
                <ElOption v-for="(val, key) in roomOptions" :key="key" :label="val" :value="key" />
              </ElSelect>
            </ElFormItem>
            <ElFormItem label="RTP" prop="rtp_id">
              <ElSelect v-model="postForm.rtp_id" style="width: 160px" filterable>
                <ElOption key="0" label="-全部-" value="0" />
                <ElOption v-for="(val, key) in rtpOptions" :key="key" :label="val" :value="key" />
              </ElSelect>
            </ElFormItem>
            <ElFormItem :label="$t('xuanZeYaZhu')" prop="bet_amount">
              <ElSelect v-model="postForm.bet_amount" style="width: 160px" filterable>
                <ElOption v-for="item in betAmountOptions" :key="item.id" :label="item.bet_amount" :value="item.bet_amount" />
              </ElSelect>
            </ElFormItem>
            <ElFormItem :label="$t('yaZhuCiShu')" prop="number">
              <ElInput v-model="postForm.number" style="width: 160px" />
            </ElFormItem>
            <ElFormItem :label="$t('youXiMoShi')" prop="room_type">
              <ElSelect v-model="postForm.room_type" style="width: 160px" clearable filterable>
                <ElOption v-for="item in modOptionsForm" :key="item.mod" :label="item.desc" :value="item.mod" />
              </ElSelect>
            </ElFormItem>
            <ElFormItem>
              <ElButton type="primary" :loading="postLoading" @click="handlePostSubmit">{{ $t('queDing') }}</ElButton>
              <ElButton v-if="showModEditBtn" plain @click="openModEditDialog">游戏模式编辑</ElButton>
            </ElFormItem>
          </ElForm>
          <div v-if="checkCount > 0" style="display:inline-flex;align-items:center;gap:8px;padding:6px 16px;background:linear-gradient(135deg,#e6f7ff,#bae7ff);border:1px solid #91d5ff;border-radius:20px;font-size:14px;color:#0050b3;margin-top:8px;">
            <el-icon class="is-loading" style="font-size:16px;"><Loading /></el-icon>
            <span>返奖数据处理中，还有 <strong style="font-size:18px;color:#1890ff;">{{ checkCount }}</strong> 条数据正在处理...</span>
          </div>
        </div>
      </ElCollapseTransition>
    </ElCard>

    <!-- 搜索栏 -->
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

    <!-- 游戏模式编辑弹窗 -->
    <ElDialog v-model="modEditVisible" title="游戏模式编辑" width="520px" destroy-on-close>
      <ElForm label-width="80px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <span style="color:#909399;font-size:12px;">配置当前房间的游戏模式（值 + 名称）</span>
          <ElButton type="primary" size="small" @click="addModRow">新增</ElButton>
        </div>
        <div v-for="(row, idx) in modEditList" :key="idx" style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
          <ElInput v-model="row.mod" placeholder="值" style="width:140px;" />
          <ElInput v-model="row.desc" placeholder="名称" style="width:240px;" />
          <ElButton type="danger" size="small" plain @click="removeModRow(idx)">删除</ElButton>
        </div>
        <div v-if="!modEditList.length" style="color:#909399;text-align:center;padding:16px 0;">暂无数据，点击右上角“新增”添加</div>
      </ElForm>
      <template #footer>
        <ElButton @click="modEditVisible = false">取消</ElButton>
        <ElButton type="primary" :loading="modEditLoading" :disabled="!modEditValid" @click="handleModSave">保存</ElButton>
      </template>
    </ElDialog>
  </div>
</template>

<script lang="ts">
import { ref as reff, type Ref } from 'vue'
import { ElMessage as ElMessagee } from 'element-plus'
import { checkSpin } from '@/api/spin'

// 全局唯一定时器：当前显示哪个 tab，就查询对应 typeId
const activeTypeId: Ref<string> = reff('')
const activeCount: Ref<number> = reff(0)
let globalTimer: ReturnType<typeof setInterval> | null = null
let _prevNonZeroCount = false
let _onDone: (() => void) | null = null

function stopGlobalTimer() {
  if (globalTimer) {
    clearInterval(globalTimer)
    globalTimer = null
  }
}

function startGlobalTimer() {
  if (globalTimer) return
  _prevNonZeroCount = false
  const poll = async () => {
    const tid = activeTypeId.value
    if (!tid) { stopGlobalTimer(); return }
    try {
      const res = await checkSpin({ type_id: tid })
      const count = (res as any).data?.count ?? 0
      activeCount.value = count
      if (count === 0) {
        stopGlobalTimer()
        if (_prevNonZeroCount) {
          ElMessagee.success('返奖数据处理完成')
          _onDone?.()
        }
      } else {
        _prevNonZeroCount = true
      }
    } catch {
      activeCount.value = 0
      stopGlobalTimer()
    }
  }
  poll()
  globalTimer = setInterval(poll, 3000)
}
</script>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { ref, computed, watch, onActivated, onDeactivated, onBeforeUnmount } from 'vue'
  import { useRoute } from 'vue-router'
  import { $t } from '@/locales'
  import {
    ElCard,
    ElButton,
    ElForm,
    ElFormItem,
    ElSelect,
    ElOption,
    ElInput,
    ElMessage,
    ElMessageBox,
    ElCollapseTransition,
    ElDialog
  } from 'element-plus'
  import { ArrowUp, ArrowDown, Loading } from '@element-plus/icons-vue'
  import {
    getSpinStatisticList,
    getRoomConfig,
    apiPostSpin,
    onceMoreSpin,
    getModList,
    apiPostModConfig
  } from '@/api/spin'

  defineOptions({ name: 'TestSpinStatistic' })

  const route = useRoute()
  const typeId = ref('')
  watch(() => route.fullPath, (val) => {
    const m = val.match(/\/type_id\/(\d+)/)
    typeId.value = m ? m[1] : ''
  }, { immediate: true })

  // 仅当前 tab 是激活实例时才显示数量
  const checkCount = computed(() => {
    return typeId.value === activeTypeId.value ? activeCount.value : 0
  })

  // tab 切到前台：成为激活实例，注册完成回调，启动全局轮询
  onActivated(() => {
    activeTypeId.value = typeId.value
    _onDone = refreshData
    startGlobalTimer()
  })

  // tab 切到后台：停止全局定时器
  onDeactivated(() => {
    activeTypeId.value = ''
    _onDone = null
    stopGlobalTimer()
  })

  // 组件销毁时清理
  onBeforeUnmount(() => {
    activeTypeId.value = ''
    _onDone = null
    stopGlobalTimer()
  })

  const searchBarRef = ref()
  const artTableRef = ref()

  // ---- 生成返奖面板 ----
  const postExpand = ref(true)
  const postLoading = ref(false)
  const postForm = ref({
    room_id: [],
    rtp_id: '',
    bet_amount: '',
    number: '',
    room_type: ''
  })
  const roomOptions = ref<any[]>([])
  const rtpOptions = ref<any[]>([])
  const modOptions = ref<any[]>([])
  const modOptionsForm = ref<any[]>([])
  const betAmountOptions = ref<any[]>([])

  async function handleRoomChange(val: string | number | string[]) {
    postForm.value.bet_amount = ''
    postForm.value.room_type = ''
    if (!val) {
      betAmountOptions.value = []
      modOptionsForm.value = []
      return
    }
    try {
      const res = await getRoomConfig({ room_id: val })
      betAmountOptions.value = res.data.bet || res || []
      modOptionsForm.value = res.data.mod || res || []
    } catch {
      betAmountOptions.value = []
      modOptionsForm.value = []
    }
  }

  async function loadModList(roomId: string | number) {
    try {
      const res = await getModList({ roomId: roomId || 0 })
      if (res.code === 200) {
        modOptions.value = res.data || []
      }
    } catch {
      modOptions.value = []
    }
  }

  async function handlePostSubmit() {
    const f = postForm.value
    if (!f.room_id.length || !f.rtp_id || !f.bet_amount || !f.number) {
      ElMessage.warning($t('qingTianXieWanZheng'))
      return
    }
    postLoading.value = true
    try {
      const res = await apiPostSpin({
        room_id: f.room_id,
        bet_amount: f.bet_amount,
        number: f.number,
        room_type: f.room_type,
        rtp_id: f.rtp_id,
        type_id: typeId.value
      })
      if (res.code === 200) {
        ElMessage.success($t('chengGong'))
        await refreshData()
        // 提交成功后激活当前 tab，启动全局轮询
        activeTypeId.value = typeId.value
        activeCount.value = 1
        startGlobalTimer()
      } else {
        ElMessage.error(res.msg || $t('shiBai'))
      }
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    } finally {
      postLoading.value = false
    }
  }

  // 仅当选中一个房间且不是“全部(0)”时显示游戏模式编辑按钮
  const showModEditBtn = computed(() => {
    const ids = postForm.value.room_id
    return ids.length === 1 && !ids.includes('0')
  })

  // ---- 游戏模式编辑弹窗 ----
  const modEditVisible = ref(false)
  const modEditLoading = ref(false)
  const modEditList = ref<{ mod: string; desc: string }[]>([])
  const modEditValid = computed(() => {
    if (!modEditList.value.length) return false
    return modEditList.value.every(r => String(r.mod).trim() && String(r.desc).trim())
  })
  let modEditRoomId = ''

  function openModEditDialog() {
    const roomId = postForm.value.room_id[0]
    if (!roomId) return
    modEditRoomId = String(roomId)
    modEditList.value = (modOptionsForm.value || []).map((it: any) => ({
      mod: String(it.mod ?? ''),
      desc: String(it.desc ?? '')
    }))
    modEditVisible.value = true
  }

  function addModRow() {
    modEditList.value.push({ mod: '', desc: '' })
  }

  function removeModRow(idx: number) {
    modEditList.value.splice(idx, 1)
  }

  async function handleModSave() {
    if (!modEditList.value.length) {
      ElMessage.warning('请至少添加一条游戏模式')
      return
    }
    const list = modEditList.value.map(r => ({ mod: String(r.mod).trim(), desc: String(r.desc).trim() }))
    if (list.some(r => !r.mod || !r.desc)) {
      ElMessage.warning('请完整填写每一行的“值”和“名称”')
      return
    }
    modEditLoading.value = true
    try {
      const res = await apiPostModConfig({ room_id: modEditRoomId, list })
      if (res.code === 200 || res.code === 0) {
        ElMessage.success($t('chengGong'))
        modEditVisible.value = false
        // 用返回数据（或重新拉一次）刷新本地下拉
        if (Array.isArray(res.data?.mod)) {
          modOptionsForm.value = res.data.mod
        } else {
          const r = await getRoomConfig({ room_id: modEditRoomId })
          modOptionsForm.value = r.data.mod || r.data || []
        }
      } else {
        ElMessage.error(res.msg || $t('shiBai'))
      }
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    } finally {
      modEditLoading.value = false
    }
  }

  // ---- 搜索栏 ----
  const searchForm = ref<Record<string, any>>({
    room_id: '',
    rtp_id: '',
    mod: '',
    bet_amount: [],
    bet_time: [],
    bet_total: [],
    win_total: [],
    spe_times: [],
    spe_total: [],
    free_times: [],
    free_win: [],
    created_at: [],
    rebate_diff: ''
  })

  // 监听搜索栏游戏切换，更新模选项
  const prevRoomId = ref()
  watch(() => searchForm.value.room_id, (val) => {
    if (val !== prevRoomId.value) {
      searchForm.value.mod = ''
      prevRoomId.value = val
      loadModList(val || 0)
    }
  }, { immediate: true })

  const formItems = computed(() => [
    {
      label: '',
      key: 'room_id',
      type: 'select',
      props: {
        placeholder: $t('suoShuYouXi'),
        style: { width: '140px' },
        filterable: true,
        clearable: true,
        options: Object.entries(roomOptions.value).map(([key, val]) => ({ label: val, value: key }))
      }
    },
    {
      label: '',
      key: 'mod',
      type: 'select',
      props: {
        placeholder: $t('moShi'),
        style: { width: '100px' },
        clearable: true,
        /* options: [
          { label: $t('puTong'), value: '0' },
          { label: $t('biZhongMoShi'), value: '2' }
        ] */
       options: modOptions.value.map((item: any) => ({ label: item.desc, value: item.mod }))
      }
    },
    {
        label: '',
        key: 'rtp_id',
        type: 'select',
        props: {
          placeholder: $t('rtp'),
          style: { width: '140px' },
          filterable: true,
          clearable: true,
          options: Object.entries(rtpOptions.value).map(([key, val]) => ({ label: val, value: key }))
        }
    },
    {
      label: '',
      key: 'bet_amount',
      type: 'numberrange',
      props: { placeholder: [$t('xiaZhuJinE'), $t('xiaZhuJinE')] }
    },
    {
      label: '',
      key: 'bet_time',
      type: 'numberrange',
      props: { placeholder: [$t('xiaZhuCiShu'), $t('xiaZhuCiShu')] }
    },
    {
      label: '',
      key: 'bet_total',
      type: 'numberrange',
      props: { placeholder: [$t('yaZhuZongE'), $t('yaZhuZongE')] }
    },
    {
      label: '',
      key: 'win_total',
      type: 'numberrange',
      props: { placeholder: [$t('zongYingJin'), $t('zongYingJin')] }
    },
    {
      label: '',
      key: 'spe_times',
      type: 'numberrange',
      props: { placeholder: [$t('teShuYouXiCiShu'), $t('teShuYouXiCiShu')] }
    },
    {
      label: '',
      key: 'spe_total',
      type: 'numberrange',
      props: { placeholder: [$t('teShuYouXiZhongJiangZongE'), $t('teShuYouXiZhongJiangZongE')] }
    },
    {
      label: '',
      key: 'free_times',
      type: 'numberrange',
      props: { placeholder: [$t('mianFeiYouXiCiShu'), $t('mianFeiYouXiCiShu')] }
    },
    {
      label: '',
      key: 'free_win',
      type: 'numberrange',
      props: { placeholder: [$t('mianFeiYouXiYingJin'), $t('mianFeiYouXiYingJin')] }
    },
    {
      label: '',
      key: 'created_at',
      type: 'daterange',
      props: {
        type: 'daterange',
        'start-placeholder': $t('chuangJianShiJian'),
        'end-placeholder': $t('chuangJianShiJian'),
        'value-format': 'YYYY-MM-DD',
        style: { width: '240px' }
      }
    },
    {
      label: '',
      key: 'rebate_diff',
      type: 'select',
      props: {
        placeholder: $t('xianShiYiChangShuJu'),
        style: { width: '130px' },
        clearable: true,
        options: [
          { label: $t('shi'), value: '1' },
        ]
      }
    },
  ])

  // ---- 表格 ----
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
      apiFn: getSpinStatisticList,
      apiParams: { page: 1, limit: 20, type_id: typeId.value },
      columnsFactory: () => [
        { label: $t('suoShuYouXi'), prop: 'room_name', minWidth: 120 },
        { label: $t('dangWei'), prop: 'rtp', minWidth: 80 },
        {
          label: $t('fanJiangLv'),
          prop: 'rebate_per',
          minWidth: 90,
          formatter: (row: any) => {
            const rtpVal = parseFloat(String(row.rtp).replace('%', ''))
            const rebateVal = parseFloat(String(row.rebate_per).replace('%', ''))
            if (!isNaN(rtpVal) && !isNaN(rebateVal) && Math.abs(rebateVal - rtpVal) > 5) {
              return h('span', { style: 'color: red; font-size: 16px; font-weight: bold' }, row.rebate_per)
            }
            return h('span', null, row.rebate_per)
          }
        },
        {
          label: $t('moShi'),
          prop: 'mod_name',
          width: 70
        },
        { label: $t('xiaZhuJinE'), prop: 'bet_amount', minWidth: 100 },
        { label: $t('xiaZhuCiShu'), prop: 'bet_time', minWidth: 100 },
        { label: $t('yaZhuZongE'), prop: 'bet_total', minWidth: 120 },
        { label: $t('zongYingJin'), prop: 'win_total', minWidth: 120 },
        { label: $t('zongYingCiShu'), prop: 'win_times', minWidth: 100 },
        { label: $t('zhongJiangGaiLv'), prop: 'win_per', minWidth: 90 },
        { label: $t('teShuYouXiCiShu'), prop: 'spe_times', minWidth: 110 },
        { label: $t('teShuYouXiZhongJiangZongE'), prop: 'spe_total', minWidth: 150 },
        { label: $t('teShuYouXiChuFaGaiLv'), prop: 'special_trigger_probability', minWidth: 120 },
        { label: $t('mianFeiYouXiCiShu'), prop: 'free_times', minWidth: 110 },
        { label: $t('mianFeiYouXiYingJin'), prop: 'free_win', minWidth: 120 },
        { label: $t('mianFeiYouXiChuFaGaiLv'), prop: 'free_trigger_probability', minWidth: 120 },
        {
          label: $t('chuangJianShiJian'),
          prop: 'created_at',
          minWidth: 150,
          formatter: (row: any) => (window as any).formatTimestamp(row.created_at)
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 120,
          fixed: 'right',
          formatter: (row: any) =>
            h(ElButton, {
              type: 'primary',
              size: 'small',
              onClick: () => handleOnceMore(row)
            }, () => $t('zaiLaiYiCi'))
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        roomOptions.value = response.room || [],
        rtpOptions.value = response.rtps || []
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }

  })

  // ---- 再来一次 ----
  async function handleOnceMore(row: any) {
    try {
      await ElMessageBox.confirm($t('queRenZaiLaiYiCi'), $t('tiShi'), { type: 'warning' })
    } catch {
      return
    }
    try {
      const res = await onceMoreSpin({ id: row.id, type_id: typeId.value })
      if (res.code === 200 || res.code === 0) {
        ElMessage.success($t('chengGong'))
        activeTypeId.value = typeId.value
        activeCount.value = 1
        startGlobalTimer()
        // await refreshData()
      } else {
        ElMessage.error(res.msg || $t('shiBai'))
      }
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 搜索 ----
  async function handleSearch(params: any) {
    replaceSearchParams({ ...params, type_id: typeId.value })
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }
</script>

<style scoped lang="scss">
</style>
