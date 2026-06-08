import { ElMessage } from 'element-plus'
import { $t } from '@/locales'
import { refreshTable, getMultiTaskProcess } from '@/api/system'

/**
 * 切换状态
 * @param status
 */
export function toggleStatus(status: 0 | 1): number {
  return 1 - status
}

/**
 * 时间戳格式化
 * 输出格式：2025-10-10 00:00:00
 * @param timestamp 秒级时间戳
 */
export function formatTimestamp(timestamp: number): string {
  if (!timestamp) return '-'
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

/**
 * 时间戳格式化（仅日期）
 * 输出格式：2025-01-02
 * @param timestamp 秒级时间戳
 */
export function formatDate(timestamp: number): string {
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

/**
 * 纯数字日期格式化
 * 输出格式：2026-05-21
 * @param dateNum 八位纯数字或字符串（如：20260521）
 */
export function formatPureDate(dateNum: number | string): string {
  const dateStr = String(dateNum)
  if (dateStr.length !== 8) {
    return dateStr
  }
  const year = dateStr.slice(0, 4)
  const month = dateStr.slice(4, 6)
  const day = dateStr.slice(6, 8)
  return `${year}-${month}-${day}`
}

/**
 * 复制内容到剪切板
 * @param text 要复制的内容
 * @returns 是否复制成功
 */
export async function copy(text: string | number): Promise<boolean> {
  try {
    await navigator.clipboard.writeText(String(text))
    return true
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
  } catch (error) {
    try {
      const textarea = document.createElement('textarea')
      textarea.value = String(text)
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.focus()
      textarea.select()
      const success = document.execCommand('copy')
      document.body.removeChild(textarea)
      return success
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (err) {
      return false
    }
  }
}

/**
 * 根据 ID 字符串从对象数组中提取指定字段并拼接成逗号分隔的字符串
 * * @param idsStr       输入的 ID 字符串，支持 "1,2,3" 或 ",1,2,3," 等各种格式
 * @param idsStr
 * @param list         数据源二维数组（对象数组）
 * @param primaryKey   主键字段名（例如 'id'）
 * @param displayKey   需要展示并拼接的字段名（例如 'title'）
 * @returns            拼接后的字符串，例如 "title1,title2"
 */
export function formatFieldByKeys<T extends Record<string, any>>(
  idsStr: string | number | undefined | null,
  list: T[],
  primaryKey: keyof T,
  displayKey: keyof T
): string {
  if (!idsStr || !list || list.length === 0) return ''
  const targetIds = String(idsStr)
    .split(',')
    .map((id) => id.trim())
    .filter((id) => id !== '')

  if (targetIds.length === 0) return ''
  const idSet = new Set(targetIds)
  const matchedTitles = list
    .filter((item) => {
      const itemKey = String(item[primaryKey])
      return idSet.has(itemKey)
    })
    .map((item) => item[displayKey])

  // 5. 用逗号将结果拼接返回
  return matchedTitles.join(',')
}

// 同步配置
export async function refreshConfig() {
  const result = await refreshTable()
  if (result.code === 200) {
    const refreshResult = await getMultiTaskProcess({
      taskIds: result.data.taskId
    })
    if (refreshResult.code === 200) {
      ElMessage.success($t('tongBuChengGong'))
    } else {
      ElMessage.error(result.message)
    }
  } else {
    ElMessage.error(result.message)
  }
}
