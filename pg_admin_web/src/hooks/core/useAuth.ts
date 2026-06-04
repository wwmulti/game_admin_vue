
import { useRoute } from 'vue-router'
import type { AppRouteRecord } from '@/types/router'

type AuthItem = NonNullable<AppRouteRecord['meta']['authList']>[number]

export const useAuth = () => {
  const route = useRoute()

  // 从路由 meta 配置的权限列表（例如：[{ authMark: 'add' }]）
  const backendAuthList: AuthItem[] = Array.isArray(route.meta.authList)
    ? (route.meta.authList as AuthItem[])
    : []

  /**
   * 检查是否拥有某权限标识
   * @param auth 权限标识
   * @returns 是否有权限
   */
  const hasAuth = (auth: string): boolean => {
    return backendAuthList.some((item) => item?.authMark === auth)
  }

  return {
    hasAuth
  }
}