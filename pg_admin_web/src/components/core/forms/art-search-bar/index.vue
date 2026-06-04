<template>
  <section class="art-search-bar art-card-xs">
    <ElForm
      ref="formRef"
      :model="modelValue"
      :label-position="labelPosition"
      v-bind="{ ...$attrs }"
      class="search-form-container"
    >
      <div
        v-for="item in items.filter((i) => !i.hidden)"
        :key="item.key"
        class="adaptive-form-item"
        :class="[`item-type-${item.type}`, { 'is-checkbox-item': item.type === 'checkbox' }]"
      >
        <ElFormItem :prop="item.key" label-width="auto">
          <template #label v-if="item.label && item.type !== 'checkbox'">
            <component v-if="typeof item.label !== 'string'" :is="item.label" />
            <span v-else>{{ translateText(item.label) }}</span>
          </template>

          <slot :name="item.key" :item="item" :modelValue="modelValue">
            <component
              :is="getComponent(item)"
              :model-value="getFieldValue(item.key)"
              @update:model-value="setFieldValue(item.key, $event)"
              v-bind="getProps(item)"
              class="inner-component"
            >
              <template v-if="item.type === 'checkbox'">
                <component v-if="typeof item.label !== 'string'" :is="item.label" />
                <span v-else>{{ translateText(item.label) }}</span>
              </template>

              <template v-else-if="item.type === 'select' && getProps(item)?.options">
                <ElOption
                  v-for="option in getProps(item).options"
                  v-bind="option"
                  :key="option.value"
                />
              </template>

              <template v-else-if="item.type === 'checkboxgroup' && getProps(item)?.options">
                <ElCheckbox
                  v-for="option in getProps(item).options"
                  v-bind="option"
                  :key="option.value"
                />
              </template>

              <template v-else-if="item.type === 'radiogroup' && getProps(item)?.options">
                <ElRadio
                  v-for="option in getProps(item).options"
                  v-bind="option"
                  :key="option.value"
                />
              </template>

              <template v-for="(slotFn, slotName) in getSlots(item)" :key="slotName" #[slotName]>
                <component :is="slotFn" />
              </template>
            </component>
          </slot>
        </ElFormItem>
      </div>

      <div class="action-column">
        <div class="form-buttons">
          <ElButton v-if="showReset" class="reset-button" @click="handleReset" v-ripple>
            {{ t('table.searchBar.reset') }}
          </ElButton>
          <ElButton
            v-if="showSearch"
            type="primary"
            class="search-button"
            @click="handleSearch"
            v-ripple
            :disabled="disabledSearch"
          >
            {{ t('table.searchBar.search') }}
          </ElButton>
        </div>
      </div>
    </ElForm>
  </section>
</template>

<script setup lang="ts">
  import { useI18n } from 'vue-i18n'
  import { $t } from '@/locales'
  import { toRaw, type Component, computed, ref, toRefs } from 'vue'
  import dayjs from 'dayjs'
  import {
    ElCascader,
    ElCheckbox,
    ElCheckboxGroup,
    ElDatePicker,
    ElInput,
    ElInputTag,
    ElInputNumber,
    ElRadioGroup,
    ElRate,
    ElSelect,
    ElSlider,
    ElSwitch,
    ElTimePicker,
    ElTimeSelect,
    ElTreeSelect,
    type FormInstance
  } from 'element-plus'

  defineOptions({ name: 'ArtSearchBar' })

  const componentMap = {
    input: ElInput,
    inputTag: ElInputTag,
    number: ElInputNumber,
    select: ElSelect,
    switch: ElSwitch,
    checkbox: ElCheckbox,
    checkboxgroup: ElCheckboxGroup,
    radiogroup: ElRadioGroup,
    date: ElDatePicker,
    daterange: ElDatePicker,
    datetime: ElDatePicker,
    datetimerange: ElDatePicker,
    rate: ElRate,
    slider: ElSlider,
    cascader: ElCascader,
    timepicker: ElTimePicker,
    timeselect: ElTimeSelect,
    treeselect: ElTreeSelect
  }

  const { t } = useI18n()
  const formInstance = useTemplateRef<FormInstance>('formRef')

  // 预设的左侧快捷选项数据
  const dateShortcuts = [
    {
      text: $t('jinTian'),
      value: () => [dayjs().startOf('day').toDate(), dayjs().endOf('day').toDate()]
    },
    {
      text: $t('zuoTian'),
      value: () => [
        dayjs().subtract(1, 'day').startOf('day').toDate(),
        dayjs().subtract(1, 'day').endOf('day').toDate()
      ]
    },
    {
      text: $t('btn.benZhou'),
      value: () => [dayjs().startOf('week').toDate(), dayjs().endOf('week').toDate()]
    },
    {
      text: $t('jin_7Tian'),
      value: () => [
        dayjs().subtract(7, 'day').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('jin_14Tian'),
      value: () => [
        dayjs().subtract(14, 'day').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('jin_30Tian'),
      value: () => [
        dayjs().subtract(30, 'day').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('btn.benYue'),
      value: () => [dayjs().startOf('month').toDate(), dayjs().endOf('month').toDate()]
    },
    {
      text: $t('btn.shangYue'),
      value: () => [
        dayjs().subtract(1, 'month').startOf('month').toDate(),
        dayjs().subtract(1, 'month').endOf('month').toDate()
      ]
    },
    {
      text: $t('jinSanGeYue'),
      value: () => [
        dayjs().subtract(3, 'month').startOf('day').toDate(),
        dayjs().endOf('day').toDate()
      ]
    },
    {
      text: $t('qianSanGeYue'),
      value: () => [
        dayjs().subtract(3, 'month').startOf('month').toDate(),
        dayjs().subtract(1, 'month').endOf('month').toDate()
      ]
    }
  ]

  export interface SearchFormItem {
    key: string
    label: string | (() => VNode) | Component
    type?: keyof typeof componentMap | string
    render?: (() => VNode) | Component
    hidden?: boolean
    options?: Record<string, any>
    props?: Record<string, any>
    slots?: Record<string, (() => any) | undefined>
    placeholder?: string
  }

  interface SearchBarProps {
    items: SearchFormItem[]
    labelPosition?: 'left' | 'right' | 'top'
    showReset?: boolean
    showSearch?: boolean
    disabledSearch?: boolean
    sanitizeOutput?: Partial<SanitizeOutputOptions>
  }

  interface SanitizeOutputOptions {
    removeEmptyString: boolean
    removeEmptyArray: boolean
    removeEmptyObject: boolean
    removeEmptyRichText: boolean
    keepZero: boolean
    keepFalse: boolean
  }

  const props = withDefaults(defineProps<SearchBarProps>(), {
    items: () => [],
    labelPosition: 'right',
    showReset: true,
    showSearch: true,
    disabledSearch: false,
    sanitizeOutput: () => ({})
  })

  interface SearchBarEmits {
    reset: []
    search: [Record<string, any>]
  }

  const emit = defineEmits<SearchBarEmits>()

  const modelValue = defineModel<Record<string, any>>({ default: {} })
  const initialModelValue = ref<Record<string, any>>({})

  const cloneModelValue = (value: Record<string, any> | undefined) => {
    if (!value) return {}

    const deepClone = (source: unknown): unknown => {
      if (Array.isArray(source)) {
        return source.map((item) => deepClone(item))
      }

      if (source && typeof source === 'object') {
        const rawSource = toRaw(source)
        return Object.keys(rawSource).reduce<Record<string, unknown>>((accumulator, key) => {
          accumulator[key] = deepClone((rawSource as Record<string, unknown>)[key])
          return accumulator
        }, {})
      }

      return source
    }

    return deepClone(toRaw(value)) as Record<string, any>
  }

  initialModelValue.value = cloneModelValue(modelValue.value)

  const rootProps = ['label', 'key', 'type', 'hidden', 'slots']
  const sanitizeOutputOptions = computed<SanitizeOutputOptions>(() => ({
    removeEmptyString: true,
    removeEmptyArray: true,
    removeEmptyObject: true,
    removeEmptyRichText: true,
    keepZero: true,
    keepFalse: true,
    ...props.sanitizeOutput
  }))

  const translateText = (text: string): string => {
    if (!text || typeof text !== 'string') return text
    try {
      const translated = t(text)
      if (translated && translated !== text) {
        return translated
      }
    } catch (error) {}
    return text
  }

  const getProps = (item: SearchFormItem) => {
    let baseProps: Record<string, any> = {}

    if (item.props) {
      baseProps = { ...item.props }
    } else {
      baseProps = { ...item }
      rootProps.forEach((key) => delete baseProps[key])
    }

    if (baseProps.placeholder && typeof baseProps.placeholder === 'string') {
      baseProps.placeholder = translateText(baseProps.placeholder)
    }

    // 如果是范围选择日期组件，自动注入快捷选项
    if (
      (item.type === 'daterange' || item.type === 'datetimerange') &&
      (item.props?.type === 'daterange' || item.props?.type === 'datetimerange')
    ) {
      baseProps.shortcuts = baseProps.shortcuts || dateShortcuts
    }

    return baseProps
  }

  const getSlots = (item: SearchFormItem) => {
    if (!item.slots) return {}
    const validSlots: Record<string, () => any> = {}
    Object.entries(item.slots).forEach(([key, slotFn]) => {
      if (slotFn) {
        validSlots[key] = slotFn
      }
    })
    return validSlots
  }

  const normalizeFieldValue = (value: unknown) => {
    return value === '' ? undefined : value
  }

  const getFieldValue = (key: string) => modelValue.value[key]

  const setFieldValue = (key: string, value: unknown) => {
    const normalizedValue = normalizeFieldValue(value)

    if (normalizedValue === undefined) {
      delete modelValue.value[key]
      return
    }

    modelValue.value[key] = normalizedValue
  }

  const isRichTextEmpty = (value: string) => {
    if (/<(img|video|audio|iframe|embed|object)\b/i.test(value)) {
      return false
    }
    return (
      value
        .replace(/&nbsp;/gi, '')
        .replace(/<br\s*\/?>/gi, '')
        .replace(/<[^>]*>/g, '')
        .trim() === ''
    )
  }

  const sanitizeOutputValue = (value: unknown): unknown => {
    const options = sanitizeOutputOptions.value

    if (Array.isArray(value)) {
      const sanitizedArray = value
        .map((item) => sanitizeOutputValue(item))
        .filter((item) => item !== undefined)
      return sanitizedArray.length === 0 && options.removeEmptyArray ? undefined : sanitizedArray
    }

    if (value && typeof value === 'object') {
      const rawValue = toRaw(value)
      const sanitizedObject = Object.entries(rawValue).reduce<Record<string, unknown>>(
        (accumulator, [key, item]) => {
          const sanitizedItem = sanitizeOutputValue(item)
          if (sanitizedItem !== undefined) {
            accumulator[key] = sanitizedItem
          }
          return accumulator
        },
        {}
      )
      return Object.keys(sanitizedObject).length === 0 && options.removeEmptyObject
        ? undefined
        : sanitizedObject
    }

    if (typeof value === 'string') {
      if (options.removeEmptyString && value.trim() === '') {
        return undefined
      }
      if (options.removeEmptyRichText && isRichTextEmpty(value)) {
        return undefined
      }
      return value
    }

    if (value === 0) {
      return options.keepZero ? value : undefined
    }

    if (value === false) {
      return options.keepFalse ? value : undefined
    }

    return value ?? undefined
  }

  const getSanitizedOutput = () => {
    return (sanitizeOutputValue(cloneModelValue(modelValue.value)) || {}) as Record<string, any>
  }

  const getComponent = (item: SearchFormItem) => {
    if (item.render) {
      return item.render
    }
    const { type } = item
    return componentMap[type as keyof typeof componentMap] || componentMap['input']
  }

  const handleReset = () => {
    formInstance.value?.resetFields()
    Object.keys(modelValue.value).forEach((key) => {
      delete modelValue.value[key]
    })
    Object.assign(modelValue.value, cloneModelValue(initialModelValue.value))
    emit('reset')
  }

  const handleSearch = () => {
    emit('search', getSanitizedOutput())
  }

  defineExpose({
    ref: formInstance,
    validate: (...args: any[]) => formInstance.value?.validate(...args),
    reset: handleReset,
    getOutput: getSanitizedOutput
  })

  const { labelPosition } = toRefs(props)
</script>

<style lang="scss" scoped>
  .art-search-bar {
    padding: 15px 20px 15px;

    .search-form-container {
      display: flex;
      flex-flow: row wrap;
      align-items: center; // 改为居中，解决 checkbox/按钮与普通输入框高度不一致导致的对齐瑕疵
      gap: 16px 24px;
    }

    .adaptive-form-item {
      flex: 0 0 auto;
      display: flex;
      align-items: center;

      .el-form-item {
        margin-bottom: 0 !important;
        margin-right: 0 !important;
        display: flex;
        align-items: center;
      }

      :deep(.el-form-item__label) {
        width: auto !important;
        white-space: nowrap !important;
        padding-right: 8px;
        height: 32px;
        line-height: 32px;
      }

      :deep(.el-form-item__content) {
        line-height: 32px;
        display: flex;
        align-items: center;
      }

      .inner-component {
        width: 200px;
      }

      &.item-type-daterange,
      &.item-type-datetimerange {
        .inner-component {
          width: 320px;
        }
      }

      &.is-checkbox-item {
        height: 32px;

        :deep(.el-form-item__content) {
          width: max-content !important;
          flex: none !important;
        }

        .inner-component {
          width: auto;
          height: auto;

          :deep(.el-checkbox__label) {
            color: var(--el-text-color-regular);
            transition: color 0.15s ease;
          }

          &.is-checked {
            :deep(.el-checkbox__label) {
              color: var(--el-color-primary) !important;
            }
          }
        }
      }
    }

    .action-column {
      display: flex;
      align-items: center;
      flex-shrink: 0;
      height: 32px;

      .form-buttons {
        display: flex;
        gap: 8px;
      }
    }
  }

  @media (width <= 768px) {
    .art-search-bar {
      padding: 16px;

      .search-form-container {
        flex-direction: column;
        align-items: stretch;
        gap: 16px 0;
      }

      .adaptive-form-item {
        flex: 1 1 auto;

        .el-form-item {
          width: 100%;
        }

        .inner-component {
          width: 100% !important;
        }

        &.is-checkbox-item {
          .inner-component {
            width: auto !important;
          }
        }
      }

      .action-column {
        justify-content: center;
        width: 100%;
        margin-top: 8px;

        .form-buttons {
          flex: 1;
          justify-content: center;

          .reset-button,
          .search-button {
            flex: 1;
          }
        }
      }
    }
  }
</style>
