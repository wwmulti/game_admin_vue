<!-- 登录页面 -->
<template>
  <div class="flex w-full h-screen">
    <LoginLeftView />

    <div class="relative flex-1">
      <AuthTopBar />

      <div class="auth-right-wrap">
        <div class="form">
          <h3 class="title">{{ $t('login.title') }}</h3>
          <p class="sub-title">{{ $t('login.subTitle') }}</p>
          <ElForm
            ref="formRef"
            :model="formData"
            :rules="rules"
            :key="formKey"
            @keyup.enter="handleSubmit"
            style="margin-top: 25px"
          >
            <ElFormItem prop="username">
              <ElInput
                class="custom-height"
                :placeholder="$t('yongHuMing')"
                v-model.trim="formData.username"
              />
            </ElFormItem>
            <ElFormItem prop="password">
              <ElInput
                class="custom-height"
                :placeholder="$t('miMa')"
                v-model.trim="formData.password"
                type="password"
                autocomplete="off"
                show-password
              />
            </ElFormItem>

            <ElFormItem prop="code">
              <ElInput
                class="custom-height"
                :placeholder="$t('googleYanZhengMa')"
                v-model.trim="formData.code"
                autocomplete="off"
                @focus="handleCodeFocus"
              />
              <template v-if="verifyResult?.is_show">
                <div class="qr-wrap">
                  <div class="qr-header">
                    <svg class="qr-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="7" height="7" rx="1" />
                      <rect x="14" y="3" width="7" height="7" rx="1" />
                      <rect x="3" y="14" width="7" height="7" rx="1" />
                      <rect x="14" y="14" width="3" height="3" rx="0.5" />
                      <rect x="19" y="14" width="3" height="3" rx="0.5" />
                      <rect x="14" y="19" width="3" height="3" rx="0.5" />
                    </svg>
                    <span class="qr-title">{{ $t('erWeiMa') }}</span>
                  </div>
                  <div class="qr-body">
                    <div class="qr-code-box">
                      <QrcodeVue :value="verifyResult.url" :size="130" level="M" />
                    </div>
                    <div class="qr-divider"></div>
                    <div class="qr-secret-area">
                      <div class="secret-label">{{ $t('miYao') }}</div>
                      <div class="secret-value-box">
                        <code class="secret-value">{{ verifyResult.secret }}</code>
                        <ElButton
                          class="secret-copy-btn"
                          size="small"
                          text
                          @click="handleCopySecret(verifyResult.secret)"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="copy-icon">
                            <rect x="9" y="9" width="13" height="13" rx="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                          </svg>
                          {{ $t('btn.copy') }}
                        </ElButton>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </ElFormItem>

            <div class="flex-cb mt-2 text-sm">
              <ElCheckbox v-model="formData.keepLogin">{{
                $t('baoChiDengLu')
              }}</ElCheckbox>
            </div>

            <div style="margin-top: 30px">
              <ElButton
                class="w-full custom-height"
                type="primary"
                @click="handleSubmit"
                :loading="loading"
                v-ripple
              >
                {{ $t('login.btnText') }}
              </ElButton>
            </div>

            <!-- <div class="mt-5 text-sm text-gray-600">
              <span>{{ $t('login.noAccount') }}</span>
            </div> -->
          </ElForm>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import AppConfig from '@/config'
  import { useUserStore } from '@/store/modules/user'
  import { useI18n } from 'vue-i18n'
  import { HttpError } from '@/utils/http/error'
  import { login, verify } from '@/api/auth'
  import { ElNotification, type FormInstance, type FormRules } from 'element-plus'
  import { useSettingStore } from '@/store/modules/setting'
  import { $t } from '@/locales'
  import { copy } from '@/utils/common'
  import QrcodeVue from 'qrcode.vue'

  defineOptions({ name: 'Login' })

  const settingStore = useSettingStore()
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const { isDark } = storeToRefs(settingStore)
  const { t, locale } = useI18n()
  const formKey = ref(0)

  // 监听语言切换，重置表单
  watch(locale, () => {
    formKey.value++
  })

  const userStore = useUserStore()
  const router = useRouter()
  const route = useRoute()
  const formRef = ref<FormInstance>()

  const formData = reactive({
    username: '',
    password: '',
    code: '',
    keepLogin: true
  })

  const rules = computed<FormRules>(() => ({
    username: [{ required: true, message: t('yongHuMing'), trigger: 'blur' }],
    password: [{ required: true, message: t('miMa'), trigger: 'blur' }]
  }))

  const loading = ref(false)

  // 谷歌验证相关
  const verifyResult = ref<{ is_show: boolean; url: string; secret: string } | null>(null)
  const verifyLoading = ref(false)

  async function handleCodeFocus() {
    if (!formData.username || !formData.password) return
    if (verifyResult.value) return // 已获取过，不再重复请求
    verifyLoading.value = true
    try {
      const result = await verify({ username: formData.username, password: formData.password })
      if (result.code === 200 && result.data?.is_show) {
        verifyResult.value = result.data
      }
    } catch {
      // 未绑定或已绑定均不弹窗
    } finally {
      verifyLoading.value = false
    }
  }

  async function handleCopySecret(secret: string) {
    const success = await copy(secret)
    if (success) {
      ElMessage.success($t('fuZhiChengGong'))
    } else {
      ElMessage.error($t('fuZhiShiBai'))
    }
  }

  // 登录
  const handleSubmit = async () => {
    if (!formRef.value) return

    try {
      // 表单验证
      const valid = await formRef.value.validate()
      if (!valid) return

      loading.value = true

      // 登录请求
      const { username, password, code, keepLogin } = formData

      const result = await login({
        username: username,
        password: password,
        code: code,
        keeplogin: keepLogin
      })
      let token = ''
      if (result.code == 200) {
        token = result.data.token
      }

      let userInfo = {id: result.data.id, username: result.data.username, avatar: result.data.avatar}

      // 验证token
      if (!token) {
        ElMessage.error($t('dengLuShiBai'))
      }

      // 存储 token 和登录状态
      userStore.setToken(token)
      userStore.setUserInfo(userInfo)
      userStore.setLoginStatus(true)

      // 登录成功处理
      showLoginSuccessNotice()

      // 获取 redirect 参数，如果存在则跳转到指定页面，否则跳转到首页
      const redirect = route.query.redirect as string
      await router.push(redirect || '/')
    } finally {
      loading.value = false
    }
  }

  // 登录成功提示
  const showLoginSuccessNotice = () => {
    setTimeout(() => {
      ElNotification({
        title: t('login.success.title'),
        type: 'success',
        duration: 2500,
        zIndex: 10000,
        message: `${t('login.success.message')}!`
      })
    }, 1000)
  }
</script>

<style scoped>
  @import './style.css';
</style>

<style lang="scss" scoped>
  :deep(.el-select__wrapper) {
    height: 40px !important;
  }

  .qr-wrap {
    margin-top: 16px;
    background: var(--el-fill-color-light);
    border: 1px solid var(--el-border-color-light);
    border-radius: 10px;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
  }

  .qr-header {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    border-bottom: 1px solid var(--el-border-color-light);
    background: var(--el-bg-color);
  }

  .qr-icon {
    width: 16px;
    height: 16px;
    color: var(--el-color-primary);
  }

  .qr-title {
    font-size: 13px;
    font-weight: 500;
    color: var(--el-text-color-primary);
  }

  .qr-body {
    display: flex;
    align-items: center;
    padding: 14px;
    gap: 14px;
  }

  .qr-code-box {
    flex-shrink: 0;
    width: 130px;
    height: 130px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-radius: 8px;
    padding: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  }

  .qr-divider {
    width: 1px;
    height: 100px;
    background: var(--el-border-color-light);
    flex-shrink: 0;
  }

  .qr-secret-area {
    flex: 1;
    min-width: 0;
  }

  .secret-label {
    font-size: 12px;
    color: var(--el-text-color-secondary);
    margin-bottom: 6px;
  }

  .secret-value-box {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--el-bg-color);
    border: 1px solid var(--el-border-color);
    border-radius: 6px;
    padding: 6px 8px;
  }

  .secret-value {
    flex: 1;
    min-width: 0;
    font-size: 12px;
    font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
    color: var(--el-color-primary);
    letter-spacing: 1.5px;
    word-break: break-all;
    user-select: all;
  }

  .secret-copy-btn {
    flex-shrink: 0;
    --el-button-bg-color: transparent;
    --el-button-border-color: transparent;
    --el-button-hover-bg-color: var(--el-fill-color);
    --el-button-hover-text-color: var(--el-color-primary);
    gap: 4px;
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
  }

  .copy-icon {
    width: 13px;
    height: 13px;
  }
</style>
