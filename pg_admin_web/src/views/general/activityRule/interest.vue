<template>
  <div>
    <ElForm ref="formRef" label-width="200px">
      <ElFormItem :label="$t('zhouQiLiLv')">
        <ElInput type="number" v-model="form.interest_rate" :placeholder="$t('zhouQiLiLv')" />
      </ElFormItem>
      <ElFormItem :label="$t('qiCunJinE')">
        <ElInput type="number" v-model="form.deposit_amount" :placeholder="$t('qiCunJinE')" />
      </ElFormItem>
      <ElFormItem :label="$t('jieSuanZhouQi')">
        <ElInput type="number" v-model="form.interval" :placeholder="$t('danWeiXiaoShi')" />
      </ElFormItem>
      <ElFormItem :label="$t('lingQuShiJian')" required>
        <ElSelect v-model="form.receive_type">
          <ElOption
            v-for="item in receiveTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('liXiShangXianLeiXing')" required>
        <ElSelect v-model="form.interest_limit_type">
          <ElOption
            v-for="item in interestLimitTypes"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('liXiShangXianJinE')" v-if="form.interest_limit_type !== 3">
        <ElInput
          type="number"
          v-model="form.interest_limit_amount"
          :placeholder="$t('lingWeiWuXianZhi')"
        />
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
    activeInterestRule,
    allActiveInterestReceiveType,
    allActiveInterestInterestLimitType,
    editActiveInterestRule
  } from '@/api/activity'
  import { ref } from 'vue'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const receiveTypes = ref()
  const interestLimitTypes = ref()

  const form = reactive<any>({
    id: null,
    interest_rate: null,
    deposit_amount: null,
    interval: null,
    receive_type: null,
    interest_limit_type: null,
    interest_limit_amount: null
  })

  onMounted(async () => {
    await rule()
  })

  async function rule() {
    const loadingInstance = ElLoading.service({})
    try {
      const receiveTypeResult = await allActiveInterestReceiveType()
      if (receiveTypeResult.code === 200) {
        if (receiveTypeResult.data && receiveTypeResult.data.length > 0) {
          receiveTypes.value = receiveTypeResult.data
        }
      } else {
        ElMessage.error(receiveTypeResult.message)
      }
      const interestLimitTypeResult = await allActiveInterestInterestLimitType()
      if (interestLimitTypeResult.code === 200) {
        if (interestLimitTypeResult.data && interestLimitTypeResult.data.length > 0) {
          interestLimitTypes.value = interestLimitTypeResult.data
        }
      } else {
        ElMessage.error(interestLimitTypeResult.message)
      }
      const result = await activeInterestRule({
        activity_id: props.activity_id
      })
      if (result.code === 200) {
        form.id = result.data.id
        form.interest_rate = result.data.interest_rate
        form.deposit_amount = result.data.deposit_amount
        form.interval = result.data.interval
        form.receive_type = result.data.receive_type
        form.interest_limit_type = result.data.interest_limit_type
        form.interest_limit_amount = result.data.interest_limit_amount
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
      activity_id: props.activity_id,
      interest_rate: form.interest_rate,
      deposit_amount: form.deposit_amount,
      interval: form.interval,
      receive_type: form.receive_type,
      interest_limit_type: form.interest_limit_type,
      interest_limit_amount: form.interest_limit_amount
    }
    try {
      const result = await editActiveInterestRule(params)
      if (result.code === 200) {
        ;(window as any).refreshConfig()
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
