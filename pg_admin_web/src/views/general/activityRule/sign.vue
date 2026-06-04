<template>
  <div>
    <ElForm ref="formRef" label-width="200px">
      <ElFormItem :label="$t('qianDaoTianShu')" required>
        <ElInput v-model="form.days" :placeholder="$t('qianDaoTianShu')" />
      </ElFormItem>
      <ElFormItem :label="$t('shiFouXunHuan')">
        <ElRadioGroup v-model="form.is_loop">
          <ElRadio :value="1">{{ $t('shi') }}</ElRadio>
          <ElRadio :value="0">{{ $t('fou') }}</ElRadio>
        </ElRadioGroup>
      </ElFormItem>
      <ElFormItem :label="$t('duanQianShiFouChongZhi')">
        <ElRadioGroup v-model="form.is_interrupt_reset">
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
  import { activeSignRule, editActiveSignRule } from '@/api/activity'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const form = reactive<any>({
    days: null,
    is_loop: null,
    is_interrupt_reset: null
  })

  onMounted(async () => {
    await signRule()
  })

  async function signRule() {
    const loadingInstance = ElLoading.service({})
    try {
      const result = await activeSignRule({
        activity_id: props.activity_id
      })
      if (result.code === 200) {
        form.id = result.data.id
        form.days = result.data.days
        form.is_loop = result.data.is_loop
        form.is_interrupt_reset = result.data.is_interrupt_reset
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
      days: form.days,
      is_loop: form.is_loop,
      is_interrupt_reset: form.is_interrupt_reset
    }
    try {
      const result = await editActiveSignRule(params)
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
