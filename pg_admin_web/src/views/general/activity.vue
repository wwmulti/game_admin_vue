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
          <ElSpace wrap>
            <ElButton @click="handleShowAddDialog">{{ $t('tianJia') }}</ElButton>
          </ElSpace>
        </template>
      </ArtTableHeader>
      <ArtTable
        border
        stripe
        :loading="loading"
        :data="data as any"
        :columns="columns"
        :pagination="pagination"
        @pagination:size-change="handleSizeChange"
        @pagination:current-change="handleCurrentChange"
      />
    </ElCard>

    <ElDialog
      v-model="showEditDialog"
      :title="isAdd ? $t('tianJia') : $t('bianJi')"
      width="1000"
      center
    >
      <ElForm ref="formRef" label-width="200px">
        <ElFormItem :label="$t('huoDongLeiXing')" required>
          <ElSelect v-model="form.active_type_id">
            <ElOption
              v-for="item in selectActivityTypes"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </ElSelect>
        </ElFormItem>
        <ElFormItem :label="$t('mingCheng')" required>
          <ElInput v-model="form.name" :placeholder="$t('mingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('puYuMingCheng')" required>
          <ElInput v-model="form.pt_name" :placeholder="$t('puYuMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('yingYuMingCheng')" required>
          <ElInput v-model="form.en_name" :placeholder="$t('yingYuMingCheng')" />
        </ElFormItem>
        <ElFormItem :label="$t('tuBiao')" required>
          <ElInput v-model="form.icon" :placeholder="$t('icon')" />
        </ElFormItem>
        <ElFormItem :label="$t('paiXu')">
          <ElInput type="number" v-model="form.sort" :placeholder="$t('paiXu')" />
        </ElFormItem>
        <ElFormItem :label="$t('zhuangTai')">
          <ElRadioGroup v-model="form.status">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>
        <ElFormItem :label="$t('pingTai')" v-if="isAdd">
          <ElCheckbox v-model="isAllPackagesSelected">
            {{ $t('quanPingTai') }}
          </ElCheckbox>
        </ElFormItem>
        <ElFormItem v-if="isAdd">
          <ElCheckboxGroup v-model="form.package_ids">
            <ElCheckbox v-for="item in selectPackages" :key="item.id" :value="Number(item.id)">
              {{ item.title }}
            </ElCheckbox>
          </ElCheckboxGroup>
        </ElFormItem>
      </ElForm>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showEditDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>

    <ElDialog v-model="showRuleDialog" :title="$t('guiZe')" width="1200" center>
      <InviteRule
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRuleDialog && form.active_type_id === 1"
      ></InviteRule>

      <SignRule
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRuleDialog && form.active_type_id === 2"
      ></SignRule>

      <FirstRechargeRule
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRuleDialog && form.active_type_id === 3"
      ></FirstRechargeRule>

      <ReliefRule
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRuleDialog && form.active_type_id === 4"
      ></ReliefRule>

      <InterestRule
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRuleDialog && form.active_type_id === 5"
      ></InterestRule>

      <LuckyWheelRule
        :package_id="form.package_id"
        :key="componentKey"
        v-if="showRuleDialog && form.active_type_id === 6"
      ></LuckyWheelRule>

      <VipRule
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRuleDialog && form.active_type_id === 7"
      ></VipRule>
    </ElDialog>

    <ElDialog v-model="showRewardDialog" :title="$t('jiangLi')" width="1200" center>
      <InviteReward
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRewardDialog && form.active_type_id === 1"
      ></InviteReward>

      <SignReward
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRewardDialog && form.active_type_id === 2"
      ></SignReward>

      <FirstRechargeReward
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRewardDialog && form.active_type_id === 3"
      ></FirstRechargeReward>

      <ReliefReward
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRewardDialog && form.active_type_id === 4"
      ></ReliefReward>

      <LuckyWheelReward
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRewardDialog && form.active_type_id === 6"
      ></LuckyWheelReward>

      <VipReward
        :activity_id="form.id"
        :key="componentKey"
        v-if="showRewardDialog && form.active_type_id === 7"
      ></VipReward>
    </ElDialog>

    <ElDialog v-model="showDeleteDialog" :title="$t('shanChu')" width="800" center>
      <div style="text-align: center">
        <span style="font-size: 16px">{{ $t('shanChu') }} - {{ form.name }} ? </span>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="showDeleteDialog = false">{{ $t('quXiao') }}</ElButton>
          <ElButton type="primary" @click="deleteRow">{{ $t('tiJiao') }}</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import { $t } from '@/locales'
  import { useTable } from '@/hooks'
  import { activeList, addActive, editActive, deleteActive, editActiveAttr } from '@/api/activity'
  import { ElMessage, ElButton, ElLoading, ElSwitch } from 'element-plus'
  import { computed, ref, reactive, h, toRaw } from 'vue'
  import InviteRule from '@/views/general/activityRule/invite.vue'
  import InviteReward from '@/views/general/activityReward/invite.vue'
  import SignRule from '@/views/general/activityRule/sign.vue'
  import SignReward from '@/views/general/activityReward/sign.vue'
  import FirstRechargeRule from '@/views/general/activityRule/firstRecharge.vue'
  import FirstRechargeReward from '@/views/general/activityReward/firstRecharge.vue'
  import ReliefRule from '@/views/general/activityRule/relief.vue'
  import ReliefReward from '@/views/general/activityReward/relief.vue'
  import InterestRule from '@/views/general/activityRule/interest.vue'
  import LuckyWheelRule from '@/views/general/activityRule/luckyWheel.vue'
  import LuckyWheelReward from '@/views/general/activityReward/luckyWheel.vue'
  import VipRule from '@/views/general/activityRule/vip.vue'
  import VipReward from '@/views/general/activityReward/vip.vue'

  const searchBarRef = ref()
  const searchForm = ref({
    package_id: null,
    status: null,
    activity_type: null
  })

  const form = reactive<any>({
    id: null,
    package_id: null,
    name: null,
    pt_name: null,
    en_name: null,
    icon: null,
    active_type_id: null,
    start_time: null,
    end_time: null,
    collec_time: null,
    package_ids: [],
    sort: null,
    status: 1
  })

  const switchLoading = ref<Record<string, boolean>>({})
  const showEditDialog = ref(false)
  const showDeleteDialog = ref(false)
  const showRuleDialog = ref(false)
  const showRewardDialog = ref(false)
  const isAdd = ref(false)
  const componentKey = ref(Date.now())

  const packages = ref()
  const packageMaps = ref()
  const selectPackages = ref()
  const activityTypes = ref()
  const selectActivityTypes = ref()
  const activityTypeMaps = ref()

  const isAllPackagesSelected = computed({
    get() {
      if (!selectPackages.value || selectPackages.value.length === 0) {
        return false
      }
      return form.package_ids?.length === selectPackages.value.length
    },
    set(val: boolean) {
      if (val) {
        form.package_ids = selectPackages.value.map((item: any) => Number(item.id))
      } else {
        form.package_ids = []
      }
    }
  })

  const formItems = computed(() => [
    {
      label: 'pingTai',
      key: 'package_id',
      type: 'select',
      placeholder: 'pingTai',
      options: packages.value
        ? packages.value.map((item: any) => ({
            label: item.title,
            value: item.id
          }))
        : []
    },
    {
      label: 'huoDongLeiXing',
      key: 'active_type_id',
      type: 'select',
      placeholder: 'huoDongLeiXing',
      options: activityTypes.value
        ? activityTypes.value.map((item: any) => ({
            label: item.name,
            value: item.id
          }))
        : []
    },
    {
      label: 'zhuangTai',
      key: 'status',
      type: 'select',
      props: {
        placeholder: 'xuanZe',
        options: [
          { label: $t('quanBu'), value: null },
          { label: $t('qiYong'), value: 1 },
          { label: $t('jinYong'), value: 0 }
        ]
      }
    }
  ])

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
    refreshData,
    refreshUpdate,
    searchParams
  } = useTable({
    core: {
      apiFn: activeList,
      apiParams: {
        page: 1,
        page_size: 10,
        need_reload: 1,
        package_id: null,
        status: null,
        activity_type: null
      },
      columnsFactory: () => [
        {
          label: 'ID',
          prop: 'id',
          width: 150
        },
        {
          label: 'pingTai',
          prop: 'package_id',
          width: 150,
          formatter: (row: any) => {
            return packageMaps.value?.[row.package_id] || row.package_id
          }
        },
        {
          label: 'mingCheng',
          prop: 'name',
          width: 200
        },
        {
          label: 'huoDongLeiXing',
          prop: 'active_type_id',
          width: 300,
          formatter: (row: any) => {
            return activityTypeMaps.value[row.active_type_id]
          }
        },
        {
          label: 'puYuMingCheng',
          prop: 'pt_name',
          width: 200
        },
        {
          label: 'yingYuMingCheng',
          prop: 'en_name',
          width: 200
        },
        {
          label: 'tuBiao',
          prop: 'icon',
          width: 150,
          formatter: (row: any) => {
            if (!row.icon) {
              return null
            }
            return h('img', {
              src: row.icon,
              style: {
                width: '80px',
                height: '80px'
              }
            })
          }
        },
        {
          label: 'paiXu',
          prop: 'sort',
          width: 100
        },
        {
          label: 'kaiGuan',
          prop: 'status',
          width: 100,
          formatter: (row: any) => {
            const isEnabled = row.status === 1
            const switchStatus = isEnabled ? 0 : 1
            return h(ElSwitch, {
              modelValue: isEnabled,
              loading: switchLoading.value[row.id] || false,
              'onUpdate:modelValue': () => handleStatusChange(row, switchStatus, 'status')
            })
          }
        },
        {
          label: 'kaiShiShiJian',
          prop: 'start_time',
          width: 180,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.start_time)
          }
        },
        {
          label: 'jieShuShiJian',
          prop: 'end_time',
          width: 180,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.end_time)
          }
        },
        {
          label: 'lingQuShiJian',
          prop: 'collec_time',
          width: 180,
          formatter: (row: any) => {
            return (window as any).formatTimestamp(row.collec_time)
          }
        },
        {
          prop: 'operate',
          label: 'caoZuo',
          width: 300,
          fixed: 'right',
          formatter: (row: any) =>
            h('div', [
              h(
                ElButton,
                {
                  type: 'primary',
                  size: 'small',
                  onClick: () => handleShowEditDialog(row)
                },
                () => $t('bianJi')
              ),
              h(
                ElButton,
                {
                  type: 'warning',
                  size: 'small',
                  onClick: () => handleShowRuleDialog(row)
                },
                () => $t('guiZe')
              ),
              h(
                ElButton,
                {
                  type: 'success',
                  size: 'small',
                  onClick: () => handleShowRewardDialog(row)
                },
                () => $t('jiangLi')
              ),
              h(
                ElButton,
                {
                  type: 'danger',
                  size: 'small',
                  onClick: () => handleShowDeleteDialog(row)
                },
                () => $t('shanChu')
              )
            ])
        }
      ]
    },
    transform: {
      responseAdapter: (response: any) => {
        return {
          records: response.list || [],
          total: response.total || 0,
          packages: response.packages || [],
          activityTypes: response.activity_type || []
        }
      }
    },
    hooks: {
      onSuccess: async (_data: any[], response: any) => {
        if (response.packages && response.packages.length > 0) {
          packages.value = response.packages
          packageMaps.value = packages.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.title
            return acc
          }, {})
          selectPackages.value = [...toRaw(packages.value)]
          packages.value.unshift({ id: null, title: $t('quanBu') })
        }
        if (response.activityTypes && response.activityTypes.length > 0) {
          activityTypes.value = response.activityTypes
          activityTypeMaps.value = activityTypes.value.reduce((acc: any, item: any) => {
            acc[item.id] = item.name
            return acc
          }, {})
          selectActivityTypes.value = [...toRaw(activityTypes.value)]
          activityTypes.value.unshift({ id: null, name: $t('quanBu') })
        }
        searchParams.need_reload = 0
      }
    }
  })

  function handleReset() {
    resetSearchParams()
  }

  async function handleSearch(params: any) {
    await searchBarRef.value.validate()
    replaceSearchParams(params)
    await getData()
  }

  function handleShowAddDialog() {
    isAdd.value = true
    form.id = null
    form.package_id = null
    form.name = null
    form.pt_name = null
    form.en_name = null
    form.icon = null
    form.active_type_id = null
    form.start_time = null
    form.end_time = null
    form.collec_time = null
    form.sort = null
    form.status = 1
    form.package_ids = []
    showEditDialog.value = true
  }

  function handleShowEditDialog(row: any) {
    isAdd.value = false
    form.id = row.id
    form.package_id = row.package_id
    form.name = row.name
    form.pt_name = row.pt_name
    form.en_name = row.en_name
    form.icon = row.icon
    form.active_type_id = row.active_type_id
    form.start_time = row.start_time ? new Date(row.start_time * 1000) : null
    form.end_time = row.end_time ? new Date(row.end_time * 1000) : null
    form.collec_time = row.collec_time ? new Date(row.collec_time * 1000) : null
    form.sort = row.sort
    form.status = row.status
    showEditDialog.value = true
  }

  function handleShowDeleteDialog(row: any) {
    form.id = row.id
    form.name = row.name
    showDeleteDialog.value = true
  }

  function handleShowRuleDialog(row: any) {
    form.id = row.id
    form.active_type_id = row.active_type_id
    form.package_id = row.package_id
    componentKey.value = Date.now()
    showRuleDialog.value = true
  }

  function handleShowRewardDialog(row: any) {
    form.id = row.id
    form.active_type_id = row.active_type_id
    form.package_id = row.package_id
    if (row.active_type_id === 5) {
      ElMessage.success($t('liXiBaoHuoDongJiangLiQingZaiGuiZeZhongPeiZhi'))
    } else {
      componentKey.value = Date.now()
      showRewardDialog.value = true
    }
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      package_id: form.package_id,
      name: form.name,
      pt_name: form.pt_name,
      en_name: form.en_name,
      icon: form.icon,
      active_type_id: form.active_type_id,
      start_time: form.start_time ? Math.floor(Number(form.start_time) / 1000) : null,
      end_time: form.end_time ? Math.floor(Number(form.end_time) / 1000) : null,
      collec_time: form.collec_time ? Math.floor(Number(form.collec_time) / 1000) : null,
      sort: form.sort,
      status: form.status,
      package_ids: form.package_ids?.length ? `${form.package_ids.join(',')}` : ''
    }
    try {
      if (form.id) {
        const result = await editActive(params)
        if (result.code === 200) {
          ;(window as any).refreshConfig()
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      } else {
        const result = await addActive(params)
        if (result.code === 200) {
          ;(window as any).refreshConfig()
          await getData()
          showEditDialog.value = false
          ElMessage.success($t('chengGong'))
        } else {
          ElMessage.error(result.message)
        }
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function deleteRow() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await deleteActive({
        id: form.id
      })
      if (result.code === 200) {
        ;(window as any).refreshConfig()
        await getData()
        showDeleteDialog.value = false
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  const handleStatusChange = async (row: any, value: number, field: string) => {
    const key = row.id
    switchLoading.value[key] = true
    try {
      const result = await editActiveAttr({
        id: row.id,
        value: value,
        field: field
      })
      if (result.code === 200) {
        ;(window as any).refreshConfig()
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
      await refreshUpdate()
    } finally {
      switchLoading.value[key] = false
    }
  }
</script>

<style scoped>
  .agent-config-row .header-col {
    font-weight: normal;
  }

  .dialog-footer {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
  }
</style>
