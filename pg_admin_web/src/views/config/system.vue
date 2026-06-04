<template>
  <div class="system-config-container">
    <ElTabs v-model="activeTab" class="config-tabs">
      <ElTabPane :label="$t('xiTongPeiZhi')" name="1" />
      <ElTabPane :label="$t('tanChuangPeiZhi')" name="7" />
      <ElTabPane :label="$t('ZhiFuTiXian')" name="5" />
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
        <ElButton type="primary" class="submit-btn" @click="handleSubmit">
          {{ $t('tiJiao') }}
        </ElButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { ref, watch, onMounted } from 'vue'
  import { ElMessage } from 'element-plus'
  import { allSystemConfig, editSystemConfig } from '@/api/config'

  const activeTab = ref('1')
  const loading = ref(false)
  const configTableData = ref<any[]>([])
  const packages = ref<any[]>([])

  let originalDataSnapshot = ''

  // 💡 placeholder 全部重置为 ''
  const titleMap: Record<string, { title: string; placeholder: string }> = {
    // ---- 系统配置 (type_id = 1) ----
    game_picture_domain: { title: $t('tuPianYuMing'), placeholder: '' },
    user_register_reward: { title: $t('zhuCeZengSongJinE'), placeholder: '' },
    max_register_number: { title: $t('zhuCeZuiDaCiShu'), placeholder: '' },
    enterprise_register_code: { title: $t('qiYeZhuCeMa'), placeholder: '' },
    default_platform_game_show_number: {
      title: $t('moRenPingTaiYouXiXianShiShu'),
      placeholder: ''
    },
    customer_link: { title: $t('keFuLianJie'), placeholder: '' },

    // ---- 弹窗参数配置 (type_id = 7) ----
    alert_download_url_first: { title: $t('TanChuangXiaZaiLianJieYi'), placeholder: '' },
    alert_download_url_second: { title: $t('TanChuangXiaZaiLianJieEr'), placeholder: '' },

    // ---- 支付提现配置 (type_id = 5) ----
    password_max_wrong_time: { title: $t('tiXianMiMaMeiRiZuoDaCuoWuCiShu'), placeholder: '' },
    max_bind_withdraw_account_num: {
      title: $t('zuiDaBangDingTiXianZhangHaoShuLiang'),
      placeholder: ''
    },
    allow_unbind_withdraw_account: {
      title: $t('shiFouYunXuJieBangTiXianZhangHao'),
      placeholder: ''
    }
  }

  const fetchConfigData = async (isFirst = false) => {
    loading.value = true
    try {
      const typeId = Number(activeTab.value)
      const needReload = isFirst ? 1 : 0

      const response = await allSystemConfig({
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
              const configMeta = titleMap[item.config_key]
              flatData.push({
                id: item.id,
                type_id: item.type_id,
                package_id: item.package_id,
                config_key: item.config_key,
                config_title: configMeta ? configMeta.title : item.config_key,
                placeholder: configMeta ? configMeta.placeholder : '',
                platform_name: packageMap[item.package_id] || item.package_id,
                config_value: item.config_value
              })
            })
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
      const response = await editSystemConfig(submitPayload)
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

  const objectSpanMethod = ({ rowIndex, columnIndex }: any) => {
    if (columnIndex === 0) {
      if (rowIndex % 3 === 0) {
        return { rowspan: 3, colspan: 1 }
      } else {
        return { rowspan: 0, colspan: 0 }
      }
    }
  }

  watch(activeTab, () => {
    fetchConfigData(false)
  })

  onMounted(() => {
    fetchConfigData(true)
  })
</script>

<style scoped lang="scss">
  .system-config-container {
    padding: 20px;
    background-color: #ffffff;
    min-height: calc(100vh - 40px);
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
