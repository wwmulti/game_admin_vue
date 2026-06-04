<template>
  <div class="lucky-wheel-config-container">
    <ElTabs v-model="activeTab" class="config-tabs">
      <ElTabPane :label="$t('xingYunZhuanPan')" name="6" />
    </ElTabs>

    <div v-loading="loading" class="config-content">
      <ElTable
        :data="configTableData"
        :span-method="objectSpanMethod"
        border
        class="config-table"
        :header-cell-style="{ backgroundColor: '#f8fafc', color: '#334155', fontWeight: 'bold' }"
      >
        <ElTableColumn prop="config_title" :label="$t('peiZhiBiaoTi')" width="240" />
        <ElTableColumn prop="package_id" :label="$t('baoId')" width="120" align="center" />
        <ElTableColumn prop="platform_name" :label="$t('pingTai')" width="180" />
        <ElTableColumn :label="$t('peiZhiNeiRong')">
          <template #default="scope">
            <ElInput
              v-model="scope.row.config_value"
              :placeholder="scope.row.placeholder"
              clearable
            />
          </template>
        </ElTableColumn>
      </ElTable>

      <div class="form-actions">
        <ElButton type="primary" class="submit-btn" @click="handleSubmit"> {{ $t('tiJiao') }} </ElButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { ref, watch, onMounted } from 'vue'
  import { ElMessage } from 'element-plus'
  import { allLuckyWheelConfig, editLuckyWheelConfig } from '@/api/config'

  const props = defineProps({
    package_id: {
      type: Number,
      default: null
    }
  })

  const activeTab = ref('6')
  const loading = ref(false)
  const configTableData = ref<any[]>([])
  const packages = ref<any[]>([])

  let originalDataSnapshot = ''

  const titleMap: Record<string, { title: string; placeholder: string }> = {
    lucky_wheel_rate: { title: $t('jiFenZhuanHuanBiLi'), placeholder: '' },
    lucky_wheel_clear_time_type: { title: $t('jiFenQingLiShiJianLeiXing'), placeholder: '' },
    lucky_wheel_cost: { title: $t('jiFenXiaoHaoZhi'), placeholder: '' }
  }

  const fetchConfigData = async (isFirst = false) => {
    loading.value = true
    try {
      const typeId = Number(activeTab.value)
      const needReload = isFirst ? 1 : 0
      const response = await allLuckyWheelConfig({
        need_reload: needReload,
        type_id: typeId
      })
      if (response && response.code === 200 && response.data) {
        const serverData = response.data
        if (serverData.packages && serverData.packages.length > 0) {
          packages.value = serverData.packages
        }
        const packageMap: Record<number, string> = { 0: $t('moRenPeiZhi') }
        packages.value.forEach((pkg: any) => {
          packageMap[pkg.id] = pkg.title
        })
        const configsObj = serverData.configs || {}
        const flatData: any[] = []
        Object.keys(titleMap).forEach((key) => {
          if (configsObj[key]) {
            const list = configsObj[key]
            list.forEach((item: any) => {
              if (Number(item.package_id) !== Number(props.package_id)) {
                return
              }
              const configMeta = titleMap[item.config_key]
              flatData.push({
                id: item.id,
                type_id: item.type_id,
                package_id: item.package_id,
                config_key: item.config_key,
                config_title: configMeta ? configMeta.title : item.config_key,
                placeholder: configMeta ? configMeta.placeholder : '',
                platform_name: packageMap[item.package_id] || item.package_id,
                config_value: item.config_value,
                rowSpan: 0
              })
            })
          }
        })

        const spanMap: Record<string, number[]> = {}
        flatData.forEach((item, index) => {
          if (!spanMap[item.config_key]) {
            spanMap[item.config_key] = []
          }
          spanMap[item.config_key].push(index)
        })
        Object.keys(spanMap).forEach((key) => {
          const indexes = spanMap[key]
          if (indexes.length > 0) {
            flatData[indexes[0]].rowSpan = indexes.length
          }
        })

        configTableData.value = flatData
        originalDataSnapshot = JSON.stringify(flatData)
      } else {
        ElMessage.error(response?.msg)
      }
    } finally {
      loading.value = false
    }
  }

  const handleSubmit = async () => {
    if (configTableData.value.length === 0) {
      ElMessage.warning($t('wuShuJu'))
      return
    }

    const originalList = JSON.parse(originalDataSnapshot || '[]')
    const changedItems = configTableData.value.filter((item) => {
      const originItem = originalList.find((o: any) => o.id === item.id)
      return originItem && String(originItem.config_value) !== String(item.config_value)
    })

    if (changedItems.length === 0) {
      ElMessage.info($t('meiYouXiuGaiNeiRong'))
      return
    }

    const submitPayload = changedItems.map((item) => ({
      id: Number(item.id),
      type_id: Number(item.type_id),
      package_id: Number(item.package_id),
      config_key: item.config_key,
      config_value: String(item.config_value)
    }))

    loading.value = true
    try {
      const response = await editLuckyWheelConfig(submitPayload)
      if (response && response.code === 200) {
        ElMessage.success($t('chengGong'))
        await fetchConfigData(false)
      } else {
        ElMessage.error(response.msg)
      }
    } finally {
      loading.value = false
    }
  }

  const objectSpanMethod = ({ row, columnIndex }: any) => {
    if (columnIndex === 0) {
      if (row.rowSpan > 0) {
        return { rowspan: row.rowSpan, colspan: 1 }
      } else {
        return { rowspan: 0, colspan: 0 }
      }
    }
  }

  watch(
    () => props.package_id,
    () => {
      fetchConfigData(false)
    }
  )

  watch(activeTab, () => {
    fetchConfigData(false)
  })

  onMounted(() => {
    fetchConfigData(true)
  })
</script>

<style scoped lang="scss">
  .lucky-wheel-config-container {
    padding: 20px;
    background-color: #ffffff;
    border-radius: 6px;

    .config-tabs {
      :deep(.el-tabs__header) {
        margin-bottom: 20px;
        border-bottom: 1px solid #e2e8f0;
      }
      :deep(.el-tabs__item) {
        font-size: 14px;
        font-weight: 500;
        padding: 0 24px;
        &.is-active {
          font-weight: bold;
        }
      }
    }

    .config-content {
      margin-top: 10px;

      .config-table {
        width: 100%;
        border-radius: 4px;

        :deep(.el-table__row) {
          td {
            padding: 10px 0 !important;
            font-size: 14px;
            color: #334155;
          }
        }

        :deep(.el-input__wrapper) {
          box-shadow: 0 0 0 1px #dcdfe6 inset;
          &:hover {
            box-shadow: 0 0 0 1px #409eff inset;
          }
          &.is-focus {
            box-shadow: 0 0 0 1px #409eff inset !important;
          }
        }
      }

      .form-actions {
        margin-top: 25px;
        display: flex;
        justify-content: flex-end;

        .submit-btn {
          padding: 10px 28px;
          font-size: 14px;
          font-weight: bold;
        }
      }
    }
  }
</style>
