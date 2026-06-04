<template>
  <div>
    <ElForm ref="formRef" label-width="200px">
      <ElFormItem :label="$t('tongJiFangShi')" required>
        <ElSelect v-model="form.billing_type">
          <ElOption
            v-for="item in billingTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('lingQuFangShi')" required>
        <ElSelect v-model="form.receive_type">
          <ElOption
            v-for="item in receiveTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('renWuLeiXing')" required>
        <ElSelect v-model="form.task_type">
          <ElOption v-for="item in taskTypes" :key="item.id" :label="item.name" :value="item.id" />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('touZhuCiShu')" v-if="form.task_type === 1">
        <ElInput type="number" v-model="form.bet_number" :placeholder="$t('touZhuCiShu')" />
      </ElFormItem>
      <ElFormItem :label="$t('touZhuJinE')" v-if="form.task_type === 2">
        <ElInput type="number" v-model="form.bet_amount" :placeholder="$t('touZhuJinE')" />
      </ElFormItem>
      <ElFormItem :label="$t('gengXinTongJiShiJian')">
        <ElInput type="number" v-model="form.update_frequency" :placeholder="$t('fenZhongShu')" />
      </ElFormItem>
      <ElFormItem :label="$t('shiFouChongFuCanYu')">
        <ElRadioGroup v-model="form.repeat_active">
          <ElRadio :value="1">{{ $t('shi') }}</ElRadio>
          <ElRadio :value="0">{{ $t('fou') }}</ElRadio>
        </ElRadioGroup>
      </ElFormItem>
      <ElFormItem>
        <ElButton type="primary" @click="editRow">{{ $t('tiJiao') }}</ElButton>
      </ElFormItem>
    </ElForm>
  </div>
</template>
<script setup lang="ts">
  import { $t } from '@/locales'
  import { ElMessage, ElLoading, ElButton } from 'element-plus'
  import {
    activeFirstRechargeRule,
    allActiveFirstRechargeRuleBillingType,
    allActiveFirstRechargeRuleReceiveType,
    allActiveFirstRechargeRuleTaskType,
    editActiveFirstRechargeRule
  } from '@/api/activity'
  import { ref } from 'vue'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const billingTypes = ref()
  const receiveTypes = ref()
  const taskTypes = ref()

  const form = reactive<any>({
    id: null,
    receive_type: null,
    task_type: null,
    billing_type: null,
    bet_number: null,
    bet_amount: null,
    update_frequency: null,
    repeat_active: 1
  })

  onMounted(async () => {
    await rule()
  })

  async function rule() {
    const loadingInstance = ElLoading.service({})
    try {
      const billingTypeResult = await allActiveFirstRechargeRuleBillingType()
      if (billingTypeResult.code === 200) {
        if (billingTypeResult.data && billingTypeResult.data.length > 0) {
          billingTypes.value = billingTypeResult.data
        }
      } else {
        ElMessage.error(billingTypeResult.message)
      }
      const receiveTypeResult = await allActiveFirstRechargeRuleReceiveType()
      if (receiveTypeResult.code === 200) {
        if (receiveTypeResult.data && receiveTypeResult.data.length > 0) {
          receiveTypes.value = receiveTypeResult.data
        }
      } else {
        ElMessage.error(receiveTypeResult.message)
      }
      const taskTypeResult = await allActiveFirstRechargeRuleTaskType()
      if (taskTypeResult.code === 200) {
        if (taskTypeResult.data && taskTypeResult.data.length > 0) {
          taskTypes.value = taskTypeResult.data
        } else {
          ElMessage.error(taskTypeResult.message)
        }
      }
      const result = await activeFirstRechargeRule({
        activity_id: props.activity_id
      })
      if (result.code === 200) {
        form.id = result.data.id
        form.receive_type = result.data.receive_type
        form.task_type = result.data.task_type
        form.billing_type = result.data.billing_type
        form.bet_number = result.data.bet_number
        form.bet_amount = result.data.bet_amount
        form.update_frequency = result.data.update_frequency
        form.repeat_active = result.data.repeat_active
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
      receive_type: form.receive_type,
      task_type: form.task_type,
      billing_type: form.billing_type,
      bet_number: form.bet_number,
      bet_amount: form.bet_amount,
      update_frequency: form.update_frequency,
      repeat_active: form.repeat_active
    }
    try {
      const result = await editActiveFirstRechargeRule(params)
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
