<template>
  <div>
    <ElForm ref="formRef" label-width="200px">
      <ElFormItem :label="$t('tongJiZhouQi')" required>
        <ElSelect v-model="form.cycle">
          <ElOption v-for="item in cycleTypes" :key="item.id" :label="item.name" :value="item.id" />
        </ElSelect>
      </ElFormItem>
      <ElFormItem :label="$t('kaiFangLingQuRiQi')">
        <ElInput type="number" v-model="form.open_day" :placeholder="$t('kaiFangLingQuRiQi')" />
      </ElFormItem>
      <ElFormItem :label="$t('kaiFangLingQuShiJianDian')">
        <ElTimePicker
          v-model="form.open_time"
          :placeholder="$t('kaiFangLingQuShiJianDian')"
          format="HH:mm:ss"
          value-format="HH:mm:ss"
        />
      </ElFormItem>
      <ElFormItem :label="$t('shiFouChongFu')">
        <ElRadioGroup v-model="form.is_repeat">
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
    activeReliefRule,
    allActiveReliefRuleCycleType,
    editActiveReliefRule
  } from '@/api/activity'
  import { ref } from 'vue'

  const props = defineProps({
    activity_id: {
      type: Number,
      default: null
    }
  })

  const cycleTypes = ref()

  const form = reactive<any>({
    id: null,
    cycle: null,
    open_day: null,
    open_time: null,
    is_repeat: null
  })

  onMounted(async () => {
    await rule()
  })

  async function rule() {
    const loadingInstance = ElLoading.service({})
    try {
      const cycleTypeResult = await allActiveReliefRuleCycleType()
      if (cycleTypeResult.code === 200) {
        if (cycleTypeResult.data && cycleTypeResult.data.length > 0) {
          cycleTypes.value = cycleTypeResult.data
        }
      } else {
        ElMessage.error(cycleTypeResult.message)
      }
      const result = await activeReliefRule({
        active_id: props.activity_id
      })
      if (result.code === 200) {
        form.id = result.data.id
        form.cycle = result.data.cycle
        form.open_day = result.data.open_day
        form.open_time = result.data.open_time
        form.is_repeat = result.data.is_repeat
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
      cycle: form.cycle,
      open_day: form.open_day,
      open_time: form.open_time,
      is_repeat: form.is_repeat
    }
    try {
      const result = await editActiveReliefRule(params)
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
