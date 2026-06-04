import request from '@/utils/http'

export function userBalanceLogList(params: any) {
  return request.get<any>({
    url: '/api/log/userBalanceLogList',
    params
  })
}

export function gameWaterLogList(params: any) {
  return request.get<any>({
    url: '/api/log/gameWaterLogList',
    params
  })
}

export function systemLog(params: any) {
  return request.get<any>({
    url: '/api/log/systemLog',
    params
  })
}
