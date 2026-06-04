import request from '@/utils/http'

// 获取权限菜单
export function dayStatistics(params: any) {
  return request.get<any>({
    url: '/api/dailyStatistics/dayList',
    params
  })
}

export function totalDaily(params: any) {
  return request.get<any>({
    url: '/api/dailyStatistics/totalDaily',
    params
  })
}

export function totalMonth(params: any) {
  return request.get<any>({
    url: '/api/dailyStatistics/totalMonth',
    params
  })
}