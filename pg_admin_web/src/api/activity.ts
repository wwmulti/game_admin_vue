import request from '@/utils/http'

export function activeList(params: any) {
  return request.get<any>({
    url: '/api/active/activeList',
    params
  })
}

export function addActive(data: any) {
  return request.post<any>({
    url: '/api/active/addActive',
    data
  })
}

export function editActive(data: any) {
  return request.post<any>({
    url: '/api/active/editActive',
    data
  })
}

export function deleteActive(data: any) {
  return request.post<any>({
    url: '/api/active/deleteActive',
    data
  })
}

export function editActiveAttr(data: any) {
  return request.post<any>({
    url: '/api/active/editActiveAttr',
    data
  })
}

// 邀请活动接口
export function activeInviteRule(params: any) {
  return request.get<any>({
    url: '/api/activeInvite/activeInviteRule',
    params
  })
}

export function editActiveInviteRule(data: any) {
  return request.post<any>({
    url: '/api/activeInvite/editActiveInviteRule',
    data
  })
}

export function allActiveInviteRuleRewardType() {
  return request.get<any>({
    url: '/api/activeInvite/allActiveInviteRuleRewardType'
  })
}

export function allActiveInviteRuleConditionType() {
  return request.get<any>({
    url: '/api/activeInvite/allActiveInviteRuleConditionType'
  })
}

export function allActiveInviteRuleExpireType() {
  return request.get<any>({
    url: '/api/activeInvite/allActiveInviteRuleExpireType'
  })
}

export function allActiveInviteRuleScopeType() {
  return request.get<any>({
    url: '/api/activeInvite/allActiveInviteRuleScopeType'
  })
}

export function allActiveInviteReward(params: any) {
  return request.get<any>({
    url: '/api/activeInvite/allActiveInviteReward',
    params
  })
}

export function addActiveInviteReward(data: any) {
  return request.post<any>({
    url: '/api/activeInvite/addActiveInviteReward',
    data
  })
}

export function editActiveInviteReward(data: any) {
  return request.post<any>({
    url: '/api/activeInvite/editActiveInviteReward',
    data
  })
}

export function deleteActiveInviteReward(data: any) {
  return request.post<any>({
    url: '/api/activeInvite/deleteActiveInviteReward',
    data
  })
}

export function changeActiveInviteRewardStatus(data: any) {
  return request.post<any>({
    url: '/api/activeInvite/changeActiveInviteRewardStatus',
    data
  })
}

// 签到活动接口
export function activeSignRule(params: any) {
  return request.get<any>({
    url: '/api/activeSign/activeSignRule',
    params
  })
}

export function editActiveSignRule(data: any) {
  return request.post<any>({
    url: '/api/activeSign/editActiveSignRule',
    data
  })
}

export function allActiveSignReward(params: any) {
  return request.get<any>({
    url: '/api/activeSign/allActiveSignReward',
    params
  })
}

export function allActiveSignRewardType() {
  return request.get<any>({
    url: '/api/activeSign/allActiveSignRewardType'
  })
}

export function addActiveSignReward(data: any) {
  return request.post<any>({
    url: '/api/activeSign/addActiveSignReward',
    data
  })
}

export function editActiveSignReward(data: any) {
  return request.post<any>({
    url: '/api/activeSign/editActiveSignReward',
    data
  })
}

export function deleteActiveSignReward(data: any) {
  return request.post<any>({
    url: '/api/activeSign/deleteActiveSignReward',
    data
  })
}

// 首充活动接口
export function activeFirstRechargeRule(params: any) {
  return request.get<any>({
    url: '/api/activeFirstRecharge/activeFirstRechargeRule',
    params
  })
}

export function allActiveFirstRechargeRuleBillingType() {
  return request.get<any>({
    url: '/api/activeFirstRecharge/allActiveFirstRechargeRuleBillingType'
  })
}

export function allActiveFirstRechargeRuleReceiveType() {
  return request.get<any>({
    url: '/api/activeFirstRecharge/allActiveFirstRechargeRuleReceiveType'
  })
}

export function allActiveFirstRechargeRuleTaskType() {
  return request.get<any>({
    url: '/api/activeFirstRecharge/allActiveFirstRechargeRuleTaskType'
  })
}

export function editActiveFirstRechargeRule(data: any) {
  return request.post<any>({
    url: '/api/activeFirstRecharge/editActiveFirstRechargeRule',
    data
  })
}

export function allActiveFirstRechargeReward(params: any) {
  return request.get<any>({
    url: '/api/activeFirstRecharge/allActiveFirstRechargeReward',
    params
  })
}

export function addActiveFirstRechargeReward(data: any) {
  return request.post<any>({
    url: '/api/activeFirstRecharge/addActiveFirstRechargeReward',
    data
  })
}

export function editActiveFirstRechargeReward(data: any) {
  return request.post<any>({
    url: '/api/activeFirstRecharge/editActiveFirstRechargeReward',
    data
  })
}

export function deleteActiveFirstRechargeReward(data: any) {
  return request.post<any>({
    url: '/api/activeFirstRecharge/deleteActiveFirstRechargeReward',
    data
  })
}

// 救济活动接口
export function activeReliefRule(params: any) {
  return request.get<any>({
    url: '/api/activeRelief/activeReliefRule',
    params
  })
}

export function allActiveReliefRuleCycleType() {
  return request.get<any>({
    url: '/api/activeRelief/allActiveReliefRuleCycleType'
  })
}

export function editActiveReliefRule(data: any) {
  return request.post<any>({
    url: '/api/activeRelief/editActiveReliefRule',
    data
  })
}

export function allActiveReliefReward(params: any) {
  return request.get<any>({
    url: '/api/activeRelief/allActiveReliefReward',
    params
  })
}

export function addActiveReliefReward(data: any) {
  return request.post<any>({
    url: '/api/activeRelief/addActiveReliefReward',
    data
  })
}

export function editActiveReliefReward(data: any) {
  return request.post<any>({
    url: '/api/activeRelief/editActiveReliefReward',
    data
  })
}

export function deleteActiveReliefReward(data: any) {
  return request.post<any>({
    url: '/api/activeRelief/deleteActiveReliefReward',
    data
  })
}

export function changeActiveReliefRewardStatus(data: any) {
  return request.post<any>({
    url: '/api/activeRelief/changeActiveReliefRewardStatus',
    data
  })
}

// 利息宝活动接口
export function activeInterestRule(params: any) {
  return request.get<any>({
    url: '/api/activeInterest/activeInterestRule',
    params
  })
}

export function allActiveInterestReceiveType() {
  return request.get<any>({
    url: '/api/activeInterest/allActiveInterestReceiveType'
  })
}

export function allActiveInterestInterestLimitType() {
  return request.get<any>({
    url: '/api/activeInterest/allActiveInterestInterestLimitType'
  })
}

export function editActiveInterestRule(data: any) {
  return request.post<any>({
    url: '/api/activeInterest/editActiveInterestRule',
    data
  })
}

// 转盘活动接口
export function allActiveLuckyWheelReward(params: any) {
  return request.get<any>({
    url: '/api/activeLuckyWheel/allActiveLuckyWheelReward',
    params
  })
}

export function allActiveLuckyWheelRewardType() {
  return request.get<any>({
    url: '/api/activeLuckyWheel/allActiveLuckyWheelRewardType'
  })
}

export function addActiveLuckyWheelReward(data: any) {
  return request.post<any>({
    url: '/api/activeLuckyWheel/addActiveLuckyWheelReward',
    data
  })
}

export function editActiveLuckyWheelReward(data: any) {
  return request.post<any>({
    url: '/api/activeLuckyWheel/editActiveLuckyWheelReward',
    data
  })
}

export function deleteActiveLuckyWheelReward(data: any) {
  return request.post<any>({
    url: '/api/activeLuckyWheel/deleteActiveLuckyWheelReward',
    data
  })
}

// Vip等级活动接口
export function allActiveVipRule(params: any) {
  return request.get<any>({
    url: '/api/activeVip/allActiveVipRule',
    params
  })
}

export function allActiveVipRuleConditionType() {
  return request.get<any>({
    url: '/api/activeVip/allActiveVipRuleConditionType'
  })
}

export function addActiveVipRule(data: any) {
  return request.post<any>({
    url: '/api/activeVip/addActiveVipRule',
    data
  })
}

export function editActiveVipRule(data: any) {
  return request.post<any>({
    url: '/api/activeVip/editActiveVipRule',
    data
  })
}

export function deleteActiveVipRule(data: any) {
  return request.post<any>({
    url: '/api/activeVip/deleteActiveVipRule',
    data
  })
}

export function changeActiveVipRuleStatus(data: any) {
  return request.post<any>({
    url: '/api/activeVip/changeActiveVipRuleStatus',
    data
  })
}

export function allActiveVipTask(params: any) {
  return request.get<any>({
    url: '/api/activeVip/allActiveVipTask',
    params
  })
}

export function allActiveVipTaskConditionType() {
  return request.get<any>({
    url: '/api/activeVip/allActiveVipTaskConditionType'
  })
}

export function allActiveVipTaskCycleType() {
  return request.get<any>({
    url: '/api/activeVip/allActiveVipTaskCycleType'
  })
}

export function addActiveVipTask(data: any) {
  return request.post<any>({
    url: '/api/activeVip/addActiveVipTask',
    data
  })
}

export function editActiveVipTask(data: any) {
  return request.post<any>({
    url: '/api/activeVip/editActiveVipTask',
    data
  })
}

export function deleteActiveVipTask(data: any) {
  return request.post<any>({
    url: '/api/activeVip/deleteActiveVipTask',
    data
  })
}

export function changeActiveVipTaskStatus(data: any) {
  return request.post<any>({
    url: '/api/activeVip/changeActiveVipTaskStatus',
    data
  })
}
