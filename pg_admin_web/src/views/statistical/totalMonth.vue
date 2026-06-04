<template>
  <div class="statistics-container">
    <ArtSearchBar
      ref="searchBarRef"
      v-model="searchForm"
      :items="formItems"
      span="4"
      @reset="handleReset"
      @search="handleSearch"
    />

    <div class="section-title">{{ $t('chongTiTongJi') }}</div>
    <ElRow :gutter="12" class="statistic-row">
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('chongZhiZongShu') }}</span></div
          >
          <div class="card-value">{{ (Number(total_charge_amount) / 1000).toFixed(2) }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('chongZhiRenShu') }}</span></div
          >
          <div class="card-value">{{ total_charge_men ? total_charge_men : '0' }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('xinZengZongChongZhiShu') }}</span></div
          >
          <div class="card-value">{{
            (Number(new_charge_amount ? new_charge_amount : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('xinZengChongZhiRenShu') }}</span></div
          >
          <div class="card-value">{{ new_charge_men ? new_charge_men : '0' }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('shenQingChuKuan') }}</span></div
          >
          <div class="card-value">{{
            (apply_withdraw ? apply_withdraw : 0 / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('chengGongTiXian') }}</span></div
          >
          <div class="card-value">{{ (total_withdraw / 1000).toFixed(2) }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('tiXianShouXuFei') }}</span></div
          >
          <div class="card-value">{{ (withdraw_fee / 1000).toFixed(2) }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('shiJiTiXianJinE') }}</span></div
          >
          <div class="card-value">{{ (real_withdraw / 1000).toFixed(2) }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('shiJiCunTiCha') }}</span></div
          >
          <div class="card-value">{{ (real_diff / 1000).toFixed(2) }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('tiXianRenShu') }}</span></div
          >
          <div class="card-value">{{ total_withdraw_men ? total_withdraw_men : '0' }}</div>
        </ElCard>
      </ElCol>
    </ElRow>

    <div class="section-title">{{ $t('tiXianRenShu') }}</div>
    <ElRow :gutter="12" class="statistic-row">
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('zongTouZhu') }}({{ $t('quanBu') }})</span></div
          >
          <div class="card-value">{{ game_use ? Number(game_use / 1000).toFixed(2) : '0.00' }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('zongChanChu') }}</span></div
          >
          <div class="card-value">{{ game_get ? Number(game_get / 1000).toFixed(2) : '0.00' }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('pingTaiYouXiYingKui') }}</span></div
          >
          <div class="card-value">{{
            platform_profit ? Number(platform_profit / 1000).toFixed(2) : '0.00'
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('zongTouZhu') }} ({{ $t('ziYing') }})</span>
            <span class="ratio-label"
              >{{ $t('zhanBi') }}
              {{ game_use == 0 ? '100' : Number((game_use_zy / game_use) * 100).toFixed(2) }}%</span
            >
          </div>
          <div class="card-value">{{
            game_use_zy ? Number(game_use_zy / 1000).toFixed(2) : '0.00'
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('zongChanChu') }} ({{ $t('ziYing') }})</span>
            <span class="ratio-label"
              >{{ $t('zhanBi') }}
              {{ game_get == 0 ? '100' : Number((game_get_zy / game_get) * 100).toFixed(2) }}%</span
            >
          </div>
          <div class="card-value">{{
            game_get_zy ? Number(game_get_zy / 1000).toFixed(2) : '0.00'
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('pingTaiYouXiYingKui') }} ({{ $t('ziYing') }})</span>
            <span class="ratio-label"
              >{{ $t('zhanBi') }}
              {{
                game_profit == 0 ? '100' : Number((game_profit_zy / game_profit) * 100).toFixed(2)
              }}%</span
            >
          </div>
          <div class="card-value">{{
            game_profit_zy ? Number(game_profit_zy / 1000).toFixed(2) : '0.00'
          }}</div>
        </ElCard>
      </ElCol>
    </ElRow>

    <div class="section-title-flex">
      <span class="title-text">{{ $t('daiLiTongJi') }}</span>
      <span class="title-sub-text"
        >{{ $t('heJi') }}
        {{
          ((Number(agent_bonus) + Number(invite_bonus) + Number(game_profit)) / 1000).toFixed(2)
        }}</span
      >
    </div>
    <ElRow :gutter="12" class="statistic-row">
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('xiaJiTouZhuFanLi') }}</span></div
          >
          <div class="card-value">{{ (Number(agent_bonus) / 1000).toFixed(2) }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('yaoQingJiangLi') }}</span></div
          >
          <div class="card-value">{{ (Number(invite_bonus) / 1000).toFixed(2) }}</div>
        </ElCard>
      </ElCol>
    </ElRow>

    <div class="section-title">{{ $t('zongRenShu') }}</div>
    <ElRow :gutter="12" class="statistic-row">
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('zhengChangYongHu') }}</span></div
          >
          <div class="card-value">{{ total_normal_user_men ?? 0 }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('jinYongYongHu') }}</span></div
          >
          <div class="card-value">{{ total_disabled_user_men ?? 0 }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header"
            ><span class="tag-label">{{ $t('jinRiZhuCe') }}</span></div
          >
          <div class="card-value">{{ new_user_men ?? 0 }}</div>
        </ElCard>
      </ElCol>
    </ElRow>

    <div class="section-title-flex">
      <span class="title-text">{{ $t('xiTongJiangLiTongJi') }}</span>
      <span class="title-sub-text"
        >{{ $t('heJi') }}
        {{
          (
            (Number(platform_send) +
              Number(total_recharge_send) +
              Number(total_sign_send) +
              Number(total_vip_send) +
              Number(total_spin_send) +
              Number(total_help_send) +
              Number(total_interest_send)) /
            1000
          ).toFixed(2)
        }}</span
      >
    </div>
    <ElRow :gutter="12" class="statistic-row">
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('zhuCe') }}</span>
            <span class="info-label"
              >{{ $t('zongRenShu') }} {{ platform_send_men ? platform_send_men : '0' }}</span
            >
          </div>
          <div class="card-value">{{
            (Number(platform_send ? platform_send : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('chongZhiZengSong') }}</span>
            <span class="info-label"
              >{{ $t('zongRenShu') }}
              {{ total_recharge_send_men ? total_recharge_send_men : '0' }}</span
            >
          </div>
          <div class="card-value">{{
            (Number(total_recharge_send ? total_recharge_send : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('qianDao') }}</span>
            <span class="info-label"
              >{{ $t('zongRenShu') }} {{ total_sign_send_men ? total_sign_send_men : '0' }}</span
            >
          </div>
          <div class="card-value">{{
            (Number(total_sign_send ? total_sign_send : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('vipJiangLi') }}</span>
            <span class="info-label"
              >{{ $t('zongRenShu') }} {{ total_vip_send_men ? total_vip_send_men : '0' }}</span
            >
          </div>
          <div class="card-value">{{
            (Number(total_vip_send ? total_vip_send : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('zhuanPan') }}</span>
            <span class="info-label"
              >{{ $t('zongRenShu') }} {{ total_spin_send_men ? total_spin_send_men : '0' }}</span
            >
          </div>
          <div class="card-value">{{
            (Number(total_spin_send ? total_spin_send : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('kuiSunFanLi') }}</span>
            <span class="info-label"
              >{{ $t('zongRenShu') }} {{ total_help_send_men ? total_help_send_men : '0' }}</span
            >
          </div>
          <div class="card-value">{{
            (Number(total_help_send ? total_help_send : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
      <ElCol :span="6" :sm="12" :md="6">
        <ElCard shadow="hover" class="stat-card">
          <div class="card-header-flex">
            <span class="tag-label">{{ $t('liXiBaoFanLi') }}</span>
            <span class="info-label"
              >{{ $t('zongRenShu') }}
              {{ total_interest_send_men ? total_interest_send_men : '0' }}</span
            >
          </div>
          <div class="card-value">{{
            (Number(total_interest_send ? total_interest_send : '0') / 1000).toFixed(2)
          }}</div>
        </ElCard>
      </ElCol>
    </ElRow>
  </div>
</template>
<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { totalMonth } from '@/api/statistic'
  import dayjs from 'dayjs'
  const searchBarRef = ref()
  const searchForm = ref({
    time: [] as string[],
    status: undefined,
    package_id: undefined
  })

  const total_charge_amount = ref(0) // 充值总数
  const total_charge_men = ref(0) // 充值人数
  const new_charge_amount = ref(0) // 新增总充值数
  const new_charge_men = ref(0) // 新增充值人数
  const apply_withdraw = ref(0) // 申请出款
  const total_withdraw = ref(0) // 成功提现
  const withdraw_fee = ref(0) // 提现手续费
  const real_withdraw = ref(0) // 实际提现金额
  const real_diff = ref(0) // 实际存提差
  const total_withdraw_men = ref(0) // 提现人数
  const game_use = ref(0) // 总投注（全部）
  const game_get = ref(0) // 总产出
  const game_profit = ref(0) // 游戏盈亏
  const game_use_zy = ref(0) // 总投注（自营）
  const game_get_zy = ref(0) // 总产出（自营）
  const game_profit_zy = ref(0) // 游戏盈亏（自营）
  const agent_bonus = ref(0) // 下级投注返利
  const invite_bonus = ref(0) // 邀请奖励
  const total_normal_user_men = ref(0) // 正常用户
  const total_disabled_user_men = ref(0) // 禁用用户
  const new_user_men = ref(0) // 今日注册
  const platform_send = ref(0) // 注册（赠送金额）
  const platform_send_men = ref(0) // 注册总人数
  const total_recharge_send = ref(0) // 充值赠送（赠送金额）
  const total_recharge_send_men = ref(0) // 充值赠送总人数
  const total_sign_send = ref(0) // 签到（赠送金额）
  const total_sign_send_men = ref(0) // 签到总人数
  const total_vip_send = ref(0) // VIP奖励（赠送金额）
  const total_vip_send_men = ref(0) // VIP奖励总人数
  const total_spin_send = ref(0) // 转盘（赠送金额）
  const total_spin_send_men = ref(0) // 转盘总人数
  const total_help_send = ref(0) // 亏损返利（赠送金额）
  const total_help_send_men = ref(0) // 亏损返利总人数
  const total_interest_send = ref(0) // 利息宝返利（赠送金额）
  const total_interest_send_men = ref(0) // 利息宝返利总人数
  const active_user_men = ref(0)
  const first_charge_amount = ref(0)
  const first_charge_men = ref(0)
  const gm_send = ref(0)
  const platform_get = ref(0)
  const platform_profit = ref(0)
  const platform_rtp = ref(0)
  const platform_use = ref(0)
  const total_balance = ref(0)
  const total_profit = ref(0)
  const total_fake_withdraw = ref(0)
  const game_rtp = ref(0)
  const simulation_send = ref(0)
  const withdraw_percent = ref(0)
  const packages = ref<any[]>([])

  const formItems = computed(() => [
    {
      label: 'kaiFangPingTai',
      key: 'package_id',
      type: 'select',
      placeholder: 'kaiFangPingTai',
      options: packages.value
        ? packages.value.map((item: any) => ({
            label: item.title,
            value: item.id
          }))
        : []
    },
    {
      label: 'riQi',
      key: 'time',
      type: 'daterange',
      default: [dayjs().format('YYYY-MM'), dayjs().format('YYYY-MM')],
      props: {
        type: 'monthrange',
        'start-placeholder': $t('kaiShiRiQi'),
        'end-placeholder': $t('jieShuRiQi'),
        'value-format': 'YYYY-MM'
      }
    }
  ])

  const todayStr = dayjs().format('YYYY-MM')
  const { getData, replaceSearchParams, resetSearchParams, searchParams } = useTable({
    core: {
      apiFn: totalMonth,
      apiParams: {
        page: 1,
        need_reload: 1,
        page_size: 20,
        time: [todayStr, todayStr]
      },
      columnsFactory: () => []
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          active_user_men: response.data.active_user_men || 0,
          agent_bonus: response.data.agent_bonus || 0,
          apply_withdraw: response.data.apply_withdraw || 0,
          first_charge_amount: response.data.first_charge_amount || 0,
          first_charge_men: response.data.first_charge_men || 0,
          game_get: response.data.game_get || 0,
          game_get_zy: response.data.game_get_zy || 0,
          game_profit: response.data.game_profit || 0,
          game_profit_zy: response.data.game_profit_zy || 0,
          game_rtp: response.data.game_rtp || 0,
          game_use: response.data.game_use || 0,
          game_use_zy: response.data.game_use_zy || 0,
          gm_send: response.data.gm_send || 0,
          invite_bonus: response.data.invite_bonus || 0,
          new_charge_amount: response.data.new_charge_amount || 0,
          new_charge_men: response.data.new_charge_men || 0,
          new_user_men: response.data.new_user_men || 0,
          platform_get: response.data.platform_get || 0,
          platform_profit: response.data.platform_profit || 0,
          platform_rtp: response.data.platform_rtp || 0,
          platform_send: response.data.platform_send || 0,
          platform_send_men: response.data.platform_send_men || 0,
          platform_use: response.data.platform_use || 0,
          real_diff: response.data.real_diff || 0,
          real_withdraw: response.data.real_withdraw || 0,
          simulation_send: response.data.simulation_send || 0,
          total_balance: response.data.total_balance || 0,
          total_charge_amount: response.data.total_charge_amount || 0,
          total_charge_men: response.data.total_charge_men || 0,
          total_disabled_user_men: response.data.total_disabled_user_men || 0,
          total_fake_withdraw: response.data.total_fake_withdraw || 0,
          total_help_send: response.data.total_help_send || 0,
          total_help_send_men: response.data.total_help_send_men || 0,
          total_interest_send: response.data.total_interest_send || 0,
          total_interest_send_men: response.data.total_interest_send_men || 0,
          total_normal_user_men: response.data.total_normal_user_men || 0,
          total_profit: response.data.total_profit || 0,
          total_recharge_send: response.data.total_recharge_send || 0,
          total_recharge_send_men: response.data.total_recharge_send_men || 0,
          total_sign_send_men: response.data.total_sign_send_men || 0,
          total_spin_send: response.data.total_spin_send || 0,
          total_spin_send_men: response.data.total_spin_send_men || 0,
          total_vip_send: response.data.total_vip_send || 0,
          total_vip_send_men: response.data.total_vip_send_men || 0,
          total_withdraw: response.data.total_withdraw || 0,
          total_withdraw_men: response.data.total_withdraw_men || 0,
          withdraw_fee: response.data.withdraw_fee || 0,
          withdraw_percent: response.data.withdraw_percent || 0,
          packages: response.packages
        }
      }
    },
    hooks: {
      onSuccess: async (data: any[], response: any) => {
        packages.value = response.packages
        total_charge_amount.value = response.total_charge_amount
        total_charge_men.value = response.total_charge_men
        new_charge_amount.value = response.new_charge_amount
        new_charge_men.value = response.new_charge_men
        apply_withdraw.value = response.apply_withdraw
        total_withdraw.value = response.total_withdraw
        withdraw_fee.value = response.withdraw_fee
        real_withdraw.value = response.real_withdraw
        real_diff.value = response.real_diff
        total_withdraw_men.value = response.total_withdraw_men
        game_use.value = response.game_use
        game_get.value = response.game_get
        game_profit.value = response.game_profit
        game_use_zy.value = response.game_use_zy
        game_get_zy.value = response.game_get_zy
        game_profit_zy.value = response.game_profit_zy
        agent_bonus.value = response.agent_bonus
        invite_bonus.value = response.invite_bonus
        total_normal_user_men.value = response.total_normal_user_men
        total_disabled_user_men.value = response.total_disabled_user_men
        new_user_men.value = response.new_user_men
        platform_send.value = response.platform_send
        platform_send_men.value = response.platform_send_men
        total_recharge_send.value = response.total_recharge_send
        total_recharge_send_men.value = response.total_recharge_send_men
        total_sign_send.value = response.total_sign_send || 0
        total_sign_send_men.value = response.total_sign_send_men || response.total_sign_men || 0
        total_vip_send.value = response.total_vip_send
        total_vip_send_men.value = response.total_vip_send_men
        total_spin_send.value = response.total_spin_send
        total_spin_send_men.value = response.total_spin_send_men
        total_help_send.value = response.total_help_send
        total_help_send_men.value = response.total_help_send_men
        total_interest_send.value = response.total_interest_send
        total_interest_send_men.value = response.total_interest_send_men

        active_user_men.value = response.active_user_men
        first_charge_amount.value = response.first_charge_amount
        first_charge_men.value = response.first_charge_men
        gm_send.value = response.gm_send
        platform_get.value = response.platform_get
        platform_profit.value = response.platform_profit
        platform_rtp.value = response.platform_rtp
        platform_use.value = response.platform_use
        total_balance.value = response.total_balance
        total_profit.value = response.total_profit
        total_fake_withdraw.value = response.total_fake_withdraw
        game_rtp.value = response.game_rtp
        simulation_send.value = response.simulation_send
        withdraw_percent.value = response.withdraw_percent
        if (response.packages.length > 0) {
          packages.value.unshift({ id: null, title: $t('quanBu') })
          packages.value = response.packages
        }
        searchParams.need_reload = 0
      }
    }
  })

  function handleReset() {
    resetSearchParams()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    replaceSearchParams(params)
    await getData()
  }
</script>

<style scoped lang="scss">
  .statistics-container {
    padding: 15px;
    background-color: #f8fafc;
    min-height: 100%;

    /* 标准单文本大标题 - 调大至 16px */
    .section-title {
      font-size: 16px;
      font-weight: 600;
      color: #3b82f6;
      margin: 20px 0 14px 0;
      padding-left: 8px;
      border-left: 4px solid #3b82f6;
    }

    /* 弹性流式大标题 - 调大至 16px */
    .section-title-flex {
      margin: 20px 0 14px 0;
      padding-left: 8px;
      border-left: 4px solid #3b82f6;
      display: flex;
      align-items: center;

      .title-text {
        font-size: 16px;
        font-weight: 600;
        color: #3b82f6;
      }

      .title-sub-text {
        font-size: 14px;
        font-weight: 500;
        color: #f59e0b;
        margin-left: 14px;
      }
    }

    .statistic-row {
      margin-bottom: 10px;

      .el-col {
        margin-bottom: 14px;
      }
    }

    /* 核心卡片样式 */
    .stat-card {
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      background-color: #ffffff;
      transition: all 0.2s ease-in-out;

      &:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
      }

      :deep(.el-card__body) {
        padding: 16px 18px !important; /* 扩大卡片内边距，配合大字号 */
      }

      .card-header {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
      }

      .card-header-flex {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
        margin-bottom: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      /* 💡 核心放大：蓝色背景小标签，字号提升到 14px */
      .tag-label {
        background-color: #3b82f6;
        color: #ffffff;
        padding: 4px 8px; /* 适当增加了呼吸感 padding */
        font-size: 14px; /* 彻底看清文字 */
        font-weight: 700;
        border-radius: 4px;
        letter-spacing: 0.5px;
        white-space: nowrap;
      }

      /* 💡 核心放大：右侧自营{{$t('zhanBi')}}标签，字号提升到 13px */
      .ratio-label {
        font-size: 13px;
        font-weight: 600;
        color: #f59e0b;
        background-color: #fef3c7;
        padding: 2px 6px;
        border-radius: 4px;
      }

      /* 💡 核心放大：右侧系统奖励{{$t('zongRenShu')}}标签，字号提升到 13px */
      .info-label {
        font-size: 13px;
        font-weight: 600;
        color: #fb923c;
      }

      /* 数据值展示 */
      .card-value {
        font-size: 26px; /* 配合标签，数字也微微放大了 2px */
        font-weight: 700;
        color: #059669;
        padding: 6px 0 2px 0;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;

        &.count-type {
          color: #059669;
        }
      }
    }
  }
</style>
