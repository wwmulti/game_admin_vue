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
              <ElSelect v-model="postForm.room_id" style="width: 160px" filterable @change="handleRoomChange">
                <ElOption v-for="(val, key) in roomOptions" :key="key" :label="val" :value="key" />
              </ElSelect>
            </ElFormItem>
            <ElFormItem label="RTP" prop="rtp_id">
              <ElSelect v-model="postForm.rtp_id" style="width: 160px" filterable>
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
              <ElInput v-model="postForm.room_type" style="width: 160px" />
            </ElFormItem>
            <ElFormItem>
              <ElButton type="primary" :loading="postLoading" @click="handlePostSubmit">{{ $t('queDing') }}</ElButton>
            </ElFormItem>
          </ElForm>
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
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, computed, watch } from 'vue'
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
    ElCollapseTransition
  } from 'element-plus'
  import { ArrowUp, ArrowDown } from '@element-plus/icons-vue'
  import {
    getSpinStatisticList,
    getRoomConfig,
    apiPostSpin,
    onceMoreSpin
  } from '@/api/spin'

  defineOptions({ name: 'TestSpinStatistic' })

  const route = useRoute()
  const typeId = ref('')
  watch(() => route.fullPath, (val) => {
    const m = val.match(/\/type_id\/(\d+)/)
    typeId.value = m ? m[1] : ''
  }, { immediate: true })

  const searchBarRef = ref()
  const artTableRef = ref()

  // ---- 生成返奖面板 ----
  const postExpand = ref(true)
  const postLoading = ref(false)
  const postForm = ref({
    room_id: '',
    rtp_id: '',
    bet_amount: '',
    number: '',
    room_type: '0'
  })
  const roomOptions = ref<any[]>([])
  const rtpOptions = ref<any[]>([])
  const betAmountOptions = ref<any[]>([])

  async function handleRoomChange(val: string | number) {
    postForm.value.bet_amount = ''
    if (!val) {
      betAmountOptions.value = []
      return
    }
    try {
      const res = await getRoomConfig({ room_id: val })
      betAmountOptions.value = res.data || res || []
    } catch {
      betAmountOptions.value = []
    }
  }

  async function handlePostSubmit() {
    const f = postForm.value
    if (!f.room_id || !f.rtp_id || !f.bet_amount || !f.number) {
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
        rtp_id: f.rtp_id
      })
      if (res.code === 200 || res.code === 0) {
        ElMessage.success($t('chengGong'))
        await refreshData()
      } else {
        ElMessage.error(res.msg || $t('shiBai'))
      }
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    } finally {
      postLoading.value = false
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
    created_at: []
  })

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
        options: [
          { label: $t('puTong'), value: '0' },
          { label: $t('biZhongMoShi'), value: '2' }
        ]
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
    }
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
        { type: 'selection', width: 50 },
        { label: $t('suoShuYouXi'), prop: 'room_name', minWidth: 120 },
        { label: $t('dangWei'), prop: 'rtp', minWidth: 80 },
        {
          label: $t('moShi'),
          prop: 'mod',
          width: 70,
          formatter: (row: any) =>
            h('span', null, row.mod === '2' ? $t('biZhongMoShi') : $t('puTong'))
        },
        { label: $t('xiaZhuJinE'), prop: 'bet_amount', minWidth: 100 },
        { label: $t('xiaZhuCiShu'), prop: 'bet_time', minWidth: 100 },
        { label: $t('yaZhuZongE'), prop: 'bet_total', minWidth: 120 },
        { label: $t('zongYingJin'), prop: 'win_total', minWidth: 120 },
        { label: $t('zongYingCiShu'), prop: 'win_times', minWidth: 100 },
        { label: $t('zhongJiangGaiLv'), prop: 'win_per', minWidth: 90 },
        { label: $t('fanJiangLv'), prop: 'rebate_per', minWidth: 90 },
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
      const res = await onceMoreSpin({ id: row.id })
      if (res.code === 200 || res.code === 0) {
        ElMessage.success($t('chengGong'))
        await refreshData()
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
