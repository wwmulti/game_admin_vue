import request from '@/utils/http'

export function getRtpList(params: any) {
  return request.get<any>({
    url: '/rtp/index',
    params
  })
}

export function addRtp(data: any) {
  return request.post<any>({
    url: '/rtp/add',
    data
  })
}

export function editRtp(data: any) {
  return request.post<any>({
    url: '/rtp/edit',
    data
  })
}

export function delRtp(data: any) {
  return request.post<any>({
    url: '/rtp/del',
    data
  })
}
