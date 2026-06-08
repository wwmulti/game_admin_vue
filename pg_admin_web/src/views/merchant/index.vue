<template>
  <div>
    <ArtSearchBar
      ref="searchBarRef"
      v-model="searchForm"
      :items="formItems"
      @reset="handleReset"
      @search="handleSearch"
    />
    <ElCard class="art-table-card">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElButton @click="handleShowAddDialog">{{ $t('btn.add') }}</ElButton>
          <ElButton @click="handleBatchDelete">{{ $t('piLiangShanChu') }}</ElButton>
          <ElButton @click="handleConfigRefresh">{{ $t('peiZhiShuaXin') }}</ElButton>
          <span class="config-refresh-hint">{{ $t('peiZhiShuaXinTiShi') }}</span>
        </template>
      </ArtTableHeader>
      <ArtTable
        ref="artTableRef"
        :loading="loading"
        :data="data as any"
        :columns="columns"
        :pagination="pagination"
        @pagination:size-change="handleSizeChange"
        @pagination:current-change="handleCurrentChange"
      />
    </ElCard>

    <!-- 新增/编辑弹窗 -->
    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="800"
      top="5vh"
      center
    >
      <ElForm ref="formRef" :model="form" label-width="130px" @submit.prevent>
        <!-- 所属平台 - 仅超管可见（参考 add.html: {if $current_permission == 1}） -->
        <ElDivider content-position="left">{{ $t('suoShuPingTai') }}</ElDivider>
        <ElFormItem :label="$t('suoShuPingTai')" v-if="isSuperAdmin && isAdd" required>
          <ElSelect v-model="form.platform_id" :placeholder="$t('qingXuanZe')" filterable style="width:100%">
            <ElOption
              v-for="(value, key) in platformData"
              :key="key"
              :label="value"
              :value="Number(key)"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('niCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('niCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('yongHuMing')" required>
          <ElInput
            v-model="form.username"
            :placeholder="$t('yongHuMing')"
            :readonly="!isAdd && !isSuperAdmin"
            :disabled="!isAdd && !isSuperAdmin"
          />
        </ElFormItem>
        <ElFormItem :label="$t('miMa')" :required="isAdd">
          <ElInput
            v-model="form.password"
            type="password"
            show-password
            :placeholder="isAdd ? $t('miMa') : $t('liuKongWeiBuXiuGai')"
          />
        </ElFormItem>

        <!-- API 配置 -->
        <ElDivider content-position="left">API</ElDivider>
        <ElFormItem :label="$t('token')" required>
          <ElInput
            v-model="form.token"
            :placeholder="$t('token')"
            :readonly="!isSuperAdmin"
            :disabled="!isSuperAdmin"
          >
            <template #append>
              <ElButton @click="form.token = generateRandomString(32)">
                <ElIcon><Refresh /></ElIcon>
              </ElButton>
            </template>
          </ElInput>
        </ElFormItem>
        <ElFormItem :label="$t('secret')" required>
          <ElInput
            v-model="form.secret"
            :placeholder="$t('secret')"
            :readonly="!isSuperAdmin"
            :disabled="!isSuperAdmin"
          >
            <template #append>
              <ElButton @click="form.secret = generateRandomString(32)">
                <ElIcon><Refresh /></ElIcon>
              </ElButton>
            </template>
          </ElInput>
        </ElFormItem>
        <ElFormItem :label="$t('RTP')">
          <ElSelect v-model="form.rtp_id" :placeholder="$t('RTP')" filterable clearable style="width:100%">
            <ElOption
              v-for="r in rtpOptions"
              :key="r.id"
              :label="r.rtp"
              :value="r.id"
            />
          </ElSelect>
        </ElFormItem>

        <!-- 回调配置 -->
        <ElDivider content-position="left">{{ $t('shangHu') }}</ElDivider>
        <ElFormItem :label="$t('huoQuYuEHuiDiao')">
          <ElInput v-model="form.cash_get_url" :placeholder="$t('huoQuYuEHuiDiao')" />
        </ElFormItem>
        <ElFormItem :label="$t('xiuGaiYuEHuiDiao')">
          <ElInput v-model="form.cash_transferinout_url" :placeholder="$t('xiuGaiYuEHuiDiao')" />
        </ElFormItem>

        <!-- 显示配置 -->
        <ElDivider content-position="left">{{ $t('peiZhi') }}</ElDivider>
        <ElRow :gutter="20">
          <ElCol :span="12">
            <ElFormItem :label="$t('shiFouXianShiShiJian')">
              <ElRadioGroup v-model="form.style_name_switch">
                <ElRadio :value="1">{{ $t('xianShi') }}</ElRadio>
                <ElRadio :value="0">{{ $t('yinCang') }}</ElRadio>
              </ElRadioGroup>
            </ElFormItem>
          </ElCol>
          <ElCol :span="12">
            <ElFormItem :label="$t('shiFouXianShiTuiChuAnNiu')">
              <ElRadioGroup v-model="form.style_bb_switch">
                <ElRadio :value="1">{{ $t('xianShi') }}</ElRadio>
                <ElRadio :value="0">{{ $t('yinCang') }}</ElRadio>
              </ElRadioGroup>
            </ElFormItem>
          </ElCol>
        </ElRow>
        <ElRow :gutter="20">
          <ElCol :span="12">
            <ElFormItem :label="$t('shiFouXianShiJingSaiAnNiu')">
              <ElRadioGroup v-model="form.style_ec_switch">
                <ElRadio :value="1">{{ $t('xianShi') }}</ElRadio>
                <ElRadio :value="0">{{ $t('yinCang') }}</ElRadio>
              </ElRadioGroup>
            </ElFormItem>
          </ElCol>
          <ElCol :span="12">
            <ElFormItem :label="$t('shiFouXianShiKuiSunDuoShaoTingZhi')">
              <ElRadioGroup v-model="form.style_oj_switch">
                <ElRadio :value="1">{{ $t('xianShi') }}</ElRadio>
                <ElRadio :value="0">{{ $t('yinCang') }}</ElRadio>
              </ElRadioGroup>
            </ElFormItem>
          </ElCol>
        </ElRow>

        <!-- 状态 & 其他 -->
        <ElDivider content-position="left">{{ $t('qiTa') }}</ElDivider>
        <ElRow :gutter="20">
          <ElCol :span="12">
            <ElFormItem :label="$t('zhuangTai')">
              <ElRadioGroup v-model="form.status">
                <ElRadio :value="1">{{ $t('zhengChang') }}</ElRadio>
                <ElRadio :value="0">{{ $t('jinYong') }}</ElRadio>
              </ElRadioGroup>
            </ElFormItem>
          </ElCol>
          <ElCol :span="12">
            <ElFormItem :label="$t('mianFeiYouXiKaiGuan')">
              <ElRadioGroup v-model="form.free_spin_switch">
                <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
                <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
              </ElRadioGroup>
            </ElFormItem>
          </ElCol>
        </ElRow>
        <ElFormItem :label="$t('zuiDaZiDongCiShu')">
          <ElInput v-model.number="form.auto_max" type="number" :placeholder="$t('zuiDaZiDongCiShu')" />
        </ElFormItem>

        <!-- 编辑专属字段（仅超管可见，参考 edit.html: {if $AuthGroupAccessStatus == 1}） -->
        <template v-if="isSuperAdmin && !isAdd">
          <ElDivider content-position="left">{{ $t('beizhu') }}</ElDivider>
          <ElFormItem :label="$t('beizhu')">
            <ElInput v-model="form.mark" :placeholder="$t('beizhu')" />
          </ElFormItem>
          <ElFormItem :label="$t('ipBaiMingDan')">
            <ElInput
              v-model="form.ip_array"
              type="textarea"
              :rows="4"
              :placeholder="$t('ipBaiMingDan')"
            />
          </ElFormItem>
          <ElFormItem :label="$t('biBie')">
            <ElRadioGroup v-model="form.official_currency">
              <ElRadio value="BRL">{{ $t('baXiR$') }}</ElRadio>
              <ElRadio value="INR">{{ $t('yinDu₹') }}</ElRadio>
              <ElRadio value="THB">{{ $t('taiGuo฿') }}</ElRadio>
            </ElRadioGroup>
          </ElFormItem>
        </template>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" :loading="submitLoading" @click="submitRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <!-- 配置弹窗 -->
    <ElDialog v-model="showConfigDialog" :title="$t('peiZhi')" width="500" center>
      <ElForm ref="configFormRef" :model="configForm" label-width="140px" @submit.prevent>
        <ElFormItem :label="$t('zuiDaFanJiang')">
          <ElInput v-model.number="configForm.max_reward" type="number" :placeholder="$t('zuiDaFanJiang')" />
          <div style="color:#999;font-size:12px;margin-top:4px">1000 {{ $t('biaoShi') }} 1</div>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showConfigDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" :loading="configLoading" @click="submitConfig">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <!-- 删除确认弹窗 -->
    <ElDialog v-model="showDeleteDialog" :title="$t('shanChu')" width="500" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{ $t('queDingYaoShanChuPingTai', [deleteTargetName]) }}</span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="danger" @click="confirmDelete">{{ $t('shanChu') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks'
  import { h, ref, reactive, computed, watch, onMounted } from 'vue'
  import { $t } from '@/locales'
  import {
    ElButton,
    ElMessage,
    ElLoading,
    ElMessageBox,
    ElIcon,
    ElDivider,
    ElRow,
    ElCol,
    ElSelect,
    ElOption
  } from 'element-plus'
  import { Refresh } from '@element-plus/icons-vue'
  import { getMerchantList, addMerchant, editMerchant, deleteMerchant, configRefresh } from '@/api/merchant'
  import { editMerchantSetting } from '@/api/setting'

  defineOptions({ name: 'MerchantIndex' })

  const props = defineProps<{
    platform_id?: number | null
  }>()

  const searchBarRef = ref()
  const searchForm = ref<Record<string, any>>({
    id: '',
    name: '',
    status: '',
    platform_id: ''
  })

  const formItems = computed(() => [
     {
      label: '',
      key: 'id',
      type: 'input',
      props: { placeholder: 'ID', style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'name',
      type: 'input',
      props: { placeholder: $t('quDaoMingCheng'), style: { width: '140px' }, clearable: true }
    },
    {
      label: '',
      key: 'status',
      type: 'select',
      props: {
        placeholder: $t('zhuangTai'),
        style: { width: '120px' },
        clearable: true,
        options: [
          { label: $t('zhengChang'), value: 1 },
          { label: $t('jinYong'), value: 0 }
        ]
      }
    }
  ])

  // platform_id prop 变化时自动搜索
  watch(
    () => props.platform_id,
    (val) => {
      if (val !== undefined) {
        searchForm.value.platform_id = val
        handleSearch(searchForm.value)
      }
    }
  )

  // 弹窗
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const showConfigDialog = ref(false)
  const isAdd = ref(false)
  const submitLoading = ref(false)
  const configLoading = ref(false)
  const deleteTargetName = ref('')
  const deleteTargetId = ref<number | null>(null)
  const artTableRef = ref()

  // 权限 & 数据（从 index 接口返回的 current_permission / rtpList 中提取）
  const isSuperAdmin = ref(true)
  const rtpOptions = ref<{ id: number; rtp: string }[]>([])
  const platformData = ref<Record<number, string>>({})

  /** 生成随机字符串（参考 add.html 中的 generateRandomString） */
  function generateRandomString(length: number) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    let result = ''
    for (let i = 0; i < length; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return result
  }

  /** 初始化表单数据 */
  const initForm = () => ({
    id: null,
    platform_id: 0,
    name: '',
    username: '',
    password: '',
    token: generateRandomString(32),
    secret: generateRandomString(32),
    rtp_id: 0,
    cash_get_url: '',
    cash_transferinout_url: '',
    style_name_switch: 1,
    style_bb_switch: 1,
    style_ec_switch: 1,
    style_oj_switch: 1,
    status: 1,
    free_spin_switch: 1,
    auto_max: 0,
    // edit only
    mark: '',
    ip_array: '',
    official_currency: 'BRL'
  })

  const form = reactive<any>(initForm())

  // 配置弹窗表单
  const configForm = reactive<{ id: number | null; max_reward: number }>({
    id: null,
    max_reward: 0
  })

  /** 构建提交数据（公共字段） */
  function buildSubmitData() {
    return {
      name: form.name,
      username: form.username,
      password: form.password || undefined,
      token: form.token,
      secret: form.secret,
      platform_id: form.platform_id,
      rtp_id: form.rtp_id,
      cash_get_url: form.cash_get_url,
      cash_transferinout_url: form.cash_transferinout_url,
      style_name_switch: form.style_name_switch,
      style_bb_switch: form.style_bb_switch,
      style_ec_switch: form.style_ec_switch,
      style_oj_switch: form.style_oj_switch,
      status: form.status,
      free_spin_switch: form.free_spin_switch,
      auto_max: form.auto_max
    }
  }

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    handleSizeChange,
    handleCurrentChange,
    refreshData
  } = useTable({
    core: {
      apiFn: getMerchantList,
      apiParams: {
        page: 1,
        limit: 20
      },
      columnsFactory: () => [
        {
          type: 'selection',
          width: 50
        },
        {
          label: 'ID',
          prop: 'id',
          width: 70
        },
        {
          label: $t('yongHuMing'),
          prop: 'username',
          minWidth: 120
        },
        {
          label: $t('quDaoMingCheng'),
          prop: 'name',
          minWidth: 140
        },
        {
          label: 'token',
          prop: 'token',
          minWidth: 160
        },
        {
          label: $t('secret'),
          prop: 'secret',
          minWidth: 160
        },
        {
          label: $t('beizhu'),
          prop: 'mark',
          minWidth: 140
        },
        {
          prop: 'operate',
          label: $t('caoZuo'),
          width: 260,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowConfigDialog(row) },
                () => $t('peiZhi')
              ),
              h(
                ElButton,
                { type: 'primary', size: 'small', onClick: () => handleShowEditDialog(row) },
                () => $t('bianJi')
              ),
              h(
                ElButton,
                { type: 'danger', size: 'small', onClick: () => handleShowDeleteDialog(row) },
                () => $t('shanChu')
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        // 从 index 接口同步权限和 RTP 列表
        isSuperAdmin.value = response.current_permission === 1
        rtpOptions.value = response.rtpList || []
        platformData.value = response.platform_data || {}
        return {
          records: response.rows || [],
          total: response.total || 0
        }
      }
    }
  })

  // 初始化：如果 prop 传入了 platform_id，自动带参搜索
  onMounted(() => {
    if (props.platform_id !== undefined && props.platform_id !== null) {
      searchForm.value.platform_id = props.platform_id
      handleSearch(searchForm.value)
    }
  })

  async function handleSearch(params: any) {
    replaceSearchParams(params)
    await getData()
  }

  function handleReset() {
    resetSearchParams()
  }

  // 添加
  function handleShowAddDialog() {
    isAdd.value = true
    Object.assign(form, initForm())
    form.token = generateRandomString(32)
    form.secret = generateRandomString(32)
    // 默认选中第一个平台
    const keys = Object.keys(platformData.value)
    if (keys.length > 0) {
      form.platform_id = Number(keys[0])
    }
    // 默认选中第一个 RTP
    form.rtp_id = Number(rtpOptions.value[0].id)
    showEditDialog.value = true
  }

  // 编辑
  async function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.platform_id = row.platform_id ?? 0
    form.name = row.name || ''
    form.username = row.username || ''
    form.password = ''
    form.token = row.token || ''
    form.secret = row.secret || ''
    form.rtp_id = row.rtp_id ?? 0
    form.cash_get_url = row.cash_get_url || ''
    form.cash_transferinout_url = row.cash_transferinout_url || ''
    form.style_name_switch = row.style_name_switch ?? 0
    form.style_bb_switch = row.style_bb_switch ?? 0
    form.style_ec_switch = row.style_ec_switch ?? 0
    form.style_oj_switch = row.style_oj_switch ?? 0
    form.status = row.status ?? 1
    form.free_spin_switch = row.free_spin_switch ?? 0
    form.auto_max = row.auto_max ?? 0
    form.mark = row.mark || ''
    form.ip_array = row.ip_array || ''
    form.official_currency = row.official_currency || 'BRL'
    showEditDialog.value = true
  }

  async function submitRow() {
    submitLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const data = buildSubmitData()
      let result: any

      if (isAdd.value) {
        result = await addMerchant(data)
      } else {
        ;(data as any).id = form.id
        ;(data as any).mark = form.mark
        ;(data as any).ip_array = form.ip_array
        ;(data as any).official_currency = form.official_currency
        result = await editMerchant(data)
      }

      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showEditDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      submitLoading.value = false
    }
  }

  // 配置弹窗
  function handleShowConfigDialog(row: any) {
    configForm.id = row.id
    configForm.max_reward = row.setting?.max_reward ?? 0
    showConfigDialog.value = true
  }

  async function submitConfig() {
    configLoading.value = true
    const loadingInstance = ElLoading.service({})
    try {
      const result = await editMerchantSetting({
        id: configForm.id,
        max_reward: configForm.max_reward
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showConfigDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
      configLoading.value = false
    }
  }

  // 删除
  function handleShowDeleteDialog(row: any) {
    deleteTargetId.value = row.id
    deleteTargetName.value = row.name || row.username
    showDeleteDialog.value = true
  }

  async function confirmDelete() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deleteMerchant({
        ids: deleteTargetId.value
      })
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
        showDeleteDialog.value = false
        await getData()
      } else {
        ElMessage.error(result.msg || $t('shiBai'))
      }
    } finally {
      loadingInstance.close()
    }
  }

  // 配置刷新
  function handleConfigRefresh() {
    ElMessageBox.confirm(
      $t('queDingYaoShuaXinPeiZhi'),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    ).then(async () => {
      const loadingInstance = ElLoading.service({})
      try {
        const result = await configRefresh()
        if (result.code === 200) {
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.msg || $t('shiBai'))
        }
      } finally {
        loadingInstance.close()
      }
    }).catch(() => {})
  }

  // 批量删除
  function handleBatchDelete() {
    const tableRef = artTableRef.value?.elTableRef
    if (!tableRef) return
    const rows = tableRef.getSelectionRows()
    if (!rows.length) {
      ElMessage.warning($t('qingZhiShaoXuanZeYiXiang'))
      return
    }
    const ids = rows.map((r: any) => r.id)
    ElMessageBox.confirm(
      $t('queDingYaoShanChuXuanZhongPingTai'),
      $t('common.tips'),
      { confirmButtonText: $t('queDing'), cancelButtonText: $t('quXiao'), type: 'warning' }
    )
      .then(async () => {
        const loadingInstance = ElLoading.service({})
        try {
          const result = await deleteMerchant({
            ids: ids.join(',')
          })
          if (result.code === 200) {
            ElMessage.success($t('chengGong'))
            await getData()
          } else {
            ElMessage.error(result.msg || $t('shiBai'))
          }
        } finally {
          loadingInstance.close()
        }
      })
      .catch(() => {})
  }
</script>

<style scoped lang="scss">
.config-refresh-hint {
  color: #999;
  font-size: 12px;
  margin-left: 6px;
}
</style>
