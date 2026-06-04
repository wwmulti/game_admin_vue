import request from '@/utils/http'

export function allSystemConfig(params: any) {
  return request.get<any>({
    url: '/api/config/allSystemConfig',
    params
  })
}

export function editSystemConfig(data: any) {
  return request.post<any>({
    url: '/api/config/editSystemConfig',
    data
  })
}

export function allPrizeConfig(params: any) {
  return request.get<any>({
    url: '/api/config/allPrizeConfig',
    params
  })
}

export function editPrizeConfig(data: any) {
  return request.post<any>({
    url: '/api/config/editPrizeConfig',
    data
  })
}

export function allLuckyWheelConfig(params: any) {
  return request.get<any>({
    url: '/api/config/allLuckyWheelConfig',
    params
  })
}

export function editLuckyWheelConfig(data: any) {
  return request.post<any>({
    url: '/api/config/editLuckyWheelConfig',
    data
  })
}

export function allAgentConfig(params: any) {
  return request.get<any>({
    url: '/api/config/allAgentConfig',
    params
  })
}

export function editAgentConfig(data: any) {
  return request.post<any>({
    url: '/api/config/editAgentConfig',
    data
  })
}