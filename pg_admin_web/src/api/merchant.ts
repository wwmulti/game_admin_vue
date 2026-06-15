import request from '@/utils/http'

// 商户列表
export function getMerchantList(params: any) {
  return request.get<any>({
    url: '/merchant/merchant/index',
    params
  })
}

// 新增商户（后端通过 row/a 接收）
export function addMerchant(data: any) {
  return request.post<any>({
    url: '/merchant/merchant/add',
    data
  })
}

// 编辑商户（后端通过 row/a 接收，ids 路由参数）
export function editMerchant(data: any) {
  return request.post<any>({
    url: '/merchant/merchant/edit',
    data
  })
}

// 删除商户
export function deleteMerchant(data: any) {
  return request.post<any>({
    url: '/merchant/merchant/del',
    data
  })
}

// 配置刷新（修改商户后调用,通知游戏服务器）
export function configRefresh(data?: any) {
  return request.post<any>({
    url: '/room/room/apiPost',
    data
  })
}
