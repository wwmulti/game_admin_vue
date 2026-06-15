<template>
  <div
    ref="artTableContainerRef"
    class="art-table"
    :class="{ 'is-empty': isEmpty }"
    :style="containerHeight"
  >
    <ElTable ref="elTableRef" v-loading="!!loading" v-bind="mergedTableProps">
      <template v-for="col in columns" :key="col.prop || col.type">
        <ElTableColumn v-if="col.type === 'globalIndex'" v-bind="{ ...col }">
          <template #default="{ $index }">
            <span>{{ getGlobalIndex($index) }}</span>
          </template>
        </ElTableColumn>

        <ElTableColumn v-else-if="col.type === 'expand'" v-bind="cleanColumnProps(col)">
          <template #default="{ row }">
            <component :is="col.formatter ? col.formatter(row) : null" />
          </template>
        </ElTableColumn>

        <ElTableColumn v-else v-bind="cleanColumnProps(col)">
          <template v-if="col.useHeaderSlot && col.prop" #header="headerScope">
            <slot
              :name="col.headerSlotName || `${col.prop}-header`"
              v-bind="{ ...headerScope, prop: col.prop, label: col.label }"
            >
              {{ col.label }}
            </slot>
          </template>
          <template v-if="col.useSlot && col.prop" #default="slotScope">
            <slot
              v-if="shouldRenderSlotScope(slotScope)"
              :name="col.slotName || col.prop"
              v-bind="{
                ...slotScope,
                prop: col.prop,
                value: col.prop ? slotScope.row[col.prop] : undefined
              }"
            />
          </template>
        </ElTableColumn>
      </template>

      <template v-if="$slots.default" #default><slot /></template>

      <template #empty>
        <div v-if="loading"></div>
        <ElEmpty v-else :description="emptyText" :image-size="120" />
      </template>
    </ElTable>

    <div
      class="pagination custom-pagination"
      v-if="showPagination"
      :class="mergedPaginationOptions?.align"
      ref="paginationRef"
    >
      <ElPagination
        v-bind="mergedPaginationOptions"
        :total="pagination?.total"
        :disabled="loading"
        :page-size="pagination?.size"
        :current-page="pagination?.current"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
  import {
    ref,
    computed,
    nextTick,
    watchEffect,
    getCurrentInstance,
    useAttrs,
    onUnmounted
  } from 'vue'
  import type { ElTable, TableProps } from 'element-plus'
  import { storeToRefs } from 'pinia'
  import { ColumnOption } from '@/types'
  import { useTableStore } from '@/store/modules/table'
  import { useCommon } from '@/hooks/core/useCommon'
  import { useTableHeight } from '@/hooks/core/useTableHeight'
  import { useResizeObserver, useWindowSize } from '@vueuse/core'

  defineOptions({ name: 'ArtTable' })

  const { width } = useWindowSize()
  const artTableContainerRef = ref<HTMLElement | null>(null) // 容器 DOM
  const elTableRef = ref<InstanceType<typeof ElTable> | null>(null)
  const paginationRef = ref<HTMLElement>()
  const tableHeaderRef = ref<HTMLElement>()
  const tableStore = useTableStore()
  const { isBorder, isZebra, tableSize, isFullScreen, isHeaderBackground } = storeToRefs(tableStore)

  /** 分页配置接口 */
  interface PaginationConfig {
    /** 当前页码 */
    current: number
    /** 每页显示条目个数 */
    size: number
    /** 总条目数 */
    total: number
  }

  /** 分页器配置选项接口 */
  interface PaginationOptions {
    /** 每页显示个数选择器的选项列表 */
    pageSizes?: number[]
    /** 分页器的对齐方式 */
    align?: 'left' | 'center' | 'right'
    /** 分页器的布局 */
    layout?: string
    /** 是否显示分页器背景 */
    background?: boolean
    /** 只有一页时是否隐藏分页器 */
    hideOnSinglePage?: boolean
    /** 分页器的大小 */
    size?: 'small' | 'default' | 'large'
    /** 分页器的页码数量 */
    pagerCount?: number
  }

  /** ArtTable 组件的 Props 接口 */
  interface ArtTableProps extends TableProps<Record<string, any>> {
    /** 加载状态 */
    loading?: boolean
    /** 列渲染配置 */
    columns?: ColumnOption[]
    /** 分页状态 */
    pagination?: PaginationConfig
    /** 分页配置 */
    paginationOptions?: PaginationOptions
    /** 空数据表格高度 */
    emptyHeight?: string
    /** 空数据时显示的文本 */
    emptyText?: string
    /** 是否开启 ArtTableHeader，解决表格高度自适应问题 */
    showTableHeader?: boolean
  }

  const props = withDefaults(defineProps<ArtTableProps>(), {
    columns: () => [],
    fit: true,
    showHeader: true,
    stripe: undefined,
    border: undefined,
    size: undefined,
    emptyHeight: '100%',
    emptyText: '暂无数据',
    showTableHeader: true
  })
  const instance = getCurrentInstance()
  const attrs = useAttrs()

  const LAYOUT = {
    MOBILE: 'prev, pager, next, sizes, jumper, total',
    IPAD: 'prev, pager, next, jumper, total',
    DESKTOP: 'total, prev, pager, next, sizes, jumper'
  }

  const layout = computed(() => {
    if (width.value < 768) {
      return LAYOUT.MOBILE
    } else if (width.value < 1024) {
      return LAYOUT.IPAD
    } else {
      return LAYOUT.DESKTOP
    }
  })

  // 默认分页常量
  const DEFAULT_PAGINATION_OPTIONS: PaginationOptions = {
    pageSizes: [10, 20, 30, 50, 100],
    align: 'center',
    background: true,
    layout: layout.value,
    hideOnSinglePage: false,
    size: 'default',
    pagerCount: width.value > 1200 ? 7 : 5
  }

  // 合并分页配置
  const mergedPaginationOptions = computed(() => ({
    ...DEFAULT_PAGINATION_OPTIONS,
    ...props.paginationOptions
  }))

  // 边框 (优先级：props > store)
  const border = computed(() => props.border ?? isBorder.value)
  // 斑马纹
  const stripe = computed(() => props.stripe ?? isZebra.value)
  // 表格尺寸
  const size = computed(() => props.size ?? tableSize.value)
  // 数据是否为空
  const isEmpty = computed(() => props.data?.length === 0)

  const paginationHeight = ref(0)
  const tableHeaderHeight = ref(0)

  // 使用 useResizeObserver 监听分页器高度变化
  useResizeObserver(paginationRef, (entries) => {
    const entry = entries[0]
    if (entry) {
      requestAnimationFrame(() => {
        paginationHeight.value = entry.contentRect.height
      })
    }
  })

  // 使用 useResizeObserver 监听表格头部高度变化
  useResizeObserver(tableHeaderRef, (entries) => {
    const entry = entries[0]
    if (entry) {
      requestAnimationFrame(() => {
        tableHeaderHeight.value = entry.contentRect.height
      })
    }
  })

  // 分页器与表格之间的间距常量（计算属性，响应 showTableHeader 变化）
  const PAGINATION_SPACING = computed(() => (props.showTableHeader ? 6 : 15))

  // 使用表格高度计算 Hook
  const { containerHeight } = useTableHeight({
    showTableHeader: computed(() => props.showTableHeader),
    paginationHeight,
    tableHeaderHeight,
    paginationSpacing: PAGINATION_SPACING
  })

  // 表格高度逻辑
  const height = computed(() => {
    if (isFullScreen.value) return '100%'
    if (isEmpty.value && !props.loading) return props.emptyHeight
    if (props.height) return props.height
    return '100%'
  })

  // 表头背景颜色样式
  const headerCellStyle = computed(() => ({
    background: isHeaderBackground.value
      ? 'var(--el-fill-color-lighter)'
      : 'var(--default-box-color)',
    ...(props.headerCellStyle || {})
  }))

  const hasExplicitTableProp = (propName: string): boolean => {
    const rawProps = (instance?.vnode.props || {}) as Record<string, unknown>
    const kebabName = propName.replace(/[A-Z]/g, (match) => `-${match.toLowerCase()}`)
    return propName in rawProps || kebabName in rawProps
  }

  const mergedTableProps = computed(() => ({
    ...attrs,
    ...props,
    height: height.value,
    stripe: stripe.value,
    border: border.value,
    size: size.value,
    headerCellStyle: headerCellStyle.value,
    selectOnIndeterminate: hasExplicitTableProp('selectOnIndeterminate')
      ? props.selectOnIndeterminate
      : undefined
  }))

  const showPagination = computed(() => props.pagination && !isEmpty.value)

  const shouldRenderSlotScope = (slotScope: { $index?: number }) => {
    return slotScope.$index === undefined || slotScope.$index >= 0
  }

  const cleanColumnProps = (col: ColumnOption) => {
    const columnProps = { ...col }
    delete columnProps.useHeaderSlot
    delete columnProps.headerSlotName
    delete columnProps.useSlot
    delete columnProps.slotName
    return columnProps
  }

  const handleSizeChange = (val: number) => {
    emit('pagination:size-change', val)
  }

  const handleCurrentChange = (val: number) => {
    emit('pagination:current-change', val)
    scrollToTop()
  }

  const { scrollToTop: scrollPageToTop } = useCommon()

  const scrollToTop = () => {
    nextTick(() => {
      elTableRef.value?.setScrollTop(0)
      scrollPageToTop()
    })
  }

  const getGlobalIndex = (index: number) => {
    if (!props.pagination) return index + 1
    const { current, size } = props.pagination
    return (current - 1) * size + index + 1
  }

  const emit = defineEmits<{
    (e: 'pagination:size-change', val: number): void
    (e: 'pagination:current-change', val: number): void
  }>()

  const findTableHeader = () => {
    if (!props.showTableHeader) {
      tableHeaderRef.value = undefined
      return
    }

    const tableHeader = document.getElementById('art-table-header')
    if (tableHeader) {
      tableHeaderRef.value = tableHeader
    } else {
      tableHeaderRef.value = undefined
    }
  }

  // 🌟 鼠标拖拽逻辑核心状态
  let isDown = false
  let startX: number
  let scrollLeft: number
  let targetBodyWrapper: HTMLElement | null = null
  let activeListenerEl: HTMLElement | null = null // 记录绑定的壳层节点

  const handleMouseDown = (e: MouseEvent) => {
    const target = e.target as HTMLElement
    // 阻断交互元素，避免影响按钮点击、开关切换、复选框勾选
    if (
      ['BUTTON', 'INPUT', 'SPAN', 'I', 'TEXTAREA'].includes(target.tagName) ||
      target.closest('.el-switch') ||
      target.closest('.el-checkbox') ||
      target.closest('.el-table__fixed') // 排除固定列区域
    ) {
      return
    }

    if (!targetBodyWrapper || !activeListenerEl) return
    isDown = true
    activeListenerEl.style.cursor = 'grabbing'

    // 统一以真正执行滚动的容器来做基准坐标和初初始滚动距离计算
    startX = e.pageX - targetBodyWrapper.offsetLeft
    scrollLeft = targetBodyWrapper.scrollLeft
  }

  const handleMouseUpOrLeave = () => {
    if (!isDown) return
    isDown = false
    if (activeListenerEl) {
      activeListenerEl.style.cursor = 'grab'
    }
  }

  const handleMouseMove = (e: MouseEvent) => {
    if (!isDown || !targetBodyWrapper) return
    e.preventDefault() // 强行阻止文字选中干扰拖拽
    const x = e.pageX - targetBodyWrapper.offsetLeft
    const walk = (x - startX) * 1.8 // 1.8 滚动敏捷系数
    targetBodyWrapper.scrollLeft = scrollLeft - walk
  }

  // 🌟 绑定和自动搜寻真实滚动源方法
  const initTableDragEffect = () => {
    if (!artTableContainerRef.value) return

    // 1. 定位到事件监听的可见视图壳子
    const bodyWrapper = artTableContainerRef.value.querySelector(
      '.el-table__body-wrapper'
    ) as HTMLElement
    if (!bodyWrapper) return

    // 2. 深度寻址：捕获新版 Element Plus 真正拥有隐藏或原生滚动条的底层节点
    const scrollbarWrap = bodyWrapper.querySelector('.el-scrollbar__wrap') as HTMLElement

    // 3. 确立最终的滚动实体 (如果没有 scrollbarWrap，说明是老版本，直接降级用 bodyWrapper 自身)
    targetBodyWrapper = scrollbarWrap || bodyWrapper
    activeListenerEl = bodyWrapper

    // 4. 设置默认可抓取手势
    activeListenerEl.style.cursor = 'grab'

    // 5. 每次重绑先安全移除，防止多次堆叠事件
    activeListenerEl.removeEventListener('mousedown', handleMouseDown)
    activeListenerEl.removeEventListener('mouseleave', handleMouseUpOrLeave)
    activeListenerEl.removeEventListener('mouseup', handleMouseUpOrLeave)
    activeListenerEl.removeEventListener('mousemove', handleMouseMove)

    // 6. 重新建立监听
    activeListenerEl.addEventListener('mousedown', handleMouseDown)
    activeListenerEl.addEventListener('mouseleave', handleMouseUpOrLeave)
    activeListenerEl.addEventListener('mouseup', handleMouseUpOrLeave)
    activeListenerEl.addEventListener('mousemove', handleMouseMove)
  }

  // 🌟 组件注销时清理，杜绝产生僵尸事件监听导致内存泄漏
  onUnmounted(() => {
    if (activeListenerEl) {
      activeListenerEl.removeEventListener('mousedown', handleMouseDown)
      activeListenerEl.removeEventListener('mouseleave', handleMouseUpOrLeave)
      activeListenerEl.removeEventListener('mouseup', handleMouseUpOrLeave)
      activeListenerEl.removeEventListener('mousemove', handleMouseMove)
    }
  })

  watchEffect(
    () => {
      void props.data?.length
      const shouldShow = props.showTableHeader

      if (shouldShow) {
        nextTick(() => {
          findTableHeader()
          initTableDragEffect() // DOM 更新完，执行滚动容器寻址并注入绑定
        })
      } else {
        tableHeaderRef.value = undefined
        nextTick(() => {
          initTableDragEffect()
        })
      }
    },
    { flush: 'post' }
  )

  defineExpose({
    scrollToTop,
    elTableRef
  })
</script>

<style lang="scss" scoped>
  @use './style';

  /* 🌟 全局禁止拖拽时单元格文字被误高亮选中 */
  :deep(.el-table__body-wrapper) {
    user-select: none;
  }
</style>
