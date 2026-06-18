<template>
  <div>
    <!-- 模拟客户端返奖 -->
    <ElCard class="art-table-card" style="margin-bottom: 16px">
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <span>{{ $t('moNiKeHuDuanFanJiang') }}</span>
          <ElButton :icon="formExpand ? ArrowUp : ArrowDown" size="small" circle @click="formExpand = !formExpand" />
        </div>
      </template>
      <ElCollapseTransition style="margin-left: 15%;">
        <div v-show="formExpand">
          <!-- 用户信息 -->
          <ElForm :model="form" label-width="120px">
            <ElFormItem :label="$t('operatorToken')">
              <ElInput v-model="form.operator_token" style="width: 320px" />
            </ElFormItem>
            <ElFormItem :label="$t('secret')">
              <ElInput v-model="form.secret" style="width: 320px" />
            </ElFormItem>
            <ElFormItem :label="$t('userId')">
              <ElInput v-model="form.user_id" style="width: 200px" />
            </ElFormItem>
            <ElFormItem :label="$t('userName')">
              <ElInput v-model="form.user_name" style="width: 200px" />
            </ElFormItem>
            <ElFormItem :label="$t('rtp')">
              <ElInput v-model.number="form.rtp" type="number" style="width: 200px" />
            </ElFormItem>
            <ElFormItem>
              <ElButton type="primary" :disabled="isDataRunning" @click="createUser">{{ $t('chuangJianYongHu') }}</ElButton>
              <ElButton v-if="user_id" @click="getUserInfo">{{ $t('huoQuYongHuXinXi') }}</ElButton>
            </ElFormItem>
          </ElForm>

          <!-- 游戏操作 -->
          <template v-if="user_id">
            <ElForm :model="form" label-width="120px">
              <ElFormItem :label="$t('youXiLeiXing')">
                <ElSelect v-model="form.type_index" style="width: 200px" @change="handleGameTypeChange">
                  <ElOption v-for="(name, idx) in itemTypes" :key="idx" :label="name" :value="Number(idx)" />
                </ElSelect>
              </ElFormItem>
              <ElFormItem :label="$t('yuYan')">
                <ElSelect v-model="language" style="width: 200px">
                  <ElOption v-for="(langName, langKey) in languages" :key="langKey" :label="langName" :value="langKey" />
                </ElSelect>
              </ElFormItem>
              <ElFormItem :label="$t('youXi')">
                <ElSelect v-model="game_code" style="width: 200px">
                  <ElOption v-for="game in gameList" :key="game.code" :label="game.name" :value="game.code" />
                </ElSelect>
              </ElFormItem>
              <ElFormItem>
                <ElButton type="primary" :disabled="isDataRunning" @click="openGame">{{ $t('daKaiYouXi') }}</ElButton>
                <ElButton :disabled="isDataRunning" @click="getGameLaunchHtml">{{ $t('huoQuYouXiHtml') }}</ElButton>
                <ElButton type="warning" :disabled="isDataRunning" @click="checkGames">{{ $t('youXiJianCe') }}</ElButton>
                <ElButton @click="getCheckGamesResultAction">{{ $t('chaKanJianCeJieGuo') }}</ElButton>
              </ElFormItem>
            </ElForm>
            <iframe v-show="showIframe" id="gameFrame" width="399" height="599" style="margin-left: 120px; border: 1px solid var(--default-border);"></iframe>

            <!-- 设置 RTP -->
            <ElForm :model="form" label-width="120px">
              <ElFormItem :label="$t('rtp')">
                <ElInput v-model.number="form.rtp" type="number" style="width: 200px" />
              </ElFormItem>
              <ElFormItem>
                <ElButton type="primary" @click="setUserRtp">{{ $t('sheZhiRtp') }}</ElButton>
              </ElFormItem>
            </ElForm>

            <!-- 数据开跑 -->
            <ElForm :model="form" label-width="120px">
              <ElFormItem :label="$t('meiLunJuShu')">
                <ElInput v-model.number="nums" type="number" style="width: 200px" />
              </ElFormItem>
              <ElFormItem :label="$t('sheZhiLunCi')">
                <ElInput v-model.number="rounds" type="number" style="width: 200px" />
              </ElFormItem>
              <ElFormItem>
                <ElButton type="primary" :disabled="isDataRunning" @click="startData">{{ $t('shuJuKaiPao') }}</ElButton>
                <ElButton v-if="!isDataRunning" @click="openResultDialog">{{ $t('huoQuJieGuo') }}</ElButton>
                <ElButton v-else disabled><el-icon class="is-loading"><Loading /></el-icon> {{ $t('zhiXingZhong') }}...</ElButton>
              </ElFormItem>
              <ElFormItem>
                <div class="help-block" style="color: var(--el-color-danger);">
                  <i class="el-icon-warning"></i> {{ $t('shuJuKaiPaoTiShi') }}
                </div>
              </ElFormItem>
            </ElForm>
          </template>
        </div>
      </ElCollapseTransition>
    </ElCard>

    <!-- 表格 -->
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

    <!-- 查看结果弹窗 -->
    <ElDialog v-model="resultDialogVisible" :title="$t('biaoGeZhanShi')" width="70%" top="5vh" destroy-on-close>
      <GetResult v-if="resultDialogVisible" :id="resultDialogId" :visible="resultDialogVisible" />
    </ElDialog>

    <!-- 检测结果弹窗 -->
    <ElDialog v-model="checkDialogVisible" :title="$t('chaKanJianCeJieGuo')" width="90%" top="5vh" destroy-on-close>
      <GetCheckGames v-if="checkDialogVisible" :type="form.type" :visible="checkDialogVisible" />
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, computed, reactive, onMounted, nextTick } from 'vue'
  import { $t } from '@/locales'
  import { ElMessage, ElMessageBox, ElButton, ElCollapseTransition, ElDivider, ElTag, ElCard, ElForm, ElFormItem, ElSelect, ElOption, ElInput, ElDialog, ElTooltip } from 'element-plus'
  import { ArrowUp, ArrowDown, Loading } from '@element-plus/icons-vue'
  import CryptoJS from 'crypto-js'
  import axios from 'axios'
  import {
    getImitateInit,
    getImitateList,
    postImitateRun,
    pollImitateStatus,
    postCheckGames
  } from '@/api/spin'
  import GetResult from './components/GetResult.vue'
  import GetCheckGames from './components/GetCheckGames.vue'

  defineOptions({ name: 'TestImitate' })

  const artTableRef = ref()

  // ---- 展开/折叠 ----
  const formExpand = ref(true)

  // ---- 表单数据 ----
  const form = reactive({
    operator_token: '1lhdibfzzepmviiyfsfdqeu2nzw2jhc2',
    secret: 'uvtkutdjkdxzqpabe026o7fmrianmo_z',
    user_id: '',
    user_name: '',
    ts: 0,
    rtp: 0,
    type_index: 2, // 默认 pg
    type: 'pg',
  })

  const user_id = ref('')
  const user_token = ref('')
  const game_code = ref('')
  const language = ref('pt')
  const showIframe = ref(false)
  const nums = ref(1)
  const rounds = ref(1)
  const isDataRunning = ref(false)
  const runResultId = ref(0)

  // ---- 弹窗 ----
  const resultDialogVisible = ref(false)
  const resultDialogId = ref(0)
  const checkDialogVisible = ref(false)

  // ---- 初始化数据 ----
  const gameTypesList = ref<Record<string, any[]>>({})
  const gameTypes = ref<Record<string, string>>({})
  const languages = ref<Record<string, string>>({})
  const pageDomain = ref('')

  const itemTypes = computed(() => gameTypes.value)
  const gameList = computed(() => gameTypesList.value[form.type_index] || [])

  // ---- 表格 ----
  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    handleSizeChange,
    handleCurrentChange,
    refreshData
  } = useTable({
    core: {
      apiFn: getImitateList,
      apiParams: { page: 1, limit: 20 },
      columnsFactory: () => [
        // { type: 'selection', width: 50 },
        { label: $t('suoShuYouXi'), prop: 'game_code', minWidth: 150, fixed: 'left' },
        { label: $t('youXiLeiXing'), prop: 'type_id', minWidth: 80, fixed: 'left' },
        { label: 'RTP', prop: 'rtp', minWidth: 80, fixed: 'left' },
        { label: $t('shiJiRtp'), prop: 'real_rtp', minWidth: 80, fixed: 'left' },
        { label: $t('danCiJuShu'), prop: 'nums', minWidth: 80 },
        { label: $t('zongLunCi'), prop: 'rounds', minWidth: 80 },
        {
          label: $t('zongCiShu'),
          prop: 'totals',
          minWidth: 80,
          formatter: (row: any) => (Number(row.rounds) || 0) * (Number(row.nums) || 0)
        },
        { label: $t('chengGongLunCi'), prop: 'sus', minWidth: 90 },
        { label: $t('yingJu'), prop: 'win', minWidth: 110 },
        { label: $t('shuJu'), prop: 'lose', minWidth: 100 },
        { label: '0', prop: '0', minWidth: 100 },
        { label: '0~1', prop: '0~1', minWidth: 100 },
        { label: '1~2', prop: '1~2', minWidth: 100 },
        { label: '2~3', prop: '2~3', minWidth: 100 },
        { label: '3~4', prop: '3~4', minWidth: 100 },
        { label: '4~5', prop: '4~5', minWidth: 100 },
        { label: '5~10', prop: '5~10', minWidth: 100 },
        { label: '10~15', prop: '10~15', minWidth: 100 },
        { label: '15~20', prop: '15~20', minWidth: 100 },
        { label: '20~25', prop: '20~25', minWidth: 100 },
        { label: '25~30', prop: '25~30', minWidth: 100 },
        { label: '30~40', prop: '30~40', minWidth: 100 },
        { label: '40~50', prop: '40~50', minWidth: 100 },
        { label: '50~75', prop: '50~75', minWidth: 100 },
        { label: '75~100', prop: '75~100', minWidth: 100 },
        { label: '>100', prop: '>100', minWidth: 110 },
        {
          label: $t('zhuangTai'),
          prop: 'status',
          width: 80,
          formatter: (row: any) => {
            const statusTextMap: Record<number, string> = {
              0: $t('zhiXingZhong'),
              1: $t('chengGong'),
              2: $t('shiBai'),
              3: $t('chaoShi'),
              4: $t('xiuFuZhong'),
            }
            const tagTypes: Record<number, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
              0: 'info',
              1: 'success',
              2: 'danger',
              3: 'warning',
              4: 'primary',
            }
            const text = statusTextMap[row.status] || $t('weiZhi')
            const tagType = tagTypes[row.status] || 'info'
            return h(ElTag, { type: tagType, size: 'small' }, () => text)
          }
        },
        { label: $t('shiJian'), prop: 'c_time', minWidth: 150, formatter: (row: any) => row.c_time || '-' },
        {
          label: $t('caoZuo'),
          prop: 'operate',
          width: 150,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', { style: 'display:flex;gap:4px' }, [
              h(ElButton, {
                type: 'primary',
                size: 'small',
                onClick: () => viewResult(row)
              }, () => $t('biaoGeZhanShi')),
              row.status === 3
                ? h(ElButton, {
                    type: 'warning',
                    size: 'small',
                    onClick: () => fixData(row)
                  }, () => $t('xiuFu'))
                : null
            ])
        },
        {
          label: $t('shiBaiYuanYin'),
          prop: 'err_msg',
          minWidth: 200,
          formatter: (row: any) => {
            const msg = row.err_msg || ''
            if (!msg) return h('span', '-')
            const maxLen = 20
            const display = msg.length > maxLen ? msg.slice(0, maxLen) + '...' : msg
            return h(ElTooltip, { content: msg, placement: 'top', 'show-after': 300 }, () => display)
          }
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

  // ---- 初始化 ----
  async function loadInitData() {
    try {
      const res = await getImitateInit()
      const data = res.data || res
      gameTypesList.value = data.game_types_list || {}
      gameTypes.value = data.game_types || {}
      languages.value = data.languages || {}
      pageDomain.value = data.page_domain || ''
      // 默认选中第一个有游戏的类型
      const keys = Object.keys(gameTypesList.value).map(Number).filter(k => gameTypesList.value[k]?.length)
      if (keys.length > 0) {
        if (keys.includes(2)) {
          form.type_index = 2
        } else {
          form.type_index = keys[0]
        }
        handleGameTypeChange()
      }
      // 默认用户
      initUser()
    } catch {
      // empty
    }
  }

  function initUser() {
    const random = Math.floor(100000000 + Math.random() * 900000000)
    form.user_id = `user_${random}`
    form.user_name = `user_${random}`
  }

  function handleGameTypeChange() {
    game_code.value = ''
    if (gameList.value.length) {
      game_code.value = gameList.value[0].code
    }
    form.type = gameTypes.value[form.type_index] || 'pg'
  }

  // ---- 外部 API 签名 ----
  function buildSign(data: Record<string, any>): string {
    /*const sorted = Object.keys(data).sort().reduce((acc: Record<string, any>, k: string) => {
      acc[k] = data[k]
      return acc
    }, {})*/
    const queryStr = new URLSearchParams(data).toString()
    return CryptoJS.MD5(queryStr).toString()
  }

  function buildExternalUrl(path: string): string {
    return `${pageDomain.value}${path}`
  }

  // ---- 创建用户 ----
  async function createUser() {
    if (!form.user_id || !form.user_name) {
      ElMessage.warning($t('qingTianXieWanZheng'))
      return
    }
    form.ts = Math.floor(Date.now() / 1000)
    const data: Record<string, any> = {
      operator_token: form.operator_token,
      rtp: form.rtp || 0,
      ts: form.ts,
      user_id: form.user_id,
      user_name: form.user_name,
      key: form.secret
    }
    data.sign = buildSign(data)
    delete data.key

    try {
      const res = await axios.post(buildExternalUrl('/api/web/user_session'), data)
      if (res.data.code !== 0) {
        ElMessage.error($t('chuangJianYongHuShiBai') + ': ' + (res.data.message || ''))
        return
      }
      const resData = res.data.data
      user_id.value = resData.user_id
      user_token.value = resData.token
      form.rtp = resData.rtp
      ElMessage.success($t('chuangJianYongHuChengGong'))
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 获取用户信息 ----
  async function getUserInfo() {
    form.ts = Math.floor(Date.now() / 1000)
    const data: Record<string, any> = {
      operator_token: form.operator_token,
      ts: form.ts,
      user_id: user_id.value,
      key: form.secret
    }
    data.sign = buildSign(data)
    delete data.key

    try {
      const res = await axios.post(buildExternalUrl('/api/web/user_info'), data)
      if (res.data.code !== 0) {
        ElMessage.error($t('qingQiuShiBai'))
        return
      }
      const resData = res.data.data
      form.rtp = resData.rtp
      ElMessage.info(JSON.stringify(resData))
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 打开游戏 ----
  async function openGame() {
    if (!user_id.value || !user_token.value) {
      ElMessage.warning($t('qingXianChuangJianYongHu'))
      return
    }
    if (!game_code.value) {
      ElMessage.warning($t('qingXuanZeYouXi'))
      return
    }

    form.ts = Math.floor(Date.now() / 1000)
    const data: Record<string, any> = {
      game_code: game_code.value,
      language: language.value,
      operator_token: form.operator_token,
      ts: form.ts,
      type: form.type,
      user_id: user_id.value,
      user_token: user_token.value,
      key: form.secret
    }
    data.sign = buildSign(data)
    delete data.key

    try {
      const res = await axios.post(buildExternalUrl('/api/web/game_url'), data)
      if (res.data.code !== 0) {
        ElMessage.error($t('qingQiuShiBai') + ': ' + (res.data.message || ''))
        return
      }
      window.open(res.data.data.url, '_blank')
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 获取游戏 html ----
  async function getGameLaunchHtml() {
    if (!user_id.value || !user_token.value) {
      ElMessage.warning($t('qingXianChuangJianYongHu'))
      return
    }
    if (!game_code.value) {
      ElMessage.warning($t('qingXuanZeYouXi'))
      return
    }

    form.ts = Math.floor(Date.now() / 1000)
    const data: Record<string, any> = {
      game_code: game_code.value,
      language: language.value,
      operator_token: form.operator_token,
      ts: form.ts,
      type: form.type,
      user_id: user_id.value,
      user_token: user_token.value,
      key: form.secret
    }
    data.sign = buildSign(data)
    delete data.key

    try {
      const res = await axios.post(buildExternalUrl('/api/web/get_launch_url'), data)
      showIframe.value = true
      await nextTick()
      const iframe = document.getElementById('gameFrame') as HTMLIFrameElement
      if (iframe) {
        const doc = iframe.contentDocument || iframe.contentWindow?.document
        if (doc) {
          doc.open()
          doc.write(typeof res.data === 'string' ? res.data : JSON.stringify(res.data))
          doc.close()
        }
      }
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 设置 RTP ----
  async function setUserRtp() {
    if (form.rtp > 150) {
      ElMessage.warning($t('rtpLimit'))
      return
    }
    form.ts = Math.floor(Date.now() / 1000)
    const data: Record<string, any> = {
      operator_token: form.operator_token,
      rtp: form.rtp || 0,
      ts: form.ts,
      user_id: form.user_id,
      key: form.secret
    }
    data.sign = buildSign(data)
    delete data.key

    try {
      const res = await axios.post(buildExternalUrl('/api/web/set_user_rtp'), data)
      if (res.data.code !== 0) {
        ElMessage.error($t('qingQiuShiBai'))
        return
      }
      ElMessage.success($t('sheZhiChengGong') + '，' + $t('dangWei') + '：' + res.data.data.rtp)
      form.rtp = res.data.data.rtp
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 数据开跑 ----
  async function startData() {
    if (!user_id.value || !user_token.value) {
      ElMessage.warning($t('qingXianChuangJianYongHu'))
      return
    }
    if (!game_code.value) {
      ElMessage.warning($t('qingXuanZeYouXi'))
      return
    }
    if (nums.value < 1 || rounds.value < 1) {
      ElMessage.warning($t('juShuLunShuZhiShaoWei1'))
      return
    }

    form.ts = Math.floor(Date.now() / 1000)
    const data: Record<string, any> = {
      game_code: game_code.value,
      language: language.value,
      operator_token: form.operator_token,
      ts: form.ts,
      type: form.type,
      user_id: user_id.value,
      user_token: user_token.value,
      key: form.secret
    }
    const dataSave = { ...data, nums: nums.value, rounds: rounds.value, rtp: form.rtp, type_id: form.type_index, show_user_id: form.user_id, user_name: form.user_name }

    data.sign = buildSign(data)
    delete data.key

    try {
      const gameRes = await axios.post(buildExternalUrl('/api/web/game_url'), data)
      if (gameRes.data.code !== 0) {
        ElMessage.error($t('qingQiuShiBai') + ': ' + (gameRes.data.message || ''))
        return
      }
      const gameUrl = gameRes.data.data.url
      const runRes = await postImitateRun({
        game_url: gameUrl,
        nums: nums.value,
        rounds: rounds.value,
        user_id: user_id.value,
        rtp: form.rtp,
        type_id: form.type_index,
        id: 0,
        save_data: dataSave
      })
      if (runRes.code !== 200) {
        ElMessage.error($t('shiBai') + '：' + (runRes.msg || ''))
        return
      }
      runResultId.value = runRes.data.id
      isDataRunning.value = true
      ElMessage.success($t('zhiXingChengGong'))
      startPolling()
    } catch {
      isDataRunning.value = false
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 轮询 ----
  let intervalKey: ReturnType<typeof setInterval> | null = null

  function startPolling() {
    let runNum = 0
    if (intervalKey) {
      clearInterval(intervalKey)
    }
    intervalKey = setInterval(async () => {
      if (runNum > 180) {
        clearInterval(intervalKey!)
        intervalKey = null
        isDataRunning.value = false
        ElMessage.warning($t('lunXunChaoShi'))
        await refreshData()
        return
      }
      try {
        const poll = await pollImitateStatus({ id: runResultId.value })
        const status = poll.data?.status
        if (status >= 1 && status !== 4) {
          isDataRunning.value = false
          if (status === 1) {
            ElMessage.success($t('shuJuPaoWanCheng'))
          } else if (status === 2) {
            ElMessage.error($t('shuJuZhiXingShiBai'))
          } else if (status === 3) {
            ElMessage.warning($t('shuJuZhiXingChaoShi'))
          }
          await refreshData()
          clearInterval(intervalKey!)
          intervalKey = null
          return
        }
      } catch {
        // continue polling
      }
    }, 2000)
  }

  // ---- 查看结果 ----
  function viewResult(row: any) {
    resultDialogId.value = row.id
    resultDialogVisible.value = true
  }

  // ---- 打开结果弹窗 ----
  function openResultDialog() {
    if (!runResultId.value) {
      ElMessage.warning($t('wuZhiXingJiLu'))
      return
    }
    resultDialogId.value = runResultId.value
    resultDialogVisible.value = true
  }

  // ---- 修复 ----
  async function fixData(row: any) {
    if (isDataRunning.value) {
      ElMessage.warning($t('youZhiXingZhongRenWu'))
      return
    }
    await ElMessageBox.confirm($t('queRenYaoXiuFuMa'), $t('tiShi'), { type: 'warning' })

    if (!row.save_data) {
      ElMessage.warning($t('xiuFuCanShuWeiCunChu'))
      return
    }

    const params = typeof row.save_data === 'string' ? JSON.parse(row.save_data) : row.save_data
    const remainingRounds = Number(params.rounds) - Number(row.sus)
    if (params.nums < 1 || remainingRounds < 1) {
      ElMessage.warning($t('juShuLunShuZhiShaoWei1'))
      return
    }

    form.user_id = params.show_user_id
    form.user_name = params.user_name
    form.rtp = params.rtp
    form.type_index = params.type_id
    form.operator_token = params.operator_token
    form.secret = params.key
    form.type = params.type
    user_id.value = params.user_id
    user_token.value = params.user_token
    game_code.value = params.game_code
    language.value = params.language
    nums.value = params.nums
    rounds.value = remainingRounds

    if (!params.user_id || !params.user_token) {
      ElMessage.warning($t('yongHuWeiChuangJian'))
      return
    }
    if (!params.game_code) {
      ElMessage.warning($t('youXiWeiXuanZe'))
      return
    }

    const data: Record<string, any> = {
      game_code: params.game_code,
      language: params.language,
      operator_token: params.operator_token,
      ts: Math.floor(Date.now() / 1000),
      type: params.type,
      user_id: params.user_id,
      user_token: params.user_token,
      key: params.key
    }
    data.sign = buildSign(data)
    delete data.key

    try {
      const gameRes = await axios.post(buildExternalUrl('/api/web/game_url'), data)
      if (gameRes.data.code !== 0) {
        ElMessage.error($t('qingQiuShiBai') + ': ' + (gameRes.data.message || ''))
        return
      }
      const runRes = await postImitateRun({
        game_url: gameRes.data.data.url,
        nums: params.nums,
        rounds: remainingRounds,
        user_id: params.user_id,
        rtp: params.rtp,
        type_id: params.type_id,
        id: row.id,
        save_data: {}
      })
      if (runRes.code !== 200) {
        ElMessage.error($t('shiBai') + '：' + (runRes.msg || ''))
        return
      }
      runResultId.value = runRes.data.id
      isDataRunning.value = true
      ElMessage.success($t('zhiXingChengGong'))
      startPolling()
    } catch {
      isDataRunning.value = false
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 游戏检测 ----
  async function checkGames() {
    if (!user_id.value || !user_token.value) {
      ElMessage.warning($t('qingXianChuangJianYongHu'))
      return
    }

    form.ts = Math.floor(Date.now() / 1000)
    const data: Record<string, any> = {
      game_code: game_code.value,
      language: language.value,
      operator_token: form.operator_token,
      ts: form.ts,
      type: form.type,
      user_id: user_id.value,
      user_token: user_token.value,
      key: form.secret
    }

    try {
      const res = await postCheckGames(data)
      if (res.code === 200) {
        ElMessage.success($t('qingQiuChengGong'))
      } else {
        ElMessage.error(res.msg || $t('shiBai'))
      }
    } catch {
      ElMessage.error($t('qingQiuShiBai'))
    }
  }

  // ---- 获取检测结果 ----
  function getCheckGamesResultAction() {
    checkDialogVisible.value = true
  }

  // ---- 生命周期 ----
  onMounted(async () => {
    await loadInitData()
  })
</script>

<style scoped lang="scss">
.help-block {
  font-size: 12px;
}
</style>
