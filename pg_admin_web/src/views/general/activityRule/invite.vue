<template>
  <div>
    <ElForm ref="formRef" label-width="200px">
      <ElFormItem :label="$t('tuiGuangYuMing')" required>
        <ElInput v-model="form.share_url" :placeholder="$t('tuiGuangYuMing')" />
      </ElFormItem>
      <ElFormItem :label="$t('saoMiaoXiaJiJianGe')" required>
        <ElInput v-model="form.scan_interval" :placeholder="$t('fenZhongShu')" />
      </ElFormItem>
      <ElFormItem :label="$t('tongJiFanWei')" required>
        <ElSelect v-model="form.scope">
          <ElOption
            v-for="item in ruleScopeTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('xiaJiZuiDiXiaoFei')">
        <ElInput
          type="number"
          v-model="form.mini_total_pays"
          :placeholder="$t('lingWeiWuXianZhi')"
        />
      </ElFormItem>
      <ElFormItem :label="$t('yaZhuXiaoFeiLiuShui')">
        <ElInput
          type="number"
          v-model="form.mini_total_water"
          :placeholder="$t('lingWeiWuXianZhi')"
        />
      </ElFormItem>
      <ElFormItem :label="$t('panDuanGuanXi')" required>
        <ElSelect v-model="form.condition">
          <ElOption
            v-for="item in ruleConditionTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('jiangLiLingQuFangShi')" required>
        <ElSelect v-model="form.reward_type">
          <ElOption
            v-for="item in ruleRewardTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('guoQiChuLiCeLue')" required>
        <ElSelect v-model="form.expire_type">
          <ElOption
            v-for="item in ruleExpireTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem>
        <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
      </ElFormItem>
    </ElForm>
  </div>
</template>
<script setup lang="ts">
  import { ref } from 'vue'
  import { $t } from '@/locales'
  import { ElMessage, ElLoading, ElButton } from 'element-plus'
  import {
    activeInviteRule,
    allActiveInviteRuleRewardType,
    allActiveInviteRuleExpireType,
    allActiveInviteRuleConditionType,
    allActiveInviteRuleScopeType,
    editActiveInviteRule
  } from '@/api/activity'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const form = reactive<any>({
    share_url: '',
    scan_interval: '',
    scope: '',
    condition: '',
    reward_type: '',
    expire_type: '',
    mini_total_pays: '',
    mini_total_water: ''
  })

  const ruleRewardTypes = ref()
  const ruleConditionTypes = ref()
  const ruleExpireTypes = ref()
  const ruleScopeTypes = ref()

  onMounted(async () => {
    await activeInviteRuleRewardType()
    await activeInviteRuleConditionType()
    await activeInviteRuleExpireType()
    await activeInviteRuleScopeType()
    await rule()
  })

  async function rule() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await activeInviteRule({
        active_id: props.activity_id
      })
      if (result.code === 200) {
        form.id = result.data.id
        form.share_url = result.data.share_url
        form.scan_interval = result.data.scan_interval
        form.scope = result.data.scope
        form.condition = result.data.condition
        form.reward_type = result.data.reward_type
        form.expire_type = result.data.expire_type
        form.mini_total_pays = result.data.mini_total_pays
        form.mini_total_water = result.data.mini_total_water
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function activeInviteRuleRewardType() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await allActiveInviteRuleRewardType()
      if (result.code === 200) {
        ruleRewardTypes.value = result.data
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function activeInviteRuleConditionType() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await allActiveInviteRuleConditionType()
      if (result.code === 200) {
        ruleConditionTypes.value = result.data
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function activeInviteRuleExpireType() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await allActiveInviteRuleExpireType()
      if (result.code === 200) {
        ruleExpireTypes.value = result.data
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function activeInviteRuleScopeType() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await allActiveInviteRuleScopeType()
      if (result.code === 200) {
        ruleScopeTypes.value = result.data
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }

  async function editRow() {
    const loadingInstance = ElLoading.service({})
    const params = {
      id: form.id,
      active_id: props.activity_id,
      share_url: form.share_url,
      scan_interval: form.scan_interval,
      scope: form.scope,
      condition: form.condition,
      reward_type: form.reward_type,
      expire_type: form.expire_type,
      mini_total_pays: form.mini_total_pays,
      mini_total_water: form.mini_total_water
    }
    try {
      const result = await editActiveInviteRule(params)
      if (result.code === 200) {
        ElMessage.success($t('chengGong'))
      } else {
        ElMessage.error(result.message)
      }
    } finally {
      loadingInstance.close()
    }
  }
</script>

<style scoped lang="scss"></style>
