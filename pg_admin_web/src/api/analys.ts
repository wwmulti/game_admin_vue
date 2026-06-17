import request from '@/utils/http'

// 游戏分析 - 列表 & 提交分析（stat=1 时创建任务）
export function getGameAnalysList(params: any) {
  return request.get<any>({
    url: '/analys/game/index',
    params
  })
}

// 游戏分析详情
export function getGameAnalysDetail(params: { id: number }) {
  return request.get<any>({
    url: '/analys/game/gameAnalysDetail',
    params
  })
}

// 检查游戏分析任务状态
export function checkGameAnalysStatus(params?: { type?: number }) {
  return request.get<any>({
    url: '/analys/game/checkStatus',
    params
  })
}

// 赢金分析 - 列表 & 提交分析
export function getWinAnalysList(params: any) {
  return request.get<any>({
    url: '/analys/win/index',
    params
  })
}

// 赢金分析详情
export function getWinAnalysDetail(params: { id: number }) {
  return request.get<any>({
    url: '/analys/win/winAnalysDetail',
    params
  })
}

// 检查赢金分析任务状态
export function checkWinAnalysStatus(params?: { type?: number }) {
  return request.get<any>({
    url: '/analys/win/checkStatus',
    params
  })
}
