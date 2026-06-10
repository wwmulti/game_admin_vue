import request from '@/utils/http'

export function gameList(params: any) {
  return request.get<any>({
    url: '/api/game/gameList',
    params
  })
}

export function addGame(data: any) {
  return request.post<any>({
    url: '/api/game/addGame',
    data
  })
}

export function editGame(data: any) {
  return request.post<any>({
    url: '/api/game/editGame',
    data
  })
}

export function deleteGame(data: any) {
  return request.post<any>({
    url: '/api/game/deleteGame',
    data
  })
}

export function editGameAttr(data: any) {
  return request.post<any>({
    url: '/api/game/editGameAttr',
    data
  })
}

export function gameTypeList(params: any) {
  return request.get<any>({
    url: '/api/gameType/gameTypeList',
    params
  })
}

export function addGameType(data: any) {
  return request.post<any>({
    url: '/api/gameType/addGameType',
    data
  })
}

export function editGameType(data: any) {
  return request.post<any>({
    url: '/api/gameType/editGameType',
    data
  })
}

export function deleteGameType(data: any) {
  return request.post<any>({
    url: '/api/gameType/deleteGameType',
    data
  })
}

export function editGameTypeAttr(data: any) {
  return request.post<any>({
    url: '/api/gameType/editGameTypeAttr',
    data
  })
}

export function agentConfig(data: any) {
  return request.post<any>({
    url: '/api/gameType/agentConfig',
    data
  })
}

export function betRateConfig(data: any) {
  return request.post<any>({
    url: '/api/gameType/betRateConfig',
    data
  })
}

export function platformList(params: any) {
  return request.get<any>({
    url: '/api/platform/platformList',
    params
  })
}

export function addPlatform(data: any) {
  return request.post<any>({
    url: '/api/platform/addPlatform',
    data
  })
}

export function editPlatform(data: any) {
  return request.post<any>({
    url: '/api/platform/editPlatform',
    data
  })
}

export function deletePlatform(data: any) {
  return request.post<any>({
    url: '/api/platform/deletePlatform',
    data
  })
}

export function editPlatformAttr(data: any) {
  return request.post<any>({
    url: '/api/platform/editPlatformAttr',
    data
  })
}

export function gameTagList(params: any) {
  return request.get<any>({
    url: '/api/gameTag/gameTagList',
    params
  })
}

export function addGameTag(data: any) {
  return request.post<any>({
    url: '/api/gameTag/addGameTag',
    data
  })
}

export function editGameTag(data: any) {
  return request.post<any>({
    url: '/api/gameTag/editGameTag',
    data
  })
}

export function deleteGameTag(data: any) {
  return request.post<any>({
    url: '/api/gameTag/deleteGameTag',
    data
  })
}

export function merchantList(params: any) {
  return request.get<any>({
    url: '/api/game/merchantList',
    params
  })
}

export function addMerchant(data: any) {
  return request.post<any>({
    url: '/api/game/addMerchant',
    data
  })
}

export function editMerchant(data: any) {
  return request.post<any>({
    url: '/api/game/editMerchant',
    data
  })
}

export function deleteMerchant(data: any) {
  return request.post<any>({
    url: '/api/game/deleteMerchant',
    data
  })
}

export function pullMerchantGame(data: any) {
  return request.post<any>({
    url: '/api/game/pullMerchantGame',
    data
  })
}

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
    url: '/room/room/batch_updates',
    data
  })
}

export function refreshRoomConfig(data: any) {
  return request.post<any>({
    url: '/room/room/api_post',
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
