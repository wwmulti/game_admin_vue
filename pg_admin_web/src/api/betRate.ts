import request from '@/utils/http'

export function betRateList(params: any) {
  return request.get<any>({
    url: '/api/betRate/betRateList',
    params
  })
}

export function addBetRate(data: any) {
  return request.post<any>({
    url: '/api/betRate/addBetRate',
    data
  })
}

export function editBetRate(data: any) {
  return request.post<any>({
    url: '/api/betRate/editBetRate',
    data
  })
}
