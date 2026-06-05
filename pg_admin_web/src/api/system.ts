import request from '@/utils/http'
import { AppRouteRecord } from '@/types/router'

// 获取菜单列表
export function menuList() {
  return request.get<any>({
    url: '/auth/system/menus'
  })
}

// 获取所有菜单列表
export function allMenuList() {
  return request.get<any>({
    url: '/auth/system/allAuthMenus'
  })
}

// 添加菜单规则
export function addAuthMenu(data: any) {
  return request.post<any>({
    url: '/auth/system/authadd',
    data
  })
}

// 编辑菜单规则
export function editAuthMenu(data: any) {
  return request.post<any>({
    url: '/auth/system/authedit',
    data
  })
}

// 删除菜单规则
export function deleteAuthMenu(data: any) {
  return request.post<any>({
    url: '/auth/system/authdel',
    data
  })
}

// 菜单状态修改
export function changeAuthMenuStatus(data: any) {
  return request.post<any>({
    url: '/auth/system/authstatus',
    data
  })
}

// 角色列表
export function getRoleList() {
  return request.get<any>({
    url: '/auth/system/roles'
  })
}

// 添加角色
export function addRoleItem(data: any) {
  return request.post<any>({
    url: '/auth/system/roleadd',
    data
  })
}

// 编辑角色
export function editRoleItem(data: any) {
  return request.post<any>({
    url: '/auth/system/roleedit',
    data
  })
}

// 删除角色
export function deleteRoleItem(params: any) {
  return request.get<any>({
    url: '/auth/system/roledel',
    params
  })
}

// 获取角色授权列表
export function getRoleRules(params: any) {
  return request.get<any>({
    url: '/auth/system/rolerules',
    params
  })
}

// 角色权限修改
export function updateRoleRules(data: any) {
  return request.post<any>({
    url: '/auth/system/rolerulesedit',
    data
  })
}

// 管理员列表
export function getAdminList(params: any) {
  return request.get<any>({
    url: '/auth/system/adminList',
    params
  })
}

// 管理员新增
export function addAdminItem(data: any) {
  return request.post<any>({
    url: '/auth/system/adminAdd',
    data
  })
}

// 管理员编辑
export function editAdminItem(data: any) {
  return request.post<any>({
    url: '/auth/system/adminEdit',
    data
  })
}

// 管理员删除（支持批量，ids 逗号分隔）
export function deleteAdminItem(data: any) {
  return request.post<any>({
    url: '/auth/system/adminDel',
    data
  })
}

// 管理员日志列表
export function getAdminLog(params: any) {
  return request.get<any>({
    url: '/auth/system/adminlog',
    params
  })
}

// 删除管理员日志
export function delAdminLog(data: any) {
  return request.post<any>({
    url: '/auth/system/delAdminLog',
    data
  })
}

// 获取个人信息
export const getProfile = () => {
  return request.get<any>({
    url: '/auth/system/profile'
  })
}

// 更新个人信息
export const updateProfile = (data: any) => {
  return request.post<any>({
    url: '/auth/system/updateProfile',
    data
  })
}

// 上传头像
export const uploadAvatar = (data: any) => {
  return request.post<any>({
    url: '/auth/system/uploadimg',
    data,
    headers: { 'Content-Type': 'multipart/form-data' }
  })
}

// 获取google秘钥
export const googleKey = (params: any) => {
  return request.get<any>({
    url: '/api/system/googleKey',
    params
  })
}

// 绑定google秘钥
export const bindGoogleKey = (data: any) => {
  return request.post<any>({
    url: '/api/system/bindGoogleKey',
    data
  })
}

// 解绑google秘钥
export const unbindGoogleKey = (data: any) => {
  return request.post<any>({
    url: '/api/system/unbindGoogleKey',
    data
  })
}

// 刷新表
export const refreshTable = () => {
  return request.get<any>({
    url: '/api/serves/refreshTable'
  })
}

export const getMultiTaskProcess = (params: any) => {
  return request.get<any>({
    url: '/api/serves/getMultiTaskProcess',
    params
  })
}
