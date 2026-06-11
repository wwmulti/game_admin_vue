import request from '@/utils/http'

export function getWingoRoomList(params: any) {
  return request.get<any>({
    url: '/room/win_room/index',
    params
  })
}

export function addWingoRoom(data: any) {
  return request.post<any>({
    url: '/room/win_room/add',
    data
  })
}

export function editWingoRoom(data: any, params: any) {
  return request.post<any>({
    url: '/room/win_room/edit',
    data,
    params
  })
}

export function deleteWingoRoom(data: any) {
  return request.post<any>({
    url: '/room/win_room/del',
    data
  })
}

export function updateWingoRoomAttr(data: any) {
  return request.post<any>({
    url: '/room/win_room/editAttr',
    data
  })
}
