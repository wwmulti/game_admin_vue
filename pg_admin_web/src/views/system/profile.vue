<template>
  <ElCard class="profile-card">
    <div class="profile-wrap">
      <div class="avatar-section">
        <div class="avatar-wrapper" @click="handleAvatarClick">
          <ElAvatar :size="100" :src="form.avatar" />
          <div class="avatar-overlay">{{ $t('gengHuanTouXiang') }}</div>
        </div>
        <div class="username-text">{{ form.username }}</div>
        <input
          ref="fileInputRef"
          type="file"
          accept="image/*"
          style="display: none"
          @change="handleFileChange"
        />
      </div>

      <ElForm
        ref="formRef"
        :model="form"
        label-width="100px"
        class="profile-form"
        @submit.prevent
      >
        <ElFormItem :label="$t('yongHuMing')" prop="username">
          <ElInput v-model="form.username" />
        </ElFormItem>

        <ElFormItem :label="$t('niCheng')" prop="nickname">
          <ElInput v-model="form.nickname" />
        </ElFormItem>

        <ElFormItem :label="$t('miMa')" prop="password">
          <ElInput
            v-model="form.password"
            type="password"
            show-password
            :placeholder="$t('buXiuGaiMiMaQingLiuKong')"
          />
        </ElFormItem>

        <ElFormItem :label="$t('guGeYanZheng')">
          <ElRadioGroup v-model="form.google_switch">
            <ElRadio :value="1">{{ $t('kaiQi') }}</ElRadio>
            <ElRadio :value="0">{{ $t('guanBi') }}</ElRadio>
          </ElRadioGroup>
        </ElFormItem>

        <ElFormItem v-if="form.google_verify" :label="$t('guGeMiYue')">
          <ElInput v-model="form.google_verify" readonly>
            <template #append>
              <ElButton @click="handleCopySecret">{{ $t('btn.copy') }}</ElButton>
            </template>
          </ElInput>
        </ElFormItem>

        <ElFormItem v-if="form.qrCodeUrl" :label="$t('erWeiMa')">
          <QrcodeVue :value="form.qrCodeUrl" :size="150" level="M" />
        </ElFormItem>

        <ElFormItem>
          <ElButton type="primary" :loading="loading" @click="handleSubmit">
            {{ $t('tiJiao') }}
          </ElButton>
        </ElFormItem>
      </ElForm>
    </div>
  </ElCard>
</template>

<script setup lang="ts">
import { getProfile, updateProfile, uploadAvatar } from '@/api/system'
import { $t } from '@/locales'
import { copy } from '@/utils/common'
import QrcodeVue from 'qrcode.vue'

defineOptions({ name: 'Profile' })

const formRef = ref()
const loading = ref(false)
const uploading = ref(false)
const fileInputRef = ref<HTMLInputElement>()

const form = reactive({
  username: '',
  nickname: '',
  password: '',
  avatar: '',
  google_switch: 0,
  google_verify: '',
  qrCodeUrl: ''
})

const fetchProfile = async () => {
  try {
    const res = await getProfile()
    if (res.code === 200 && res.data) {
      form.username = res.data.username || ''
      form.nickname = res.data.nickname || ''
      form.avatar = res.data.avatar || ''
      form.google_switch = res.data.google_switch ?? 0
      form.google_verify = res.data.google_verify || ''
      form.qrCodeUrl = res.data.qrCodeUrl || ''
    }
  } catch {
    // error handled by interceptor
  }
}

const handleCopySecret = async () => {
  const ok = await copy(form.google_verify)
  if (ok) {
    ElMessage.success($t('fuZhiChengGong'))
  } else {
    ElMessage.error($t('fuZhiShiBai'))
  }
}

const handleAvatarClick = () => {
  fileInputRef.value?.click()
}

const handleFileChange = async (e: Event) => {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('img', file)
    const res = await uploadAvatar(fd)
    if (res.code === 200) {
      const avatarUrl = res.data?.fullurl || res.data?.url
      if (avatarUrl) {
        form.avatar = avatarUrl
      }
    }
  } catch {
    // error handled by interceptor
  } finally {
    uploading.value = false
    // 清空 input 以便重复选择同一文件
    target.value = ''
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    const data: Record<string, any> = {
      nickname: form.nickname,
      google_switch: form.google_switch,
      img: form.avatar
    }
    if (form.password) {
      data.password = form.password
    }

    const res = await updateProfile(data)
    if (res.code === 200) {
      ElMessage.success($t('baoCunChengGong'))
      // 重新获取最新数据（google_verify 和 qrCodeUrl 可能已更新）
      await fetchProfile()
      // 清空密码输入
      form.password = ''
    }
  } catch {
    // error handled by interceptor
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProfile()
})
</script>

<style scoped>
.profile-card {
  max-width: 640px;
  margin: 0 auto;
}

.profile-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 0;
}

.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 32px;
}

.avatar-wrapper {
  position: relative;
  cursor: pointer;
  border-radius: 50%;
  overflow: hidden;
}

.avatar-wrapper:hover .avatar-overlay {
  opacity: 1;
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  color: #fff;
  font-size: 13px;
  opacity: 0;
  transition: opacity 0.2s;
}

.username-text {
  margin-top: 12px;
  font-size: 18px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.profile-form {
  width: 100%;
  max-width: 480px;
}
</style>
