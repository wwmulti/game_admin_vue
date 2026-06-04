import request from '@/utils/http'

export function alertList(params: any) {
  return request.get<any>({
    url: '/api/alert/alertList',
    params
  })
}

export function addAlert(data: any) {
  return request.post<any>({
    url: '/api/alert/addAlert',
    data
  })
}

export function editAlert(data: any) {
  return request.post<any>({
    url: '/api/alert/editAlert',
    data
  })
}

export function deleteAlert(data: any) {
  return request.post<any>({
    url: '/api/alert/deleteAlert',
    data
  })
}

export function changeAlertStatus(data: any) {
  return request.post<any>({
    url: '/api/alert/changeAlertStatus',
    data
  })
}

export function refreshPackageAlert(data: any) {
  return request.post<any>({
    url: '/api/alert/refreshPackageAlert',
    data
  })
}

export function bannerList(params: any) {
  return request.get<any>({
    url: '/api/banner/bannerList',
    params
  })
}

export function addBanner(data: any) {
  return request.post<any>({
    url: '/api/banner/addBanner',
    data
  })
}

export function editBanner(data: any) {
  return request.post<any>({
    url: '/api/banner/editBanner',
    data
  })
}

export function deleteBanner(data: any) {
  return request.post<any>({
    url: '/api/banner/deleteBanner',
    data
  })
}

export function changeBannerStatus(data: any) {
  return request.post<any>({
    url: '/api/banner/changeBannerStatus',
    data
  })
}

export function refreshPackageBanner(data: any) {
  return request.post<any>({
    url: '/api/banner/refreshPackageBanner',
    data
  })
}

export function broadcastList(params: any) {
  return request.get<any>({
    url: '/api/broadcast/broadcastList',
    params
  })
}

export function addBroadcast(data: any) {
  return request.post<any>({
    url: '/api/broadcast/addBroadcast',
    data
  })
}

export function editBroadcast(data: any) {
  return request.post<any>({
    url: '/api/broadcast/editBroadcast',
    data
  })
}

export function deleteBroadcast(data: any) {
  return request.post<any>({
    url: '/api/broadcast/deleteBroadcast',
    data
  })
}

export function changeBroadcastStatus(data: any) {
  return request.post<any>({
    url: '/api/broadcast/changeBroadcastStatus',
    data
  })
}

export function floatLogoList(params: any) {
  return request.get<any>({
    url: '/api/floatLogo/floatLogoList',
    params
  })
}

export function addFloatLogo(data: any) {
  return request.post<any>({
    url: '/api/floatLogo/addFloatLogo',
    data
  })
}

export function editFloatLogo(data: any) {
  return request.post<any>({
    url: '/api/floatLogo/editFloatLogo',
    data
  })
}

export function deleteFloatLogo(data: any) {
  return request.post<any>({
    url: '/api/floatLogo/deleteFloatLogo',
    data
  })
}

export function changeFloatLogoStatus(data: any) {
  return request.post<any>({
    url: '/api/floatLogo/changeFloatLogoStatus',
    data
  })
}

export function refreshPackageFloatLogo(data: any) {
  return request.post<any>({
    url: '/api/floatLogo/refreshPackageFloatLogo',
    data
  })
}
