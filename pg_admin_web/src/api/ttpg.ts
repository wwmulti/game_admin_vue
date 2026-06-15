import request from '@/utils/http'

export function getTtpgList(params: any) {
  return request.get<any>({
    url: '/ttpg/ttpg/index',
    params
  })
}

// === Detail 接口 ===

export function getTtpgDetailList(params: any) {
  return request.get<any>({
    url: '/ttpg/details/index',
    params
  })
}

export function updateTtpgDetailReward(data: any) {
  return request.post<any>({
    url: '/ttpg/details/editUpdate',
    data
  })
}

export function addTtpgDetail(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/details/add',
    data,
    params
  })
}

export function editTtpgDetail(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/details/edit',
    data,
    params
  })
}

export function batchUpdateTtpgDetail(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/details/batchUpdate',
    data,
    params
  })
}

export function batchUpdateTtpgMerchant(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/details/batchMerchant',
    data,
    params
  })
}

export function batchConfigTtpg(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/details/batchConfig',
    data,
    params
  })
}

// === Template 接口 ===

export function getTemplateList(params: any) {
  return request.get<any>({
    url: '/ttpg/template/index',
    params
  })
}

export function addTemplate(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/template/add',
    data,
    params
  })
}

export function editTemplate(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/template/edit',
    data,
    params
  })
}

export function deleteTemplate(params: any) {
  return request.post<any>({
    url: '/ttpg/template/del',
    params
  })
}

// === RTP 接口 ===

export function getRtpList(params: any) {
  return request.get<any>({
    url: '/ttpg/rtp/index',
    params
  })
}

// === RTP Detail 接口 ===

export function getRtpDetailList(params: any) {
  return request.get<any>({
    url: '/ttpg/rtp_details/index',
    params
  })
}

export function updateRtpDetailReward(data: any) {
  return request.post<any>({
    url: '/ttpg/rtp_details/editUpdate',
    data
  })
}

export function addRtpDetail(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/rtp_details/add',
    data,
    params
  })
}

export function editRtpDetail(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/rtp_details/edit',
    data,
    params
  })
}

export function batchUpdateRtpDetail(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/rtp_details/batchUpdate',
    data,
    params
  })
}

export function batchConfigRtp(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/rtp_details/batchConfig',
    data,
    params
  })
}

// === RTP Template 接口 ===

export function getRtpTemplateList(params: any) {
  return request.get<any>({
    url: '/ttpg/rtp_template/index',
    params
  })
}

export function addRtpTemplate(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/rtp_template/add',
    data,
    params
  })
}

export function editRtpTemplate(data: any, params: any) {
  return request.post<any>({
    url: '/ttpg/rtp_template/edit',
    data,
    params
  })
}

export function deleteRtpTemplate(params: any) {
  return request.post<any>({
    url: '/ttpg/rtp_template/del',
    params
  })
}

// === TTPG Statistics 接口 ===

export function getTtpgStatistics(params: any) {
  return request.get<any>({
    url: '/ttpg/ttpg_statistics/index',
    params
  })
}
