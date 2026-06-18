import request from '@/utils/http'

// 供应商统计（旧版 statistics/supplier）
export function getSupplierStats(params: any) {
  return request.get<any>({
    url: '/statistics/supplier/index',
    params
  })
}

// 游戏统计
export function getGameStats(params: any) {
  return request.get<any>({
    url: '/statistics/game/index',
    params
  })
}

// 用户统计
export function getUserStats(params: any) {
  return request.get<any>({
    url: '/statistics/user/index',
    params
  })
}