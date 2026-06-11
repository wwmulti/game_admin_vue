import request from '@/utils/http'

// ---- Wingo 房间配置 ----

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

// ---- WinGo 实时/期数管理 ----

export function getWingoRealtimeList(params: any) {
  return request.get<any>({
    url: '/room/win_realtime/index',
    params
  })
}

export function getWingoRooms() {
  return request.get<any>({
    url: '/room/win_realtime/wingo_room'
  })
}

export function getLatestRoomData(params: any) {
  return request.get<any>({
    url: '/room/win_realtime/LatestData',
    params
  })
}

export function getRoomDetails(params: any) {
  return request.get<any>({
    url: '/room/win_realtime/details',
    params
  })
}

export function submitWinningNumber(data: any) {
  return request.post<any>({
    url: '/room/win_realtime/winning_submit',
    data
  })
}

export function editWingoIssue(data: any) {
  return request.post<any>({
    url: '/room/win_realtime/edit',
    data
  })
}

export function getWingoBetDetail(params: { room_id: number; issue: string; page?: number; limit?: number }) {
  return request.get<any>({
    url: '/room/wingo_log/index',
    params
  })
}

export function getWingoOperationLog(params: any) {
  return request.get<any>({
    url: '/room/wingo_operation_log/index',
    params
  })
}

// ---- WinGo 统计 ----

export function getWingoStatisticsList(params: any) {
  return request.get<any>({
    url: '/room/wingo_statistics/index',
    params
  })
}
