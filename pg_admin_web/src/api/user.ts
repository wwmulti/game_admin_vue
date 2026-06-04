import request from '@/utils/http'

export function userList(params: any) {
  return request.get<any>({
    url: '/api/user/userList',
    params
  })
}

export function addUser(data: any) {
  return request.post<any>({
    url: '/api/user/addUser',
    data
  })
}

export function editUser(data: any) {
  return request.post<any>({
    url: '/api/user/editUser',
    data
  })
}

export function deleteUser(data: any) {
  return request.post<any>({
    url: '/api/user/deleteUser',
    data
  })
}

export function configList(params: any) {
  return request.get<any>({
    url: '/api/user/configList',
    params
  })
}
export function editConfig(data: any) {
  return request.post<any>({
    url: '/api/user/editConfig',
    data
  })
}
