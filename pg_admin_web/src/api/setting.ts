import request from '@/utils/http'

// 编辑商户配置（最大返奖等）
export function editMerchantSetting(data: any) {
  return request.post<any>({
    url: '/merchant/setting/edit',
    data
  })
}
