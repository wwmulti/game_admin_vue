import request from '@/utils/http'

// 用户列表
export function getUserList(params: any) {
  return request.get<any>({
    url: '/user/user/index',
    params
  })
}

// 更新用户余额
export function updateBalance(user_id: number) {
  return request.post<any>({
    url: '/user/user/updateBalance',
    params: { user_id }
  })
}

// 游戏记录详情列表
export function getUserGameLog(params: any) {
  return request.get<any>({
    url: '/user/details/index',
    params
  })
}
