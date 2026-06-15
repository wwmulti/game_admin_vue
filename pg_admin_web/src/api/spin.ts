import request from '@/utils/http'

export function getSpinStatisticList(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/index',
    params
  })
}

export function getRoomConfig(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/roomConfig',
    params
  })
}

export function apiPostSpin(data: any) {
  return request.post<any>({
    url: '/test/spin_statistic/apiPost',
    data
  })
}

export function onceMoreSpin(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/onceMore',
    params
  })
}

export function getImitateInit() {
  return request.get<any>({
    url: '/test/spin_statistic/imitateInit'
  })
}

export function getImitateList(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/imitate',
    params
  })
}

export function postImitateRun(data: any) {
  return request.post<any>({
    url: '/test/spin_statistic/imitatePost',
    data
  })
}

export function pollImitateStatus(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/pollStatus',
    params
  })
}

export function postCheckGames(data: any) {
  return request.post<any>({
    url: '/test/spin_statistic/checkGames',
    data
  })
}

export function getCheckGamesResult(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/getCheckGames',
    params
  })
}

export function getRunResult(params: any) {
  return request.get<any>({
    url: '/test/spin_statistic/getResult',
    params
  })
}
