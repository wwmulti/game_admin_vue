import request from '@/utils/http'

export function teamList(params: any) {
  return request.get<any>({
    url: '/api/team/teamList',
    params
  })
}

export function addTeam(data: any) {
  return request.post<any>({
    url: '/api/team/addTeam',
    data
  })
}

export function editTeam(data: any) {
  return request.post<any>({
    url: '/api/team/editTeam',
    data
  })
}

export function deleteTeam(data: any) {
  return request.post<any>({
    url: '/api/team/deleteTeam',
    data
  })
}

export function salespersonList(params: any) {
  return request.get<any>({
    url: '/api/team/salespersonList',
    params
  })
}

export function addSalesperson(data: any) {
  return request.post<any>({
    url: '/api/team/addSalesperson',
    data
  })
}

export function editSalesperson(data: any) {
  return request.post<any>({
    url: '/api/team/editSalesperson',
    data
  })
}

export function deleteSalesperson(data: any) {
  return request.post<any>({
    url: '/api/team/deleteSalesperson',
    data
  })
}