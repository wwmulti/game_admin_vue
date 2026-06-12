import request from '@/utils/http'

export function getSpinStatisticList(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/index',
    params
  })
}

export function getRoomConfig(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/room_config',
    params
  })
}

export function apiPostSpin(data: any) {
  return request.post<any>({
    url: '/test/spin_statistic/api_post',
    data
  })
}

export function onceMoreSpin(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/once_more',
    params
  })
}
