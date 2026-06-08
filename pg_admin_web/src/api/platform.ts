import request from '@/utils/http'

// 平台列表
export function getPlatformList(params: any) {
  return request.get<any>({
    url: '/merchant/platform/index',
    params
  })
}

// 新增平台（后端通过 row/a 接收）
export function addPlatform(data: any) {
  return request.post<any>({
    url: '/merchant/platform/add',
    data
  })
}

// 编辑平台
export function editPlatform(data: any) {
  return request.post<any>({
    url: '/merchant/platform/edit',
    data
  })
}

// 删除平台
export function deletePlatform(data: any) {
  return request.post<any>({
    url: '/merchant/platform/del',
    data
  })
}
