import request from '@/utils/http'

export function packageList(params: any) {
  return request.get<any>({
    url: '/api/agent/packageList',
    params
  })
}

export function addPackage(data: any) {
  return request.post<any>({
    url: '/api/agent/addPackage',
    data
  })
}

export function editPackage(data: any) {
  return request.post<any>({
    url: '/api/agent/editPackage',
    data
  })
}

export function deletePackage(data: any) {
  return request.post<any>({
    url: '/api/agent/deletePackage',
    data
  })
}

export function configList(params: any) {
  return request.get<any>({
    url: '/api/agent/configList',
    params
  })
}
export function editConfig(data: any) {
  return request.post<any>({
    url: '/api/agent/editConfig',
    data
  })
}

export function userList(params: any) {
  return request.get<any>({
    url: '/api/agent/userList',
    params
  })
}

export function groupList(params: any) {
  return request.get<any>({
    url: '/api/agent/groupList',
    params
  })
}

export function addGroup(data: any) {
  return request.post<any>({
    url: '/api/agent/addGroup',
    data
  })
}

export function editGroup(data: any) {
  return request.post<any>({
    url: '/api/agent/editGroup',
    data
  })
}

export function deleteGroup(data: any) {
  return request.post<any>({
    url: '/api/agent/deleteGroup',
    data
  })
}