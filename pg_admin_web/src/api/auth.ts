import request from '@/utils/http'

/**
 * 登录
 * @returns 登录响应
 * @param data
 */
export function login(data: any) {
  return request.post<any>({
    url: '/index/login',
    data
    // showSuccessMessage: true // 显示成功消息
    // showErrorMessage: false // 不显示错误消息
  })
}
