import request from '@/utils/http'

// 游戏列表（旧版 room/room）
export function getRoomList(params: any) {
  return request.get<any>({
    url: '/room/room/index',
    params
  })
}

export function addRoom(data: any) {
  return request.post<any>({
    url: '/room/room/add',
    data
  })
}

export function editRoom(data: any) {
  return request.post<any>({
    url: '/room/room/edit',
    data
  })
}

export function deleteRoom(data: any) {
  return request.post<any>({
    url: '/room/room/del',
    data
  })
}

export function editRoomAttr(data: any) {
  return request.post<any>({
    url: '/room/room/editAttr',
    data
  })
}

export function batchUpdateRoomCategory(data: any) {
  return request.post<any>({
    url: '/room/room/batchUpdates',
    data
  })
}

export function refreshRoomConfig(data: any) {
  return request.post<any>({
    url: '/room/room/apiPost',
    data
  })
}

// 游戏分类（旧版 room/type）
export function getRoomTypeList(params: any) {
  return request.get<any>({
    url: '/room/type/index',
    params
  })
}

export function addRoomType(data: any) {
  return request.post<any>({
    url: '/room/type/add',
    data
  })
}

export function editRoomType(data: any) {
  return request.post<any>({
    url: '/room/type/edit',
    data
  })
}

export function deleteRoomType(params: any) {
  return request.post<any>({
    url: '/room/type/del',
    params
  })
}

export function editRoomTypeAttr(data: any) {
  return request.post<any>({
    url: '/room/type/editAttr',
    data
  })
}

// 游戏类型下拉（旧版 room/room/type_id_name）
export function getTypeIdName() {
  return request.get<any>({
    url: '/room/room/type_id_name'
  })
}

// 游戏列表下拉（旧版 room/room/room_list）
export function getRoomSelect(params?: any) {
  return request.post<any>({
    url: '/room/room/room_list',
    data: params
  })
}
